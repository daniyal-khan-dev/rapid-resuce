<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = ['icon', 'title', 'description', 'status', 'added_by', 'updated_by'];
}
