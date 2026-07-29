<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\User\UserDetail;
use App\Models\User\MedicalCard;
use App\Models\User\ContactMessage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $table = 'users';

    protected $fillable = [
        'username',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function details()
    {
        return $this->hasOne(UserDetail::class, 'user_id');
    }

    public function medicalCard()
    {
        return $this->hasOne(MedicalCard::class, 'user_id');
    }

    public function contactMessages()
    {
        return $this->hasMany(ContactMessage::class, 'user_id');
    }
}
