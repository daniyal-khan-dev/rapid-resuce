<?php

namespace Database\Seeders;

use App\Models\Admin\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'icon'        => 'fas fa-ambulance',
                'title'       => 'Emergency Ambulance Dispatch',
                'description' => 'Immediate 24/7 ambulance dispatch for life-threatening emergencies. Our trained coordinators assess your situation and deploy the nearest available unit within minutes, ensuring critical care reaches you fast.',
            ],
            [
                'icon'        => 'fas fa-heartbeat',
                'title'       => 'Advanced Life Support (ALS)',
                'description' => 'Our ALS ambulances are equipped with cardiac monitors, defibrillators, IV therapy kits, and advanced airway management tools, staffed by paramedics certified to handle cardiac arrests, strokes, and severe trauma.',
            ],
            [
                'icon'        => 'fas fa-procedures',
                'title'       => 'Inter-Hospital Patient Transfer',
                'description' => 'Safe and medically supervised transfer of stable or critical patients between healthcare facilities. Our transfer team coordinates with both hospitals to ensure seamless handover and uninterrupted care throughout the journey.',
            ],
            [
                'icon'        => 'fas fa-baby',
                'title'       => 'Neonatal & Pediatric Transport',
                'description' => 'Specialized transport for newborns and children requiring critical care. Our neonatal ambulances carry incubators, pediatric ventilators, and are staffed by nurses trained in neonatal intensive care.',
            ],
            [
                'icon'        => 'fas fa-user-md',
                'title'       => 'Medical Escort Services',
                'description' => 'Qualified medical escorts accompany patients during non-emergency travel, long-distance transfers, or international repatriation. Services include continuous monitoring, medication administration, and full documentation.',
            ],
            [
                'icon'        => 'fas fa-map-marked-alt',
                'title'       => 'Live GPS Tracked Response',
                'description' => 'Every dispatch is tracked in real time via GPS. Patients and families can monitor the ambulance location on a live map, receive ETA updates, and communicate directly with the crew through our platform.',
            ],
        ];

        foreach ($services as $service) {
            Service::create(array_merge($service, [
                'status'    => '1',
                'added_by'  => 'superadmin',
            ]));
        }
    }
}
