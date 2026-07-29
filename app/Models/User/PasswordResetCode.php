<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'resend_count',
        'resend_date',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'resend_date' => 'datetime',
    ];
}
