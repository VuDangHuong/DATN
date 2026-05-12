<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class LecturerSignProfile extends Model
{
    protected $fillable = [
        'lecturer_id',
        'public_key',
        'certificate_serial',
        'certificate_meta',
        'cert_expires_at',
        'is_active',
    ];
 
    protected $casts = [
        'certificate_meta' => 'array',
        'cert_expires_at'  => 'datetime',
        'is_active'        => 'boolean',
    ];
 
    // ── Accessors ─────────────────────────────────────────
 
    /**
     * Chứng chỉ còn hiệu lực không
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->cert_expires_at && $this->cert_expires_at->isPast();
    }
 
    /**
     * Sắp hết hạn trong 30 ngày không
     */
     public function getIsExpiringSoonAttribute(): bool
    {
        $days = $this->days_until_expired;
        return $days !== null && $days >= 0 && $days <= 30;
    }
 
    /**
     * Số ngày còn lại
     */
    public function getDaysUntilExpiredAttribute(): ?int
    {
        if (!$this->cert_expires_at) return null;
        return now()->startOfDay()->diffInDays($this->cert_expires_at, false);
    }
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }
    // ── Relations ─────────────────────────────────────────
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    // ── Scopes ─────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
 
    public function scopeForLecturer($query, int $lecturerId)
    {
        return $query->where('lecturer_id', $lecturerId);
    }
}
