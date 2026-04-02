<?php

namespace App\Models\Group;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Academic\Classes;
use App\Models\Auth\User;
use App\Models\Evaluation\Submission;
use App\Models\Sign\DocumentSignRequest;
use App\Models\Task\Task;
use App\Models\Evaluation\StudentGrade;
use App\Models\Evaluation\PeerEvaluation;
use App\Models\Communication\Message;
class Group extends Model
{
    protected $fillable = [
        'name',
        'class_id',
        'leader_id',
        'invitation_code',
        'is_locked',
    ];
    protected $casts = [
        'is_locked' => 'boolean',
    ];
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function topic(): HasOne
    {
        return $this->hasOne(Topic::class);
    }
    public function submission(): HasOne
    {
        //latesrofMany tránh lấy nhầm bản nộp cũ nếu nhóm nộp lại nhiều lần
        return $this->hasOne(Submission::class)->latestOfMany();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
    public function joinRequests(): HasMany
    {
        return $this->hasMany(JoinRequest::class);
    }

    public function recruitmentPosts(): HasMany
    {
        return $this->hasMany(RecruitmentPost::class);
    }

    public function peerEvaluations(): HasMany
    {
        return $this->hasMany(PeerEvaluation::class);
    }

    public function studentGrades(): HasMany
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function signRequests(): HasMany
    {
        return $this->hasMany(DocumentSignRequest::class, 'class_id', 'class_id');
    }
    /**
     * Chỉ lấy nhóm chưa bị khoá
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Lọc nhóm theo lớp
     */
    public function scopeInClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }
    /**
     * Kiểm tra user có trong nhóm không (bao gồm cả leader)
     */
    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Kiểm tra user có phải leader không
     */
    public function isLeader(int $userId): bool
    {
        return $this->leader_id === $userId;
    }

    /**
     * Số thành viên hiện tại
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Nhóm có đủ điều kiện nộp bài không
     * (có topic được duyệt + có ít nhất 1 file)
     */
    public function canSubmit(): bool
    {
        if ($this->is_locked) {
            return false;
        }

        $hasApprovedTopic = $this->topic()
            ->where('status', 'approved')
            ->exists();

        return $hasApprovedTopic;
    }

    /**
     * Nhóm đã nộp bài chưa
     */
    public function hasSubmitted(): bool
    {
        return $this->submissions()->exists();
    }

    /**
     * Tạo invitation code ngẫu nhiên nếu chưa có
     */
    public function generateInvitationCode(): string
    {
        $code = strtoupper(substr(md5($this->id . $this->name . now()), 0, 8));
        $this->update(['invitation_code' => $code]);
        return $code;
    }
}
