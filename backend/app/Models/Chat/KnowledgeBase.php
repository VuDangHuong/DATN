<?php
namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $table    = 'knowledge_base';
    protected $fillable = [
        'question', 'answer', 'category', 'embedding'
    ];
    protected $casts = [
        'embedding' => 'array',
    ];
    // Ẩn embedding khi trả JSON ra ngoài
    protected $hidden = ['embedding'];
}