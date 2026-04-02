<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class DocumentSignLog extends Model
{
    protected $fillable = ['request_id', 'actor_id', 'action', 'note', 'ip_address'];

    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
