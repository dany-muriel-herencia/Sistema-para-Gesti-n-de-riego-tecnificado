<?php
// backend/api/riego.php

require_once __DIR__ . '/cors.php';
handleCors();

require_once __DIR__ . '/../../app/Controllers/RiegoController.php';
require_once __DIR__ . '/../config/database.php';

function respond(array|bool|null $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$controller = new RiegoController();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$body = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        if ($id !== null && $id > 0) {
            $result = $controller->obtenerTurno($id);
            if ($result === null) {
                respond(['error' => 'Turno no encontrado.', 'success' => false], 404);
                break;
            }
            respond(['success' => true, 'data' => $result]);
            break;
        }
        respond(['success' => true, 'turnos' => $controller->listarTurnos()]);
        break;

    case 'POST':
        $result = $controller->crearTurno($body);
        if (isset($result['error'])) {
            respond($result, 400);
        } else {
            respond(['success' => true, 'data' => $result], 201);
        }
        break;

    case 'PUT':
    case 'PATCH':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id del turno.', 'success' => false], 400);
            break;
        }
        $result = $controller->actualizarTurno($id, $body);
        if (isset($result['error'])) {
            respond($result, 400);
        } else {
            respond(['success' => true, 'data' => $result]);
        }
        break;

    case 'DELETE':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id del turno.', 'success' => false], 400);
            break;
        }
        $result = $controller->eliminarTurno($id);
        if (isset($result['error'])) {
            respond($result, 400);
        } else {
            respond(['success' => true]);
        }
        break;

    default:
        respond(['error' => 'Método no soportado.', 'success' => false], 405);
        break;
}