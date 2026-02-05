<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use Illuminate\Http\Request;

class ModuleClassController extends Controller
{
    public function index(Request $request)
    {
        $query = Classes::with(['subject', 'semester', 'teacher']);

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