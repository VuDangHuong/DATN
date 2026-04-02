<?php

namespace App\Models\Sign;

use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;
class LecturerSignProfile extends Model
{
    protected $fillable = [
        'lecturer_id', 'public_key', 'certificate_serial',
        'certificate_meta', 'cert_expires_at', 'is_active',
    ];
    protected $casts = [
        'certificate_meta' => 'array',
        'cert_expires_at'  => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function lecturer() { return $this->belongsTo(User::class, 'lecturer_id'); }
}
