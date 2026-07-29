<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
        'ip_address',
        'browser',
        'platform',
        'device',
        'is_mobile',
    ];
}
