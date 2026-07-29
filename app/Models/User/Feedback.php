<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;
use App\Models\User\EmergencyRequest;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'request_id',
        'rating',
        'message',
        'name',
        'email',
        'driver_viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'driver_viewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function request()
    {
        return $this->belongsTo(EmergencyRequest::class, 'request_id');
    }
}
