<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Academic\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuleClassController extends Controller
{
    public function index(Request $request)
    {
        $query = Classes::with(['subjects', 'semester', 'teacher']);

        if (!$request->has('include_inactive')) {
            $query->whereHas('semester', function($q) {
                $q->where('is_active', true);
            });
        }

        if ($request->has('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }

        // Lọc theo Ngành
        if ($request->has('major_id')) {
            $query->whereHas('subjects', function($q) use ($request) {
                $q->where('major_id', $request->major_id);
            });
        }
        
        if ($request->has('lecturer_id')) {
            $query->where('lecturer_id', $request->lecturer_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_ids'   => 'required|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'semester_id'   => 'required|exists:semesters,id',
            'lecturer_id'   => 'nullable|exists:users,id',
            'code'         => 'required|unique:classes,code',
            'name'         => 'nullable|string',
            'max_members' => 'nullable|integer|min:1|max:200'
        ]);
        $class = Classes::create($request->except(['subject_details']));
        if ($request->has('subject_details')) {
        foreach ($request->subject_details as $detail) {
            $class->subjects()->attach($detail['subject_id'], [
                'max_members' => $detail['max_members']
            ]);
            }
        }
        $semester = Semester::find($request->semester_id);
        if (!$semester || !$semester->is_active) {
            return response()->json([
                'message' => 'Không thể mở lớp. Học kỳ này hiện đang đóng hoặc không tồn tại.'
            ], 403);
        }

        return DB::transaction(function () use ($request) {
            $data = $request->except('subject_ids');

            if (empty($data['max_members'])) {
                $data['max_members'] = 60;
            }

            $class = Classes::create($data);
            $class->subjects()->attach($request->subject_ids);

            return response()->json(['message' => 'Thành công', 'data' => $class->load('subjects')], 201);
        });
    }

    public function show($id)
    {
        $class = Classes::with(['subjects', 'semester', 'teacher', 'students'])->find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);
        
        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $class = Classes::with('semester')->find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);

        if (!$class->semester || !$class->semester->is_active) {
            return response()->json([
                'message' => 'Học kỳ của lớp này đã kết thúc hoặc bị đóng. Không thể chỉnh sửa.'
            ], 403);
        }

        $request->validate([
            'subject_ids'   => 'sometimes|array|min:1',
            'subject_ids.*' => 'exists:subjects,id',
            'lecturer_id'   => 'nullable|exists:users,id',
            'max_members'   => 'integer|min:1',
            'name'          => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request, $class) {
            $class->update($request->only(['lecturer_id', 'name', 'max_members']));

            // Nếu có gửi mảng môn học mới thì đồng bộ lại
            if ($request->has('subject_ids')) {
                $class->subjects()->sync($request->subject_ids);
            }

            return response()->json([
                'message' => 'Cập nhật lớp thành công', 
                'data' => $class->load('subjects')
            ]);
        });
    }

    public function destroy($id)
    {
        $class = Classes::find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);
        $class->delete();
        return response()->json(['message' => 'Đã hủy lớp học phần']);
    }
}