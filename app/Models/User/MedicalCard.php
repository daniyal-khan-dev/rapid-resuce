<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class MedicalCard extends Model
{
    protected $table = 'medical_cards';

    protected $fillable = [
        'user_id',
        'blood_type',
        'medical_history',
        'allergies',
        'medications',
        'contact_name',
        'relation',
        'contact_phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
