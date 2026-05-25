<?php

namespace App\Imports;

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
class FacultiesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure,SkipsEmptyRows 
{
    use SkipsErrors, SkipsFailures;

    protected $successCount = 0;
    protected $failCount = 0;
    protected $errorList = [];

    public function model(array $row)
    {
        if (empty(array_filter($row, fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
            return null;
        }
        // Kiểm tra trùng code -> update, không có thì tạo mới
        $existing = Faculty::where('code', $row['code'])->first();

        if ($existing) {
            $existing->update(['name' => $row['name']]);
            $this->successCount++;
            return null; // không tạo mới
        }

        $this->successCount++;
        return new Faculty([
            'code' => $row['code'],
            'name' => $row['name'],
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'code.required' => 'Mã khoa không được để trống',
            'name.required' => 'Tên khoa không được để trống',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failCount++;
            $this->errorList[] = [
                'row' => $failure->row(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailCount()
    {
        return $this->failCount;
    }

    public function getErrors()
    {
        return $this->errorList;
    }
}