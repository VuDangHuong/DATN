<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class DocumentSignLog extends Model
{
    const ACTION_LABELS = [
        'created'                => 'SV tạo yêu cầu',
        'admin_reviewing'        => 'Admin đang xem xét',  // ← tên mới
        'forwarded_to_lecturer'  => 'Admin chuyển GV',
        'lecturer_reviewing'     => 'GV đang xem xét',     // ← tên mới
        'signed'                 => 'GV đã ký số',          // ← tên mới
        'admin_rejected'         => 'Admin từ chối',
        'lecturer_rejected'      => 'GV từ chối',
        'completed'              => 'Admin phát hành',
    ];
    protected $fillable = [
        'request_id',
        'actor_id',
        'action',
        'note',
        'ip_address',
    ];
 
    // ── Action labels ─────────────────────────────────────
 
    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }
 
    // ── Relations ─────────────────────────────────────────
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
 
    public function request()
    {
        return $this->belongsTo(DocumentSignRequest::class, 'request_id');
    }
}
