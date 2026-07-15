<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        // 🔥 CONEXIÓN CON PUERTO 3307
        $host = '127.0.0.1';
        $port = 3306;  // ← PUERTO CORRECTO
        $dbname = 'sistema_riego';
        $username = 'root';
        $password = '';
        $charset = 'utf8mb4';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $dbname, $charset);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            self::$connection = new PDO($dsn, $username, $password, $options);
            return self::$connection;
        } catch (PDOException $e) {
            // Si falla con 127.0.0.1, intenta con localhost
            try {
                $dsn = sprintf('mysql:host=localhost;port=%d;dbname=%s;charset=%s', $port, $dbname, $charset);
                self::$connection = new PDO($dsn, $username, $password, $options);
                return self::$connection;
            } catch (PDOException $e) {
                error_log("Error de conexión a MySQL: " . $e->getMessage());
                throw new PDOException("No se pudo conectar a la base de datos en el puerto $port. Verifica que MySQL esté corriendo.");
            }
        }
    }
}