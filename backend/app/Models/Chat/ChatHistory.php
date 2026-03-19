<?php
namespace App\Models\Chat;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Model;
class ChatHistory extends Model
{
    protected $table    = 'chat_history';
    protected $fillable = [
        'user_id','question','file_name','answer',
        'source_text','is_liked','is_answered',
        'type','star','mail_sent'
    ];
    protected $casts = [
        'source_text' => 'array',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}