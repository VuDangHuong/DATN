<?php

namespace App\Http\Controllers\Lecturers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Group\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LecturerGroupController extends Controller
{
    // Trong LecturerGroupController (tạo mới nếu chưa có):
    public function members(int $groupId): JsonResponse
    {
        // Verify GV phụ trách lớp của nhóm này
        $group = Group::whereHas('classRoom', fn($q) =>
            $q->where('lecturer_id', Auth::id())
        )->findOrFail($groupId);
    
        $members = $group->members()
            ->with('user:id,name,code,email')
            ->get()
            ->map(fn($m) => [
                'id'   => $m->user_id,
                'name' => $m->user->name,
                'code' => $m->user->code,
                'role' => $m->role,
            ]);
    
        return response()->json(['members' => $members]);
    }
    public function tasks(Request $request, int $groupId): JsonResponse
    {
        // Verify GV phụ trách lớp chứa nhóm này
        $group = Group::whereHas('classRoom', fn($q) =>
            $q->where('lecturer_id', Auth::id())
        )->findOrFail($groupId); // 403 nếu không phải GV lớp

        // Lấy tasks — dùng lại logic từ TaskController nhưng không check member
        $tasks = $group->tasks()
            ->with(['assignee:id,name,code', 'creator:id,name'])
            ->get();

        $stats = [
            'total' => $tasks->count(),
            'todo'  => $tasks->where('status', 'todo')->count(),
            'doing' => $tasks->where('status', 'doing')->count(),
            'done'  => $tasks->where('status', 'done')->count(),
            'late'  => $tasks->where('status', 'late')->count(),
        ];

        return response()->json([
            'tasks' => $tasks,
            'stats' => $stats,
        ]);
    }
}
