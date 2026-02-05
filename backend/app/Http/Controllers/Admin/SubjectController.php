<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject; // Đảm bảo đúng namespace model của bạn
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('major');

        // 1. Lọc theo Ngành
        if ($request->has('major_id')) {
            $query->where('major_id', $request->major_id);
        }

        // 2. Lọc theo Khoa
        if ($request->has('faculty_id')) {
            $query->whereHas('major', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        // 3. Tìm kiếm theo tên hoặc mã môn
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%$keyword%")
                  ->orWhere('code', 'like', "%$keyword%");
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'major_id' => 'required|exists:majors,id',
           'code' => [
            'required',
            Rule::unique('subjects')->where(function ($query) use ($request) {
                return $query->where('major_id', $request->major_id);
            }),
        ],
            'name'     => 'required|string|max:255',
            'credits'  => 'required|integer|min:1',
        ], [
            'major_id.exists' => 'Ngành học không tồn tại.',
            'code.unique'     => 'Mã môn học này đã tồn tại.'
        ]);

        $subject = Subject::create($request->all());

        return response()->json([
            'message' => 'Thêm môn học thành công',
            'data'    => $subject
        ], 201);
    }

    public function show($id)
    {
        $subject = Subject::with('major')->find($id);
        
        if (!$subject) {
            return response()->json(['message' => 'Môn học không tồn tại'], 404);
        }
        
        return response()->json($subject);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);
        
        if (!$subject) {
            return response()->json(['message' => 'Môn học không tồn tại'], 404);
        }

        $request->validate([
            'major_id' => 'sometimes|exists:majors,id',
            'code' => [
                'sometimes',
                'required',
                Rule::unique('subjects')->where(function ($query) use ($request, $subject) {
                    $majorId = $request->major_id ?? $subject->major_id;
                    return $query->where('major_id', $majorId);
                })->ignore($id), 
            ],
            'name'     => 'sometimes|string',
            'credits'  => 'sometimes|integer|min:1',
        ]);

        $subject->update($request->all());

        return response()->json([
            'message' => 'Cập nhật môn học thành công',
            'data'    => $subject
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::find($id);
        
        if (!$subject) {
            return response()->json(['message' => 'Môn học không tồn tại'], 404);
        }

        if ($subject->moduleClasses()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa môn này vì đã có Lớp học phần đang mở.'
            ], 400);
        }

        $subject->delete();

        return response()->json(['message' => 'Đã xóa môn học thành công']);
    }
}