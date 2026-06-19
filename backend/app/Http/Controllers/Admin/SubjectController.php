<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Subject; // Đảm bảo đúng namespace model của bạn
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SubjectsImport;
class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('major');

        // 1. Lọc theo Ngành
        if ($request->has('major_id') && $request->major_id) {
            $query->where('major_id', $request->major_id);
        }

        // 2. Lọc theo Khoa
        if ($request->has('faculty_id') && $request->faculty_id) {
            $query->whereHas('major', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        // 3. Tìm kiếm theo tên hoặc mã môn
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $query->orderBy('code');

        // Không yêu cầu phân trang → trả full (dropdown nơi khác)
        if (!$request->boolean('paginate')) {
            return response()->json($query->get());
        }

        $perPage = (int) $request->input('per_page', 5);
        $perPage = max(1, min($perPage, 100));

        return response()->json($query->paginate($perPage));
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

        if ($subject->classes()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa môn này vì đã có Lớp học phần đang mở.'
            ], 400);
        }

        $subject->delete();

        return response()->json(['message' => 'Đã xóa môn học thành công']);
    }
    /**
     * Import danh sách Môn học từ file Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new SubjectsImport();
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
            'Content-Disposition' => 'attachment; filename="mau_import_mon_hoc.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, ['major_code', 'code', 'name', 'credits']);
            fputcsv($file, ['7480201', 'CSE101', 'Nhập môn lập trình', 3]);
            fputcsv($file, ['7480201', 'CSE201', 'Cấu trúc dữ liệu và Giải thuật', 4]);
            fputcsv($file, ['7340101', 'BUS101', 'Nguyên lý kế toán', 3]);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}