<?php

namespace Database\Seeders;

use App\Models\Driver\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        Driver::create([
            'name'        => 'James Carter',
            'username'    => 'james_carter',
            'email'       => 'driver@rapidrescue.com',
            'phone'       => '+1-555-201-4477',
            'password'    => Hash::make('Driver@1234'),
            'license_no'  => 'DL-2024-NY-88521',
            'photo'       => 'default.jpg',
            'status'      => '1',
            'availability' => '1',
            'lat'         => 40.7128,
            'lng'         => -74.0060,
            'added_by'    => 'superadmin',
            'last_seen_at' => now(),
        ]);
    }
}
