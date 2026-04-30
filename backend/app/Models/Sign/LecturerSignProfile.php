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
    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) return false;
        if (!$this->cert_expires_at) return true;
        return now()->lt($this->cert_expires_at);
    }
 
    /**
     * Sắp hết hạn trong 30 ngày không
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->cert_expires_at) return false;
        return now()->diffInDays($this->cert_expires_at) <= 30
            && now()->lt($this->cert_expires_at);
    }
 
    /**
     * Số ngày còn lại
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->cert_expires_at) return null;
        return max(0, (int) now()->diffInDays($this->cert_expires_at, false));
    }
 
    // ── Relations ─────────────────────────────────────────
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
