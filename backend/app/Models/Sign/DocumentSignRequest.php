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
        'forwarded_at',
        'signed_at',
    ];
 
    protected $casts = [
        'forwarded_at' => 'datetime',
        'signed_at'    => 'datetime',
    ];
 
    // ── Status constants ──────────────────────────────────
    const STATUS_PENDING              = 'pending';
    const STATUS_ADMIN_REVIEWING      = 'admin_reviewing';
    const STATUS_FORWARDED            = 'forwarded';
    const STATUS_LECTURER_REVIEWING   = 'lecturer_reviewing';
    const STATUS_SIGNED               = 'signed';
    const STATUS_REJECTED_BY_ADMIN    = 'rejected_by_admin';
    const STATUS_REJECTED_BY_LECTURER = 'rejected_by_lecturer';
    const STATUS_COMPLETED            = 'completed';
 
    // ── Status label accessor ─────────────────────────────
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING              => 'Chờ Admin duyệt',
            self::STATUS_ADMIN_REVIEWING      => 'Admin đang xem xét',
            self::STATUS_FORWARDED            => 'Đã chuyển GV',
            self::STATUS_LECTURER_REVIEWING   => 'GV đang xem xét',
            self::STATUS_SIGNED               => 'Đã ký số',
            self::STATUS_REJECTED_BY_ADMIN    => 'Admin từ chối',
            self::STATUS_REJECTED_BY_LECTURER => 'GV từ chối',
            self::STATUS_COMPLETED            => 'Hoàn thành',
            default                           => $this->status,
        };
    }
 
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
}