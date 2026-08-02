<?php

namespace Database\Seeders;

use App\Models\Admin\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'name'    => 'HQ — Rapid Rescue',
                'address' => 'Faisal Cantonment Karachi, Pakistan',
                'phone'   => '+92 3172959985',
                'email'   => 'info@daniyal-khan.com',
            ],
            [
                'name'    => 'Brooklyn Hub',
                'address' => '1 MetroTech Center, Brooklyn, NY 11201',
                'phone'   => '+17185550142',
                'email'   => 'brooklyn@rapidrescue.com',
            ],
            [
                'name'    => 'Queens Station',
                'address' => '120-55 Queens Boulevard, Kew Gardens, NY 11415',
                'phone'   => '+17185550287',
                'email'   => 'queens@rapidrescue.com',
            ],
            [
                'name'    => 'Bronx Response Centre',
                'address' => '851 Grand Concourse, Bronx, NY 10451',
                'phone'   => '+17185550365',
                'email'   => 'bronx@rapidrescue.com',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create(array_merge($branch, [
                'status'   => '1',
                'added_by' => 'superadmin',
            ]));
        }
    }
}
