<?php

return [
    'enabled' => env('WHATSAPP_ENABLED', false),
    'provider' => env('WHATSAPP_PROVIDER', 'fonnte'),
    'base_url' => rtrim(env('WHATSAPP_BASE_URL', 'https://api.fonnte.com'), '/'),
    'token' => env('WHATSAPP_TOKEN'),
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),
    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '62'),
];
