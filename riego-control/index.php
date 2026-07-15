<?php
// index.php - Punto de entrada principal

// Verificar si la solicitud es para la API
$path = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($path, '/backend/api/') === 0) {
    // Dejar que el servidor maneje las API directamente
    return false;
}

// Si no es API, redirigir al frontend
header('Location: frontend/index.html');
exit;
?>