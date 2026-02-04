<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Academic\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SemesterController extends Controller
{
    /**
     * 1. DANH SÁCH (READ)
     * Lấy tất cả hoặc lọc theo trạng thái
     */
    public function index(Request $request)
    {
        $query = Semester::query();

        // Sắp xếp kỳ mới nhất lên đầu
        $query->orderBy('year', 'desc')->orderBy('code', 'desc');

        // Chỉ lọc kì đang mở
        if ($request->has('status') && $request->status == 'active') {
            $query->where('is_active', true);
        }

        return response()->json($query->get());
    }

    /**
     * 2. TẠO MỚI (CREATE)
     */
    public function store(Request $request)
    {
        // Validate dữ liệu
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|unique:semesters,code', // Mã không được trùng
            'year'       => 'required|integer|min:2000',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date', // Ngày kết thúc phải sau ngày bắt đầu
            'is_active'  => 'boolean'
        ], [
            'code.unique'      => 'Mã học kỳ này đã tồn tại.',
            'end_date.after'   => 'Ngày kết thúc phải diễn ra sau ngày bắt đầu.'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Logic phụ: Nếu tạo kỳ mới là Active -> tắt các kì cũ đi 
        // if ($request->is_active) { Semester::where('is_active', true)->update(['is_active' => false]); }

        $semester = Semester::create($request->all());

        return response()->json([
            'message' => 'Tạo học kỳ mới thành công!',
            'data'    => $semester
        ], 201);
    }

    public function show($id)
    {
        $semester = Semester::find($id);
        if (!$semester) {
            return response()->json(['message' => 'Không tìm thấy học kỳ.'], 404);
        }
        return response()->json($semester);
    }

    public function update(Request $request, $id)
    {
        $semester = Semester::find($id);
        if (!$semester) {
            return response()->json(['message' => 'Không tìm thấy học kỳ.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'       => 'sometimes|required|string|max:255',
            'code'       => 'sometimes|required|string|unique:semesters,code,' . $id, // Bỏ qua check trùng chính nó
            'year'       => 'sometimes|required|integer',
            'start_date' => 'sometimes|required|date',
            'end_date'   => 'sometimes|required|date|after:start_date',
            'is_active'  => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Logic phụ: Nếu update thành Active, tắt các kỳ khác
        if ($request->has('is_active') && $request->is_active == true) {
             Semester::where('id', '!=', $id)->update(['is_active' => false]);
        }

        $semester->update($request->all());

        return response()->json([
            'message' => 'Cập nhật học kỳ thành công!',
            'data'    => $semester
        ]);
    }

    //Xóa mềm
    public function destroy($id)
    {
        $semester = Semester::find($id);
        
        if (!$semester) {
            return response()->json(['message' => 'Không tìm thấy học kỳ.'], 404);
        }
        $semester->is_active = false;
        $semester->save();

        return response()->json([
            'message' => 'Đã ẩn học kỳ thành công (Trạng thái chuyển sang Không hoạt động).',
            'data'    => $semester
        ]);
    }
}