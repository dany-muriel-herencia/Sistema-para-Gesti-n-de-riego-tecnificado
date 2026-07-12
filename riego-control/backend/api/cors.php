<?php
// backend/api/cors.php

function handleCors(): void
{
    // Permitir desde cualquier origen (en desarrollo)
    $allowed_origins = [
        'http://localhost:8000',
        'http://localhost:8080',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080',
        'http://localhost:3000',
        'http://127.0.0.1:3000'
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins) || strpos($origin, 'localhost') !== false) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
    } else {
        header('Access-Control-Allow-Origin: *');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Max-Age: 86400');

    // Manejar solicitudes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}