<?php

namespace App\Models\Sign;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignProfileDeactivationRequest extends Model
{
    protected $fillable = [
        'lecturer_id',
        'profile_id',
        'reason',
        'status',
        'admin_id',
        'admin_note',
        'resolved_at',
    ];
 
    protected $casts = [
        'resolved_at' => 'datetime',
    ];
 
    // Quan hệ
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
 
    public function profile(): BelongsTo
    {
        return $this->belongsTo(LecturerSignProfile::class, 'profile_id');
    }
 
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
 
    // Scope: chỉ những request đang pending
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
