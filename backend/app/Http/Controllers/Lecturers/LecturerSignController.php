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
use Illuminate\Http\JsonResponse;
class LecturerSignController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

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
 
        return response()->json($requests);
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
                'submission.group.members:id,name,code',
                'submission.student:id,name,code',
                'classModel:id,name,code',
                'logs.actor:id,name,role',
            ])
            ->findOrFail($id);
 
        // GV xem → chuyển forwarded sang lecturer_reviewing
        if ($signRequest->status === DocumentSignRequest::STATUS_FORWARDED) {
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
            'document_type' => $signRequest->document_type,          // pdf/docx
            'category'      => $signRequest->document_category_label, // "Báo cáo thực tập"
        ]);
    }

    /**
     * POST /api/lecturer/sign-requests/{id}/sign
     * GV upload file đã ký
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'signed_file'      => 'required|file|mimes:pdf,docx|max:20480',
            'sign_hash'        => 'nullable|string|size:64', // SHA256 = 64 hex chars
            'sign_certificate' => 'nullable|string',
        ]);

        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_FORWARDED,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->findOrFail($id);
 
        $file     = $request->file('signed_file');
        $filePath = $file->store("signed_documents/{$signRequest->id}", 'private');
 
        // Verify hash nếu client gửi lên
        if ($request->sign_hash) {
            if (!$this->signService->verifyFileHash($file, $request->sign_hash)) {
                Storage::delete($filePath);
                return response()->json(['message' => 'Hash file không khớp. Vui lòng kiểm tra lại.'], 422);
            }
        }
 
        // Tạo hash từ file server để lưu
        $serverHash = $this->signService->generateFileHash($file->getRealPath());
 
        DB::transaction(function () use ($signRequest, $filePath, $serverHash, $request) {
            $signRequest->update([
                'signed_file'      => $filePath,
                'sign_hash'        => $serverHash,
                'sign_certificate' => $request->sign_certificate,
                'status'           => DocumentSignRequest::STATUS_SIGNED,
                'signed_at'        => now(),
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'signed',
                'GV đã upload file đã ký số.'
            );
        });

        return response()->json([
            'message' => 'Đã ký số thành công. Admin sẽ phát hành tài liệu cho sinh viên.',
            'data'    => [
                'id'        => $signRequest->id,
                'status'    => DocumentSignRequest::STATUS_SIGNED,
                'sign_hash' => $serverHash,
                'signed_at' => now(),
            ],
        ]);
    }

    /**
     * POST /api/lecturer/sign-requests/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
 
        $signRequest = DocumentSignRequest::where('lecturer_id', Auth::id())
            ->whereIn('status', [
                DocumentSignRequest::STATUS_FORWARDED,
                DocumentSignRequest::STATUS_LECTURER_REVIEWING,
            ])
            ->findOrFail($id);
 
        DB::transaction(function () use ($signRequest, $request) {
            $signRequest->update([
                'status'        => DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
                'reject_reason' => $request->reason,
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'lecturer_rejected',
                $request->reason
            );
        });

        $signRequest->requester->notify(new SignRequestRejected($signRequest, 'lecturer'));

        return response()->json(['message' => 'Đã từ chối ký tài liệu.']);
    }
}
