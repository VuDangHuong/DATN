<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Auth\User;
use App\Notifications\SignRequestSigned;
use App\Services\DocumentSignService;
use App\Notifications\SignRequestForwarded;
use App\Notifications\SignRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSignController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    /**
     * GET /api/admin/sign-requests?status=pending&class_id=1
     */
    public function index(Request $request)
    {
        $requests = DocumentSignRequest::with([
                'requester:id,name,code',
                'submission.group:id,name',
                'classModel:id,name,code',
                'lecturer:id,name',
            ])
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->latest()
            ->paginate(15);

        return response()->json($requests);
    }

    /**
     * GET /api/admin/sign-requests/{id}
     * Chi tiết đầy đủ kèm audit log
     */
    public function show(int $id)
    {
        $signRequest = DocumentSignRequest::with([
                'requester:id,name,code,email',
                'submission.group.members.user:id,name,code',
                'classModel:id,name,code',
                'lecturer:id,name,email',
                'logs.actor:id,name,role',
            ])
            ->findOrFail($id);

        // Nếu đang pending thì Admin đang xem → chuyển sang admin_reviewing
        if ($signRequest->status === DocumentSignRequest::STATUS_PENDING) {
            $signRequest->update(['status' => DocumentSignRequest::STATUS_ADMIN_REVIEWING]);
            $this->signService->log($signRequest->id, Auth::id(), 'admin_approved');
        }

        return response()->json(['data' => $signRequest]);
    }

    /**
     * POST /api/admin/sign-requests/{id}/forward
     * Admin chuyển yêu cầu cho GV cụ thể
     */
    public function forward(Request $request, int $id)
    {
        $data = $request->validate([
            'lecturer_id' => 'required|exists:users,id',
            'note'        => 'nullable|string|max:500',
        ]);

        // Validate lecturer_id phải có role lecturer
        $lecturer = User::where('id', $data['lecturer_id'])
            ->where('role', 'lecturer')
            ->firstOrFail();

        $signRequest = DocumentSignRequest::whereIn('status', [
                DocumentSignRequest::STATUS_PENDING,
                DocumentSignRequest::STATUS_ADMIN_REVIEWING,
            ])
            ->findOrFail($id);

        DB::transaction(function () use ($signRequest, $lecturer, $data) {
            $signRequest->update([
                'lecturer_id'  => $lecturer->id,
                'status'       => DocumentSignRequest::STATUS_FORWARDED,
                'forwarded_at' => now(),
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'forwarded_to_lecturer',
                $data['note'] ?? null
            );
        });
        $signRequest->refresh();
        // Gửi thông báo cho GV
        // $lecturer->notify(new SignRequestForwarded($signRequest));

        return response()->json([
            'message' => "Đã chuyển yêu cầu cho {$lecturer->name}.",
            'data'    => $signRequest->fresh(['lecturer:id,name,email']),
        ]);
    }

    /**
     * POST /api/admin/sign-requests/{id}/reject
     * Admin từ chối (tài liệu không hợp lệ)
     */
    public function reject(Request $request, int $id)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        // Tìm record trước — nếu không có id này thì 404 rõ ràng
        $signRequest = DocumentSignRequest::findOrFail($id);

        // Kiểm tra status riêng — trả về lỗi có message cụ thể
        if (in_array($signRequest->status, [
            DocumentSignRequest::STATUS_COMPLETED,
            DocumentSignRequest::STATUS_REJECTED_BY_ADMIN,
            DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
        ])) {
            return response()->json([
                'message' => "Không thể từ chối yêu cầu đang ở trạng thái: {$signRequest->status}",
            ], 422);
        }

        DB::transaction(function () use ($signRequest, $data) {
            $signRequest->update([
                'status'        => DocumentSignRequest::STATUS_REJECTED_BY_ADMIN,
                'reject_reason' => $data['reason'],
            ]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'admin_rejected',
                $data['reason']
            );
        });

        $signRequest->requester->notify(new SignRequestRejected($signRequest, 'admin'));

        return response()->json(['message' => 'Đã từ chối yêu cầu.']);
    }

    /**
     * POST /api/admin/sign-requests/{id}/complete
     * Admin phát hành file đã ký về cho SV
     */
    public function complete(int $id)
    {
        $signRequest = DocumentSignRequest::where('status', DocumentSignRequest::STATUS_SIGNED)
            ->findOrFail($id);

        DB::transaction(function () use ($signRequest) {
            $signRequest->update(['status' => DocumentSignRequest::STATUS_COMPLETED]);

            $this->signService->log(
                $signRequest->id,
                Auth::id(),
                'completed',
                'Admin đã phát hành file đã ký cho sinh viên.'
            );
        });

        // Thông báo SV có thể tải về
        $signRequest->requester->notify(new SignRequestSigned($signRequest));

        return response()->json(['message' => 'Đã phát hành tài liệu đã ký cho sinh viên.']);
    }
}
