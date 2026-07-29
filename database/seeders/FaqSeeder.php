<?php

namespace Database\Seeders;

use App\Models\Admin\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How quickly will an ambulance reach me after I submit a request?',
                'answer'   => 'Our target response time is 8 minutes or less within our primary service zones. Once you submit a request, our dispatch system immediately identifies the nearest available unit and routes it to your location. You will receive a live map view of the ambulance and an estimated arrival time within seconds of your booking being confirmed.',
            ],
            [
                'question' => 'What information do I need to provide when booking an ambulance?',
                'answer'   => 'You will need to provide your pickup location (searchable via the map), your mobile number, a contact email, and the type of emergency. If you have a registered medical card on your profile — including blood type, allergies, and current medications — our crew will have that information before they arrive, which significantly helps first responders.',
            ],
            [
                'question' => 'Can I track the ambulance after I have made a booking?',
                'answer'   => 'Yes. As soon as a driver accepts your request, a live map appears in your dashboard showing the ambulance\'s exact GPS position, updated in real time. You will also see the driver\'s name, vehicle number, and estimated time of arrival. You can message the driver or admin directly through the in-app chat while you wait.',
            ],
            [
                'question' => 'What types of ambulances are available?',
                'answer'   => 'We operate five categories of ambulance: Basic Life Support (BLS) for stable patient transport, Advanced Life Support (ALS) for critical emergencies, Mobile ICU for intensive care transfer, Neonatal Transport for newborns requiring specialist care, and Bariatric units for patients with specific physical requirements. The appropriate unit is assigned automatically based on the emergency type you select.',
            ],
            [
                'question' => 'Do I need to create an account to request an ambulance?',
                'answer'   => 'Yes, a verified account is required to place a booking. Registration takes under two minutes — you provide your name, email, and phone number, then verify your email address. Having an account allows us to pre-load your medical card, maintain your ride history, and provide faster service in future emergencies.',
            ],
            [
                'question' => 'Is my personal and medical information kept private?',
                'answer'   => 'Absolutely. All personal and medical data is encrypted at rest and in transit. Your medical card information is only accessible to the assigned ambulance crew and the admin team for the duration of an active ride. We never share your data with third parties, and you can update or delete your medical information at any time from your profile.',
            ],
            [
                'question' => 'What happens if I need to cancel a request?',
                'answer'   => 'You can cancel a pending request from your dashboard before a driver has been assigned. If a driver has already been dispatched, please use the in-app chat to notify them and call our emergency line to confirm the cancellation so we can redeploy the unit. Please only cancel if the situation is genuinely resolved — unnecessary cancellations delay response to real emergencies.',
            ],
            [
                'question' => 'How do I arrange a non-emergency inter-hospital transfer?',
                'answer'   => 'Log in and select "Scheduled Transfer" from the booking options. You will be asked to provide the origin facility, destination hospital, preferred date and time, and the patient\'s current condition level. Our coordination team will confirm availability and assign a suitable unit. For same-day transfers, please contact our operations centre directly.',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create(array_merge($faq, [
                'status'   => '1',
                'added_by' => 'superadmin',
            ]));
        }
    }
}
