<?php

namespace App\Models\Academic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Major extends Model
{
    use HasFactory;
    protected $table = 'majors';

    protected $fillable = [
        'faculty_id',
        'code',
        'name',
    ];

    /**
     * Quan hệ: Ngành thuộc về một Khoa
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    /**
     * Quan hệ: Ngành có nhiều Môn học (Subjects)
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'major_id');
    }
    
    /**
     * Quan hệ tiện ích: Lấy tất cả Lớp học phần của Ngành này
     */
    public function classes()
    {
        return $this->hasManyThrough(Classes::class, Subject::class, 'major_id', 'subject_id', 'id', 'id');
    }
}
