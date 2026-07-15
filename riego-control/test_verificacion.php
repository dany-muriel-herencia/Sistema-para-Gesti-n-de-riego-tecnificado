<?php
// Script de verificacion de modelos y base de datos

require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/app/Models/Parcela.php';
require_once __DIR__ . '/app/Models/Hidrante.php';

echo "=== TEST 1: Verificar tablas existen ===\n";
$db = Database::getConnection();
$stmt = $db->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo "  tabla: " . $row[0] . "\n";
}

echo "\n=== TEST 2: Insertar parcela de prueba via Modelo ===\n";
$parcela = Parcela::fromArray(['nombre' => 'Parcela Test CLI', 'cultivo' => 'Olivo', 'area' => 2.5, 'estado' => 'activa']);
$ok = $parcela->save();
echo $ok ? "  Parcela insertada con ID: " . $parcela->getId() . "\n" : "  ERROR al insertar parcela\n";

echo "\n=== TEST 3: Insertar hidrante de prueba via Modelo ===\n";
$hidrante = Hidrante::fromArray(['nombre' => 'Hidrante Test CLI', 'capacidad' => 2, 'estado' => 'disponible']);
$ok2 = $hidrante->save();
echo $ok2 ? "  Hidrante insertado con ID: " . $hidrante->getId() . "\n" : "  ERROR al insertar hidrante\n";

echo "\n=== TEST 4: Leer todas las parcelas ===\n";
foreach (Parcela::all() as $p) {
    echo "  [" . $p->getId() . "] " . $p->getNombre() . " | " . $p->getCultivo() . " | estado: " . $p->getEstado() . "\n";
}

echo "\n=== TEST 5: Leer todos los hidrantes ===\n";
foreach (Hidrante::all() as $h) {
    echo "  [" . $h->getId() . "] " . $h->getNombre() . " | cap: " . $h->getCapacidad() . " | estado: " . $h->getEstado() . "\n";
}

echo "\n=== TEST 6: SUM capacidad hidrantes disponibles ===\n";
$stmt2 = $db->query("SELECT COALESCE(SUM(capacidad),0) AS total FROM hidrantes WHERE estado = 'disponible'");
$row = $stmt2->fetch();
echo "  SUM(capacidad) WHERE estado='disponible': " . $row['total'] . "\n";
echo "\n=== VERIFICACION COMPLETA ===\n";
