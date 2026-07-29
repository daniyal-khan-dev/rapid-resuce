<?php

namespace Database\Seeders;

use App\Models\Admin\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name'    => 'Dr. Patricia Nguyen',
                'role'    => 'Emergency Physician, NYU Langone',
                'content' => 'Rapid Rescue has transformed how we coordinate pre-hospital care. The real-time driver tracking and live dispatch dashboard give our ER team precious minutes to prepare before the patient arrives. I have seen outcomes improve directly because of this platform.',
                'rating'  => 5,
            ],
            [
                'name'    => 'Marcus Thompson',
                'role'    => 'Patient Family Member',
                'content' => 'When my father collapsed at home, every second felt like an hour. Rapid Rescue had an ambulance at our door in under eight minutes. I could watch the unit moving toward us on the live map, which kept me calm enough to follow the operator\'s instructions. I am forever grateful.',
                'rating'  => 5,
            ],
            [
                'name'    => 'Aisha Okonkwo',
                'role'    => 'Head Nurse, Brooklyn Methodist Hospital',
                'content' => 'Inter-facility transfers used to be a logistical headache. Now our ward coordinators book through Rapid Rescue, track the ambulance in real time, and receive full patient handover documentation before the crew even arrives. It has saved our staff hours each week.',
                'rating'  => 5,
            ],
            [
                'name'    => 'Lieutenant Daniel Reyes',
                'role'    => 'Fire & Rescue Commander, FDNY',
                'content' => 'We have run joint operations with Rapid Rescue crews on multiple mass-casualty incidents. Their dispatch technology and communication channels integrate seamlessly with our command structure. Professional, well-equipped, and always on time.',
                'rating'  => 5,
            ],
            [
                'name'    => 'Priya Sharma',
                'role'    => 'New Mother, Queens',
                'content' => 'I was rushed to hospital in active labour with complications. The neonatal ambulance team arrived prepared, kept both my baby and me stable, and communicated with the hospital throughout the journey. The care was extraordinary — I could not have asked for more.',
                'rating'  => 5,
            ],
            [
                'name'    => 'Robert Fitzgerald',
                'role'    => 'Corporate Health & Safety Manager',
                'content' => 'We have contracted Rapid Rescue as our on-site medical response partner for large events. Their team is professional, the equipment is top-tier, and the admin dashboard lets us monitor response activity in real time. An essential service for any serious organisation.',
                'rating'  => 4,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create(array_merge($testimonial, [
                'status'   => '1',
                'added_by' => 'superadmin',
            ]));
        }
    }
}
