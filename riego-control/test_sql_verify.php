<?php
require_once __DIR__ . '/backend/config/database.php';
$db = Database::getConnection();

$stmt = $db->query("SELECT t.id, t.parcela_id, t.hidrante_id, h.estado as hidrante_estado FROM turnos_riego t LEFT JOIN hidrantes h ON t.hidrante_id = h.id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "--- VALIDACION DE TURNOS EN BASE DE DATOS ---\n";
foreach($rows as $row) {
    if (empty($row['hidrante_estado'])) {
        echo "Turno {$row['id']} tiene hidrante_id {$row['hidrante_id']} INVALIDO (no existe o fue borrado)\n";
    } else {
        echo "Turno {$row['id']} -> Parcela {$row['parcela_id']} -> Hidrante REAL {$row['hidrante_id']} ({$row['hidrante_estado']})\n";
    }
}
echo "Total de turnos creados: " . count($rows) . "\n";
