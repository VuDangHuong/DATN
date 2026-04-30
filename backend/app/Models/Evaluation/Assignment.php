<?php

namespace App\Models\Evaluation;

use App\Models\Academic\Classes;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Assignment extends Model
{
    const DOCUMENT_CATEGORIES = [
        'bao_cao_thuc_tap'  => 'Báo cáo thực tập',
        'nckh'              => 'Nghiên cứu khoa học',
        'do_an_tot_nghiep'  => 'Đồ án tốt nghiệp',
        'bao_cao_du_an'     => 'Báo cáo dự án',
        'khoa_luan'         => 'Khóa luận',
    ];
    protected $fillable = [
        'class_id',
        'created_by',
        'title',
        'description',
        'deadline',
        'allow_late',
        'submission_type',
        'max_file_size',
        'allowed_extensions',
        'is_active',
        'requires_signing',        // ← mới: đợt nộp này có cần ký số không
        'document_category',       // ← mới: bao_cao_thuc_tap | nckh | ...
        'document_category_label', // ← mới: label hiển thị
    ];
 
    protected $casts = [
        'deadline'            => 'datetime',
        'allow_late'          => 'boolean',
        'is_active'           => 'boolean',
        'allowed_extensions'  => 'array',
        'max_file_size'       => 'integer',
    ];
 
    // ── Relations ─────────────────────────────────────────
 
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
 
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
 
    // ── Accessors ─────────────────────────────────────────
 
    /**
     * Đã hết hạn nộp chưa
     */
    public function getIsExpiredAttribute(): bool
    {
        return now()->gt($this->deadline);
    }
 
    /**
     * Còn cho phép nộp không (kể cả trễ hạn)
     */
    public function getCanSubmitAttribute(): bool
    {
        if (!$this->is_active) return false;
        if ($this->is_expired && !$this->allow_late) return false;
        return true;
    }
 
    /**
     * Label loại nộp bài
     */
    public function getSubmissionTypeLabelAttribute(): string
    {
        return match ($this->submission_type) {
            'group'      => 'Theo nhóm',
            'individual' => 'Cá nhân',
            'both'       => 'Nhóm + Cá nhân',
            default      => $this->submission_type,
        };
    }
 
    // ── Helpers ───────────────────────────────────────────
 
    /**
     * Lấy bài nộp của nhóm cụ thể
     */
    public function getGroupSubmission(int $groupId): ?Submission
    {
        return $this->submissions()
            ->where('submitter_type', 'group')
            ->where('group_id', $groupId)
            ->first();
    }
 
    /**
     * Lấy bài nộp của sinh viên cụ thể
     */
    public function getStudentSubmission(int $studentId): ?Submission
    {
        return $this->submissions()
            ->where('submitter_type', 'individual')
            ->where('student_id', $studentId)
            ->first();
    }
 
    /**
     * Validate file extension có được phép không
     */
    public function isExtensionAllowed(string $extension): bool
    {
        if (empty($this->allowed_extensions)) return true;
        return in_array(strtolower($extension), $this->allowed_extensions);
    }
}
