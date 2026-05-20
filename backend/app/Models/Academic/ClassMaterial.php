<?php

namespace App\Models\Academic;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassMaterial extends Model
{
    protected $fillable = [
        'class_id',
        'uploaded_by',
        'title',
        'description',
        'category',
        'copied_from',
    ];
 
    public const CATEGORIES = [
        'lecture'   => 'Slide bài giảng',
        'exercise'  => 'Bài tập',
        'reference' => 'Tham khảo',
        'exam'      => 'Đề thi',
        'other'     => 'Khác',
    ];
 
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
 
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
 
    public function files(): HasMany
    {
        return $this->hasMany(ClassMaterialFile::class, 'material_id')
                    ->orderBy('sort_order')
                    ->orderBy('id');
    }
 
    public function originalMaterial(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copied_from');
    }
 
    // ─── Accessors ────────────────────────
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
 
    public function getFileCountAttribute(): int
    {
        return $this->files()->count();
    }
 
    public function getTotalSizeAttribute(): int
    {
        return $this->files()->sum('file_size');
    }
}
