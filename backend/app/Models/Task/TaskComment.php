<?php

namespace App\Models\Task;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class TaskComment extends Model
{
    use HasFactory;
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'attachment_url',
    ];
 
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskCommentAttachment::class, 'comment_id');
    }
}
