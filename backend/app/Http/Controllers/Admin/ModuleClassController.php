<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Academic\Classes;
use App\Models\Academic\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClassesImport;
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
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
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
        $perPage = (int) $request->input('per_page', 5);
        $perPage = max(1, min($perPage, 100));

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject_details'              => 'required|array|min:1',
            'subject_details.*.subject_id' => 'required|exists:subjects,id',
            'subject_details.*.max_members'=> 'required|integer|min:1',
            'semester_id'                  => 'required|exists:semesters,id',
            'code'                         => 'required|unique:classes,code',
        ], [
        // subject_details
            'subject_details.required' => 'Vui lòng chọn ít nhất một môn học.',
            'subject_details.array'    => 'Danh sách môn học không hợp lệ.',
            'subject_details.min'      => 'Phải có ít nhất một môn học.',

            // subject_id
            'subject_details.*.subject_id.required' => 'Môn học không được để trống.',
            'subject_details.*.subject_id.exists'   => 'Môn học được chọn không tồn tại.',

            // max_members
            'subject_details.*.max_members.required' => 'Số lượng sinh viên tối đa không được để trống.',
            'subject_details.*.max_members.integer'  => 'Số lượng sinh viên tối đa phải là số nguyên.',
            'subject_details.*.max_members.min'      => 'Số lượng sinh viên tối đa phải lớn hơn 0.',

            // semester_id
            'semester_id.required' => 'Vui lòng chọn học kỳ.',
            'semester_id.exists'   => 'Học kỳ không tồn tại.',

            // code
            'code.required' => 'Mã lớp không được để trống.',
            'code.unique'   => 'Mã lớp đã tồn tại.',
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

    /**
     * Import danh sách Lớp học phần từ file Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $import = new ClassesImport();
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
            'Content-Disposition' => 'attachment; filename="mau_import_lop_hoc_phan.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, ['code', 'name', 'semester_code', 'lecturer_email', 'subject_codes', 'max_members_list']);
            fputcsv($file, ['CSE101.01', 'Lớp CSE101.01 - HK1 2024', 'HK1_2024', 'lecturer1@example.com', 'CSE101', '40']);
            fputcsv($file, ['CSE201.01', 'Lớp CSE201.01 - HK1 2024', 'HK1_2024', 'lecturer2@example.com', 'CSE201|CSE301', '35|35']);
            fputcsv($file, ['BUS101.01', 'Lớp BUS101.01 - HK1 2024', 'HK1_2024', '', 'BUS101', '50']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}