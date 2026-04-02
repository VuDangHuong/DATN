<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
use App\Models\Academic\Classes;
use App\Models\Evaluation\Submission;
class DocumentSignRequest extends Model
{
    protected $fillable = [
        'submission_id', 'requester_id', 'class_id', 'lecturer_id',
        'document_type', 'original_file', 'signed_file',
        'sign_hash', 'sign_certificate', 'status',
        'reject_reason', 'forwarded_at', 'signed_at',
    ];

    protected $casts = [
        'forwarded_at' => 'datetime',
        'signed_at'    => 'datetime',
    ];

    // Status constants — tránh hardcode string khắp nơi
    const STATUS_PENDING              = 'pending';
    const STATUS_ADMIN_REVIEWING      = 'admin_reviewing';
    const STATUS_FORWARDED            = 'forwarded';
    const STATUS_LECTURER_REVIEWING   = 'lecturer_reviewing';
    const STATUS_SIGNED               = 'signed';
    const STATUS_REJECTED_BY_ADMIN    = 'rejected_by_admin';
    const STATUS_REJECTED_BY_LECTURER = 'rejected_by_lecturer';
    const STATUS_COMPLETED            = 'completed';

    public function submission()   { return $this->belongsTo(Submission::class); }
    public function requester()    { return $this->belongsTo(User::class, 'requester_id'); }
    public function lecturer()     { return $this->belongsTo(User::class, 'lecturer_id'); }
    public function classModel()   { return $this->belongsTo(Classes::class, 'class_id'); }
    public function logs()         { return $this->hasMany(DocumentSignLog::class, 'request_id'); }
}