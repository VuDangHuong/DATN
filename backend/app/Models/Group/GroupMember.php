<?php

namespace App\Models\Group;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Auth\User;
class GroupMember extends Model
{
    protected $fillable = ['group_id', 'user_id', 'role', 'joined_at'];

    protected $casts = ['joined_at' => 'datetime'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo   // ← relation này phải có
    {
        return $this->belongsTo(User::class);
    }

    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }
}
