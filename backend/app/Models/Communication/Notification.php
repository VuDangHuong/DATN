<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Auth\User;
class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'link',
        'is_read',
        'type',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Quan hệ: Notification thuộc về User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
