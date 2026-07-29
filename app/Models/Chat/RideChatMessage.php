<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideChatMessage extends Model
{
    protected $fillable = [
        'emergency_request_id',
        'sender_type',
        'sender_id',
        'sender_name',
        'message',
        'is_read_driver',
        'is_read_admin',
        'is_read_user',
    ];

    protected $casts = [
        'is_read_driver' => 'boolean',
        'is_read_admin'  => 'boolean',
        'is_read_user'   => 'boolean',
    ];

    public function emergencyRequest(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User\EmergencyRequest::class);
    }

    public static function chatStatus(string $reqStatus): string
    {
        if (in_array($reqStatus, ['6', '7'])) return 'closed';
        if (in_array($reqStatus, ['2', '3', '4', '5'])) return 'active';
        return 'locked';
    }
}
