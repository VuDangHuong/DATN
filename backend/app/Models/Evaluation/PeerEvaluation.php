<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Auth\User;
use App\Models\Academic\Classes;
class PeerEvaluation extends Model
{
    protected $fillable = [
        'evaluator_id',
        'evaluated_id',
        'class_id',
        'score',
        'comment',
        'phase',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    // Phase constants
    const PHASE_MIDTERM = 'Giữa kỳ';
    const PHASE_FINAL   = 'Cuối kỳ';

    const PHASES = [
        self::PHASE_MIDTERM,
        self::PHASE_FINAL,
    ];

    // ==================== RELATIONS ====================

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evaluated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    // ==================== SCOPES ====================

    /**
     * Lọc theo giai đoạn đánh giá
     */
    public function scopeInPhase(Builder $query, string $phase): Builder
    {
        return $query->where('phase', $phase);
    }

    /**
     * Lọc theo lớp
     */
    public function scopeInClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Lấy tất cả đánh giá do một user thực hiện
     */
    public function scopeByEvaluator(Builder $query, int $evaluatorId): Builder
    {
        return $query->where('evaluator_id', $evaluatorId);
    }

    /**
     * Lấy tất cả đánh giá nhận được của một user
     */
    public function scopeForEvaluated(Builder $query, int $evaluatedId): Builder
    {
        return $query->where('evaluated_id', $evaluatedId);
    }

    // ==================== HELPERS ====================

    /**
     * Kiểm tra evaluator đã chấm evaluated trong phase + class này chưa
     * — dùng để chặn chấm trùng
     */
    public static function alreadyEvaluated(
        int $evaluatorId,
        int $evaluatedId,
        int $classId,
        string $phase
    ): bool {
        return self::where('evaluator_id', $evaluatorId)
            ->where('evaluated_id', $evaluatedId)
            ->where('class_id', $classId)
            ->where('phase', $phase)
            ->exists();
    }

    /**
     * Điểm trung bình nhận được của một user trong lớp + phase
     */
    public static function averageScore(
        int $evaluatedId,
        int $classId,
        ?string $phase = null
    ): float {
        return self::where('evaluated_id', $evaluatedId)
            ->where('class_id', $classId)
            ->when($phase, fn($q) => $q->where('phase', $phase))
            ->avg('score') ?? 0.0;
    }

    /**
     * Số lượt đã chấm của evaluator trong lớp + phase
     * — dùng để kiểm tra đã hoàn thành chấm chéo chưa
     */
    public static function countByEvaluator(
        int $evaluatorId,
        int $classId,
        ?string $phase = null
    ): int {
        return self::where('evaluator_id', $evaluatorId)
            ->where('class_id', $classId)
            ->when($phase, fn($q) => $q->where('phase', $phase))
            ->count();
    }
}
