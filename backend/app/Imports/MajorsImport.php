<?php

namespace App\Imports;

use App\Models\Academic\Major;
use App\Models\Academic\Faculty;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
class MajorsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure,SkipsEmptyRows
{
    use SkipsErrors, SkipsFailures;

    protected $successCount = 0;
    protected $failCount = 0;
    protected $errorList = [];

    // Cache mã khoa -> id để không phải query lại
    protected $facultyCache = [];

    public function model(array $row)
    {
        if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
            return null;
        }
        // Tra faculty_id từ faculty_code
        $facultyCode = trim($row['faculty_code']);

        if (!isset($this->facultyCache[$facultyCode])) {
            $faculty = Faculty::where('code', $facultyCode)->first();
            $this->facultyCache[$facultyCode] = $faculty ? $faculty->id : null;
        }

        $facultyId = $this->facultyCache[$facultyCode];

        // Nếu mã khoa không tồn tại -> ghi nhận lỗi và bỏ qua
        if (!$facultyId) {
            $this->failCount++;
            $this->errorList[] = [
                'row'    => $this->successCount + $this->failCount + 1,
                'errors' => ["Mã khoa '{$facultyCode}' không tồn tại trong hệ thống"],
                'values' => $row,
            ];
            return null;
        }

        // Trùng mã ngành -> update
        $existing = Major::where('code', $row['code'])->first();

        if ($existing) {
            $existing->update([
                'faculty_id' => $facultyId,
                'name'       => $row['name'],
            ]);
            $this->successCount++;
            return null;
        }

        $this->successCount++;
        return new Major([
            'faculty_id' => $facultyId,
            'code'       => $row['code'],
            'name'       => $row['name'],
        ]);
    }

    public function rules(): array
    {
        return [
            'faculty_code' => 'required|string|max:50',
            'code'         => 'required|string|max:50',
            'name'         => 'required|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'faculty_code.required' => 'Mã khoa không được để trống',
            'code.required'         => 'Mã ngành không được để trống',
            'name.required'         => 'Tên ngành không được để trống',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failCount++;
            $this->errorList[] = [
                'row'    => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    public function getSuccessCount() { return $this->successCount; }
    public function getFailCount()    { return $this->failCount; }
    public function getErrors()       { return $this->errorList; }
}