<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $service) {}
 
    /**
     * GET /student/groups/{groupId}/tasks?status=todo&priority=high&assignee_id=3
     *
     * Lấy danh sách task trong nhóm (có filter).
     */
    public function index(Request $request, int $groupId): JsonResponse
    {
        $filters = $request->only(['status', 'priority', 'assignee_id']);
 
        $result = $this->service->getTasks(auth()->user(), $groupId, $filters);
 
        return $this->toResponse($result);
    }
 
    /**
     * POST /student/groups/{groupId}/tasks
     *
     * Body: {
     *   "title": "Thiết kế database",
     *   "description": "Thiết kế ERD cho module user",
     *   "assignee_id": 3,
     *   "deadline": "2026-05-01 23:59:00",
     *   "start_date": "2026-04-15 00:00:00",
     *   "priority": "high",
     *   "weight": 2
     * }
     *
     * Nhóm trưởng tạo task và giao cho thành viên.
     */
    public function store(Request $request, int $groupId): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'deadline'    => 'required|date|after:now',
            'start_date'  => 'nullable|date',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'weight'      => 'nullable|integer|min:1|max:10',
        ]);
 
        // Inject group_id
        $result = $this->service->createTask(auth()->user(), $groupId, $data);
 
        return $this->toResponse($result, 201);
    }
 
    /**
     * GET /student/tasks/{taskId}
     *
     * Xem chi tiết task kèm lịch sử hoạt động + comments.
     */
    public function show(int $taskId): JsonResponse
    {
        $result = $this->service->getTaskDetail(auth()->user(), $taskId);
        return $this->toResponse($result);
    }
 
    /**
     * PATCH /student/tasks/{taskId}/status
     *
     * Body: { "status": "doing" }
     *
     * Thành viên (assignee) hoặc leader cập nhật trạng thái task.
     * Nếu hoàn thành sau deadline → tự động chuyển thành 'late'.
     */
    public function updateStatus(Request $request, int $taskId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:todo,doing,done,late',
        ]);
 
        $result = $this->service->updateTaskStatus(
            auth()->user(),
            $taskId,
            $request->status
        );
 
        return $this->toResponse($result);
    }
 
    /**
     * PUT /student/tasks/{taskId}
     *
     * Nhóm trưởng cập nhật thông tin task.
     */
    public function update(Request $request, int $taskId): JsonResponse
    {
        $data = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'deadline'    => 'nullable|date',
            'start_date'  => 'nullable|date',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'weight'      => 'nullable|integer|min:1|max:10',
        ]);
 
        $result = $this->service->updateTask(auth()->user(), $taskId, $data);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/tasks/{taskId}
     *
     * Nhóm trưởng xóa task.
     */
    public function destroy(int $taskId): JsonResponse
    {
        $result = $this->service->deleteTask(auth()->user(), $taskId);
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
