<?php

namespace App\Models\Task;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    use HasFactory;
    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
    ];
 
    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
    ];
 
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
