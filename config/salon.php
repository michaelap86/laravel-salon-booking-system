<?php

return [
    'name' => 'Lumière Studio',
    'tagline' => 'Hair, nails & skin, beautifully done.',
    'address' => '214 Rosewood Avenue, New York, NY 10012',
    'phone' => '(212) 555-0148',
    'email' => 'hello@lumiere-studio.example',
    'instagram' => '@lumiere.studio',

    // Booking rules (editable in Admin > Settings, these are the defaults)
    'defaults' => [
        'slot_interval' => 15,        // minutes between bookable start times
        'booking_window_days' => 60,  // how far ahead clients can book
        'min_notice_hours' => 2,      // earliest bookable time from now
        'deposit_percent' => 20,
        'free_cancel_hours' => 24,
        'loyalty_dollars_per_point' => 10,
    ],

    'loyalty_tiers' => [
        ['name' => 'Bronze', 'min' => 0, 'perk' => 'Birthday treat'],
        ['name' => 'Silver', 'min' => 150, 'perk' => '5% off every visit'],
        ['name' => 'Gold', 'min' => 400, 'perk' => '10% off + priority booking'],
    ],

    // The demo accounts shown on the login page
    'demo_password' => env('DEMO_PASSWORD', 'demo1234'),
    'demo_accounts' => [
        ['role' => 'Admin', 'email' => 'admin@lumiere.demo', 'hint' => 'Owner: everything, settings and users'],
        ['role' => 'Manager', 'email' => 'manager@lumiere.demo', 'hint' => 'Runs the day: bookings, clients, services, reports'],
        ['role' => 'Stylist', 'email' => 'stylist@lumiere.demo', 'hint' => 'Sees only their own calendar and clients'],
        ['role' => 'Client', 'email' => 'client@lumiere.demo', 'hint' => 'The customer portal: bookings, rewards, reviews'],
    ],

    // Telegram bot (optional). Set TELEGRAM_TOKEN to turn it on; TELEGRAM_ADMIN_CHAT_ID gets a message for every bot booking
    'telegram' => [
        'username' => env('TELEGRAM_BOT_USERNAME'),
        'admin_chat' => env('TELEGRAM_ADMIN_CHAT_ID'),
    ],

    // Regenerate the fictional data on the first request of each day
    'demo_auto_refresh' => env('DEMO_AUTO_REFRESH', true),
];
