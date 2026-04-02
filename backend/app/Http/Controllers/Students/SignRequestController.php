<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sign\DocumentSignRequest;
use App\Services\DocumentSignService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Evaluation\Submission;
class SignRequestController extends Controller
{
    public function __construct(protected DocumentSignService $signService) {}

    /**
     * POST /api/sign-requests
     * SV tạo yêu cầu số hóa cho một submission của nhóm mình
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'submission_id' => 'required|exists:submissions,id',
            'document_type' => 'required|in:report,slide',
        ]);
        // dd(Auth::id());
        $submission = Submission::findOrFail($data['submission_id']);
        // Kiểm tra SV có thuộc nhóm của submission này không
        $isMember = $submission->group->members()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$isMember) {
            return response()->json(['message' => 'Bạn không thuộc nhóm này.'], 403);
        }

        // Kiểm tra file tương ứng có tồn tại không
        $fileField = $data['document_type'] === 'report' ? 'report_file' : 'slide_file';
        if (empty($submission->$fileField)) {
            return response()->json(['message' => 'Nhóm chưa nộp tài liệu loại này.'], 422);
        }

        // Không cho tạo trùng yêu cầu đang xử lý
        $existing = DocumentSignRequest::where('submission_id', $data['submission_id'])
            ->where('document_type', $data['document_type'])
            ->whereNotIn('status', [
                DocumentSignRequest::STATUS_REJECTED_BY_ADMIN,
                DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
                DocumentSignRequest::STATUS_COMPLETED,
            ])
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Đã có yêu cầu đang xử lý cho tài liệu này.'], 409);
        }

        $signRequest = DocumentSignRequest::create([
            'submission_id' => $submission->id,
            'requester_id'  => Auth::id(),
            'class_id'      => $submission->group->class_id,
            'document_type' => $data['document_type'],
            'original_file' => $submission->$fileField,
            'status'        => DocumentSignRequest::STATUS_PENDING,
        ]);

        $this->signService->log($signRequest->id, Auth::id(), 'created');

        return response()->json([
            'message' => 'Yêu cầu số hóa đã được gửi.',
            'data'    => $signRequest->load('submission'),
        ], 201);
    }

    /**
     * GET /api/sign-requests
     * SV xem danh sách yêu cầu của mình
     */
    public function myRequests(Request $request)
    {
        $requests = DocumentSignRequest::where('requester_id', Auth::id())
            ->with(['submission.group', 'lecturer:id,name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10);

        return response()->json($requests);
    }

    /**
     * GET /api/sign-requests/{id}
     * SV xem chi tiết một yêu cầu
     */
    public function show(int $id)
    {
        $signRequest = DocumentSignRequest::where('requester_id', Auth::id())
            ->with(['submission.group', 'lecturer:id,name,email', 'logs.actor:id,name,role'])
            ->findOrFail($id);

        return response()->json(['data' => $signRequest]);
    }

    /**
     * GET /api/sign-requests/{id}/download
     * SV tải file đã ký (chỉ khi status = completed)
     */
    public function download(int $id)
    {
        $signRequest = DocumentSignRequest::where('requester_id', Auth::id())
            ->where('status', DocumentSignRequest::STATUS_COMPLETED)
            ->findOrFail($id);

        if (!$signRequest->signed_file || !Storage::exists($signRequest->signed_file)) {
            return response()->json(['message' => 'File chưa sẵn sàng.'], 404);
        }

        return Storage::download(
            $signRequest->signed_file,
            'signed_' . basename($signRequest->original_file)
        );
    }
}
