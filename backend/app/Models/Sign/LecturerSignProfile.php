<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class LecturerSignProfile extends Model
{
    protected $fillable = [
        'lecturer_id',
        'public_key',
        'encrypted_private_key',
        'encryption_salt',
        'encryption_iv',
        'signing_password_hash',
        'certificate_serial',
        'subject_cn',
        'issuer_cn',
        'algorithm',
        'cert_valid_from',
        'cert_expires_at',
        'certificate_meta',
        'is_active',
        'pending_deactivation',
    ];
     protected $hidden = [
        // Không expose private key qua API
        'encrypted_private_key',
        'encryption_salt',
        'encryption_iv',
        'signing_password_hash',
    ];
    protected $casts = [
        'certificate_meta' => 'array',
        'cert_valid_from'  => 'datetime',
        'cert_expires_at'  => 'datetime',
        'is_active'        => 'boolean',
        'pending_deactivation' => 'boolean',
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
    public function getHasPrivateKeyAttribute(): bool
    {
        return !empty($this->encrypted_private_key);
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

    // Thêm relationship
    public function deactivationRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SignProfileDeactivationRequest::class, 'profile_id');
    }
    
    // Yêu cầu pending hiện tại (nếu có)
    public function pendingDeactivationRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SignProfileDeactivationRequest::class, 'profile_id')
                    ->where('status', 'pending');
    }
    
    // Scope: chỉ profile có thể ký (active + KHÔNG pending deactivation)
    public function scopeCanSign($query)
    {
        return $query->where('is_active', true)
                    ->where('pending_deactivation', false);
    }
}
