<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Sign\LecturerSignProfile;
use App\Services\DocumentSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class SignProfileController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}
 
    /**
     * GET /api/lecturer/sign-profile
     */
    public function show(): JsonResponse
    {
        $profile = LecturerSignProfile::where('lecturer_id', Auth::id())->first();
 
        if (!$profile) {
            return response()->json([
                'data'    => null,
                'message' => 'Chưa cấu hình chữ ký số.',
            ]);
        }
 
        return response()->json([
            'data' => [
                'id'                 => $profile->id,
                'certificate_serial' => $profile->certificate_serial,
                'certificate_meta'   => $profile->certificate_meta,
                'cert_expires_at'    => $profile->cert_expires_at,
                'is_active'          => $profile->is_active,
                'has_public_key'     => !empty($profile->public_key),
                // Thêm: cảnh báo sắp hết hạn
                'is_expiring_soon'   => $profile->cert_expires_at
                    && now()->diffInDays($profile->cert_expires_at) <= 30,
            ],
        ]);
    }
 
    /**
     * POST /api/lecturer/sign-profile
     * Tạo hoặc cập nhật profile chữ ký
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'public_key'         => 'required|string',
            'certificate_serial' => 'nullable|string|max:255',
            'certificate_meta'   => 'nullable|array',
            'cert_expires_at'    => 'nullable|date|after:today',
        ]);
 
        // Validate PEM format
        if (!str_contains($data['public_key'], '-----BEGIN PUBLIC KEY-----')) {
            return response()->json([
                'message' => 'Public key không đúng định dạng PEM.',
            ], 422);
        }
 
        $profile = LecturerSignProfile::updateOrCreate(
            ['lecturer_id' => Auth::id()],
            array_merge($data, ['is_active' => true])
        );
 
        return response()->json([
            'message' => 'Cập nhật profile chữ ký thành công.',
            'data'    => [
                'certificate_serial' => $profile->certificate_serial,
                'cert_expires_at'    => $profile->cert_expires_at,
                'is_active'          => $profile->is_active,
            ],
        ]);
    }
 
    /**
     * DELETE /api/lecturer/sign-profile
     * Vô hiệu hóa chữ ký (không xóa hẳn)
     */
    public function deactivate(): JsonResponse
    {
        $profile = LecturerSignProfile::where('lecturer_id', Auth::id())
            ->firstOrFail();
 
        $profile->update(['is_active' => false]);
 
        return response()->json(['message' => 'Đã vô hiệu hóa chữ ký số.']);
    }
 
    /**
     * GET /api/lecturer/sign-profile/categories
     * Danh sách loại tài liệu cần ký số
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => collect($this->signService->getCategories())
                ->map(fn($label, $key) => ['value' => $key, 'label' => $label])
                ->values(),
        ]);
    }
}
