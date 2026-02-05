<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Major;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    // 1. Lấy danh sách Ngành (Hỗ trợ lọc theo Khoa)
    public function index(Request $request)
    {
        $query = Major::with('faculty');
        // Nếu client gửi lên ?faculty_id=1 thì chỉ lấy ngành của khoa đó
        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        return response()->json($query->get());
    }

    // 2. Tạo Ngành
    public function store(Request $request)
    {
        $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'code'       => 'required|unique:majors,code',
            'name'       => 'required|string',
        ]);

        $major = Major::create($request->all());

        return response()->json(['message' => 'Tạo ngành thành công', 'data' => $major], 201);
    }

    public function update(Request $request, $id)
    {
        $major = Major::find($id);
        if (!$major) return response()->json(['message' => 'Không tìm thấy ngành'], 404);

        $request->validate([
            'faculty_id' => 'sometimes|exists:faculties,id',
            'code'       => 'sometimes|unique:majors,code,' . $id,
            'name'       => 'sometimes|string',
        ]);

        $major->update($request->all());
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $major]);
    }

    public function destroy($id)
    {
        $major = Major::find($id);
        if (!$major) return response()->json(['message' => 'Không tìm thấy ngành'], 404);
        
        // Nên check xem ngành này có Môn học/Lớp nào chưa
        // if ($major->subjects()->exists()) ...

        $major->delete();
        return response()->json(['message' => 'Đã xóa ngành']);
    }
}