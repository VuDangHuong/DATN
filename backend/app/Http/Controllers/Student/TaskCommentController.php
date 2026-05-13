<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\TaskCommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class TaskCommentController extends Controller
{
    public function __construct(private readonly TaskCommentService $service) {}
 
    /**
     * GET /student/tasks/{taskId}/comments
     *
     * Lấy danh sách bình luận trong task.
     */
    public function index(int $taskId): JsonResponse
    {
        $result = $this->service->getComments(auth()->user(), $taskId);
        return $this->toResponse($result);
    }
 
    /**
     * POST /student/tasks/{taskId}/comments
     *
     * Body: { "content": "Mình nghĩ phần này nên...", "attachment_url": null }
     *
     * Thành viên nhóm thêm bình luận vào task.
     */
    public function store(Request $request, int $taskId): JsonResponse
    {
        $data = $request->validate([
            'content' => 'required|string|max:5000',
            'files'   => 'nullable|array|max:10',
            'files.*' => 'file|max:20480',
        ], [
            'files.max'   => 'Tối đa 10 file mỗi bình luận',
            'files.*.max' => 'Mỗi file không vượt quá 20MB',
        ]);
 
        $files = $request->file('files', []);
 
        $result = $this->service->addComment(
            auth()->user(),
            $taskId,
            ['content' => $data['content']],
            $files
        );
 
        return $this->toResponse($result, 201);
    }
 
    /**
     * PUT /student/comments/{commentId}
     *
     * Body: { "content": "Nội dung sửa lại" }
     *
     * Chỉ người viết mới được sửa bình luận.
     */
    public function update(Request $request, int $commentId): JsonResponse
    {
        $data = $request->validate([
            'content'                 => 'required|string|max:5000',
            'files'                   => 'nullable|array|max:10',
            'files.*'                 => 'file|max:20480',
            'removed_attachment_ids'  => 'nullable|array',
            'removed_attachment_ids.*'=> 'integer|exists:task_comment_attachments,id',
        ]);
 
        $newFiles            = $request->file('files', []);
        $removedAttachmentIds = $data['removed_attachment_ids'] ?? [];
 
        $result = $this->service->updateComment(
            auth()->user(),
            $commentId,
            ['content' => $data['content']],
            $newFiles,
            $removedAttachmentIds
        );
 
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/comments/{commentId}
     *
     * Người viết hoặc nhóm trưởng mới được xóa.
     */
     public function destroy(int $commentId): JsonResponse
    {
        $result = $this->service->deleteComment(auth()->user(), $commentId);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/comments/attachments/{attachmentId}
     * Xóa 1 attachment riêng lẻ (không cần update toàn bộ comment)
     */
    public function deleteAttachment(int $attachmentId): JsonResponse
    {
        $result = $this->service->deleteAttachment(auth()->user(), $attachmentId);
        return $this->toResponse($result);
    }
 
    // ─────────────────────────────────────────────
 
    private function toResponse(array $result, int $successCode = 200): JsonResponse
    {
        if ($result['status'] === 'error') {
            return response()->json(['message' => $result['message']], $result['code']);
        }
 
        return response()->json([
            'message' => $result['message'],
            ...$result['data'],
        ], $successCode);
    }
}
