<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation\Assignment;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Sign\LecturerSignProfile;
use App\Notifications\SignRequestSigned;
use App\Services\DocumentSignService;
use App\Notifications\SignRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\PkiService;
use Illuminate\Support\Facades\Hash;
class LecturerSignController extends Controller
{
    public function __construct(protected DocumentSignService $signService, protected PkiService $pki) {}

    /**
     * GET /api/lecturer/sign-requests
     */
    public function index(Request $request): JsonResponse
    {
        $requests = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->with([
                'requester:id,name,code',
                'submission.assignment:id,title,document_category_label',
                'submission.group:id,name',
                'submission.student:id,name,code',
                'classModel:id,name,code',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);
 
        // Thêm stats
        $stats = [
            'total'              => DocumentSignRequest::where('lecturer_id', Auth::id())->count(),
            'pending'            => DocumentSignRequest::where('lecturer_id', Auth::id())->where('status', 'pending')->count(),
            'lecturer_reviewing' => DocumentSignRequest::where('lecturer_id', Auth::id())->where('status', 'lecturer_reviewing')->count(),
            'signed'             => DocumentSignRequest::where('lecturer_id', Auth::id())->where('status', 'signed')->count(),
            'rejected'           => DocumentSignRequest::where('lecturer_id', Auth::id())->where('status', 'rejected')->count(),
        ];
 
        return response()->json([
            'data'  => $requests->items(),
            'meta'  => [
                'current_page' => $requests->currentPage(),
                'last_page'    => $requests->lastPage(),
                'total'        => $requests->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/lecturer/sign-requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->with([
                'requester:id,name,code,email',
                'submission.assignment:id,title,deadline,document_category_label',
                'submission.group:id,name',
                'submission.student:id,name,code',
                'classModel:id,name,code',
                'logs.actor:id,name,role',
            ])
            ->findOrFail($id);
 
        if ($signRequest->status === DocumentSignRequest::STATUS_PENDING) {
            $signRequest->update(['status' => DocumentSignRequest::STATUS_LECTURER_REVIEWING]);
            $this->signService->log($signRequest->id, Auth::id(), 'lecturer_reviewing');
        }
 
        return response()->json(['data' => $signRequest]);
    }

    /**
     * GET /api/lecturer/sign-requests/{id}/preview
     * Lấy URL xem trước file gốc (temporary URL 30 phút)
     */
    public function preview(int $id): JsonResponse
    {
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->findOrFail($id);
 
        if (!Storage::exists($signRequest->original_file)) {
            return response()->json(['message' => 'File không tồn tại.'], 404);
        }
 
        return response()->json([
            'url'           => Storage::temporaryUrl($signRequest->original_file, now()->addMinutes(30)),
            'file_name'     => basename($signRequest->original_file),
            'document_type' => $signRequest->document_type,
            'category'      => $signRequest->document_category_label,
        ]);
    }
 

    /**
     * POST /api/lecturer/sign-requests/{id}/sign
     * GV upload file đã ký
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        // ── 1. Validate signing password ────────────────
        $request->validate([
            'signing_password' => 'required|string',
        ], [
            'signing_password.required' => 'Vui lòng nhập mật khẩu ký số',
        ]);
 
        // ── 2. E2: Check sign-profile tồn tại ───────────
        $signProfile = LecturerSignProfile::where('lecturer_id', Auth::id())
            ->where('is_active', true)
            ->first();
 
        if (!$signProfile) {
            return response()->json([
                'message'    => 'Bạn chưa đăng ký chữ ký số.',
                'error_code' => 'NO_SIGN_PROFILE',
            ], 422);
        }
 
        //E3: Check expires_at
        if ($signProfile->cert_expires_at && $signProfile->cert_expires_at->isPast()) {
            return response()->json([
                'message' => 'Chữ ký số của bạn đã hết hạn.',
                'error_code' => 'SIGN_PROFILE_EXPIRED',
            ], 422);
        }
 
        // ── 4. Verify signing password ──────────────────
        if (!Hash::check($request->signing_password, $signProfile->signing_password_hash)) {
            return response()->json([
                'message'    => 'Mật khẩu ký số không chính xác',
                'error_code' => 'WRONG_SIGNING_PASSWORD',
            ], 422);
        }
 
        // ── 5. Decrypt private key ──────────────────────
        $privateKeyPem = $this->pki->decryptPrivateKey(
            $signProfile->encrypted_private_key,
            $signProfile->encryption_salt,
            $signProfile->encryption_iv,
            $request->signing_password
        );
 
        if (!$privateKeyPem) {
            return response()->json([
                'message'    => 'Không thể giải mã private key. Có thể mật khẩu sai hoặc dữ liệu bị hỏng.',
                'error_code' => 'DECRYPT_FAILED',
            ], 500);
        }
 
        // ── 6. Load sign request ────────────────────────
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_PENDING,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->with(['requester', 'classModel', 'submission'])
            ->findOrFail($id);
 
        // ── 7. Generate PDF + Hash + Sign ───────────────
        $signedFilePath = $this->generateSignedPdf($signRequest, $signProfile);
        $fileHash       = $this->pki->hashFile(Storage::path($signedFilePath));
 
        // ✅ Ký SHA-256 hash của PDF bằng RSA-SHA256
        try {
            $signatureB64 = $this->pki->signData($fileHash, $privateKeyPem);
        } catch (\Exception $e) {
            \Log::error('Sign failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ký số thất bại: ' . $e->getMessage(),
                'error_code' => 'SIGN_FAILED',
            ], 500);
        }
 
        // ── 8. Verify ngay (sanity check) ───────────────
        $verified = $this->pki->verifySignature($fileHash, $signatureB64, $signProfile->public_key);
        if (!$verified) {
            return response()->json([
                'message' => 'Verify chữ ký thất bại sau khi ký. Có lỗi hệ thống.',
                'error_code' => 'VERIFY_FAILED',
            ], 500);
        }
 
        // ── 9. Lưu vào DB ───────────────────────────────
        DB::transaction(function () use ($signRequest, $signedFilePath, $fileHash, $signatureB64, $signProfile) {
            $signRequest->update([
                'signed_file'         => $signedFilePath,
                'sign_hash'           => $fileHash,
                'file_hash'           => $fileHash,
                'signature'           => $signatureB64,
                'signer_public_key'   => $signProfile->public_key,
                'signature_algorithm' => 'sha256WithRSAEncryption',
                'sign_certificate'    => $signProfile->certificate_serial,
                'status'              => DocumentSignRequest::STATUS_SIGNED,
                'signed_at'           => now(),
            ]);
 
            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'signed',
                "Ký số bằng cert #{$signProfile->certificate_serial} ({$signProfile->algorithm})"
            );
        });
 
        // Xóa private key khỏi memory
        unset($privateKeyPem);
 
        // ── 10. Notify SV ───────────────────────────────
        try {
            $signRequest->requester->notify(
                new SignRequestSigned(
                    $signRequest->fresh(['requester', 'lecturer'])
                )
            );
        } catch (\Exception $e) {
            \Log::warning('Send notification failed: ' . $e->getMessage());
        }
 
        return response()->json([
            'message' => 'Đã ký số tài liệu thành công',
            'data'    => [
                'id'        => $signRequest->id,
                'status'    => DocumentSignRequest::STATUS_SIGNED,
                'signed_at' => now(),
                'file_hash'           => $fileHash,
                'signature_algorithm' => 'sha256WithRSAEncryption',
            ],
        ]);
    }
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
 
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_PENDING,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->with(['requester'])
            ->findOrFail($id);
 
        DB::transaction(function () use ($signRequest, $request) {
            $signRequest->update([
                'status'        => DocumentSignRequest::STATUS_REJECTED,
                'reject_reason' => $request->reason,
            ]);
 
            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'rejected',
                $request->reason
            );
        });
 
        // ✅ Gửi email cho SV
        try {
           $signRequest->requester->notify(new SignRequestRejected(
                $signRequest->fresh(['requester', 'lecturer']),
                'lecturer'
            )
        );
        } catch (\Exception $e) {
            \Log::warning('Send sign-rejected email failed: ' . $e->getMessage());
        }
 
        return response()->json(['message' => 'Đã từ chối yêu cầu ký số.']);
    }

    public function getDocumentCategories()
    {
        return response()->json(
            collect(Assignment::DOCUMENT_CATEGORIES)
                ->map(fn($label, $value) => compact('value', 'label'))
                ->values()
        );
    }
    private function generateSignedPdf(DocumentSignRequest $signRequest, LecturerSignProfile $signProfile): string
    {
        $lecturer    = Auth::user();
        $student     = $signRequest->requester;
        $classModel  = $signRequest->classModel;
        $signedAt    = now()->format('d/m/Y H:i:s');
        $signHash    = strtoupper(substr(hash('sha256', $signRequest->id . $lecturer->id . now()->timestamp), 0, 16));
        $originalFileName = basename($signRequest->original_file);
        $categoryLabel    = $signRequest->document_category_label ?? $signRequest->document_category;
        $certSerial       = $signProfile->certificate_serial ?? 'N/A';
        $certExpires      = $signProfile->cert_expires_at?->format('d/m/Y') ?? 'Không xác định';
 
        $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"/>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; padding: 40px; }
 
.header { text-align: center; margin-bottom: 32px; border-bottom: 3px solid #0d9488; padding-bottom: 20px; }
.header .logo { font-size: 22px; font-weight: bold; color: #0d9488; letter-spacing: 2px; }
.header .subtitle { font-size: 11px; color: #6b7280; margin-top: 4px; letter-spacing: 1px; text-transform: uppercase; }
.header h1 { font-size: 18px; font-weight: bold; color: #111827; margin-top: 16px; }
 
.stamp-box {
    border: 3px solid #0d9488; border-radius: 8px;
    padding: 16px 24px; margin: 24px auto; max-width: 480px;
    text-align: center; background: #f0fdf4;
}
.stamp-box .stamp-title { font-size: 14px; font-weight: bold; color: #065f46; text-transform: uppercase; letter-spacing: 1px; }
.stamp-box .stamp-code { font-size: 18px; font-weight: bold; color: #0d9488; font-family: monospace; margin: 8px 0; letter-spacing: 2px; }
.stamp-box .stamp-date { font-size: 11px; color: #374151; }
 
.section { margin-bottom: 20px; }
.section-title { font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 12px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field label { font-size: 10px; color: #9ca3af; display: block; margin-bottom: 2px; }
.field span { font-size: 13px; color: #111827; font-weight: 500; }
 
.cert-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 16px; margin-top: 12px; }
.cert-box .cert-title { font-size: 11px; font-weight: bold; color: #1d4ed8; margin-bottom: 6px; }
.cert-box .cert-serial { font-family: monospace; font-size: 12px; color: #1e40af; font-weight: bold; }
 
.verify-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; margin-top: 20px; }
.verify-box .verify-title { font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 6px; }
.verify-box .hash { font-family: monospace; font-size: 10px; color: #374151; word-break: break-all; }
 
.footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
 
.signature-area { margin-top: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
.sig-box { text-align: center; }
.sig-box .sig-title { font-size: 11px; font-weight: bold; color: #374151; margin-bottom: 60px; }
.sig-box .sig-name { font-size: 12px; font-weight: bold; color: #111827; border-top: 1px solid #374151; padding-top: 8px; margin-top: 8px; }
.sig-box .sig-role { font-size: 10px; color: #6b7280; }
</style>
</head>
<body>
 
<div class="header">
<div class="logo">EDUGROUP</div>
<div class="subtitle">Hệ thống quản lý học phần nhóm</div>
<h1>PHIẾU XÁC NHẬN KÝ SỐ TÀI LIỆU</h1>
</div>
 
<div class="stamp-box">
<div class="stamp-title">✅ Đã được ký số xác nhận</div>
<div class="stamp-code">{$signHash}</div>
<div class="stamp-date">Ký lúc: {$signedAt}</div>
</div>
 
<div class="section">
<div class="section-title">Thông tin tài liệu</div>
<div class="grid">
    <div class="field"><label>Loại tài liệu</label><span>{$categoryLabel}</span></div>
    <div class="field"><label>File gốc</label><span>{$originalFileName}</span></div>
</div>
</div>
 
<div class="section">
<div class="section-title">Thông tin sinh viên</div>
<div class="grid">
    <div class="field"><label>Họ và tên</label><span>{$student->name}</span></div>
    <div class="field"><label>Mã sinh viên</label><span>{$student->code}</span></div>
    <div class="field"><label>Email</label><span>{$student->email}</span></div>
    <div class="field"><label>Lớp học phần</label><span>{$classModel->name} - {$classModel->code}</span></div>
</div>
</div>
 
<div class="section">
<div class="section-title">Thông tin giảng viên ký</div>
<div class="grid">
    <div class="field"><label>Họ và tên</label><span>{$lecturer->name}</span></div>
    <div class="field"><label>Email</label><span>{$lecturer->email}</span></div>
    <div class="field"><label>Thời gian ký</label><span>{$signedAt}</span></div>
    <div class="field"><label>Mã xác nhận</label><span style="font-family:monospace;color:#0d9488">{$signHash}</span></div>
</div>
 
<div class="cert-box">
    <div class="cert-title">🔐 Chữ ký số sử dụng</div>
    <div class="cert-serial">Serial: {$certSerial}</div>
    <div style="font-size:10px;color:#374151;margin-top:4px;">Hết hạn: {$certExpires}</div>
</div>
</div>
 
<div class="verify-box">
<div class="verify-title">Thông tin xác thực</div>
<p style="font-size:10px;color:#374151;margin-bottom:6px;">
    Tài liệu này đã được giảng viên ký số bằng chữ ký số cá nhân đã đăng ký trên hệ thống EduGroup.
    Mã xác thực SHA-256 dùng để kiểm tra tính toàn vẹn của tài liệu.
</p>
<div class="hash">Request ID: #{$signRequest->id} | GV ID: #{$lecturer->id} | Cert: {$certSerial}</div>
</div>
 
<div class="signature-area">
<div class="sig-box">
    <div class="sig-title">Sinh viên</div>
    <div class="sig-name">{$student->name}</div>
    <div class="sig-role">Người nộp tài liệu</div>
</div>
<div class="sig-box">
    <div class="sig-title">Giảng viên xác nhận</div>
    <div class="sig-name">{$lecturer->name}</div>
    <div class="sig-role">Đã ký số bằng chứng thư #{$certSerial}</div>
</div>
</div>
 
<div class="footer">
<p>Tài liệu được tạo tự động bởi hệ thống EduGroup · {$signedAt}</p>
<p>Đây là xác nhận điện tử có giá trị trong phạm vi hệ thống</p>
</div>
 
</body>
</html>
HTML;
 
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
 
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
 
        $pdfContent = $dompdf->output();
        $filePath   = "signed_documents/{$signRequest->id}/signed_confirmation.pdf";
 
        Storage::put($filePath, $pdfContent);
 
        return $filePath;
    }
}
