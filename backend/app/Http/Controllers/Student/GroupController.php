<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class GroupController extends Controller
{
    public function __construct(private readonly GroupService $service) {}
 
    /**
     * GET /student/classes/{classId}/groups
     *
     * Lấy danh sách nhóm trong lớp.
     */
    public function index(int $classId): JsonResponse
    {
        $this->resolveClass($classId);
        $result = $this->service->getGroupsByClass($classId);
        return response()->json($result['data']);
    }
     private function resolveClass($classId): Classes
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin xem được tất cả lớp
            return Classes::findOrFail($classId);
        }

        // Lecturer chỉ xem lớp mình quản lý
        return Classes::where('lecturer_id', $user->id)
            ->findOrFail($classId);
    }
    /**
     * POST /student/groups
     *
     * Body: { "class_id": 1, "name": "Nhóm 1" }
     *
     * Sinh viên tạo nhóm mới — tự động làm nhóm trưởng.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'name'     => 'required|string|max:255',
        ]);
 
        $result = $this->service->createGroup(
            auth()->user(),
            $request->class_id,
            $request->name
        );
 
        return $this->toResponse($result, 201);
    }
 
    /**
     * GET /student/groups/{groupId}
     *
     * Xem chi tiết nhóm (chỉ thành viên).
     */
    public function show(int $groupId): JsonResponse
    {
        $result = $this->service->getGroupDetail(auth()->user(), $groupId);
        return $this->toResponse($result);
    }
    /**
     * PUT /student/groups/{groupId}
     * Body: { "name": "Tên mới", "is_locked": true }
     *
     * Nhóm trưởng chỉnh sửa tên nhóm, khóa/mở nhóm.
     */
    public function update(Request $request, int $groupId): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'nullable|string|max:255',
            'is_locked' => 'nullable|boolean',
        ]);
 
        $result = $this->service->updateGroup(auth()->user(), $groupId, $data);
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/groups/{groupId}
     
     * Nhóm trưởng xóa nhóm. Xóa hết thành viên, messages, tasks.
     */
    public function destroy(int $groupId): JsonResponse
    {
        $result = $this->service->deleteGroup(auth()->user(), $groupId);
        return $this->toResponse($result);
    }
    /**
     * POST /student/groups/{groupId}/members
     *
     * Body: { "student_code": "2251172367" }
     *
     * Nhóm trưởng thêm thành viên.
     */
    public function addMember(Request $request, int $groupId): JsonResponse
    {
        $request->validate([
            'student_code' => 'required|string|max:50',
        ]);

        $user = auth()->user();

        // ✅ Lecturer bypass isLeader check — truyền leader của nhóm thay vì user thật
        $actor = $this->resolveActor($groupId, $user);

        $result = $this->service->addMember($actor, $groupId, $request->student_code);
        return $this->toResponse($result);
    }

    // ── Xóa thành viên ───────────────────────────────────
    public function removeMember(int $groupId, int $memberId): JsonResponse
    {
        $user  = auth()->user();
        $actor = $this->resolveActor($groupId, $user);

        $result = $this->service->removeMember($actor, $groupId, $memberId);
        return $this->toResponse($result);
    }
    public function leave(int $groupId): JsonResponse
    {
        $result = $this->service->leaveGroup(auth()->user(), $groupId);
        return $this->toResponse($result);
    }
    private function resolveActor(int $groupId, $user)
    {
        if ($user->role === 'lecturer' || $user->role === 'admin') {
            // Kiểm tra nhóm thuộc lớp của giảng viên
            $group = \App\Models\Group\Group::whereHas('classRoom', fn($q) =>
                $q->where('lecturer_id', $user->id)
            )->findOrFail($groupId);

            // Trả về leader để service không báo lỗi "chỉ leader mới được"
            return $group->leader;
        }

        return $user;
    }
    /**
     * POST /student/groups/{groupId}/transfer-leader
     * Body: { "new_leader_id": 3 }
     *
     * Nhóm trưởng chuyển quyền cho thành viên khác.
     */
    public function transferLeader(Request $request, int $groupId): JsonResponse
    {
        $request->validate([
            'new_leader_id' => 'required|integer|exists:users,id',
        ]);
 
        $result = $this->service->transferLeader(
            auth()->user(),
            $groupId,
            $request->new_leader_id
        );
 
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
