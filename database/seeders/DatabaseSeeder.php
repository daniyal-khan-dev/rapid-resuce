<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DriverSeeder::class,
            UserSeeder::class,
            BranchSeeder::class,
            ServiceSeeder::class,
            AmbulanceSeeder::class,
            TestimonialSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
