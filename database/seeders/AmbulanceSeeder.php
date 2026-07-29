<?php

namespace Database\Seeders;

use App\Models\Admin\Ambulance;
use Illuminate\Database\Seeder;

class AmbulanceSeeder extends Seeder
{
    public function run(): void
    {
        // Driver ID 1 assigned to the first ambulance (created by DriverSeeder)
        $ambulances = [
            [
                'vehicle_number'   => 'RR-AMB-001',
                'type'             => '2', // ALS
                'equipment_level'  => '2', // Advanced
                'status'           => '1',
                'driver_id'        => 1,
                'lat'              => null,
                'lng'              => null,
                'notes'            => 'Primary dispatch unit. Fully serviced March 2024.',
                'card_title'       => 'ALS Unit Alpha',
                'card_description' => 'Our flagship Advanced Life Support ambulance equipped for the most critical emergencies. Carries a full paramedic crew with cardiac, airway, and trauma intervention capability.',
                'card_image'       => 'als-alpha.png',
                'card_features'    => '12-lead ECG monitor, Defibrillator, Advanced airway kit, IV therapy, Portable ventilator',
                'card_rating'      => 4.9,
                'card_trips'       => 312,
            ],
            [
                'vehicle_number'   => 'RR-AMB-002',
                'type'             => '1', // BLS
                'equipment_level'  => '1', // Basic
                'status'           => '1', // Available
                'driver_id'        => null,
                'lat'              => null,
                'lng'              => null,
                'notes'            => 'Basic Life Support unit ideal for stable patient transfers.',
                'card_title'       => 'BLS Unit Bravo',
                'card_description' => 'Reliable Basic Life Support ambulance for non-critical patient transport and inter-facility transfers. Staffed by certified EMTs with essential emergency equipment.',
                'card_image'       => 'bls-bravo.png',
                'card_features'    => 'Oxygen system, AED, Spinal immobilisation, First-aid kit, Stretcher',
                'card_rating'      => 4.7,
                'card_trips'       => 528,
            ],
            [
                'vehicle_number'   => 'RR-AMB-003',
                'type'             => '3', // ICU
                'equipment_level'  => '2', // Advanced
                'status'           => '1', // Available
                'driver_id'        => null,
                'lat'              => null,
                'lng'              => null,
                'notes'            => 'Mobile ICU for critical care transport. Requires specialist nurse.',
                'card_title'       => 'ICU Mobile Unit Charlie',
                'card_description' => 'A fully equipped mobile intensive care unit designed to transport critically ill patients with continuous monitoring and life-support systems active throughout the journey.',
                'card_image'       => 'icu-charlie.png',
                'card_features'    => 'ICU ventilator, Syringe pump, Multi-parameter monitor, Blood gas analyser, Infusion pump',
                'card_rating'      => 5.0,
                'card_trips'       => 187,
            ],
            [
                'vehicle_number'   => 'RR-AMB-004',
                'type'             => '4', // Neonatal
                'equipment_level'  => '2', // Advanced
                'status'           => '1', // Available
                'driver_id'        => null,
                'lat'              => null,
                'lng'              => null,
                'notes'            => 'Neonatal specialist transport. Incubator calibrated weekly.',
                'card_title'       => 'Neonatal Unit Delta',
                'card_description' => 'Purpose-built neonatal transport ambulance with a self-contained incubator, paediatric ventilation, and a trained NICU nurse for the safest transfer of newborns.',
                'card_image'       => 'neonatal-delta.png',
                'card_features'    => 'Servo transport incubator, Neonatal ventilator, Pulse oximetry, IV micro-pump, Temperature control',
                'card_rating'      => 4.8,
                'card_trips'       => 94,
            ],
            [
                'vehicle_number'   => 'RR-AMB-005',
                'type'             => '1', // BLS
                'equipment_level'  => '1', // Basic
                'status'           => '3', // Maintenance
                'driver_id'        => null,
                'lat'              => null,
                'lng'              => null,
                'notes'            => 'Scheduled maintenance — brake system overhaul. Expected back: 3 days.',
                'card_title'       => 'BLS Unit Echo',
                'card_description' => 'High-mileage BLS unit undergoing scheduled maintenance. Will return to active service shortly with refreshed equipment and certification.',
                'card_image'       => 'bls-echo.png',
                'card_features'    => 'Oxygen system, AED, Fracture kit, Stretcher, Patient monitoring',
                'card_rating'      => 4.6,
                'card_trips'       => 741,
            ]
        ];

        foreach ($ambulances as $ambulance) {
            Ambulance::create(array_merge($ambulance, [
                'added_by' => 'superadmin',
            ]));
        }
    }
}
