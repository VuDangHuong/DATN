<?php

namespace App\Models\Evaluation;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Submissionhistory extends Model
{
    protected $table = 'submission_history';
 
    protected $fillable = [
        'submission_id',
        'submitted_by',
        'file_path',
        'file_name',
        'file_size',
        'note',
        'is_late',
        'submitted_at',
    ];
 
    protected $casts = [
        'is_late'      => 'boolean',
        'submitted_at' => 'datetime',
        'file_size'    => 'integer',
    ];
 
    // ── Relations ─────────────────────────────────────────
 
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
 
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
 
    // ── Helpers ───────────────────────────────────────────
 
    public function getFileSizeReadableAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }
}
