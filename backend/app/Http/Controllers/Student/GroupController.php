<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        $result = $this->service->getGroupsByClass($classId);
        return response()->json($result['data']);
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
 
        $result = $this->service->addMember(
            auth()->user(),
            $groupId,
            $request->student_code
        );
 
        return $this->toResponse($result);
    }
 
    /**
     * DELETE /student/groups/{groupId}/members/{memberId}
     *
     * Nhóm trưởng xóa thành viên.
     */
    public function removeMember(int $groupId, int $memberId): JsonResponse
    {
        $result = $this->service->removeMember(auth()->user(), $groupId, $memberId);
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
