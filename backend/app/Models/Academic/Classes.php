<?php

namespace App\Models\Academic;
use App\Models\Communication\Group;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class Classes extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $fillable = [
        'code',
        'semester_id',
        'lecturer_id',
        'name',
        'min_members',
        'max_members',
        'group_registration_deadline',
        'is_active',
    ];
    protected $casts = [
        'is_active'                   => 'boolean',
        'group_registration_deadline' => 'datetime',
    ];
 
    /**
     * Quan hệ: Lớp thuộc về một Môn học
     */
    public function subjects()
    {
       return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
                    ->withPivot('max_members') ->withTimestamps();
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
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Quan hệ: Lớp có nhiều Sinh viên (Thông qua bảng trung gian class_students)
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_id', 'student_id')
                    ->withPivot('has_group', 'created_at', 'updated_at')
                    ->withTimestamps();
    }
    public function groups()
    {
        return $this->hasMany(Group::class, 'class_id');
    }
}
