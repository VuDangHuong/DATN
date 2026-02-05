<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Faculty; // Đảm bảo bạn đã tạo Model Faculty
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function index()
    {
        return response()->json(Faculty::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:faculties,code',
            'name' => 'required|string|max:255',
        ]);

        $faculty = Faculty::create($request->all());

        return response()->json(['message' => 'Tạo khoa thành công', 'data' => $faculty], 201);
    }

    public function update(Request $request, $id)
    {
        $faculty = Faculty::find($id);
        if (!$faculty) return response()->json(['message' => 'Không tìm thấy khoa'], 404);

        $request->validate([
            'code' => 'sometimes|required|unique:faculties,code,' . $id,
            'name' => 'sometimes|required|string',
        ]);

        $faculty->update($request->all());

        return response()->json(['message' => 'Cập nhật thành công', 'data' => $faculty]);
    }

    public function destroy($id)
    {
        $faculty = Faculty::find($id);
        if (!$faculty) return response()->json(['message' => 'Không tìm thấy khoa'], 404);

        // Kiểm tra xem khoa có ngành nào không, nếu có thì chặn xóa
        if ($faculty->majors()->exists()) {
            return response()->json(['message' => 'Không thể xóa khoa này vì đã có dữ liệu Ngành trực thuộc.'], 400);
        }

        $faculty->delete();
        return response()->json(['message' => 'Đã xóa khoa thành công']);
    }
}