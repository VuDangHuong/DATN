<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Sign\LecturerSignProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignProfileController extends Controller
{
    /**
     * GET /api/lecturer/sign-profile
     */
    public function show()
    {
        $profile = LecturerSignProfile::where('lecturer_id', Auth::id())->first();

        if (!$profile) {
            return response()->json(['data' => null, 'message' => 'Chưa cấu hình chữ ký số.']);
        }

        // Không trả về public_key raw, chỉ trả metadata
        return response()->json([
            'data' => [
                'id'                  => $profile->id,
                'certificate_serial'  => $profile->certificate_serial,
                'certificate_meta'    => $profile->certificate_meta,
                'cert_expires_at'     => $profile->cert_expires_at,
                'is_active'           => $profile->is_active,
                'has_public_key'      => !empty($profile->public_key),
            ],
        ]);
    }

    /**
     * POST /api/lecturer/sign-profile
     * Tạo hoặc cập nhật profile chữ ký
     */
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'public_key'          => 'required|string',       // PEM format
            'certificate_serial'  => 'nullable|string|max:255',
            'certificate_meta'    => 'nullable|array',
            'cert_expires_at'     => 'nullable|date|after:today',
        ]);

        // Validate PEM format cơ bản
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
}
