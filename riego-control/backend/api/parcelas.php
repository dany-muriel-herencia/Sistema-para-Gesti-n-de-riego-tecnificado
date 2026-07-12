<?php
// backend/api/parcelas.php

require_once __DIR__ . '/cors.php';
handleCors();

require_once __DIR__ . '/../../app/Controllers/ParcelaController.php';
require_once __DIR__ . '/../config/database.php';

function respond(array|bool|null $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

$controller = new ParcelaController();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$body = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        if ($id !== null && $id > 0) {
            $result = $controller->obtener($id);
            if ($result === null) {
                respond(['error' => 'Parcela no encontrada.', 'success' => false], 404);
                break;
            }
            respond(['success' => true, 'data' => $result]);
            break;
        }
        respond(['success' => true, 'parcelas' => $controller->listar()]);
        break;

    case 'POST':
        $result = $controller->crear($body);
        if (isset($result['error'])) {
            respond($result, 400);
        } else {
            respond(['success' => true, 'data' => $result], 201);
        }
        break;

    case 'PUT':
    case 'PATCH':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id de la parcela.', 'success' => false], 400);
            break;
        }
        $result = $controller->actualizar($id, $body);
        if (isset($result['error'])) {
            respond($result, 400);
        } else {
            respond(['success' => true, 'data' => $result]);
        }
        break;

    case 'DELETE':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id de la parcela.', 'success' => false], 400);
            break;
        }
        $result = $controller->eliminar($id);
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