<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;
use App\Models\Admin\Ambulance;
use App\Models\Driver\Driver;

class EmergencyRequest extends Model
{
    protected $table = 'emergency_requests';

    protected $fillable = [
        'rreb_id',
        'user_id',
        'hospital_name',
        'mobile_no',
        'email',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'type',
        'status',
        'ambulance_id',
        'driver_id',
        'notes',
        'dispatched_at',
        'completed_at',
        'hospital_lat',
        'hospital_lng',
        'accepted_lat',
        'accepted_lng',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
        'completed_at'  => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->rreb_id)) {
                $lastId = static::max('id') ?? 0;
                $model->rreb_id = 'rreb-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ambulance()
    {
        return $this->belongsTo(Ambulance::class, 'ambulance_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function rideChatMessages()
    {
        return $this->hasMany(\App\Models\Chat\RideChatMessage::class);
    }
}
