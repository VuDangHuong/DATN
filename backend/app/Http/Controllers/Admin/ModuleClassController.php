<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use Illuminate\Http\Request;
use App\Models\Academic\Semester;
class ModuleClassController extends Controller
{
    public function index(Request $request)
    {
        $query = Classes::with(['subject', 'semester', 'teacher']);

        if (!$request->has('include_inactive')) {
            $query->whereHas('semester', function($q) {
                $q->where('is_active', true);
            });
        }
        // Lọc theo Học kỳ
        if ($request->has('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        // Lọc theo Ngành
        if ($request->has('major_id')) {
            $query->whereHas('subject', function($q) use ($request) {
                $q->where('major_id', $request->major_id);
            });
        }
        
        // Lọc theo Giảng viên
        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_id'   => 'required|exists:subjects,id',
            'semester_id'  => 'required|exists:semesters,id',
            'lecturer_id'   => 'nullable|exists:users,id',
            'code'         => 'required|unique:classes,code',
            'name'         => 'nullable|string',
            'max_members' => 'integer|min:1'
        ]);
        $semester = Semester::find($request->semester_id);
        if (!$semester || !$semester->is_active) {
            return response()->json([
                'message' => 'Không thể mở lớp. Học kỳ này hiện đang đóng hoặc không tồn tại.'
            ], 403);
        }
        $class = Classes::create($request->all());

        return response()->json(['message' => 'Mở lớp học phần thành công', 'data' => $class], 201);
    }

    public function show($id)
    {
        $class = Classes::with(['subject', 'semester', 'teacher', 'students'])->find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);
        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $class = Classes::find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);

        if (!$class->semester || !$class->semester->is_active) {
            return response()->json([
                'message' => 'Học kỳ của lớp này đã kết thúc hoặc bị đóng. Không thể chỉnh sửa thông tin.'
            ], 403);
        }
        $request->validate([
            'lecturer_id'   => 'nullable|exists:users,id',
            'max_members' => 'integer|min:1',
        ]);

        $class->update($request->only(['lecturer_id', 'name', 'max_members']));

        return response()->json(['message' => 'Cập nhật thông tin lớp thành công', 'data' => $class]);
    }

    public function destroy($id)
    {
        $class = Classes::find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);
        
        $class->delete();
        return response()->json(['message' => 'Đã hủy lớp học phần']);
    }
}