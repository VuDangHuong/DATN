<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Sign\DocumentSignRequest;
use App\Services\PkiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicVerificationController extends Controller
{
    public function __construct(protected PkiService $pki) {}

    private function normalizeSerial(string $serial): string
    {
        return strtoupper(preg_replace('/[\s\-:]/', '', $serial));
    }

    public function lookupBySerial(string $serial): JsonResponse
    {
        $normalized = $this->normalizeSerial($serial);

        $request = DocumentSignRequest::whereRaw('UPPER(sign_certificate) = ?', [$normalized])
            ->where('status', 'signed')
            ->with(['requester:id,name,code', 'lecturer:id,name,email'])
            ->latest('signed_at')
            ->first();

        if (!$request) {
            return response()->json([
                'found'   => false,
                'message' => 'Không tìm thấy tài liệu nào với serial này',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'data'  => [
                'request_id'         => $request->id,
                'category'           => $request->document_category_label,
                'signer'             => $request->lecturer?->name,
                'signer_email'       => $request->lecturer?->email,
                'requester'          => $request->requester?->name,
                'signed_at'          => $request->signed_at,
                'certificate_serial' => $request->sign_certificate,
                'algorithm'          => $request->signature_algorithm ?? 'sha256 (legacy)',
                'has_rsa_signature'  => !empty($request->signature),
            ],
        ]);
    }

    public function verifyFile(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|file|mimes:pdf|max:20480',
            'serial' => 'required|string|max:255',
        ]);

        $normalized = $this->normalizeSerial($request->serial);

        // Bỏ whereNotNull('signature') để verify được cả bản cũ
        $signRequest = DocumentSignRequest::whereRaw('UPPER(sign_certificate) = ?', [$normalized])
            ->where('status', 'signed')
            ->latest('signed_at')
            ->first();
        \Log::debug('verifyFile loaded record', [
            'request_id'  => $signRequest?->id,
            'signed_at'   => $signRequest?->signed_at,
            'file_hash'   => $signRequest?->file_hash,
            'sign_hash'   => $signRequest?->sign_hash,
        ]);
        if (!$signRequest) {
            return response()->json([
                'verified' => false,
                'reason'   => 'NOT_FOUND',
                'message'  => 'Không tìm thấy chữ ký nào với serial này',
            ], 404);
        }

        // ── Hash file upload ────────────────────────────
        $uploadedHash = $this->pki->hashFile($request->file('file')->getPathname());

        // ── Lấy hash đã lưu (ưu tiên file_hash, fallback sang sign_hash cũ)
        $storedHash = $signRequest->file_hash ?? $signRequest->sign_hash;

        if (!$storedHash) {
            return response()->json([
                'verified' => false,
                'reason'   => 'NO_HASH',
                'message'  => 'Bản ký này không có hash để xác thực',
            ]);
        }

        if ($uploadedHash !== $storedHash) {
            return response()->json([
                'verified' => false,
                'reason'   => 'HASH_MISMATCH',
                'message'  => 'File đã bị sửa đổi sau khi ký. Hash không khớp.',
                'data'     => [
                    'expected_hash' => $storedHash,
                    'actual_hash'   => $uploadedHash,
                ],
            ]);
        }

        // ── Verify RSA signature CHỈ KHI có (bản mới) ──
        $hasRsaSignature = false;

        if ($signRequest->signature && $signRequest->signer_public_key) {
            $hasRsaSignature = true;

            $signatureValid = $this->pki->verifySignature(
                file_get_contents($request->file('file')->getPathname()),
                $signRequest->signature,
                $signRequest->signer_public_key
            );

            if (!$signatureValid) {
                return response()->json([
                    'verified' => false,
                    'reason'   => 'INVALID_SIGNATURE',
                    'message'  => 'Chữ ký RSA không hợp lệ',
                ]);
            }
        }

        // Hợp lệ
        return response()->json([
            'verified' => true,
            'message'  => $hasRsaSignature
                ? 'Tài liệu hợp lệ — chưa bị sửa đổi và đã ký RSA-SHA256'
                : 'Hash khớp — file chưa bị sửa đổi (chế độ hash-only, không có RSA)',
            'data'     => [
                'request_id'         => $signRequest->id,
                'category'           => $signRequest->document_category_label,
                'signer'             => $signRequest->lecturer?->name,
                'signer_email'       => $signRequest->lecturer?->email,
                'requester'          => $signRequest->requester?->name,
                'requester_code'     => $signRequest->requester?->code,
                'signed_at'          => $signRequest->signed_at,
                'certificate_serial' => $signRequest->sign_certificate,
                'algorithm'          => $signRequest->signature_algorithm ?? 'sha256 (legacy)',
                'file_hash'          => $storedHash,
                'has_rsa_signature'  => $hasRsaSignature,
            ],
        ]);
    }
}