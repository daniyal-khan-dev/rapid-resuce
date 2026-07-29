<?php

namespace Database\Seeders;

use App\Models\User\User;
use App\Models\User\UserDetail;
use App\Models\User\MedicalCard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'username' => 'sarah_mitchell',
            'password' => Hash::make('User@1234'),
            'status'   => '1',
        ]);

        UserDetail::create([
            'user_id'          => $user->id,
            'first_name'       => 'Sarah',
            'last_name'        => 'Mitchell',
            'consumer_no'      => 'RR-2024-00001',
            'email'            => 'user@rapidrescue.com',
            'phone'            => '+1-555-318-9942',
            'address'          => '142 Maple Avenue, Brooklyn, New York, NY 11201',
            'date_of_birth'    => '1992-06-15',
            'profile_picture'  => 'default.jpg',
            'email_verified_at' => now(),
        ]);

        MedicalCard::create([
            'user_id'         => $user->id,
            'blood_type'      => 'O+',
            'medical_history' => 'Mild asthma diagnosed in 2015. No surgeries. Annual check-ups normal.',
            'allergies'       => 'Penicillin, Pollen',
            'medications'     => 'Salbutamol inhaler (as needed)',
            'contact_name'    => 'Robert Mitchell',
            'relation'        => 'Brother',
            'contact_phone'   => '+1-555-318-0011',
        ]);
    }
}
