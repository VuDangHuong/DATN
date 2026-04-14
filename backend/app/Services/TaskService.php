<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Group\Group;
use App\Models\Task\Task;
use App\Models\Task\TaskActivity;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Lấy danh sách task trong nhóm.
     *
     * Hỗ trợ filter theo status, assignee, priority.
     */
    public function getTasks(User $user, int $groupId, array $filters = []): array
    {
        $group = Group::findOrFail($groupId);

        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        $query = Task::where('group_id', $groupId)
            ->with([
                'assignee:id,code,name',
                'creator:id,code,name',
            ]);

        // Filter theo status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter theo assignee
        if (!empty($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        // Filter theo priority
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $taskList = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
                         ->orderBy('deadline', 'asc')
                         ->get();

        // ✅ Dùng array_map thay vì Collection->map để tránh lỗi callable
        $tasks = array_map(
            function ($task) { return $this->formatTask($task); },
            $taskList->all()
        );

        // Thống kê nhanh — dùng 1 query thay vì 5
        $statusCounts = Task::where('group_id', $groupId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'todo' THEN 1 ELSE 0 END) as todo,
                SUM(CASE WHEN status = 'doing' THEN 1 ELSE 0 END) as doing,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
            ")
            ->first();

        $stats = [
            'total' => (int) $statusCounts->total,
            'todo'  => (int) $statusCounts->todo,
            'doing' => (int) $statusCounts->doing,
            'done'  => (int) $statusCounts->done,
            'late'  => (int) $statusCounts->late,
        ];

        return $this->success('Danh sách công việc', [
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }

    /**
     * Nhóm trưởng tạo task và giao cho thành viên.
     *
     * Chỉ leader mới được tạo task.
     * assignee_id phải là thành viên trong nhóm.
     */
    public function createTask(User $leader, int $groupId, array $data): array
    {
        $group = Group::findOrFail($groupId);

        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền giao việc', 403);
        }

        // Kiểm tra assignee thuộc nhóm
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

            // Ghi log activity
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
     *
     * - Thành viên (assignee) được cập nhật status: todo → doing → done
     * - Nhóm trưởng được cập nhật mọi field
     * - Nếu done sau deadline → tự động chuyển thành 'late'
     */
    public function updateTaskStatus(User $user, int $taskId, string $newStatus): array
    {
        $task = Task::with('group')->findOrFail($taskId);
        $group = $task->group;

        if (!$group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        // Chỉ assignee hoặc leader mới được cập nhật status
        $isAssignee = $task->assignee_id === $user->id;
        $isLeader   = $group->isLeader($user->id);

        if (!$isAssignee && !$isLeader) {
            return $this->error('Chỉ người được giao việc hoặc nhóm trưởng mới được cập nhật trạng thái', 403);
        }

        $validStatuses = ['todo', 'doing', 'done', 'late'];
        if (!in_array($newStatus, $validStatuses)) {
            return $this->error('Trạng thái không hợp lệ. Cho phép: todo, doing, done, late', 422);
        }

        $oldStatus = $task->status;

        DB::transaction(function () use ($task, $user, $newStatus, $oldStatus) {
            $updateData = ['status' => $newStatus];

            // Nếu chuyển sang done → ghi actual_finish_date
            if ($newStatus === 'done') {
                $updateData['actual_finish_date'] = now();

                // Nếu hoàn thành sau deadline → đánh dấu late
                if (now()->gt($task->deadline)) {
                    $updateData['status'] = 'late';
                }
            }

            // Nếu quay lại todo/doing → xóa actual_finish_date
            if (in_array($newStatus, ['todo', 'doing'])) {
                $updateData['actual_finish_date'] = null;
            }

            $task->update($updateData);

            // Ghi log
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
     * Nhóm trưởng cập nhật thông tin task (title, description, assignee, priority, deadline...).
     */
    public function updateTask(User $leader, int $taskId, array $data): array
    {
        $task = Task::with('group')->findOrFail($taskId);
        $group = $task->group;

        if (!$group->isLeader($leader->id)) {
            return $this->error('Chỉ nhóm trưởng mới có quyền chỉnh sửa công việc', 403);
        }

        // Nếu đổi assignee → kiểm tra thuộc nhóm
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
     * Xem chi tiết task kèm lịch sử hoạt động.
     */
    public function getTaskDetail(User $user, int $taskId): array
    {
        $task = Task::with(['assignee:id,code,name', 'creator:id,code,name', 'activities.user:id,code,name', 'comments.user:id,code,name', 'group'])
            ->findOrFail($taskId);

        if (!$task->group->isMember($user->id)) {
            return $this->error('Bạn không phải thành viên của nhóm này', 403);
        }

        $result = $this->formatTask($task);

        $result['activities'] = $task->activities->map(function ($a) {
            return [
                'id'         => $a->id,
                'action'     => $a->action,
                'old_value'  => $a->old_value,
                'new_value'  => $a->new_value,
                'user'       => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null,
                'created_at' => $a->created_at,
            ];
        })->values()->toArray();

        $result['comments'] = $task->comments->map(function ($c) {
            return [
                'id'             => $c->id,
                'content'        => $c->content,
                'attachment_url' => $c->attachment_url,
                'user'           => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                'created_at'     => $c->created_at,
            ];
        })->values()->toArray();

        return $this->success('Chi tiết công việc', ['task' => $result]);
    }

    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────

    private function formatTask(Task $task): array
    {
        return [
            'id'                 => $task->id,
            'title'              => $task->title,
            'description'        => $task->description,
            'status'             => $task->status,
            'priority'           => $task->priority,
            'weight'             => $task->weight,
            'start_date'         => $task->start_date,
            'deadline'           => $task->deadline,
            'actual_finish_date' => $task->actual_finish_date,
            'is_overdue'         => $task->isOverdue(),
            'creator'            => $task->creator ? [
                'id'   => $task->creator->id,
                'code' => $task->creator->code,
                'name' => $task->creator->name,
            ] : null,
            'assignee'           => $task->assignee ? [
                'id'   => $task->assignee->id,
                'code' => $task->assignee->code,
                'name' => $task->assignee->name,
            ] : null,
            'created_at'         => $task->created_at,
            'updated_at'         => $task->updated_at,
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