<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Major;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MajorsImport;
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

        if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
        }

        $query->orderBy('code');

        // Không yêu cầu phân trang → trả full (dùng cho dropdown nơi khác)
        if (!$request->boolean('paginate')) {
            return response()->json($query->get());
        }

        $perPage = (int) $request->input('per_page', 5);
        $perPage = max(1, min($perPage, 100));

        return response()->json($query->paginate($perPage));
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
    /**
     * Import danh sách Ngành từ file Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new MajorsImport();
            Excel::import($import, $request->file('file'));

            return response()->json([
                'message' => 'Import thành công',
                'data' => [
                    'success_count' => $import->getSuccessCount(),
                    'fail_count'    => $import->getFailCount(),
                    'errors'        => $import->getErrors(),
                ],
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row'       => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors'    => $failure->errors(),
                    'values'    => $failure->values(),
                ];
            }
            return response()->json([
                'message' => 'File có dữ liệu không hợp lệ',
                'errors'  => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tải file mẫu CSV để Import
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="mau_import_nganh.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, ['faculty_code', 'code', 'name']);
            fputcsv($file, ['CNTT', '7480201', 'Công nghệ thông tin']);
            fputcsv($file, ['CNTT', '7480103', 'Kỹ thuật phần mềm']);
            fputcsv($file, ['KT',   '7340101', 'Quản trị kinh doanh']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}