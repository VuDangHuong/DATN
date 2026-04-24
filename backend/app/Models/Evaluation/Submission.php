<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Auth\User;
use App\Models\Group\Group;
class Submission extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    protected $fillable = [
        'assignment_id',
        'submitter_type',
        'group_id',
        'student_id',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'note',
        'is_late',
        'submitted_at',
        'status',
        'score',
        'feedback',
        'reviewed_by',
        'reviewed_at',
    ];
    protected $casts = [
        'is_late'      => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'file_size'    => 'integer',
        'score'        => 'decimal:2',
    ];
    // ── Relations ─────────────────────────────────────────
 
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
 
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
 
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    public function history(): HasMany
    {
        return $this->hasMany(SubmissionHistory::class)->latest('submitted_at');
    }
 
    // ── Helpers ───────────────────────────────────────────
 
    /**
     * Tên hiển thị của người/nhóm nộp
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã chấp nhận',
            self::STATUS_REJECTED => 'Đã từ chối',
            default               => $this->status,
        };
    }

    public function getSubmitterNameAttribute(): string
    {
        if ($this->submitter_type === 'group') {
            return $this->group?->name ?? 'Nhóm không xác định';
        }
        return $this->student?->name ?? 'Sinh viên không xác định';
    }
 
    /**
     * Dung lượng file dạng đọc được (VD: 2.5 MB)
     */
    public function getFileSizeReadableAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }
    public function isPending(): bool   { return $this->status === self::STATUS_PENDING; }
    public function isApproved(): bool  { return $this->status === self::STATUS_APPROVED; }
    public function isRejected(): bool  { return $this->status === self::STATUS_REJECTED; }

    public function getNotifyEmailsAttribute(): array
    {
        if ($this->submitter_type === 'group' && $this->group) {
            return $this->group->members->pluck('email')->filter()->values()->toArray();
        }
        return array_filter([$this->student?->email]);
    }
    /**
     * Kiểm tra bài nộp có trễ hạn không (tính realtime)
     */
    public function checkIsLate(): bool
    {
        return $this->submitted_at->gt($this->assignment->deadline);
    }
}
