<?php

namespace App\Imports;

use App\Models\Academic\Subject;
use App\Models\Academic\Major;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;

class SubjectsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure,
    SkipsEmptyRows
{
    use SkipsErrors, SkipsFailures;

    protected $successCount = 0;
    protected $failCount = 0;
    protected $errorList = [];

    // Cache mã ngành -> id
    protected $majorCache = [];

    public function model(array $row)
    {
        // Bỏ qua dòng trống
        if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
            return null;
        }

        $majorCode = trim($row['major_code'] ?? '');

        // Tra major_id từ major_code (có cache)
        if (!isset($this->majorCache[$majorCode])) {
            $major = Major::where('code', $majorCode)->first();
            $this->majorCache[$majorCode] = $major ? $major->id : null;
        }

        $majorId = $this->majorCache[$majorCode];

        if (!$majorId) {
            $this->failCount++;
            $this->errorList[] = [
                'row'    => $this->successCount + $this->failCount + 1,
                'errors' => ["Mã ngành '{$majorCode}' không tồn tại trong hệ thống"],
                'values' => $row,
            ];
            return null;
        }

        // Trùng mã môn TRONG CÙNG NGÀNH -> update
        // (vì unique constraint là code + major_id)
        $existing = Subject::where('code', $row['code'])
            ->where('major_id', $majorId)
            ->first();

        if ($existing) {
            $existing->update([
                'name'    => $row['name'],
                'credits' => (int) $row['credits'],
            ]);
            $this->successCount++;
            return null;
        }

        $this->successCount++;
        return new Subject([
            'major_id' => $majorId,
            'code'     => $row['code'],
            'name'     => $row['name'],
            'credits'  => (int) $row['credits'],
        ]);
    }

    public function rules(): array
    {
        return [
            'major_code' => 'required|string|max:50',
            'code'       => 'required|string|max:50',
            'name'       => 'required|string|max:255',
            'credits'    => 'required|integer|min:1|max:10',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'major_code.required' => 'Mã ngành không được để trống',
            'code.required'       => 'Mã môn không được để trống',
            'name.required'       => 'Tên môn không được để trống',
            'credits.required'    => 'Số tín chỉ không được để trống',
            'credits.integer'     => 'Số tín chỉ phải là số nguyên',
            'credits.min'         => 'Số tín chỉ phải lớn hơn hoặc bằng 1',
            'credits.max'         => 'Số tín chỉ không được lớn hơn 10',
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