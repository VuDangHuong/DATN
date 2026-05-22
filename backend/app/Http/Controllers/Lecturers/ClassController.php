<?php

namespace App\Http\Controllers\Lecturers;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Group\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GroupService;
use App\Services\ClassStudentService;
class ClassController extends Controller
{
    public function __construct(private readonly GroupService $groupService,) {}

    public function index(Request $request)
    {
        $classes = Classes::where('lecturer_id', $request->user()->id)
            ->select('id', 'name', 'code','max_members_per_group')
            ->get();

        return response()->json($classes);
    }
    public function groups(int $classId): JsonResponse
    {
        $class = Classes::where('lecturer_id', Auth::id())->findOrFail($classId);
    
        $groups = Group::where('class_id', $classId)
        ->select('id', 'name', 'leader_id', 'invitation_code', 'is_locked', 'class_id')
        ->with(['leader:id,code,name'])
        ->withCount('members')   //Đếm members
        ->get()
        ->map(function ($g) {
            return [
                'id'              => $g->id,
                'name'            => $g->name,
                'leader_id'       => $g->leader_id,
                'invitation_code' => $g->invitation_code,
                'is_locked'       => $g->is_locked,
                'member_count'    => $g->members_count,   // ← từ withCount
                'leader'          => $g->leader ? [
                    'id'   => $g->leader->id,
                    'code' => $g->leader->code,
                    'name' => $g->leader->name,
                ] : null,
            ];
        });
    
        return response()->json(['groups' => $groups]);
    }
    public function updateMaxMembersPerGroup(Request $request, int $classId): JsonResponse
    {
        $data = $request->validate([
            'max_members_per_group' => 'nullable|integer|min:1|max:100',
        ], [
            'max_members_per_group.min' => 'Số thành viên ít nhất là 1',
            'max_members_per_group.max' => 'Số thành viên tối đa là 100',
        ]);
    
        $result = $this->groupService->updateMaxMembersPerGroup(
            auth()->user(),
            $classId,
            $data['max_members_per_group'] ?? null,
        );
    
        return $this->toResponse($result);
    }
    
    /**
     * POST /api/lecturer/groups/{id}/add-member
     * Body: { "student_code": "2251172368" }
     * GV thêm SV vào nhóm — BYPASS max
     */
    public function addMemberToGroup(Request $request, int $groupId): JsonResponse
    {
        $data = $request->validate([
            'student_code' => 'required|string|max:50',
        ]);
    
        $result = $this->groupService->addMemberByLecturer(
            auth()->user(),
            $groupId,
            $data['student_code'],
        );
    
        return $this->toResponse($result);
    }
    private function toResponse(array $result, int $successCode = 200): JsonResponse
    {
        if ($result['status'] === 'error') {
            return response()->json(
                ['message' => $result['message']],
                $result['code'] ?? 400
            );
        }
 
        return response()->json([
            'message' => $result['message'],
            ...$result['data'],
        ], $successCode);
    }
}
