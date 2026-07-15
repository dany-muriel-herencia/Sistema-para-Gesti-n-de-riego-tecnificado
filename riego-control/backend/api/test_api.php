<?php
// test_api.php - Prueba de conexión a la base de datos

header('Content-Type: application/json; charset=utf-8');

// Configurar CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Intentar cargar la conexión
try {
    require_once __DIR__ . '/../config/database.php';
    
    $db = Database::getConnection();
    
    // Probar consulta simple
    $stmt = $db->query("SELECT COUNT(*) as total FROM parcelas");
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ Conexión exitosa a la base de datos',
        'total_parcelas' => $result ? (int)$result['total'] : 0,
        'status' => 'online',
        'port' => 3307,
        'database' => 'sistema_riego'
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Error de base de datos: ' . $e->getMessage(),
        'status' => 'offline',
        'port' => 3307,
        'solution' => 'Verifica que MySQL esté corriendo en el puerto 3307 y que la base de datos "sistema_riego" existe'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ Error: ' . $e->getMessage(),
        'status' => 'error'
    ], JSON_PRETTY_PRINT);
}