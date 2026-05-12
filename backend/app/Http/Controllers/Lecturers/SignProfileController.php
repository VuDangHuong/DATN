<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Sign\LecturerSignProfile;
use App\Services\DocumentSignService;
use App\Services\PkiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class SignProfileController extends Controller
{
    public function __construct(protected PkiService $pki) {}
 
    /**
     * GET /api/lecturer/sign-profile
     */
   public function show(): JsonResponse
    {
        /** @var LecturerSignProfile|null $profile */
        $profile = LecturerSignProfile::forLecturer(Auth::id())
            ->active()
            ->latest()
            ->first();
 
        if (!$profile) {
            return response()->json([
                'profile' => null,
                'message' => 'Chưa đăng ký chữ ký số',
            ], 404);
        }
 
        return response()->json([
            'profile' => $this->formatProfile($profile),
        ]);
    }
 
    /**
     * GET /api/lecturer/sign-profile/history
     */
    public function history(): JsonResponse
    {
        $profiles = LecturerSignProfile::forLecturer(Auth::id())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn(LecturerSignProfile $p) => $this->formatProfile($p));
 
        return response()->json(['profiles' => $profiles]);
    }
 
    /**
     * POST /api/lecturer/sign-profile/parse-cert
     * Parse .crt để preview info trước khi submit
     *
     * Body: certificate_file = upload
     */
    public function parseCertificate(Request $request): JsonResponse
    {
        $request->validate([
            'certificate_file' => 'required|file|mimes:cer,crt,pem,txt|max:5120',
        ]);
 
        $content = file_get_contents($request->file('certificate_file')->getPathname());
 
        // Đảm bảo là PEM
        $pemCert = str_contains($content, '-----BEGIN CERTIFICATE-----')
            ? $content
            : "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($content), 64, "\n") . "-----END CERTIFICATE-----";
 
        $info = $this->pki->parseCertificate($pemCert);
 
        if (!$info) {
            return response()->json([
                'message' => 'File chứng thư không hợp lệ',
            ], 422);
        }
 
        return response()->json([
            'message' => 'Đã đọc chứng thư thành công',
            'data'    => [
                'serial'          => $info['serial'],
                'subject_cn'      => $info['subject_cn'],
                'subject_full'    => $info['subject_full'],
                'issuer_cn'       => $info['issuer_cn'],
                'issuer_full'     => $info['issuer_full'],
                'valid_from'      => $info['valid_from'],
                'valid_to'        => $info['valid_to'],
                'algorithm'       => $info['key_algorithm'] . '-' . $info['key_bits'],
                'signature_algo'  => $info['signature_algo'],
                'is_expired'      => $info['is_expired'],
                'public_key_pem'  => $info['public_key_pem'],
            ],
        ]);
    }
 
    /**
     * POST /api/lecturer/sign-profile
     *
     * Đăng ký chữ ký số với cả public + private key
     *
     * Body:
     *   - certificate_file:    .crt/.pem
     *   - private_key_file:    .key/.pem
     *   - signing_password:    password riêng để decrypt private key sau này
     *   - signing_password_confirmation
     *   - account_password:    password tài khoản (verify quyền)
     */
    public function register(Request $request): JsonResponse
    {
        // ── 1. Validate password tài khoản ──────────────
        $request->validate([
            'account_password' => 'required|string',
        ]);
 
        if (!Hash::check($request->account_password, Auth::user()->password)) {
            return response()->json([
                'message' => 'Mật khẩu tài khoản không chính xác',
                'errors'  => ['account_password' => ['Mật khẩu không chính xác']],
            ], 422);
        }
 
        // ── 2. Validate files + signing password ────────
        $request->validate([
            'certificate_file'  => 'required|file|mimes:cer,crt,pem,txt|max:5120',
            'private_key_file'  => 'required|file|mimes:key,pem,txt|max:5120',
            'signing_password'  => 'required|string|min:8|confirmed',
        ], [
            'signing_password.min' => 'Mật khẩu ký số phải có ít nhất 8 ký tự',
            'signing_password.confirmed' => 'Mật khẩu ký số xác nhận không khớp',
        ]);
 
        // ── 3. Đọc và parse cert ────────────────────────
        $certContent = file_get_contents($request->file('certificate_file')->getPathname());
        $pemCert = str_contains($certContent, '-----BEGIN CERTIFICATE-----')
            ? $certContent
            : "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certContent), 64, "\n") . "-----END CERTIFICATE-----";
 
        $certInfo = $this->pki->parseCertificate($pemCert);
 
        if (!$certInfo) {
            return response()->json([
                'message' => 'File chứng thư không hợp lệ',
            ], 422);
        }
 
        if ($certInfo['is_expired']) {
            return response()->json([
                'message' => 'Chứng thư đã hết hạn',
            ], 422);
        }
 
        // ── 4. Đọc private key ──────────────────────────
        $privateKeyPem = file_get_contents($request->file('private_key_file')->getPathname());
 
        // ── 5. Verify private key khớp với cert ─────────
        $isMatch = $this->pki->verifyKeyPair(
            $privateKeyPem,
            $certInfo['public_key_pem']
        );
 
        if (!$isMatch) {
            return response()->json([
                'message'    => 'Private key không khớp với chứng thư. Vui lòng kiểm tra lại file.',
                'error_code' => 'KEY_PAIR_MISMATCH',
            ], 422);
        }
 
        // ── 6. Check serial trùng ───────────────────────
        $duplicate = LecturerSignProfile::active()
            ->where('certificate_serial', $certInfo['serial'])
            ->where('lecturer_id', '!=', Auth::id())
            ->exists();
 
        if ($duplicate) {
            return response()->json([
                'message' => 'Serial chứng thư này đã được giảng viên khác đăng ký',
            ], 422);
        }
 
        // ── 7. Encrypt private key bằng signing_password ─
        $encryption = $this->pki->encryptPrivateKey(
            $privateKeyPem,
            $request->signing_password
        );
 
        // ── 8. Disable cũ + tạo mới trong transaction ───
        /** @var LecturerSignProfile $newProfile */
        $newProfile = DB::transaction(function () use ($request, $certInfo, $encryption) {
            LecturerSignProfile::forLecturer(Auth::id())
                ->active()
                ->update(['is_active' => false]);
 
            return LecturerSignProfile::create([
                'lecturer_id'          => Auth::id(),
                'public_key'           => $certInfo['public_key_pem'],
                'encrypted_private_key'=> $encryption['encrypted'],
                'encryption_salt'      => $encryption['salt'],
                'encryption_iv'        => $encryption['iv'],
                'signing_password_hash'=> Hash::make($request->signing_password),
                'certificate_serial'   => $certInfo['serial'],
                'subject_cn'           => $certInfo['subject_cn'],
                'issuer_cn'            => $certInfo['issuer_cn'],
                'algorithm'            => $certInfo['key_algorithm'] . '-' . $certInfo['key_bits'],
                'cert_valid_from'      => $certInfo['valid_from'],
                'cert_expires_at'      => $certInfo['valid_to'],
                'is_active'            => true,
                'certificate_meta'     => [
                    'subject_full'   => $certInfo['subject_full'],
                    'issuer_full'    => $certInfo['issuer_full'],
                    'signature_algo' => $certInfo['signature_algo'],
                    'registered_at'  => now()->toIso8601String(),
                    'registered_ip'  => $request->ip(),
                ],
            ]);
        });
 
        Log::info("Lecturer #" . Auth::id() . " registered PKI sign profile #{$newProfile->id}");
 
        return response()->json([
            'message' => 'Đăng ký chữ ký số thành công',
            'profile' => $this->formatProfile($newProfile),
        ]);
    }
 
    /**
     * POST /api/lecturer/sign-profile/generate-test
     *
     * Tạo cặp key + cert test cho demo
     * Trả về file .key + .crt để GV download → upload lại
     *
     * Body: { cn?, o?, email?, valid_days? }
     */
    public function generateTest(Request $request): JsonResponse
    {
        $request->validate([
            'cn'         => 'nullable|string|max:100',
            'o'          => 'nullable|string|max:100',
            'email'      => 'nullable|email',
            'valid_days' => 'nullable|integer|min:30|max:3650',
        ]);
 
        $user = Auth::user();
 
        $generated = $this->pki->generateTestKeyPair([
            'cn'    => $request->cn ?? $user->name,
            'o'     => $request->o ?? 'EduGroup',
            'email' => $request->email ?? $user->email,
        ], $request->valid_days ?? 365);
 
        return response()->json([
            'message' => 'Đã tạo cặp khóa test thành công. Vui lòng tải file về và đăng ký.',
            'data'    => $generated,
        ]);
    }
 
    /**
     * POST /api/lecturer/sign-profile/verify-password
     *
     * Verify signing_password trước khi cho phép ký (tùy chọn pre-check)
     */
    public function verifySigningPassword(Request $request): JsonResponse
    {
        $request->validate([
            'signing_password' => 'required|string',
        ]);
 
        $profile = LecturerSignProfile::forLecturer(Auth::id())
            ->active()
            ->first();
 
        if (!$profile) {
            return response()->json(['valid' => false, 'message' => 'Chưa có chữ ký số'], 404);
        }
 
        $valid = Hash::check($request->signing_password, $profile->signing_password_hash);
 
        return response()->json(['valid' => $valid]);
    }
 
    /**
     * DELETE /api/lecturer/sign-profile
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'account_password' => 'required|string',
        ]);
 
        if (!Hash::check($request->account_password, Auth::user()->password)) {
            return response()->json([
                'message' => 'Mật khẩu không chính xác',
            ], 422);
        }
 
        $updated = LecturerSignProfile::forLecturer(Auth::id())
            ->active()
            ->update(['is_active' => false]);
 
        if (!$updated) {
            return response()->json([
                'message' => 'Không có chữ ký nào đang hoạt động',
            ], 404);
        }
 
        return response()->json(['message' => 'Đã vô hiệu hóa chữ ký số']);
    }
 
    // ── Helpers ────────────────────────────────────────
 
    private function formatProfile(LecturerSignProfile $profile): array
    {
        return [
            'id'                 => $profile->id,
            'certificate_serial' => $profile->certificate_serial,
            'subject_cn'         => $profile->subject_cn,
            'issuer_cn'          => $profile->issuer_cn,
            'algorithm'          => $profile->algorithm,
            'cert_valid_from'    => $profile->cert_valid_from,
            'cert_expires_at'    => $profile->cert_expires_at,
            'is_active'          => $profile->is_active,
            'is_expired'         => $profile->is_expired,
            'is_expiring_soon'   => $profile->is_expiring_soon,
            'is_valid'           => $profile->is_valid,
            'days_until_expired' => $profile->days_until_expired,
            'has_private_key'    => $profile->has_private_key,
            'certificate_meta'   => $profile->certificate_meta,
            'created_at'         => $profile->created_at,
        ];
    }
}
