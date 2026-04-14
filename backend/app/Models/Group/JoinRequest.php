<?php

namespace App\Models\Group;

use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoinRequest extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'group_id',
        'user_id',
        'message',
        'status',   // 'pending' | 'accepted' | 'rejected'
    ];
 
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
