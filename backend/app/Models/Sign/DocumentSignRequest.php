<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use App\Models\Academic\Classes;
use App\Models\Evaluation\Submission;
class DocumentSignRequest extends Model
{
    protected $fillable = [
        'submission_id',
        'requester_id',
        'class_id',
        'lecturer_id',
        'document_type',            // file_type từ submission: pdf/docx/zip
        'document_category',        // bao_cao_thuc_tap | nckh | do_an_tot_nghiep...
        'document_category_label',  // "Báo cáo thực tập"
        'original_file',
        'signed_file',
        'sign_hash',
        'sign_certificate',
        'status',
        'reject_reason',
        'signed_at',
    ];
 
    protected $casts = [
        'signed_at'    => 'datetime',
    ];
 
    // ── Status constants ──────────────────────────────────
    const STATUS_PENDING              = 'pending';
    const STATUS_LECTURER_REVIEWING   = 'lecturer_reviewing';
    const STATUS_SIGNED               = 'signed';
    const STATUS_REJECTED           = 'rejected';
 
    // ── Status label accessor ─────────────────────────────
    const STATUS_LABELS = [
        self::STATUS_PENDING            => 'Chờ giảng viên ký',
        self::STATUS_LECTURER_REVIEWING => 'Đang xem xét',
        self::STATUS_SIGNED             => 'Đã ký hoàn tất',
        self::STATUS_REJECTED           => 'Đã từ chối',
    ];
 
    // ── Relations ─────────────────────────────────────────
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
 
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
 
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
 
    public function classModel()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
 
    public function logs()
    {
        return $this->hasMany(DocumentSignLog::class, 'request_id');
    }
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
 
    public function getIsTerminalAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_SIGNED, self::STATUS_REJECTED]);
    }
}