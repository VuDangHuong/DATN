<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Sign\LecturerSignProfile;
use App\Services\DocumentSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
class SignProfileController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}
 
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
            ->map(fn(LecturerSignProfile $p) => $this->formatProfile($p)); // ← thêm type hint
 
        return response()->json(['profiles' => $profiles]);
    }
 
    /**
     * GET /api/lecturer/sign-profile/categories
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'providers' => [
                ['value' => 'viettel-ca',  'label' => 'Viettel-CA'],
                ['value' => 'vnpt-ca',     'label' => 'VNPT-CA'],
                ['value' => 'fpt-ca',      'label' => 'FPT-CA'],
                ['value' => 'bkav-ca',     'label' => 'BKAV-CA'],
                ['value' => 'misa-esign',  'label' => 'MISA eSign'],
                ['value' => 'other',       'label' => 'Khác'],
            ],
            'cert_types' => [
                ['value' => 'personal',     'label' => 'Cá nhân'],
                ['value' => 'organization', 'label' => 'Tổ chức'],
            ],
        ]);
    }
 
    /**
     * POST /api/lecturer/sign-profile
     */
    public function upsert(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu để xác nhận',
        ]);
 
        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return response()->json([
                'message' => 'Mật khẩu không chính xác',
                'errors'  => ['current_password' => ['Mật khẩu không chính xác']],
            ], 422);
        }
 
        $request->validate([
            'mode'               => 'required|in:upload,manual',
            'certificate_serial' => 'required|string|max:255',
            'cert_expires_at'    => 'required|date|after:today',
            'provider'           => 'nullable|string|max:100',
            'cert_type'          => 'nullable|in:personal,organization',
        ], [
            'cert_expires_at.after' => 'Ngày hết hạn phải sau ngày hôm nay',
        ]);
 
        $publicKey = '';
 
        if ($request->mode === 'upload') {
            $request->validate([
                'certificate_file' => 'required|file|mimes:cer,crt,pem,key,txt|max:5120',
            ]);
 
            $publicKey = $this->parseCertificateFile($request->file('certificate_file'));
 
            if (!$publicKey) {
                return response()->json([
                    'message' => 'File chứng thư không hợp lệ',
                ], 422);
            }
        } else {
            $request->validate([
                'public_key' => 'required|string|min:50',
            ]);
 
            $publicKey = $this->normalizePublicKey($request->public_key);
        }
 
        $duplicate = LecturerSignProfile::active()
            ->where('certificate_serial', $request->certificate_serial)
            ->where('lecturer_id', '!=', Auth::id())
            ->exists();
 
        if ($duplicate) {
            return response()->json([
                'message' => 'Serial chứng thư này đã được giảng viên khác đăng ký',
            ], 422);
        }
 
        /** @var LecturerSignProfile $newProfile */
        $newProfile = DB::transaction(function () use ($request, $publicKey) {
            LecturerSignProfile::forLecturer(Auth::id())
                ->active()
                ->update(['is_active' => false]);
 
            return LecturerSignProfile::create([
                'lecturer_id'        => Auth::id(),
                'public_key'         => $publicKey,
                'certificate_serial' => $request->certificate_serial,
                'cert_expires_at'    => $request->cert_expires_at,
                'is_active'          => true,
                'certificate_meta'   => [
                    'provider'      => $request->provider,
                    'cert_type'     => $request->cert_type,
                    'mode'          => $request->mode,
                    'registered_at' => now()->toIso8601String(),
                    'registered_ip' => $request->ip(),
                ],
            ]);
        });
 
        Log::info("Lecturer #" . Auth::id() . " registered sign profile #{$newProfile->id}");
 
        return response()->json([
            'message' => 'Đăng ký chữ ký số thành công',
            'profile' => $this->formatProfile($newProfile),
        ]);
    }
 
    /**
     * DELETE /api/lecturer/sign-profile
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
        ]);
 
        if (!Hash::check($request->current_password, Auth::user()->password)) {
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
 
        return response()->json([
            'message' => 'Đã vô hiệu hóa chữ ký số',
        ]);
    }
 
    // ── Private helpers ────────────────────────────────
 
    private function parseCertificateFile($file): ?string
    {
        try {
            $content = file_get_contents($file->getPathname());
 
            if (str_contains($content, '-----BEGIN')) {
                return $this->normalizePublicKey($content);
            }
 
            $pem = "-----BEGIN CERTIFICATE-----\n"
                 . chunk_split(base64_encode($content), 64, "\n")
                 . "-----END CERTIFICATE-----";
 
            return $pem;
        } catch (\Exception $e) {
            Log::warning('Parse certificate failed: ' . $e->getMessage());
            return null;
        }
    }
 
    private function normalizePublicKey(string $key): string
    {
        $lines = array_map('trim', explode("\n", $key));
        $lines = array_filter($lines, fn($l) => $l !== '');
        return implode("\n", $lines);
    }
 
    /**
     * Format profile cho response
     */
    private function formatProfile(LecturerSignProfile $profile): array
    {
        return [
            'id'                 => $profile->id,
            'certificate_serial' => $profile->certificate_serial,
            'cert_expires_at'    => $profile->cert_expires_at,
            'is_active'          => $profile->is_active,
            'is_expired'         => $profile->is_expired,
            'is_expiring_soon'   => $profile->is_expiring_soon,
            'is_valid'           => $profile->is_valid,
            'days_until_expired' => $profile->days_until_expired,
            'public_key_preview' => $this->maskPublicKey($profile->public_key),
            'certificate_meta'   => $profile->certificate_meta,
            'created_at'         => $profile->created_at,
        ];
    }
 
    private function maskPublicKey(string $key): string
    {
        $clean = preg_replace('/-----[A-Z ]+-----/', '', $key);
        $clean = preg_replace('/\s+/', '', $clean);
        if (strlen($clean) < 100) return $key;
        return substr($clean, 0, 50) . '...' . substr($clean, -30);
    }
}
