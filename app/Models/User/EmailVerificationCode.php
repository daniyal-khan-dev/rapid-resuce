<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class EmailVerificationCode extends Model
{
    protected $table = 'email_verification_codes';

    protected $fillable = [
        'email',
        'code',
        'expires_at',
        'resend_count',
        'resend_date',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'resend_date' => 'date',
    ];
}
