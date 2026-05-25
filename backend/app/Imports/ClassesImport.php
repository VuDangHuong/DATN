<?php

namespace App\Imports;

use App\Models\Academic\Classes;
use App\Models\Academic\Subject;
use App\Models\Academic\Semester;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;

class ClassesImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure,
    SkipsEmptyRows
{
    use SkipsFailures;

    protected $successCount = 0;
    protected $failCount = 0;
    protected $errorList = [];

    // Cache để tránh query lặp
    protected $semesterCache = [];
    protected $subjectCache  = [];
    protected $lecturerCache = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            // Số dòng thực tế trong Excel (cộng 2 vì header ở dòng 1)
            $rowNumber = $index + 2;

            // Bỏ qua dòng trống
            if (empty(array_filter($row->toArray(), fn($v) => !is_null($v) && trim((string)$v) !== ''))) {
                continue;
            }

            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (\Exception $e) {
                $this->failCount++;
                $this->errorList[] = [
                    'row'    => $rowNumber,
                    'errors' => [$e->getMessage()],
                    'values' => $row->toArray(),
                ];
            }
        }
    }

    protected function processRow(array $row, int $rowNumber)
    {
        // 1. Validate semester_code
        $semesterCode = trim($row['semester_code']);
        if (!isset($this->semesterCache[$semesterCode])) {
            $semester = Semester::where('code', $semesterCode)->first();
            $this->semesterCache[$semesterCode] = $semester ? $semester->id : null;
        }
        $semesterId = $this->semesterCache[$semesterCode];

        if (!$semesterId) {
            $this->addError($rowNumber, "Mã học kỳ '{$semesterCode}' không tồn tại", $row);
            return;
        }

        // 2. Validate lecturer (optional)
        $lecturerId = null;
        $lecturerEmail = trim($row['lecturer_email'] ?? '');
        if ($lecturerEmail !== '') {
            if (!isset($this->lecturerCache[$lecturerEmail])) {
                $lecturer = User::where('email', $lecturerEmail)
                    ->where('role', 'lecturer')
                    ->first();
                $this->lecturerCache[$lecturerEmail] = $lecturer ? $lecturer->id : null;
            }
            $lecturerId = $this->lecturerCache[$lecturerEmail];

            if (!$lecturerId) {
                $this->addError($rowNumber, "Email giảng viên '{$lecturerEmail}' không tồn tại hoặc không phải lecturer", $row);
                return;
            }
        }

        // 3. Tách và validate subject_codes + max_members_list
        $subjectCodes  = array_map('trim', explode('|', (string) $row['subject_codes']));
        $maxMembersArr = array_map('trim', explode('|', (string) $row['max_members_list']));

        if (count($subjectCodes) !== count($maxMembersArr)) {
            $this->addError(
                $rowNumber,
                "Số lượng môn học (" . count($subjectCodes) . ") không khớp với số lượng sĩ số (" . count($maxMembersArr) . ")",
                $row
            );
            return;
        }

        // Tra subject_id cho từng mã môn
        $syncData = [];
        foreach ($subjectCodes as $i => $subCode) {
            if (!isset($this->subjectCache[$subCode])) {
                $sub = Subject::where('code', $subCode)->first();
                $this->subjectCache[$subCode] = $sub ? $sub->id : null;
            }
            $subjectId = $this->subjectCache[$subCode];

            if (!$subjectId) {
                $this->addError($rowNumber, "Mã môn '{$subCode}' không tồn tại", $row);
                return;
            }

            $maxMembers = (int) $maxMembersArr[$i];
            if ($maxMembers < 1) {
                $this->addError($rowNumber, "Sĩ số của môn '{$subCode}' phải lớn hơn 0", $row);
                return;
            }

            $syncData[$subjectId] = ['max_members' => $maxMembers];
        }

        // 4. Lưu DB trong transaction
        DB::transaction(function () use ($row, $semesterId, $lecturerId, $syncData) {
            $existing = Classes::where('code', $row['code'])->first();

            if ($existing) {
                // Update
                $existing->update([
                    'name'         => $row['name'],
                    'semester_id'  => $semesterId,
                    'lecturer_id'  => $lecturerId,
                ]);
                $existing->subjects()->sync($syncData);
            } else {
                // Create
                $class = Classes::create([
                    'code'         => $row['code'],
                    'name'         => $row['name'],
                    'semester_id'  => $semesterId,
                    'lecturer_id'  => $lecturerId,
                ]);
                $class->subjects()->attach($syncData);
            }
        });

        $this->successCount++;
    }

    protected function addError($rowNumber, $message, $row)
    {
        $this->failCount++;
        $this->errorList[] = [
            'row'    => $rowNumber,
            'errors' => [$message],
            'values' => $row,
        ];
    }

    public function rules(): array
    {
        return [
            'code'             => 'required|string|max:50',
            'name'             => 'required|string|max:255',
            'semester_code'    => 'required|string|max:50',
            'subject_codes'    => 'required|string',
            'max_members_list' => 'required|string',
            // lecturer_email không required (lớp có thể chưa phân công GV)
        ];
    }

    public function customValidationMessages()
    {
        return [
            'code.required'             => 'Mã lớp không được để trống',
            'name.required'             => 'Tên lớp không được để trống',
            'semester_code.required'    => 'Mã học kỳ không được để trống',
            'subject_codes.required'    => 'Mã môn học không được để trống',
            'max_members_list.required' => 'Sĩ số không được để trống',
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