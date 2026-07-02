<?php

require_once __DIR__ . '/../../app/Controllers/RiegoController.php';

function respond(array|bool|null $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
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
                respond(['error' => 'Turno no encontrado.'], 404);
                break;
            }
            respond($result);
            break;
        }
        respond(['turnos' => $controller->listarTurnos()]);
        break;

    case 'POST':
        respond($controller->crearTurno($body), 201);
        break;

    case 'PUT':
    case 'PATCH':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id del turno.'], 400);
            break;
        }
        respond($controller->actualizarTurno($id, $body));
        break;

    case 'DELETE':
        if ($id === null || $id <= 0) {
            respond(['error' => 'Debe proporcionar el id del turno.'], 400);
            break;
        }
        respond($controller->eliminarTurno($id));
        break;

    default:
        respond(['error' => 'Método no soportado.'], 405);
        break;
}
