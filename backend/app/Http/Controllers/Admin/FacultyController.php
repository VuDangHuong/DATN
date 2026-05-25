<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Faculty; // Đảm bảo bạn đã tạo Model Faculty
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\FacultiesImport;
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
    /**
     * Import danh sách Khoa từ file Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120', // max 5MB
        ]);

        try {
            $import = new FacultiesImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Import thành công',
                'data' => [
                    'success_count' => $import->getSuccessCount(),
                    'fail_count' => $import->getFailCount(),
                    'errors' => $import->getErrors(),
                ],
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }
            return response()->json([
                'message' => 'File có dữ liệu không hợp lệ',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tải file mẫu Excel để Import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_import_khoa.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // BOM để Excel đọc đúng tiếng Việt
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['code', 'name']);
            fputcsv($file, ['CNTT', 'Công nghệ Thông tin']);
            fputcsv($file, ['KT', 'Kinh tế']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}