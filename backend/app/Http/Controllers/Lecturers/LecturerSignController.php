<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Sign\DocumentSignRequest;
use App\Services\DocumentSignService;
use App\Notifications\SignRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LecturerSignController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    /**
     * GET /api/lecturer/sign-requests
     */
    public function index(Request $request)
    {
        $requests = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->with(['requester:id,name,code', 'submission.group:id,name', 'classModel:id,name,code'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('forwarded_at')
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * GET /api/lecturer/sign-requests/{id}
     */
    public function show(int $id)
    {
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->with(['requester:id,name,code', 'submission.group', 'logs.actor:id,name,role'])
            ->findOrFail($id);

        // Đánh dấu GV đang xem
        if ($signRequest->status === DocumentSignRequest::STATUS_FORWARDED) {
            $signRequest->update(['status' => DocumentSignRequest::STATUS_LECTURER_REVIEWING]);
            $this->signService->log($signRequest->id, Auth::id(), 'lecturer_viewed');
        }

        return response()->json(['data' => $signRequest]);
    }

    /**
     * GET /api/lecturer/sign-requests/{id}/preview
     * Trả về URL tạm để xem file gốc trước khi ký
     */
    public function preview(int $id)
    {
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->findOrFail($id);

        if (!Storage::exists($signRequest->original_file)) {
            return response()->json(['message' => 'File không tồn tại.'], 404);
        }

        // Tạo URL tạm thời có hiệu lực 30 phút
        $url = Storage::temporaryUrl($signRequest->original_file, now()->addMinutes(30));

        return response()->json(['preview_url' => $url, 'expires_in' => 1800]);
    }

    /**
     * POST /api/lecturer/sign-requests/{id}/sign
     * GV upload file PDF đã ký
     */
    public function sign(Request $request, int $id)
    {
        $data = $request->validate([
            'signed_file'       => 'required|file|mimes:pdf|max:20480', // 20MB
            'sign_hash'         => 'required|string|size:64',            // SHA-256 hex
            'sign_certificate'  => 'nullable|string|max:255',
            'note'              => 'nullable|string|max:500',
        ]);

        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_FORWARDED,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->findOrFail($id);

        // Xác minh hash file upload
        if (!$this->signService->verifyFileHash($request->file('signed_file'), $data['sign_hash'])) {
            return response()->json([
                'message' => 'Hash không khớp. File có thể bị lỗi trong quá trình upload.',
            ], 422);
        }

        DB::transaction(function () use ($request, $signRequest, $data) {
            // Lưu file đã ký vào storage
            $path = $request->file('signed_file')->store(
                'signed_documents/' . date('Y/m'),
                'private'
            );

            $signRequest->update([
                'signed_file'      => $path,
                'sign_hash'        => $data['sign_hash'],
                'sign_certificate' => $data['sign_certificate'] ?? null,
                'status'           => DocumentSignRequest::STATUS_SIGNED,
                'signed_at'        => now(),
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'lecturer_signed',
                $data['note'] ?? null
            );
        });

        return response()->json([
            'message' => 'Ký số thành công. Admin sẽ phát hành tài liệu cho sinh viên.',
            'data'    => [
                'id'        => $signRequest->id,
                'status'    => $signRequest->fresh()->status,
                'signed_at' => $signRequest->signed_at,
            ],
        ]);
    }

    /**
     * POST /api/lecturer/sign-requests/{id}/reject
     */
    public function reject(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_FORWARDED,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->findOrFail($id);

        DB::transaction(function () use ($signRequest, $data) {
            $signRequest->update([
                'status'        => DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
                'reject_reason' => $data['reason'],
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'lecturer_rejected',
                $data['reason']
            );
        });

        $signRequest->requester->notify(new SignRequestRejected($signRequest, 'lecturer'));

        return response()->json(['message' => 'Đã từ chối ký tài liệu.']);
    }
}
