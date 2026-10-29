<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'OPTIONS'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://127.0.0.1:8080')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-XSRF-TOKEN', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
