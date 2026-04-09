<?php

namespace App\Services;

use App\Models\Academic\Classes;
use App\Models\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentCodeImport;
class ClassStudentService
{

    public function __construct()
    {
        //
    }
    public function getStudents(Classes $class): array
    {
        return [
            'class_id'      => $class->id,
            'class_code'    => $class->code,
            'class_name'    => $class->name,
            'max_members'   => $this->getMaxMembers($class),
            'current_count' => $class->students()->count(),
            'students'      => $class->students()
                ->select('users.id', 'users.code', 'users.name', 'users.email', 'users.phone')
                ->withPivot('has_group', 'created_at')
                ->orderBy('users.code')
                ->get()
                ->map(fn($s) => [
                    'id'        => $s->id,
                    'code'      => $s->code,
                    'name'      => $s->name,
                    'email'     => $s->email,
                    'phone'     => $s->phone,
                    'has_group' => (bool) $s->pivot->has_group,
                    'joined_at' => $s->pivot->created_at,
                ]),
        ];
    }

    public function addStudent(Classes $class, string $studentCode): array
    {
        $student = $this->findStudentByCodeOrFail($studentCode);
 
        if ($class->students()->where('student_id', $student->id)->exists()) {
            return $this->error('Sinh viên đã có trong lớp', 422);
        }
 
        $maxMembers = $this->getMaxMembers($class);
        if ($class->students()->count() >= $maxMembers) {
            return $this->error("Lớp đã đạt sĩ số tối đa ({$maxMembers})", 422);
        }
 
        $class->students()->attach($student->id, [
            'has_group'  => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
 
        return $this->success('Đã thêm sinh viên vào lớp', [
            'student'       => $this->formatStudent($student),
            'current_count' => $class->students()->count(),
        ]);
    }
 
    public function removeStudent(Classes $class, int $studentId): array
    {
        if (!$class->students()->where('student_id', $studentId)->exists()) {
            return $this->error('Sinh viên không có trong lớp', 404);
        }
 
        $class->students()->detach($studentId);
 
        return $this->success('Đã xóa sinh viên khỏi lớp', [
            'current_count' => $class->students()->count(),
        ]);
    }
 
    /**
     * Cập nhật thông tin pivot (has_group) của sinh viên trong lớp.
     */
    public function updateStudentPivot(Classes $class, int $studentId, array $data): array
    {
        if (!$class->students()->where('student_id', $studentId)->exists()) {
            return $this->error('Sinh viên không có trong lớp', 404);
        }
 
        $class->students()->updateExistingPivot($studentId, array_merge($data, [
            'updated_at' => now(),
        ]));
 
        return $this->success('Cập nhật thành công');
    }
 
    /**
     * Import sinh viên từ file Excel/CSV.
     *
     * Yêu cầu định dạng cột:
     *   A: Mã sinh viên (bắt buộc — phải khớp với users.code, role = student)
     *
     * Bỏ qua dòng header nếu cột A không phải mã số.
     */
    public function importStudents(Classes $class, UploadedFile $file): array
    {
        // ✅ Fix: dùng Import class thay vì []
        $import = new StudentCodeImport();
        Excel::import($import, $file);
        $rows = $import->rows;

        if (empty($rows)) {
            return $this->error('File trống hoặc không đúng định dạng', 422);
        }

        $maxMembers = $this->getMaxMembers($class);
        $added      = 0;
        $skipped    = 0;
        $notFound   = [];
        $duplicate  = [];
        $limitRows  = [];

        DB::transaction(function () use (
            &$added, &$skipped, &$notFound, &$duplicate, &$limitRows,
            $rows, $class, $maxMembers
        ) {
            foreach ($rows as $index => $row) {
                $code = trim((string) ($row[0] ?? ''));

                // Bỏ qua dòng rỗng hoặc header chữ
                if ($code === '' || !ctype_alnum(str_replace(['-', '_', '.'], '', $code))) {
                    continue;
                }

                if ($class->students()->count() >= $maxMembers) {
                    $limitRows[] = $code;
                    $skipped++;
                    continue;
                }

                $student = User::where('code', $code)
                    ->where('role', 'student')
                    ->first();

                if (!$student) {
                    $notFound[] = $code;
                    $skipped++;
                    continue;
                }

                if ($class->students()->where('student_id', $student->id)->exists()) {
                    $duplicate[] = $code;
                    $skipped++;
                    continue;
                }

                $class->students()->attach($student->id, [
                    'has_group'  => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $added++;
            }
        });

        return $this->success("Import hoàn tất: thêm {$added}, bỏ qua {$skipped}", [
            'added'         => $added,
            'skipped'       => $skipped,
            'current_count' => $class->students()->count(),
            'details'       => [
                'not_found_codes' => $notFound,
                'duplicate_codes' => $duplicate,
                'over_limit'      => $limitRows,
            ],
        ]);
    }
 
    // ─────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────
 
    /**
     * Tìm sinh viên theo code — bắt buộc phải có trong bảng users với role student.
     */
    private function findStudentByCodeOrFail(string $code): User
    {
        $student = User::where('code', $code)
            ->where('role', 'student')
            ->first();
 
        if (!$student) {
            abort(404, "Không tìm thấy sinh viên với mã: {$code}");
        }
 
        return $student;
    }
 
    /**
     * Lấy sĩ số tối đa: ưu tiên từ bảng class_subject, fallback về classes.max_members.
     */
    private function getMaxMembers(Classes $class): int
    {
        return $class->subjects()->first()?->pivot?->max_members
            ?? $class->max_members
            ?? PHP_INT_MAX;
    }
 
    private function formatStudent(User $student): array
    {
        return [
            'id'    => $student->id,
            'code'  => $student->code,
            'name'  => $student->name,
            'email' => $student->email,
        ];
    }
 
    private function success(string $message, array $data = []): array
    {
        return ['status' => 'success', 'message' => $message, 'data' => $data];
    }
 
    private function error(string $message, int $code = 400): array
    {
        return ['status' => 'error', 'message' => $message, 'code' => $code];
    }
}
