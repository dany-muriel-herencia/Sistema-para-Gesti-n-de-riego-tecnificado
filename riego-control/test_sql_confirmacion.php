<?php
// Script de confirmacion SQL directa (Tarea 4)

require_once __DIR__ . '/backend/config/database.php';
$db = Database::getConnection();

echo "=== QUERY 1: Ultima parcela insertada ===\n";
$stmt = $db->query("SELECT id, nombre, cultivo, area, estado, created_at FROM parcelas ORDER BY id DESC LIMIT 3");
while ($row = $stmt->fetch()) {
    echo "  id={$row['id']} | nombre='{$row['nombre']}' | cultivo='{$row['cultivo']}' | area={$row['area']} | estado='{$row['estado']}' | created_at={$row['created_at']}\n";
}

echo "\n=== QUERY 2: Ultimo hidrante insertado ===\n";
$stmt2 = $db->query("SELECT id, nombre, capacidad, estado, created_at FROM hidrantes ORDER BY id DESC LIMIT 3");
while ($row2 = $stmt2->fetch()) {
    echo "  id={$row2['id']} | nombre='{$row2['nombre']}' | capacidad={$row2['capacidad']} | estado='{$row2['estado']}' | created_at={$row2['created_at']}\n";
}

echo "\n=== QUERY 3: SUM capacidad disponible (usada por el script Python) ===\n";
$stmt3 = $db->query("SELECT COALESCE(SUM(capacidad),0) AS total_capacidad, COUNT(*) AS total_hidrantes FROM hidrantes WHERE estado = 'disponible'");
$row3 = $stmt3->fetch();
echo "  total_capacidad={$row3['total_capacidad']} | hidrantes_disponibles={$row3['total_hidrantes']}\n";

echo "\n=== QUERY 4: Todas las parcelas activas (lo que lee el Productor Python) ===\n";
$stmt4 = $db->query("SELECT id, nombre FROM parcelas WHERE estado = 'activa' ORDER BY id");
while ($row4 = $stmt4->fetch()) {
    echo "  id={$row4['id']} | nombre='{$row4['nombre']}'\n";
}
echo "\n=== CONFIRMACION SQL COMPLETA ===\n";
