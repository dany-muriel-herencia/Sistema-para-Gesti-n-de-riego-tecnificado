<?php
// Test script to verify API writes and DB saves

function runTest() {
    $apiUrl = 'http://localhost:8000/backend/api/parcelas.php';
    
    // 1. Create a new parcel
    echo "1. CREANDO PARCELA CON HUMEDAD 15 Y TEMP 38...\n";
    $postData = json_encode([
        'nombre' => 'Parcela Test Demo',
        'cultivo' => 'Uvas',
        'humedad' => 15.0,
        'temperatura' => 38.0
    ]);
    
    $opts = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n",
            "content" => $postData
        ]
    ];
    $context = stream_context_create($opts);
    $result = file_get_contents($apiUrl, false, $context);
    echo "Respuesta POST: " . $result . "\n";
    
    // Strip BOM
    $result = preg_replace('/^[\xef\xbb\xbf]+/', '', $result);
    $resDecoded = json_decode($result, true);
    $newParcelId = $resDecoded['data']['id'] ?? null;
    
    if (!$newParcelId) {
        echo "Error al obtener ID de la parcela creada.\n";
        return;
    }
    
    // 2. Query DB to verify
    echo "\n2. VERIFICANDO BD PARA LA NUEVA PARCELA (ID $newParcelId)...\n";
    require_once __DIR__ . '/backend/config/database.php';
    $db = Database::getConnection();
    
    $stmt = $db->query("SELECT p.nombre, p.cultivo, s.humedad, s.temperatura FROM parcelas p LEFT JOIN sensores s ON p.id = s.parcela_id WHERE p.id = $newParcelId ORDER BY s.id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
    
    // 3. Edit an existing parcel
    echo "\n3. EDITANDO LA PARCELA EXISTENTE (ID $newParcelId) -> HUMEDAD 20...\n";
    $putData = json_encode([
        'humedad' => 20.0,
        'temperatura' => 35.0
    ]);
    
    $opts2 = [
        "http" => [
            "method" => "PUT",
            "header" => "Content-Type: application/json\r\n",
            "content" => $putData
        ]
    ];
    $context2 = stream_context_create($opts2);
    $result2 = file_get_contents($apiUrl . "?id=" . $newParcelId, false, $context2);
    echo "Respuesta PUT: " . $result2 . "\n";
    
    // 4. Query DB to verify
    echo "\n4. VERIFICANDO BD DESPUES DEL PUT...\n";
    $stmt2 = $db->query("SELECT p.nombre, p.cultivo, s.humedad, s.temperatura FROM parcelas p LEFT JOIN sensores s ON p.id = s.parcela_id WHERE p.id = $newParcelId ORDER BY s.id DESC LIMIT 1");
    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    print_r($row2);
    
    // 5. Create a new hydrant
    echo "\n5. CREANDO NUEVO HIDRANTE...\n";
    $hidranteData = json_encode([
        'nombre' => 'H-Test-UI',
        'capacidad' => 5,
        'estado' => 'mantenimiento'
    ]);
    $opts3 = [
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/json\r\n",
            "content" => $hidranteData
        ]
    ];
    $context3 = stream_context_create($opts3);
    $result3 = file_get_contents('http://localhost:8000/backend/api/hidrantes.php', false, $context3);
    echo "Respuesta POST Hidrante: " . $result3 . "\n";
    
    $stmt3 = $db->query("SELECT * FROM hidrantes ORDER BY id DESC LIMIT 1");
    $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
    print_r($row3);
}

runTest();
