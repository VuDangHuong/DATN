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
    protected $fillable = [
        'group_id',
        'submitter_id',
        'report_file',
        'source_code_link',
        'slide_file',
        'is_integrity_confirmed',
    ];
    protected $casts = [
        'is_integrity_confirmed' => 'boolean',
    ];
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    public function signRequests(): HasMany
    {
        return $this->hasMany(DocumentSignRequest::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * URL đầy đủ của file báo cáo
     */
    public function getReportFileUrlAttribute(): ?string
    {
        return $this->report_file
            ? Storage::url($this->report_file)
            : null;
    }

    /**
     * URL đầy đủ của slide
     */
    public function getSlideFileUrlAttribute(): ?string
    {
        return $this->slide_file
            ? Storage::url($this->slide_file)
            : null;
    }

    // ==================== HELPERS ====================

    /**
     * Lấy đường dẫn file theo loại ('report' | 'slide')
     */
    public function getFileByType(string $type): ?string
    {
        return match ($type) {
            'report' => $this->report_file,
            'slide'  => $this->slide_file,
            default  => null,
        };
    }

    /**
     * Kiểm tra submission có file loại này chưa
     */
    public function hasFile(string $type): bool
    {
        return !empty($this->getFileByType($type));
    }

    /**
     * Kiểm tra document_type có đang chờ ký không
     * (tránh tạo yêu cầu trùng)
     */
    public function hasPendingSignRequest(string $documentType): bool
    {
        return $this->signRequests()
            ->where('document_type', $documentType)
            ->whereNotIn('status', [
                DocumentSignRequest::STATUS_REJECTED_BY_ADMIN,
                DocumentSignRequest::STATUS_REJECTED_BY_LECTURER,
                DocumentSignRequest::STATUS_COMPLETED,
            ])
            ->exists();
    }
}
