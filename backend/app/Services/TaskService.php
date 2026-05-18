<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Group\Group;
use App\Models\Task\Task;
use App\Models\Task\TaskActivity;
use App\Notifications\TaskReviewResolved;
use App\Notifications\TaskSubmittedForReview;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Lấy danh sách task trong nhóm.
     */
    public function getTasks(User $user, int $groupId, array $filters = []): array
    {
        $group = Group::findOrFail($groupId);

        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        $query = Task::where('group_id', $groupId)
            ->with([
                'assignee:id,code,name,avatar',
                'creator:id,code,name,avatar',
                'comments' => fn($q) => $q->orderBy('created_at', 'asc'),
                'comments.user:id,code,name,avatar',
                'comments.attachments',
                'comments.attachments.uploader:id,name',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $taskList = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
                         ->orderBy('deadline', 'asc')
                         ->get();

        $tasks = array_map(
            function ($task) { return $this->formatTask($task); },
            $taskList->all()
        );

        $statusCounts = Task::where('group_id', $groupId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN status = 'doing' THEN 1 ELSE 0 END) as doing,
                SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_review,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            ")
            ->first();

        $stats = [
            'total' => (int) $statusCounts->total,
            'todo'  => (int) $statusCounts->todo,
            'doing' => (int) $statusCounts->doing,
            'pending_review' => (int) $statusCounts->pending_review,
            'done'  => (int) $statusCounts->done,
            'late'  => (int) $statusCounts->late,
        ];

        return $this->success('Danh sách công việc', [
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }

    /**
     * Nhóm trưởng tạo task.
     */
    public function createTask(User $leader, int $groupId, array $data): array
    {
        $group = Group::findOrFail($groupId);

        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền giao việc', 403);
        }

        if (!empty($data['assignee_id'])) {
            if (!$group->isMember($data['assignee_id'])) {
                return $this->error('Người được giao việc không phải thành viên nhóm', 422);
            }
        }

        $task = DB::transaction(function () use ($leader, $group, $data) {
            $task = Task::create([
                'group_id'    => $group->id,
                'created_by'  => $leader->id,
                'assignee_id' => $data['assignee_id'] ?? null,
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'start_date'  => $data['start_date'] ?? null,
                'deadline'    => $data['deadline'],
                'priority'    => $data['priority'] ?? 'medium',
                'status'      => 'todo',
                'weight'      => $data['weight'] ?? 1,
            ]);

            TaskActivity::create([
                'task_id'   => $task->id,
                'user_id'   => $leader->id,
                'action'    => 'created',
                'old_value' => null,
                'new_value' => ['title' => $task->title, 'assignee_id' => $task->assignee_id],
            ]);

            return $task;
        });

        return $this->success('Tạo công việc thành công', [
            'task' => $this->formatTask($task->fresh(['assignee', 'creator'])),
        ]);
    }

    /**
     * Cập nhật trạng thái task.
     */
    public function updateTaskStatus(User $user, int $taskId, string $newStatus): array
    {
        $task = Task::with('group')->findOrFail($taskId);
        $group = $task->group;

        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        $isAssignee = $task->assignee_id === $user->id;
        $isLeader   = $group->isLeader($user->id);

        if (!$isAssignee && !$isLeader) {
            return $this->error('Chỉ người được giao việc hoặc nhóm trưởng mới được cập nhật trạng thái', 403);
        }

        $validStatuses = ['todo', 'doing', 'done', 'late'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->error('Trạng thái không hợp lệ. Cho phép: todo, doing, done, late', 422);
        }
        //Assignee (không phải leader) chỉ được chuyển todo ↔ doing
        if ($isAssignee && !$isLeader) {
            if (!in_array($newStatus, ['todo', 'doing'])) {
                return $this->error(
                    'Bạn chỉ có thể chuyển sang trạng thái "Đang làm" hoặc "Cần làm". '
                    . 'Để báo hoàn thành, hãy bấm "Báo hoàn thành" để gửi cho nhóm trưởng duyệt.',
                    403
                );
            }
        }

        $oldStatus = $task->status;

        DB::transaction(function () use ($task, $user, $newStatus, $oldStatus) {
            $updateData = ['status' => $newStatus];

            if ($newStatus === 'done') {
                $updateData['actual_finish_date'] = now();
                if (now()->gt($task->deadline)) {
                    $updateData['status'] = 'late';
                }
            }

            if (in_array($newStatus, ['todo', 'doing'])) {
                $updateData['actual_finish_date'] = null;
            }

            $task->update($updateData);

            TaskActivity::create([
                'task_id'   => $task->id,
                'user_id'   => $user->id,
                'action'    => 'status_changed',
                'old_value' => ['status' => $oldStatus],
                'new_value' => ['status' => $task->fresh()->status],
            ]);
        });

        return $this->success('Cập nhật trạng thái thành công', [
            'task' => $this->formatTask($task->fresh(['assignee', 'creator'])),
        ]);
    }

    /**
     * Nhóm trưởng cập nhật task.
     */
    public function updateTask(User $leader, int $taskId, array $data): array
    {
        $task = Task::with('group')->findOrFail($taskId);
        $group = $task->group;

        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền chỉnh sửa công việc', 403);
        }

        if (!empty($data['assignee_id']) && $data['assignee_id'] != $task->assignee_id) {
            if (!$group->isMember($data['assignee_id'])) {
                return $this->error('Người được giao việc không phải thành viên nhóm', 422);
            }
        }

        $oldData = $task->only(['title', 'description', 'assignee_id', 'priority', 'deadline', 'weight']);

        DB::transaction(function () use ($task, $leader, $data, $oldData) {
            $task->update(array_filter([
                'title'       => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'assignee_id' => $data['assignee_id'] ?? null,
                'priority'    => $data['priority'] ?? null,
                'deadline'    => $data['deadline'] ?? null,
                'start_date'  => $data['start_date'] ?? null,
                'weight'      => $data['weight'] ?? null,
            ], fn($v) => $v !== null));

            TaskActivity::create([
                'task_id'   => $task->id,
                'user_id'   => $leader->id,
                'action'    => 'updated',
                'old_value' => $oldData,
                'new_value' => $task->fresh()->only(['title', 'description', 'assignee_id', 'priority', 'deadline', 'weight']),
            ]);
        });

        return $this->success('Cập nhật công việc thành công', [
            'task' => $this->formatTask($task->fresh(['assignee', 'creator'])),
        ]);
    }

    /**
     * Nhóm trưởng xóa task.
     */
    public function deleteTask(User $leader, int $taskId): array
    {
        $task = Task::with('group')->findOrFail($taskId);

        if (!$task->group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền xóa công việc', 403);
        }

        $task->delete();

        return $this->success('Đã xóa công việc');
    }

    /**
     *Xem chi tiết task — eager load đầy đủ + KHÔNG overwrite comments
     */
    public function getTaskDetail(User $user, int $taskId): array
    {
        $task = Task::with([
            'group',                                            // để check member
            'assignee:id,name,code,avatar',
            'creator:id,name,code,avatar',
            'comments' => fn($q) => $q->orderBy('created_at', 'asc'),
            'comments.user:id,name,code,avatar',
            'comments.attachments',                             // ✅ eager load attachments
            'comments.attachments.uploader:id,name',
            'activities' => fn($q) => $q->orderBy('created_at', 'desc'),
            'activities.user:id,name',
        ])->find($taskId);

        if (!$task) {
            return $this->error('Task không tồn tại', 404);
        }

        if (!$task->group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        //Lấy data từ formatTask (đã có comments + attachments + avatar)
        $result = $this->formatTask($task);

        //CHỈ THÊM activities — KHÔNG overwrite comments
        $result['activities'] = $task->activities->map(function ($a) {
            return [
                'id'         => $a->id,
                'action'     => $a->action,
                'old_value'  => $a->old_value,
                'new_value'  => $a->new_value,
                'user'       => $a->user ? [
                    'id'         => $a->user->id,
                    'name'       => $a->user->name,
                    'avatar'     => $a->user->avatar,
                    'avatar_url' => $a->user->avatar_url,
                ] : null,
                'created_at' => $a->created_at,
            ];
        })->values()->toArray();

        return $this->success('Chi tiết công việc', ['task' => $result]);
    }

    // ─── 1. SV submit hoàn thành ──────────────────────────
    /**
     * Assignee báo task đã hoàn thành — chờ leader duyệt
     */
    public function submitForReview(User $user, int $taskId, ?string $note = null): array
    {
        $task = Task::with('group')->findOrFail($taskId);
    
        if (!$task->group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }
    
        // Chỉ assignee mới được submit
        if ($task->assignee_id !== $user->id) {
            return $this->error('Chỉ người được giao việc mới có thể báo hoàn thành', 403);
        }
    
        // Không cho submit nếu đang done hoặc đang pending_review
        if ($task->status === 'done') {
            return $this->error('Task này đã hoàn thành', 409);
        }
        if ($task->status === 'pending_review') {
            return $this->error('Đã gửi yêu cầu duyệt, đang chờ nhóm trưởng', 409);
        }
    
        try {
            DB::transaction(function () use ($task, $user, $note) {
                $oldStatus = $task->status;
    
                $task->update([
                    'status'                  => 'pending_review',
                    'submitted_for_review_at' => now(),
                    'submission_note'         => $note,
                    // Reset reviewed_by nếu là lần submit lại sau khi bị reject
                    'reviewed_by'             => null,
                    'reviewed_at'             => null,
                    'review_note'             => null,
                ]);
    
                TaskActivity::create([
                    'task_id'   => $task->id,
                    'user_id'   => $user->id,
                    'action'    => 'submitted_for_review',
                    'old_value' => ['status' => $oldStatus],
                    'new_value' => ['status' => 'pending_review', 'note' => $note],
                ]);
            });
    
            // Notify leader
            $this->notifyLeader($task->fresh(['group', 'assignee']));
    
            return $this->success('Đã gửi yêu cầu xác nhận hoàn thành. Chờ nhóm trưởng duyệt.', [
                'task' => $this->formatTask($task->fresh(['assignee', 'creator', 'reviewer'])),
            ]);
        } catch (\Exception $e) {
            \Log::error('Submit task review failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
    
    // ─── 2. Leader approve ────────────────────────────────
    /**
     * Trưởng nhóm duyệt task hoàn thành → status=done
     */
    public function approveCompletion(User $leader, int $taskId, ?string $note = null): array
    {
        $task = Task::with(['group', 'assignee'])->findOrFail($taskId);
    
        if (!$task->group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền duyệt', 403);
        }
    
        if ($task->status !== 'pending_review') {
            return $this->error('Task không ở trạng thái chờ duyệt', 409);
        }
    
        try {
            DB::transaction(function () use ($task, $leader, $note) {
                $finalStatus = 'done';
    
                // Nếu hoàn thành sau deadline → đánh dấu late
                if ($task->deadline && now()->gt($task->deadline)) {
                    $finalStatus = 'late';
                }
    
                $task->update([
                    'status'             => $finalStatus,
                    'actual_finish_date' => $task->submitted_for_review_at ?? now(),
                    'reviewed_by'        => $leader->id,
                    'reviewed_at'        => now(),
                    'review_note'        => $note,
                ]);
    
                TaskActivity::create([
                    'task_id'   => $task->id,
                    'user_id'   => $leader->id,
                    'action'    => 'review_approved',
                    'old_value' => ['status' => 'pending_review'],
                    'new_value' => ['status' => $finalStatus, 'note' => $note],
                ]);
            });
    
            // Notify assignee
            if ($task->assignee) {
                $this->notifyAssignee($task->fresh(['group', 'reviewer']), 'approved');
            }
    
            return $this->success('Đã duyệt hoàn thành công việc', [
                'task' => $this->formatTask($task->fresh(['assignee', 'creator', 'reviewer'])),
            ]);
        } catch (\Exception $e) {
            \Log::error('Approve task failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
    
    // ─── 3. Leader reject ─────────────────────────────────
    /**
     * Trưởng nhóm từ chối → status quay về 'doing', assignee phải làm tiếp
     */
    public function rejectCompletion(User $leader, int $taskId, string $reason): array
    {
        $task = Task::with(['group', 'assignee'])->findOrFail($taskId);
    
        if (!$task->group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền từ chối', 403);
        }
    
        if ($task->status !== 'pending_review') {
            return $this->error('Task không ở trạng thái chờ duyệt', 409);
        }
    
        try {
            DB::transaction(function () use ($task, $leader, $reason) {
                $task->update([
                    'status'             => 'doing',          // Quay về làm tiếp
                    'actual_finish_date' => null,
                    'reviewed_by'        => $leader->id,
                    'reviewed_at'        => now(),
                    'review_note'        => $reason,
                ]);
    
                TaskActivity::create([
                    'task_id'   => $task->id,
                    'user_id'   => $leader->id,
                    'action'    => 'review_rejected',
                    'old_value' => ['status' => 'pending_review'],
                    'new_value' => ['status' => 'doing', 'reason' => $reason],
                ]);
            });
    
            // Notify assignee
            if ($task->assignee) {
                $this->notifyAssignee($task->fresh(['group', 'reviewer']), 'rejected');
            }
    
            return $this->success('Đã từ chối, công việc sẽ quay lại trạng thái Đang làm', [
                'task' => $this->formatTask($task->fresh(['assignee', 'creator', 'reviewer'])),
            ]);
        } catch (\Exception $e) {
            \Log::error('Reject task failed: ' . $e->getMessage());
            return $this->error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    /**
     * Format task — bao gồm comments + attachments + user avatar
     */
    private function notifyLeader(Task $task): void
    {
        try {
            $leader = $task->group->leader ?? null;
            if (!$leader || $leader->id === $task->assignee_id) return;
    
            $leader->notify(new TaskSubmittedForReview($task));
        } catch (\Exception $e) {
            \Log::warning('Notify leader review failed: ' . $e->getMessage());
        }
    }
    
    private function notifyAssignee(Task $task, string $action): void
    {
        try {
            if (!$task->assignee) return;
            $task->assignee->notify(new TaskReviewResolved($task, $action));
        } catch (\Exception $e) {
            \Log::warning('Notify assignee review failed: ' . $e->getMessage());
        }
    }
    private function formatTask($task): array
    {
        return [
            'id'          => $task->id,
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status,
            'priority'    => $task->priority,
            'deadline'    => $task->deadline,
            'start_date'  => $task->start_date,
            'weight'      => $task->weight,
            'is_overdue'  => $task->deadline < now() && $task->status !== 'done',
            'assignee'    => $task->assignee,
            'creator'     => $task->creator,
            // Review info
            'submitted_for_review_at' => $task->submitted_for_review_at,
            'submission_note'         => $task->submission_note,
            'reviewer'                => $task->relationLoaded('reviewer') ? $task->reviewer : null,
            'reviewed_at'             => $task->reviewed_at,
            'review_note'             => $task->review_note,
            //Comments với attachments + user avatar (qua accessor avatar_url)
            'comments'    => $task->relationLoaded('comments')
                ? $task->comments->map(function ($c) {
                    return [
                        'id'          => $c->id,
                        'content'     => $c->content,
                        'created_at'  => $c->created_at,
                        'updated_at'  => $c->updated_at,
                        'user'        => $c->user,                  // include avatar_url accessor
                        'attachments' => $c->attachments ?? [],     // mỗi attachment có file_url, is_image, file_size_human
                    ];
                })->values()->toArray()
                : [],

            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
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