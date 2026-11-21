<?php

return [
    'demo_mode' => env('ARCHIVE_DEMO_MODE', false),
    'max_upload_kb' => (int) env('ARCHIVE_MAX_UPLOAD_KB', 20480),
    'allowed_mimes' => ['pdf', 'docx', 'xlsx', 'doc', 'xls', 'png', 'jpg', 'jpeg', 'txt'],
    'mfa_ttl_minutes' => 15,
    'reminder_days' => [90, 60, 30],
    'disk' => 'archive',
];
