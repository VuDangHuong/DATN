<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Task\Task;
use App\Models\Task\TaskActivity;
use App\Models\Task\TaskComment;

class TaskCommentService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getComments(User $user, int $taskId): array
    {
        $task = Task::with('group')->findOrFail($taskId);
 
        if (!$task->group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        $comments = TaskComment::where('task_id', $taskId)
            ->with('user:id,code,name,avatar')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($c) {
                return $this->formatComment($c);
            })->values()->toArray();
 
        return $this->success('Danh sách bình luận', [
            'comments' => $comments,
            'total'    => count($comments),
        ]);
    }
 
    /**
     * Thêm bình luận vào task.
     *
     * Mọi thành viên trong nhóm đều có thể bình luận.
     */
    public function addComment(User $user, int $taskId, array $data): array
    {
        $task = Task::with('group')->findOrFail($taskId);
 
        if (!$task->group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
 
        $comment = TaskComment::create([
            'task_id'        => $taskId,
            'user_id'        => $user->id,
            'content'        => $data['content'],
            'attachment_url' => $data['attachment_url'] ?? null,
        ]);
 
        // Ghi log activity
        TaskActivity::create([
            'task_id'   => $taskId,
            'user_id'   => $user->id,
            'action'    => 'commented',
            'old_value' => null,
            'new_value' => ['comment_id' => $comment->id, 'content' => $comment->content],
        ]);
 
        $comment->load('user:id,code,name,avatar');
 
        return $this->success('Đã thêm bình luận', [
            'comment' => $this->formatComment($comment),
        ]);
    }
 
    /**
     * Chỉnh sửa bình luận.
     *
     * Chỉ người viết bình luận mới được sửa.
     */
    public function updateComment(User $user, int $commentId, array $data): array
    {
        $comment = TaskComment::with(['task.group', 'user'])->findOrFail($commentId);
 
        if ($comment->user_id !== $user->id) {
            return $this->error('Bạn chỉ có thể sửa bình luận của mình', 403);
        }
 
        $oldContent = $comment->content;
 
        $comment->update([
            'content'        => $data['content'] ?? $comment->content,
            'attachment_url' => $data['attachment_url'] ?? $comment->attachment_url,
        ]);
 
        // Ghi log
        TaskActivity::create([
            'task_id'   => $comment->task_id,
            'user_id'   => $user->id,
            'action'    => 'comment_updated',
            'old_value' => ['content' => $oldContent],
            'new_value' => ['content' => $comment->content],
        ]);
 
        $comment->load('user:id,code,name,avatar');
 
        return $this->success('Đã cập nhật bình luận', [
            'comment' => $this->formatComment($comment),
        ]);
    }
 
    /**
     * Xóa bình luận.
     *
     * Người viết hoặc nhóm trưởng mới được xóa.
     */
    public function deleteComment(User $user, int $commentId): array
    {
        $comment = TaskComment::with('task.group')->findOrFail($commentId);
        $group = $comment->task->group;
 
        // Chỉ người viết hoặc leader mới được xóa
        if ($comment->user_id !== $user->id && !$group->isLeader($user->id)) {
            return $this->error('Bạn không có quyền xóa bình luận này', 403);
        }
 
        // Ghi log trước khi xóa
        TaskActivity::create([
            'task_id'   => $comment->task_id,
            'user_id'   => $user->id,
            'action'    => 'comment_deleted',
            'old_value' => ['content' => $comment->content, 'author_id' => $comment->user_id],
            'new_value' => null,
        ]);
 
        $comment->delete();
 
        return $this->success('Đã xóa bình luận');
    }
 
    // ─────────────────────────────────────────────
 
    private function formatComment(TaskComment $comment): array
    {
        return [
            'id'             => $comment->id,
            'content'        => $comment->content,
            'attachment_url' => $comment->attachment_url,
            'user'           => $comment->user ? [
                'id'     => $comment->user->id,
                'code'   => $comment->user->code,
                'name'   => $comment->user->name,
                'avatar' => $comment->user->avatar ?? null,
            ] : null,
            'is_mine'        => false, // sẽ được set ở controller nếu cần
            'created_at'     => $comment->created_at,
            'updated_at'     => $comment->updated_at,
        ];
    }
 
    private function success(string $message, array $data = []): array
    {
        return ['status' => 'success', 'message' => $message, 'data' => $data];
    }
 
    private function error(string $message, int $code = 400): array
    {
        return ['status' => 'error', 'message' => $message, 'code' => $code];
    }
}
