<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Evaluation\Submission;
use App\Services\DocumentSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignRequestController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    /**
     * POST /api/sign-requests
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'submission_id' => 'required|exists:submissions,id',
        ]);

        $submission = Submission::with(['assignment', 'group.members', 'student'])
            ->findOrFail($data['submission_id']);

        $assignment = $submission->assignment;

        // 1. Đợt nộp có yêu cầu ký số không
        if (!$assignment->requires_signing) {
            return response()->json([
                'message' => 'Đợt nộp bài này không yêu cầu ký số tài liệu.',
            ], 422);
        }

        // 2. Kiểm tra quyền
        if (!$this->canAccessSubmission($submission)) {
            return response()->json(['message' => 'Bạn không có quyền truy cập bài nộp này.'], 403);
        }

        // 3. Phải được duyệt trước
        if (!$submission->isApproved()) {
            return response()->json([
                'message' => 'Bài nộp chưa được giảng viên chấp nhận. Vui lòng chờ duyệt trước khi yêu cầu số hóa.',
            ], 422);
        }

        // 4. Có file không
        if (empty($submission->file_path)) {
            return response()->json(['message' => 'Bài nộp chưa có file tài liệu.'], 422);
        }

        // 5. Không trùng yêu cầu đang xử lý
        $existing = DocumentSignRequest::where('submission_id', $submission->id)
            ->whereNotIn('status', [
                DocumentSignRequest::STATUS_REJECTED_BY_ADMIN,
                DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
                DocumentSignRequest::STATUS_COMPLETED,
            ])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Đã có yêu cầu đang xử lý cho tài liệu này.'], 409);
        }

        // 6. Tạo yêu cầu
        $signRequest = DocumentSignRequest::create([
            'submission_id'           => $submission->id,
            'requester_id'            => Auth::id(),
            'class_id'                => $assignment->class_id,
            'document_type'           => pathinfo($submission->file_name, PATHINFO_EXTENSION), // ← sửa dòng này
            'document_category'       => $assignment->document_category,
            'document_category_label' => $assignment->document_category_label,
            'original_file'           => $submission->file_path,
            'status'                  => DocumentSignRequest::STATUS_PENDING,
        ]);

        $this->signService->log($signRequest->id, Auth::id(), 'created');

        return response()->json([
            'message' => 'Yêu cầu số hóa đã được gửi thành công.',
            'data'    => $signRequest->load(['submission.assignment:id,title']),
        ], 201);
    }

    /**
     * GET /api/sign-requests
     */
    public function myRequests(Request $request): JsonResponse
    {
        $requests = DocumentSignRequest::where('requester_id', Auth::id())
            ->with([
                'submission.assignment:id,title,document_category_label',
                'submission.group:id,name',
                'submission.student:id,name,code',
                'lecturer:id,name',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * GET /api/sign-requests/{id}
     */
    public function show(int $id): JsonResponse
    {
        $signRequest = DocumentSignRequest::where('requester_id', Auth::id())
            ->with([
                'submission.assignment:id,title,deadline,document_category_label',
                'submission.group:id,name',
                'submission.group.members:id,name,code',
                'submission.student:id,name,code',
                'lecturer:id,name,email',
                'logs.actor:id,name,role',
            ])
            ->findOrFail($id);

        return response()->json(['data' => $signRequest]);
    }

    /**
     * GET /api/sign-requests/{id}/download
     */
    public function download(int $id)
    {
        $signRequest = DocumentSignRequest::where('requester_id', Auth::id())
            ->where('status', DocumentSignRequest::STATUS_COMPLETED)
            ->findOrFail($id);

        if (!$signRequest->signed_file || !Storage::exists($signRequest->signed_file)) {
            return response()->json(['message' => 'File chưa sẵn sàng.'], 404);
        }
        $fileName = "PhieuXacNhanKySo_{$signRequest->id}_{$signRequest->requester->code}.pdf";
        return Storage::download(
            $signRequest->signed_file,
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function canAccessSubmission(Submission $submission): bool
    {
        $userId = Auth::id();
        if ($submission->submitter_type === 'group') {
            return $submission->group?->members()
                ->where('user_id', $userId)
                ->exists() ?? false;
        }
        return $submission->student_id === $userId;
    }
}