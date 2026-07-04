<?php

require_once __DIR__ . '/../app/Controllers/RiegoController.php';
require_once __DIR__ . '/../backend/analyzer/StressAnalyzer.php';

$jsonPath = __DIR__ . '/datos/entrada_backend.json';

if (!is_file($jsonPath)) {
    fwrite(STDERR, "No se encontró el JSON de entrada: {$jsonPath}\n");
    exit(1);
}

$contents = file_get_contents($jsonPath);
$data = json_decode($contents, true);

if (!is_array($data)) {
    fwrite(STDERR, "El archivo JSON no pudo decodificarse correctamente.\n");
    exit(1);
}

$analyzer = new StressAnalyzer();
$controller = new RiegoController();
$resultado = $controller->planificarDesdeJsonPorBloques($jsonPath, 1);

echo "=== SIMULACION BACKEND ===\n";
echo "Fuente JSON: {$jsonPath}\n";
echo "Parcelas procesadas: " . count($data['parcelas'] ?? []) . "\n";
echo "Hidrantes disponibles: " . count($data['hidrantes'] ?? []) . "\n";
echo "\n";

foreach ($data['parcelas'] ?? [] as $parcela) {
    $temperatura = (float) ($parcela['temperatura'] ?? 0);
    $humedad = (float) ($parcela['humedad'] ?? 0);
    $estres = $analyzer->evaluarValores($temperatura, $humedad);

    echo "Parcela {$parcela['id']} ({$parcela['nombre']})\n";
    echo "  humedad={$humedad} temperatura={$temperatura} => estres={$estres}\n";

    if ($estres === 'alto' || $estres === 'medio') {
        echo "  decision=encolar para riego\n";
    } else {
        echo "  decision=descartar / sin prioridad\n";
    }
    echo "\n";
}

echo "=== RESULTADO DEL CONTROLADOR ===\n";
echo "Turnos generados: " . count($resultado['turnos']) . "\n";
echo "Bloques procesados: " . ($resultado['bloques'] ?? 0) . "\n";

echo "Eventos del backend:\n";
foreach ($resultado['eventos'] as $evento) {
    echo " - {$evento}\n";
}
