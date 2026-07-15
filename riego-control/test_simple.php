<?php
/**
 * test_simple.php - Prueba de conexión directa a MySQL en puerto 3307
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "     TEST DE CONEXIÓN A MYSQL (3307)\n";
echo "========================================\n\n";

// Configuración con puerto 3307
$host = '127.0.0.1';
$port = 3307;  // ← PUERTO 3307
$dbname = 'sistema_riego';
$username = 'root';
$password = '';

try {
    echo "📡 Conectando a MySQL...\n";
    echo "   Host: $host\n";
    echo "   Puerto: $port\n";
    echo "   Base de datos: $dbname\n";
    echo "   Usuario: $username\n";
    echo "   Contraseña: " . (empty($password) ? "(vacía)" : "***") . "\n\n";
    
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ ¡CONEXIÓN EXITOSA!\n\n";
    
    // Verificar tablas
    echo "📊 Verificando tablas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Tablas encontradas: " . implode(", ", $tables) . "\n\n";
    
    // Contar parcelas
    echo "📊 Contando parcelas...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM parcelas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Total parcelas: " . $result['total'] . "\n\n";
    
    echo "\n🎉 ¡TODO FUNCIONA CORRECTAMENTE!\n";
    echo "========================================\n";
    echo "✅ Conexión exitosa en puerto 3307\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE BASE DE DATOS:\n";
    echo "   " . $e->getMessage() . "\n\n";
    
    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "   1. Verifica que MySQL está corriendo en el puerto 3307\n";
    echo "   2. Verifica que la base de datos 'sistema_riego' existe\n";
    echo "   3. Ejecuta: mysql -u root -P 3307 -e 'SHOW DATABASES;'\n";
    echo "   4. Si usas XAMPP, verifica que MySQL esté en el puerto 3307\n";
    echo "========================================\n";
}