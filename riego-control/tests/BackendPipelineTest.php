<?php

require_once __DIR__ . '/../app/Controllers/RiegoController.php';

class BackendPipelineTest
{
    private int $totalTests = 0;
    private int $passedTests = 0;

    public function run(): void
    {
        echo "=== PRUEBAS BACKEND SIN FRONTEND ===\n\n";

        $this->testCargaJsonDesdeCarpetaDatos();
        $this->testProcesamientoPorBloques();

        echo "\n=== RESULTADO FINAL ===\n";
        echo "Pasadas: {$this->passedTests}/{$this->totalTests}\n";

        if ($this->passedTests === $this->totalTests) {
            echo "✅ PRUEBAS BACKEND PASARON\n";
        } else {
            echo "❌ ALGUNAS PRUEBAS FALLARON\n";
        }
    }

    private function assert(bool $condition, string $message): void
    {
        $this->totalTests++;
        if ($condition) {
            $this->passedTests++;
            echo "  ✓ $message\n";
        } else {
            echo "  ✗ $message\n";
        }
    }

    private function testCargaJsonDesdeCarpetaDatos(): void
    {
        echo "TEST: carga de JSON desde tests/datos\n";
        $jsonFile = __DIR__ . '/datos/entrada_backend.json';

        $this->assert(file_exists($jsonFile), 'El archivo JSON existe');

        $contents = file_get_contents($jsonFile);
        $data = json_decode($contents, true);

        $this->assert(is_array($data), 'El JSON se decodifica correctamente');
        $this->assert(array_key_exists('parcelas', $data), 'El JSON contiene parcelas');
        $this->assert(array_key_exists('hidrantes', $data), 'El JSON contiene hidrantes');
    }

    private function testProcesamientoPorBloques(): void
    {
        echo "TEST: procesamiento incremental por bloques\n";
        $controller = new RiegoController();
        $resultado = $controller->planificarDesdeJsonPorBloques(__DIR__ . '/datos/entrada_backend.json', 1);

        $this->assert(isset($resultado['turnos']), 'Se generan turnos de riego');
        $this->assert(is_array($resultado['turnos']), 'Los turnos son un arreglo');
        $this->assert(count($resultado['turnos']) > 0, 'Se generan turnos');
        $this->assert(isset($resultado['bloques']), 'Se reporta la cantidad de bloques procesados');
        $this->assert($resultado['bloques'] > 0, 'Se procesa al menos un bloque');
        $this->assert(isset($resultado['eventos']), 'Se registran eventos del backend');
    }
}

$test = new BackendPipelineTest();
$test->run();
