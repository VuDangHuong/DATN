<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Auth\User;
use App\Notifications\SignRequestSigned;
use App\Services\DocumentSignService;
use App\Notifications\SignRequestForwarded;
use App\Notifications\SignRequestRejected;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminSignController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    /**
     * GET /api/admin/sign-requests
     * Filter: ?status=pending&class_id=1&category=bao_cao_thuc_tap
     */
    public function index(Request $request): JsonResponse
    {
        $requests = DocumentSignRequest::with([
                'requester:id,name,code',
                'submission.group:id,name',
                'submission.student:id,name,code',
                'classModel:id,name,code',
                'lecturer:id,name',
            ])
            ->whereNotNull('document_category')
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->category, fn($q) => $q->where('document_category', $request->category))
            ->latest()
            ->paginate(15);

        return response()->json($requests);
    }

    /**
     * GET /api/admin/sign-requests/{id}
     * Chi tiết đầy đủ kèm audit log
     */
    public function show(int $id): JsonResponse
    {
        $signRequest = DocumentSignRequest::with([
                'requester:id,name,code,email',
                'submission.assignment:id,title,document_category_label',
                'submission.group:id,name',
                'submission.group.members:id,name,code',
                'submission.student:id,name,code',
                'classModel:id,name,code',
                'lecturer:id,name,email',
                'logs.actor:id,name,role',
            ])
            ->findOrFail($id);

        // Nếu đang pending thì Admin đang xem → chuyển sang admin_reviewing
        if ($signRequest->status === DocumentSignRequest::STATUS_PENDING) {
            $signRequest->update(['status' => DocumentSignRequest::STATUS_ADMIN_REVIEWING]);
            $this->signService->log($signRequest->id, Auth::id(), 'admin_reviewing');
        }

        return response()->json(['data' => $signRequest]);
    }

    /**
     * POST /api/admin/sign-requests/{id}/forward
     * Admin chuyển yêu cầu cho GV cụ thể
     */
    public function forward(Request $request, int $id): JsonResponse
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
        try {
            $lecturer->notify(new SignRequestForwarded(
                $signRequest->load(['submission', 'classModel', 'requester'])
            ));
        } catch (\Exception $e) {
            // Log lỗi nhưng không block response
            \Log::error('SignRequestForwarded notification failed: ' . $e->getMessage());
        }
 
        return response()->json([
            'message' => "Đã chuyển yêu cầu cho {$lecturer->name}.",
            'data'    => $signRequest->fresh(['lecturer:id,name,email']),
        ]);
    }

    /**
     * POST /api/admin/sign-requests/{id}/reject
     * Admin từ chối (tài liệu không hợp lệ)
     */
    public function reject(Request $request, int $id): JsonResponse
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
    public function complete(int $id): JsonResponse
    {
        $signRequest = DocumentSignRequest::where('status', DocumentSignRequest::STATUS_SIGNED)
            ->findOrFail($id);
 
        // Kiểm tra GV đã upload file chưa
        if (empty($signRequest->signed_file)) {
            return response()->json([
                'message' => 'Chưa có file đã ký. GV cần upload file trước.',
            ], 422);
        }
 
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
 
    /**
     * GET /api/admin/sign-requests/stats
     * Thống kê theo trạng thái và loại tài liệu
     */
    public function stats(): JsonResponse
    {
        $byStatus = DocumentSignRequest::selectRaw('status, count(*) as total')
            ->whereNotNull('document_category')
            ->groupBy('status')
            ->get()
            ->map(fn($r) => [
                'status'       => $r->status,
                'status_label' => (new DocumentSignRequest(['status' => $r->status]))->status_label,
                'total'        => $r->total,
            ]);
 
        $byCategory = DocumentSignRequest::selectRaw('document_category, document_category_label, count(*) as total')
            ->whereNotNull('document_category')
            ->groupBy('document_category', 'document_category_label')
            ->get();
 
        return response()->json([
            'by_status'   => $byStatus,
            'by_category' => $byCategory,
        ]);
    }
}
