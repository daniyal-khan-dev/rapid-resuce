<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'email', 'status', 'added_by', 'updated_by'];
}
