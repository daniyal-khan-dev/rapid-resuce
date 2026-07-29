<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = ['name', 'role', 'avatar', 'content', 'rating', 'status', 'added_by', 'updated_by'];
}
