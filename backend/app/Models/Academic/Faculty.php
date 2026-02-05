<?php

namespace App\Models\Academic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;
    
    protected $table = 'faculties';

    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Quan hệ: Một Khoa có nhiều Ngành
     */
    public function majors()
    {
        return $this->hasMany(Major::class, 'faculty_id');
    }
}
