<?php

namespace App\Models\Academic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class Classes extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $fillable = [
        'subject_id',
        'semester_id',
        'lecturer_id',
        'code',
        'name',
        'max_members',
    ];

    /**
     * Quan hệ: Lớp thuộc về một Môn học
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Quan hệ: Lớp thuộc về một Học kỳ
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Quan hệ: Lớp do một Giảng viên dạy
     */
    public function teacher()
    {
        // Giả sử bảng users lưu cả GV và SV
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Quan hệ: Lớp có nhiều Sinh viên (Thông qua bảng trung gian class_students)
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'student_id');
    }
}
