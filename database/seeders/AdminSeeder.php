<?php

namespace Database\Seeders;

use App\Models\Admin\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'name'      => 'Super Admin',
            'username'  => 'superadmin',
            'email'     => 'admin@rapidrescue.com',
            'password'  => Hash::make('Admin@1234'),
            'status'    => '1',
            'added_by'  => 'system',
        ]);
    }
}
