<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClassMaterialFile extends Model
{
    protected $fillable = [
        'material_id',
        'file_path',
        'file_name',
        'file_extension',
        'file_mime',
        'file_size',
        'sort_order',
        'download_count',
    ];
 
    protected $casts = [
        'file_size'      => 'integer',
        'sort_order'     => 'integer',
        'download_count' => 'integer',
    ];
 
    public function material(): BelongsTo
    {
        return $this->belongsTo(ClassMaterial::class, 'material_id');
    }
 
    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        try {
            return Storage::temporaryUrl($this->file_path, now()->addMinutes(30));
        } catch (\Exception $e) {
            return null;
        }
    }
 
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
    }
 
    // public function getIconAttribute(): string
    // {
    //     return match (strtolower($this->file_extension)) {
    //         'pdf'                       => '📄',
    //         'doc', 'docx'               => '📝',
    //         'xls', 'xlsx'               => '📊',
    //         'ppt', 'pptx'               => '📈',
    //         'zip', 'rar', '7z'          => '🗜️',
    //         'jpg', 'jpeg', 'png', 'gif' => '🖼️',
    //         'mp4', 'avi', 'mov'         => '🎥',
    //         'mp3', 'wav'                => '🎵',
    //         default                     => '📎',
    //     };
    // }
}
