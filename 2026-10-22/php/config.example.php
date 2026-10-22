<?php
// Copy this file to config.local.php and set environment-specific values there.
return [
    'recipient' => getenv('INQUIRY_MAIL_TO') ?: 'CHANGE-ME@example.com',
    'from_email' => getenv('INQUIRY_FROM_EMAIL') ?: 'no-reply@example.com',
    'from_name' => getenv('INQUIRY_FROM_NAME') ?: 'Inquiry desk',
    'database' => getenv('INQUIRY_DB_PATH') ?: dirname(__DIR__) . '/storage/inquiries.sqlite',
    // Set a long random string (32+ characters) in the server environment or config.local.php.
    'admin_token' => getenv('INQUIRY_ADMIN_TOKEN') ?: 'CHANGE-THIS-TO-A-LONG-RANDOM-SECRET',
    'timezone' => getenv('INQUIRY_TIMEZONE') ?: 'Asia/Tokyo',
    'rate_limit' => 5,
    'rate_window_seconds' => 3600,
];
