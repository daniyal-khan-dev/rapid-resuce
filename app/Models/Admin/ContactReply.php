<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ContactReply extends Model
{
    protected $fillable = [
        'contact_message_id',
        'sender_type',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function contactMessage()
    {
        return $this->belongsTo(\App\Models\User\ContactMessage::class);
    }
}
