<?php

namespace App\Models\Communication;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
     protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];
 
    protected $appends = ['file_url', 'file_size_human', 'is_image'];
 
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
 
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
 
    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
 
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
 
    public function getFileSizeHumanAttribute(): string
    {
        $b = $this->file_size;
        if ($b < 1024) return $b . ' B';
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        if ($b < 1073741824) return round($b / 1048576, 1) . ' MB';
        return round($b / 1073741824, 1) . ' GB';
    }
 
    protected static function booted()
    {
        // Khi xóa attachment → xóa file vật lý
        static::deleting(function ($att) {
            if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                Storage::disk('public')->delete($att->file_path);
            }
        });
    }
}
