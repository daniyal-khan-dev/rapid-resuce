<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['username', 'ip_address', 'action'];

    public $timestamps = true;
}
