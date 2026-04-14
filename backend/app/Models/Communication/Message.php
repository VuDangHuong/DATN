<?php

namespace App\Models\Communication;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class Message extends Model
{
    protected $fillable = [
        'group_id',
        'user_id',
        'content',
    ];

    // ==================== RELATIONS ====================

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ==================== SCOPES ====================

    /**
     * Lấy tin nhắn của một nhóm, mới nhất trước
     */
    public function scopeInGroup(Builder $query, int $groupId): Builder
    {
        return $query->where('group_id', $groupId)->latest();
    }

    /**
     * Lấy tin nhắn sau một thời điểm — dùng cho realtime polling
     */
    public function scopeAfter(Builder $query, string $timestamp): Builder
    {
        return $query->where('created_at', '>', $timestamp);
    }

    // ==================== HELPERS ====================

    /**
     * Kiểm tra tin nhắn có phải của user này không
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }
}
