<?php
require_once __DIR__ . '/backend/config/database.php';
$db = Database::getConnection();

// 1. Limpiar turnos_riego
$db->query("TRUNCATE TABLE turnos_riego");

// 2. Limpiar hidrantes y dejar solo 2 (id 1 y 2)
$db->query("DELETE FROM hidrantes");
$db->query("ALTER TABLE hidrantes AUTO_INCREMENT = 1");
$db->query("INSERT INTO hidrantes (id, nombre, capacidad, estado) VALUES (1, 'Hidrante 1', 2, 'disponible')");
$db->query("INSERT INTO hidrantes (id, nombre, capacidad, estado) VALUES (2, 'Hidrante 2', 2, 'disponible')");

// 3. Forzar estres hidrico en una parcela
$db->query("UPDATE sensores SET humedad = 10, temperatura = 40 WHERE parcela_id = 1");

echo "Configuracion de base de datos lista.\n";
