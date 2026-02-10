<?php

namespace App\Models\Academic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Academic\Major;
use App\Models\Academic\Classes;
class Subject extends Model
{
    use HasFactory;
    protected $table = 'subjects';
    protected $fillable = [
        'major_id',
        'code',
        'name',
        'credits',
    ];

    public function major()
    {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function moduleClasses()
    {
        return $this->belongsToMany(Classes::class, 'class_subject', 'subject_id', 'class_id');
    }
}
