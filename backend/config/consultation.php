<?php

return [
    // Used only when creating fictional local/test accounts, never sent to Flutter.
    'demo_password' => env('DEMO_PASSWORD'),
    'scan_limit' => (int) env('AUTOMATION_SCAN_LIMIT', 100),
    'reminder_hours' => (int) env('AUTOMATION_REMINDER_HOURS', 24),
];
