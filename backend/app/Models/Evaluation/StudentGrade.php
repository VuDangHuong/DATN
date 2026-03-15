<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Auth\User;
use App\Models\Academic\Classes;
class StudentGrade extends Model
{
   use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'group_id',
        'score',
        'lecturer_note',
        'is_final'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Lấy thông tin lớp học phần tương ứng [cite: 232]
     */
    public function courseClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Lấy thông tin nhóm của sinh viên (nếu có) [cite: 240]
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
