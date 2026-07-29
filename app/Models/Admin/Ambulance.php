<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Ambulance extends Model
{
    protected $table = 'ambulances';

    protected $fillable = [
        'vehicle_number',
        'type',
        'equipment_level',
        'status',
        'driver_id',
        'notes',
        'card_title',
        'card_description',
        'card_image',
        'card_features',
        'card_rating',
        'card_trips',
        'added_by',
        'updated_by',
    ];

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver\Driver::class, 'driver_id');
    }
}
