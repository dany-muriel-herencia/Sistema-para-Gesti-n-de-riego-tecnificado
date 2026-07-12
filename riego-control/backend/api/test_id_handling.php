<?php

header('Content-Type: application/json');

// Simular datos de parcelas con IDs numéricos
$parcelas = [
    ['id' => 1, 'nombre' => 'Parcela 1', 'cultivo' => 'Olivo'],
    ['id' => 2, 'nombre' => 'Parcela 2', 'cultivo' => 'Vid'],
    ['id' => 3, 'nombre' => 'Parcela 3', 'cultivo' => 'Aji']
];

// Simular datos de hidrantes con IDs numéricos
$hidrantes = [
    ['id' => 1, 'nombre' => 'Hidrante Norte', 'disponible' => true],
    ['id' => 2, 'nombre' => 'Hidrante Sur', 'disponible' => true],
    ['id' => 3, 'nombre' => 'Hidrante Este', 'disponible' => false]
];

// Simular datos de turnos con IDs numéricos
$turnos = [
    ['id' => 1, 'parcela_id' => 1, 'hidrante_id' => 1, 'estado' => 'completado'],
    ['id' => 2, 'parcela_id' => 2, 'hidrante_id' => 1, 'estado' => 'pendiente'],
    ['id' => 3, 'parcela_id' => 3, 'hidrante_id' => 2, 'estado' => 'en espera']
];

// Verificar que los IDs sean numéricos
foreach ($parcelas as $parcela) {
    if (!is_int($parcela['id'])) {
        echo json_encode(['error' => 'ID de parcela no es numérico']);
        exit;
    }
}

foreach ($hidrantes as $hidrante) {
    if (!is_int($hidrante['id'])) {
        echo json_encode(['error' => 'ID de hidrante no es numérico']);
        exit;
    }
}

foreach ($turnos as $turno) {
    if (!is_int($turno['id']) || !is_int($turno['parcela_id']) || ($turno['hidrante_id'] !== null && !is_int($turno['hidrante_id']))) {
        echo json_encode(['error' => 'ID de turno no es numérico']);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Todos los IDs son numéricos y válidos',
    'parcelas' => $parcelas,
    'hidrantes' => $hidrantes,
    'turnos' => $turnos
]);