<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Group\Group;

class StudentDashboardService
{
    /**
     * Lấy thông tin dashboard cho sinh viên khi login.
     *
     * Trả về danh sách lớp mà SV được add/import vào, kèm:
     *   - Thông tin lớp (code, name)
     *   - Môn học của lớp (qua class_subject → subjects)
     *   - Giảng viên phụ trách (lecturer)
     *   - Kỳ học (semester)
     *   - Nhóm của SV trong lớp (nếu có)
     */
    public function getMyClasses(User $student): array
    {
        $classes = $student->enrolledClasses()
            ->with([
                'semester:id,code,name,year',
                'lecturer:id,code,name,email',
                'subjects:subjects.id,subjects.code,subjects.name,subjects.credits',
            ])
            ->get()
            ->map(function ($class) use ($student) {
                // Tìm nhóm của SV trong lớp này — nguồn sự thật duy nhất
                $group = Group::where('class_id', $class->id)
                    ->whereHas('members', fn($q) => $q->where('user_id', $student->id))
                    ->with(['leader:id,code,name', 'members.user:id,code,name'])
                    ->first();

                // ✅ has_group tính từ thực tế (có nhóm hay không)
                // KHÔNG dùng $class->pivot->has_group vì dữ liệu pivot có thể bị lệch
                $actuallyHasGroup = $group !== null;

                // ✅ Auto-sync: nếu pivot bị lệch → sửa lại cho đúng
                if ((bool) $class->pivot->has_group !== $actuallyHasGroup) {
                    $class->students()->updateExistingPivot($student->id, [
                        'has_group'  => $actuallyHasGroup,
                        'updated_at' => now(),
                    ]);
                }

                return [
                    'class' => [
                        'id'   => $class->id,
                        'code' => $class->code,
                        'name' => $class->name,
                        'min_members' => $class->min_members,
                        'max_members' => $class->max_members,
                        'group_registration_deadline' => $class->group_registration_deadline,
                        'is_active' => $class->is_active,
                    ],
                    'semester' => $class->semester ? [
                        'id'   => $class->semester->id,
                        'code' => $class->semester->code,
                        'name' => $class->semester->name,
                        'year' => $class->semester->year,
                    ] : null,
                    'lecturer' => $class->lecturer ? [
                        'id'    => $class->lecturer->id,
                        'code'  => $class->lecturer->code,
                        'name'  => $class->lecturer->name,
                        'email' => $class->lecturer->email,
                    ] : null,
                    'subjects' => $class->subjects->map(fn($s) => [
                        'id'      => $s->id,
                        'code'    => $s->code,
                        'name'    => $s->name,
                        'credits' => $s->credits,
                    ]),
                    'my_group' => $group ? [
                        'id'              => $group->id,
                        'name'            => $group->name,
                        'invitation_code' => $group->invitation_code,
                        'is_locked'       => $group->is_locked,
                        'leader'          => $group->leader ? [
                            'id'   => $group->leader->id,
                            'code' => $group->leader->code,
                            'name' => $group->leader->name,
                        ] : null,
                        'members' => $group->members->map(fn($m) => [
                            'id'   => $m->user->id,
                            'code' => $m->user->code,
                            'name' => $m->user->name,
                            'role' => $m->role,
                        ]),
                    ] : null,
                    'has_group' => $actuallyHasGroup,  // ✅ Từ thực tế, không từ pivot
                ];
            });

        return [
            'status'  => 'success',
            'message' => 'Thông tin lớp học của bạn',
            'data'    => [
                'student' => [
                    'id'    => $student->id,
                    'code'  => $student->code,
                    'name'  => $student->name,
                    'email' => $student->email,
                ],
                'classes' => $classes,
            ],
        ];
    }
}