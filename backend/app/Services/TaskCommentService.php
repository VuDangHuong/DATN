<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Task\Task;
use App\Models\Task\TaskComment;
use App\Models\Task\TaskCommentAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskCommentService
{
    /**
     * Lấy danh sách comment + attachments
     */
    public function getComments(User $user, int $taskId): array
    {
        $task = Task::find($taskId);
        if (!$task) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Task không tồn tại'];
        }

        // ✅ Eager load attachments
        $comments = TaskComment::with([
                'user:id,name,code,avatar',
                'attachments.uploader:id,name',
            ])
            ->where('task_id', $taskId)
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'status'  => 'success',
            'message' => 'OK',
            'data'    => ['comments' => $comments],
        ];
    }

    /**
     *Thêm comment + upload files
     */
    public function addComment(User $user, int $taskId, array $data, array $files = []): array
    {
        $task = Task::find($taskId);
        if (!$task) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Task không tồn tại'];
        }

        // Check quyền (giữ logic cũ — chỉ thành viên nhóm)
        // ... permission check của bạn ...

        try {
            $comment = DB::transaction(function () use ($user, $taskId, $data, $files) {
                $comment = TaskComment::create([
                    'task_id' => $taskId,
                    'user_id' => $user->id,
                    'content' => $data['content'],
                ]);

                // Upload từng file
                foreach ($files as $file) {
                    $this->saveAttachment($comment->id, $file, $user->id);
                }

                return $comment;
            });

            $comment->load(['user:id,name,code,avatar', 'attachments']);

            return [
                'status'  => 'success',
                'message' => 'Đã thêm bình luận',
                'data'    => ['comment' => $comment],
            ];
        } catch (\Exception $e) {
            \Log::error('Add comment failed: ' . $e->getMessage());
            return ['status' => 'error', 'code' => 500, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Sửa comment + thêm file mới + xóa file cũ
     */
    public function updateComment(
        User $user,
        int $commentId,
        array $data,
        array $newFiles = [],
        array $removedAttachmentIds = []
    ): array {
        $comment = TaskComment::with('attachments')->find($commentId);
        if (!$comment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Comment không tồn tại'];
        }

        // Check quyền — chỉ chủ comment
        if ($comment->user_id !== $user->id) {
            return ['status' => 'error', 'code' => 403, 'message' => 'Không có quyền sửa'];
        }

        try {
            DB::transaction(function () use ($comment, $data, $newFiles, $removedAttachmentIds, $user) {
                // Update content
                $comment->update(['content' => $data['content']]);

                // Xóa các attachment được chỉ định
                if (!empty($removedAttachmentIds)) {
                    $attachmentsToDelete = TaskCommentAttachment::where('comment_id', $comment->id)
                        ->whereIn('id', $removedAttachmentIds)
                        ->get();

                    foreach ($attachmentsToDelete as $att) {
                        $att->delete(); // model event sẽ xóa file vật lý
                    }
                }

                // Upload file mới
                foreach ($newFiles as $file) {
                    $this->saveAttachment($comment->id, $file, $user->id);
                }
            });

            $comment->load(['user:id,name,code,avatar', 'attachments']);

            return [
                'status'  => 'success',
                'message' => 'Đã cập nhật bình luận',
                'data'    => ['comment' => $comment->fresh(['user', 'attachments'])],
            ];
        } catch (\Exception $e) {
            \Log::error('Update comment failed: ' . $e->getMessage());
            return ['status' => 'error', 'code' => 500, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Xóa comment + tất cả attachments
     */
    public function deleteComment(User $user, int $commentId): array
    {
        $comment = TaskComment::with('attachments', 'task.group')->find($commentId);
        if (!$comment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'Comment không tồn tại'];
        }

        // Chỉ chủ comment hoặc leader nhóm được xóa
        $isOwner  = $comment->user_id === $user->id;
        $isLeader = $comment->task?->group?->leader_id === $user->id;
        if (!$isOwner && !$isLeader) {
            return ['status' => 'error', 'code' => 403, 'message' => 'Không có quyền xóa'];
        }

        try {
            DB::transaction(function () use ($comment) {
                // Xóa attachments (model event xóa file vật lý)
                foreach ($comment->attachments as $att) {
                    $att->delete();
                }
                $comment->delete();
            });

            return [
                'status'  => 'success',
                'message' => 'Đã xóa bình luận',
                'data'    => [],
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * ✅ Xóa 1 attachment riêng lẻ
     */
    public function deleteAttachment(User $user, int $attachmentId): array
    {
        $attachment = TaskCommentAttachment::with('comment')->find($attachmentId);
        if (!$attachment) {
            return ['status' => 'error', 'code' => 404, 'message' => 'File không tồn tại'];
        }

        // Quyền: chỉ uploader hoặc chủ comment
        $canDelete = $attachment->uploaded_by === $user->id
                  || $attachment->comment?->user_id === $user->id;

        if (!$canDelete) {
            return ['status' => 'error', 'code' => 403, 'message' => 'Không có quyền xóa file này'];
        }

        try {
            $attachment->delete(); // model event xóa file vật lý
            return [
                'status'  => 'success',
                'message' => 'Đã xóa file đính kèm',
                'data'    => [],
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'code' => 500, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────

    /**
     * Save 1 file vào storage + tạo record DB
     */
    private function saveAttachment(int $commentId, $file, int $uploaderId): TaskCommentAttachment
    {
        // Lưu file vào storage/app/public/comments/{comment_id}/
        $path = $file->store("comments/{$commentId}", 'public');

        return TaskCommentAttachment::create([
            'comment_id'  => $commentId,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'mime_type'   => $file->getMimeType() ?? 'application/octet-stream',
            'file_size'   => $file->getSize(),
            'uploaded_by' => $uploaderId,
        ]);
    }
}