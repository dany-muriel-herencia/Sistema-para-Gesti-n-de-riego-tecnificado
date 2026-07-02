<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = self::loadConfig();
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['DB_HOST'], $config['DB_PORT'], $config['DB_DATABASE'], $config['DB_CHARSET']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$connection = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], $options);
        return self::$connection;
    }

    private static function loadConfig(): array
    {
        $defaults = [
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => 3306,
            'DB_DATABASE' => 'sistema_riego',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'DB_CHARSET' => 'utf8mb4',
        ];

        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) {
            return $defaults;
        }

        $contents = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($contents === false) {
            return $defaults;
        }

        foreach ($contents as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = array_map('trim', $parts);
            if ($key !== '' && array_key_exists($key, $defaults)) {
                $defaults[$key] = is_numeric($value) ? (int) $value : $value;
            }
        }

        return $defaults;
    }
}
