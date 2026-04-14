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
        $query = Classes::with(['subjects', 'semester', 'lecturer']);

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
            'subject_details'              => 'required|array|min:1',
            'subject_details.*.subject_id' => 'required|exists:subjects,id',
            'subject_details.*.max_members'=> 'required|integer|min:1',
            'semester_id'                  => 'required|exists:semesters,id',
            'code'                         => 'required|unique:classes,code',
        ]);

        return DB::transaction(function () use ($request) {
            // Tạo lớp học (Bỏ max_members ở bảng chính)
            $class = Classes::create($request->except(['subject_details']));

            // Chuẩn bị dữ liệu cho attach
            $syncData = [];
            foreach ($request->subject_details as $detail) {
                $syncData[$detail['subject_id']] = ['max_members' => $detail['max_members']];
            }

            // Lưu hàng loạt vào bảng trung gian
            $class->subjects()->attach($syncData);

            return response()->json(['message' => 'Tạo lớp thành công', 'data' => $class->load('subjects')]);
        });
    }

    public function show($id)
    {
        $class = Classes::with(['subjects', 'semester', 'lecturer', 'students'])->find($id);
        if (!$class) return response()->json(['message' => 'Lớp không tồn tại'], 404);
        
        return response()->json($class);
    }

    public function update(Request $request, $id)
    {
        $class = Classes::findOrFail($id);

        $request->validate([
            'subject_details'              => 'required|array|min:1',
            'subject_details.*.subject_id' => 'required|exists:subjects,id',
            'subject_details.*.max_members'=> 'required|integer|min:1',
            'code'                         => 'required|unique:classes,code,' . $id,
        ]);

        return DB::transaction(function () use ($request, $class) {
            $class->update($request->except(['subject_details']));

            // Dùng Sync để tự động: Xóa môn cũ, Thêm môn mới, Cập nhật sĩ số môn hiện tại
            $syncData = [];
            foreach ($request->subject_details as $detail) {
                $syncData[$detail['subject_id']] = ['max_members' => $detail['max_members']];
            }
            
            $class->subjects()->sync($syncData);

            return response()->json(['message' => 'Cập nhật thành công', 'data' => $class->load('subjects')]);
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