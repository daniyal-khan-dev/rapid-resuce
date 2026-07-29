<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class UserDetail extends Model
{
    use HasFactory;
    protected $table = 'user_details';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'consumer_no',
        'email',
        'phone',
        'address',
        'date_of_birth',
        'profile_picture',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'     => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
