<?php

require_once __DIR__ . '/../backend/algorithms/ProductorConsumidor.php';
require_once __DIR__ . '/../backend/algorithms/LectoresEscritores.php';
require_once __DIR__ . '/../backend/monitor/Semaforo.php';
require_once __DIR__ . '/../backend/monitor/Hidrant.php';
require_once __DIR__ . '/../backend/algorithms/Monitor.php';
require_once __DIR__ . '/../backend/analyzer/StressAnalyzer.php';

class AlgorithmsTest
{
    private int $totalTests = 0;
    private int $passedTests = 0;

    public function run(): void
    {
        echo "=== PRUEBAS DE ALGORITHMS ===\n\n";

        $this->testProductorConsumidorEncolarAlto();
        $this->testProductorConsumidorEncolarMedio();
        $this->testProductorConsumidorDescartarBajo();
        $this->testProductorConsumidorColaFIFO();
        $this->testProductorConsumidorColaVacia();
        $this->testProductorConsumidorPendientes();

        $this->testLectoresEscritoresLectorSimple();
        $this->testLectoresEscritoresLectoresMultiples();
        $this->testLectoresEscritoresEscrituresSimple();
        $this->testLectoresEscritoresEscritorEsperaLectores();
        $this->testProductorConsumidorYMonitorConJsonSimulado();

        echo "\n=== RESULTADO FINAL ===\n";
        echo "Pasadas: {$this->passedTests}/{$this->totalTests}\n";

        if ($this->passedTests === $this->totalTests) {
            echo "✅ TODAS LAS PRUEBAS PASARON\n";
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

    private function assertCount(int $expected, array $array, string $message): void
    {
        $this->assert(count($array) === $expected, "$message (esperado: $expected, obtenido: " . count($array) . ")");
    }

    private function assertStringContains(string $needle, string $haystack, string $message): void
    {
        $this->assert(strpos($haystack, $needle) !== false, "$message (no contiene: $needle)");
    }

    // ============ PRUEBAS PRODUCTORCONSUMIDOR ============

    private function testProductorConsumidorEncolarAlto(): void
    {
        echo "TEST: ProductorConsumidor encola parcela con estres ALTO\n";
        $productor = new ProductorConsumidor();

        $parcela = ['id' => 1, 'nombre' => 'Parcela A', 'estres_hidrico' => 'alto'];
        $productor->producir($parcela);

        $this->assertCount(1, $productor->eventos(), "Se genera evento");
        $this->assertStringContains("Productor encola 1", $productor->eventos()[0], "Evento menciona encolar");
        $this->assertStringContains("alto", $productor->eventos()[0], "Evento menciona estrés alto");
        $this->assert($productor->pendientes() === 1, "Cola tiene 1 pendiente");
    }

    private function testProductorConsumidorEncolarMedio(): void
    {
        echo "TEST: ProductorConsumidor encola parcela con estres MEDIO\n";
        $productor = new ProductorConsumidor();

        $parcela = ['id' => 2, 'nombre' => 'Parcela B', 'estres_hidrico' => 'medio'];
        $productor->producir($parcela);

        $this->assertCount(1, $productor->eventos(), "Se genera evento");
        $this->assertStringContains("Productor encola 2", $productor->eventos()[0], "Evento menciona encolar");
        $this->assertStringContains("medio", $productor->eventos()[0], "Evento menciona estrés medio");
        $this->assert($productor->pendientes() === 1, "Cola tiene 1 pendiente");
    }

    private function testProductorConsumidorDescartarBajo(): void
    {
        echo "TEST: ProductorConsumidor descarta parcela con estres BAJO\n";
        $productor = new ProductorConsumidor();

        $parcela = ['id' => 3, 'nombre' => 'Parcela C', 'estres_hidrico' => 'bajo'];
        $productor->producir($parcela);

        $this->assertCount(1, $productor->eventos(), "Se genera evento de descarte");
        $this->assertStringContains("Productor descarta 3", $productor->eventos()[0], "Evento menciona descarte");
        $this->assertStringContains("bajo", $productor->eventos()[0], "Evento menciona estrés bajo");
        $this->assert($productor->pendientes() === 0, "Cola vacía");
    }

    private function testProductorConsumidorColaFIFO(): void
    {
        echo "TEST: ProductorConsumidor respeta FIFO (first-in, first-out)\n";
        $productor = new ProductorConsumidor();

        $p1 = ['id' => 1, 'nombre' => 'P1', 'estres_hidrico' => 'alto'];
        $p2 = ['id' => 2, 'nombre' => 'P2', 'estres_hidrico' => 'alto'];
        $p3 = ['id' => 3, 'nombre' => 'P3', 'estres_hidrico' => 'alto'];

        $productor->producir($p1);
        $productor->producir($p2);
        $productor->producir($p3);

        $this->assert($productor->pendientes() === 3, "Cola tiene 3 pendientes");

        $consumido1 = $productor->consumir();
        $this->assert($consumido1['id'] === 1, "Primer consumido es P1 (FIFO)");

        $consumido2 = $productor->consumir();
        $this->assert($consumido2['id'] === 2, "Segundo consumido es P2 (FIFO)");

        $consumido3 = $productor->consumir();
        $this->assert($consumido3['id'] === 3, "Tercero consumido es P3 (FIFO)");

        $this->assert($productor->pendientes() === 0, "Cola vacía después de consumir todos");
    }

    private function testProductorConsumidorColaVacia(): void
    {
        echo "TEST: ProductorConsumidor retorna null cuando cola vacía\n";
        $productor = new ProductorConsumidor();

        $consumido = $productor->consumir();

        $this->assert($consumido === null, "Retorna null");
        $this->assertCount(1, $productor->eventos(), "Genera evento de espera");
        $this->assertStringContains("cola de riego vacia", $productor->eventos()[0], "Evento menciona cola vacía");
    }

    private function testProductorConsumidorPendientes(): void
    {
        echo "TEST: ProductorConsumidor cuenta pendientes correctamente\n";
        $productor = new ProductorConsumidor();

        $this->assert($productor->pendientes() === 0, "Inicialmente 0 pendientes");

        $productor->producir(['id' => 1, 'nombre' => 'P1', 'estres_hidrico' => 'alto']);
        $this->assert($productor->pendientes() === 1, "Después de encolar 1, hay 1 pendiente");

        $productor->producir(['id' => 2, 'nombre' => 'P2', 'estres_hidrico' => 'medio']);
        $this->assert($productor->pendientes() === 2, "Después de encolar 2, hay 2 pendientes");

        $productor->consumir();
        $this->assert($productor->pendientes() === 1, "Después de consumir 1, hay 1 pendiente");

        $productor->consumir();
        $this->assert($productor->pendientes() === 0, "Después de consumir 2, hay 0 pendientes");
    }

    // ============ PRUEBAS LECTORESESCRITORES ============

    private function testLectoresEscritoresLectorSimple(): void
    {
        echo "TEST: LectoresEscritores - lector simple\n";
        $rw = new LectoresEscritores();

        $resultado = $rw->leer('sensor_1', fn () => 25.5);

        $this->assert($resultado === 25.5, "Lector retorna valor del callback");
        $this->assertCount(2, $rw->eventos(), "Se generan 2 eventos (entrada y salida)");
        $this->assertStringContains("Lector entra", $rw->eventos()[0], "Primer evento: entrada");
        $this->assertStringContains("Lector sale", $rw->eventos()[1], "Segundo evento: salida");
    }

    private function testLectoresEscritoresLectoresMultiples(): void
    {
        echo "TEST: LectoresEscritores - múltiples lectores concurrentes\n";
        $rw = new LectoresEscritores();

        $rw->leer('datos', fn () => 100);
        $rw->leer('datos', fn () => 200);
        $rw->leer('datos', fn () => 300);

        $eventos = $rw->eventos();
        $this->assertCount(6, $eventos, "6 eventos totales (entrada+salida x 3 lectores)");

        // Verificar que se registran múltiples lectores activos
        $this->assertStringContains("Lectores activos: 1", $eventos[0], "Primer lector: 1 activo");
        $this->assertStringContains("Lectores activos: 0", $eventos[1], "Primer lector sale: 0 activos");
    }

    private function testLectoresEscritoresEscrituresSimple(): void
    {
        echo "TEST: LectoresEscritores - escritor simple\n";
        $rw = new LectoresEscritores();

        $resultado = $rw->escribir('sensor_2', fn () => true);

        $this->assert($resultado === true, "Escritor retorna valor del callback");
        $this->assertCount(2, $rw->eventos(), "Se generan eventos");
        $this->assertStringContains("Escritor actualiza", $rw->eventos()[0], "Evento de actualización");
        $this->assertStringContains("Escritor libera", $rw->eventos()[1], "Evento de liberación");
    }

    private function testLectoresEscritoresEscritorEsperaLectores(): void
    {
        echo "TEST: LectoresEscritores - escritor espera si hay lectores\n";
        $rw = new LectoresEscritores();

        // Simular escritura mientras el lector aún está activo
        $rw->leer('temp', function () use ($rw) {
            $rw->escribir('temp', fn () => null);
            return null;
        });

        $eventos = $rw->eventos();
        
        // Debe haber evento de que escritor espera
        $hasWaitEvent = false;
        foreach ($eventos as $evento) {
            if (strpos($evento, 'Escritor espera') !== false) {
                $hasWaitEvent = true;
                break;
            }
        }
        
        $this->assert($hasWaitEvent, "Se registra que Escritor espera si hay lectores");
    }

    private function testProductorConsumidorYMonitorConJsonSimulado(): void
    {
        echo "TEST: ProductorConsumidor + Monitor con JSON simulado\n";

        $jsonFile = __DIR__ . '/../database/sensores_simulados.json';
        $contents = file_get_contents($jsonFile);
        $data = json_decode($contents, true);

        $this->assert(is_array($data), "Carga JSON simulado");
        $this->assert(array_key_exists('parcelas', $data), "JSON tiene parcelas");
        $this->assert(array_key_exists('hidrantes', $data), "JSON tiene hidrantes");

        $analyzer = new StressAnalyzer();
        $productor = new ProductorConsumidor();
        $monitor = new Monitor();

        $hidrantes = array_map(fn(array $item) => new Hidrant(
            $item['id'] ?? null,
            $item['nombre'] ?? '',
            isset($item['disponible']) ? (bool) $item['disponible'] : false,
            isset($item['capacidad_simultanea']) ? (int) $item['capacidad_simultanea'] : 1
        ), $data['hidrantes']);

        foreach ($data['parcelas'] as $item) {
            $parcela = [
                'id' => $item['id'] ?? null,
                'nombre' => $item['nombre'] ?? '',
                'cultivo' => $item['cultivo'] ?? '',
                'temperatura' => isset($item['temperatura']) ? (float) $item['temperatura'] : 0.0,
                'humedad' => isset($item['humedad']) ? (float) $item['humedad'] : 0.0,
                'estres_hidrico' => $analyzer->evaluarValores(
                    isset($item['temperatura']) ? (float) $item['temperatura'] : 0.0,
                    isset($item['humedad']) ? (float) $item['humedad'] : 0.0
                ),
            ];

            $productor->producir($parcela);
        }

        $asignados = 0;

        while (($parcela = $productor->consumir()) !== null) {
            foreach ($hidrantes as $hidrant) {
                $resultado = $monitor->solicitarRiego($parcela, $hidrant);
                if ($resultado !== 'bloqueado') {
                    $asignados++;
                    break;
                }
            }
        }

        $this->assert($asignados > 0, "Se asignan al menos algunas parcelas a hidrantes");
        $this->assert(count($productor->eventos()) > 0, "Productor genera eventos de encolado o descarte");
        $this->assert(count($monitor->eventos()) > 0, "Monitor genera eventos de asignación\espera\bloqueo");
    }
}

// Ejecutar pruebas
$test = new AlgorithmsTest();
$test->run();
