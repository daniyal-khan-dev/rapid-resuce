<?php

namespace App\Models\Driver;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Driver extends Authenticatable
{
    use Notifiable;

    protected $table = 'drivers';

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'license_no',
        'photo',
        'status',
        'availability',
        'lat',
        'lng',
        'added_by',
        'updated_by',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

        public function emergencyRequests()
    {
        return $this->hasMany(\App\Models\User\EmergencyRequest::class, 'driver_id');
    }
}
