<?php

namespace App\Models\Task;

use App\Models\Auth\User;
use App\Models\Group\Group;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'created_by',
        'assignee_id',
        'title',
        'description',
        'start_date',
        'deadline',
        'actual_finish_date',
        'priority',    // 'low' | 'medium' | 'high' | 'urgent'
        'status',      // 'todo' | 'doing' | 'done' | 'late'
        'weight',
    ];
 
    protected $casts = [
        'start_date'         => 'datetime',
        'deadline'           => 'datetime',
        'actual_finish_date' => 'datetime',
    ];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
 
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
 
    public function activities()
    {
        return $this->hasMany(TaskActivity::class)->orderBy('created_at', 'desc');
    }
 
    public function comments()
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at', 'asc');
    }
 
    /* ── Helpers ────────────────────────────────── */
 
    /**
     * Kiểm tra task đã quá hạn chưa
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'done' && now()->gt($this->deadline);
    }
}
