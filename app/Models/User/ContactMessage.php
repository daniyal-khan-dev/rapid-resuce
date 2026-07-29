<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $table = 'contact_messages';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'admin_read',
        'is_resolved',
    ];

    protected $casts = [
        'admin_read'  => 'boolean',
        'is_resolved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(\App\Models\Admin\ContactReply::class)->orderBy('created_at');
    }
}
