<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function storeBulk(Request $request, int $groupId): JsonResponse
    {
        $validated = $request->validate([
            'tasks'                 => 'required|array|min:1|max:20',
            'tasks.*.title'         => 'required|string|max:255',
            'tasks.*.description'   => 'nullable|string',
            'tasks.*.assignee_id'   => 'nullable|integer|exists:users,id',
            'tasks.*.deadline'      => 'required|date|after:now',
            'tasks.*.start_date'    => 'nullable|date',
            'tasks.*.priority'      => 'nullable|in:low,medium,high,urgent',
            'tasks.*.weight'        => 'nullable|integer|min:1|max:10',
        ]);

        $created = [];

        try {
            DB::transaction(function () use ($validated, $groupId, &$created) {
                foreach ($validated['tasks'] as $taskData) {
                    $result = $this->service->createTask(auth()->user(), $groupId, $taskData);
                    $created[] = $result['data'] ?? $result;
                }
            });

            return response()->json([
                'message' => 'Đã tạo ' . count($created) . ' công việc',
                'data'    => $created,
                'total'   => count($created),
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Bulk create tasks failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Tạo công việc thất bại: ' . $e->getMessage(),
                'created_count' => count($created),
            ], 422);
        }
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
    /**
     * POST /api/student/tasks/{id}/submit-review
     *
     * Assignee báo hoàn thành công việc
     * Body: { "note": "Đã làm xong phần này..." } (optional)
     */
    public function submitForReview(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);
    
        $result = $this->service->submitForReview(
            auth()->user(),
            $id,
            $data['note'] ?? null,
        );
    
        return $this->toResponse($result);
    }
 
    /**
     * POST /api/student/tasks/{id}/approve
     *
     * Trưởng nhóm duyệt
     * Body: { "note": "OK rồi" } (optional)
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);
    
        $result = $this->service->approveCompletion(
            auth()->user(),
            $id,
            $data['note'] ?? null,
        );
    
        return $this->toResponse($result);
    }
    
    /**
     * POST /api/student/tasks/{id}/reject
     *
     * Trưởng nhóm từ chối
     * Body: { "reason": "Phần này chưa đủ..." } (BẮT BUỘC)
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|min:5|max:1000',
        ], [
            'reason.required' => 'Vui lòng nhập lý do từ chối',
            'reason.min'      => 'Lý do phải có ít nhất 5 ký tự',
        ]);
    
        $result = $this->service->rejectCompletion(
            auth()->user(),
            $id,
            $data['reason'],
        );
    
        return $this->toResponse($result);
    }
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
