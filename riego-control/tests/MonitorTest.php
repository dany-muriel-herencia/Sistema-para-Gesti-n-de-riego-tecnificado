<?php

require_once __DIR__ . '/../backend/monitor/Semaforo.php';
require_once __DIR__ . '/../backend/monitor/Hidrant.php';
require_once __DIR__ . '/../backend/algorithms/Monitor.php';

class MonitorTest
{
    private int $totalTests = 0;
    private int $passedTests = 0;

    public function run(): void
    {
        echo "=== PRUEBAS DE MONITOR ===\n\n";

        $this->testMonitorNoDejaEntradasFueraCapacidad();
        $this->testMonitorEncolaWaitCuandoCapacidadAgotada();
        $this->testMonitorRespetaCapacidadPorHidrante();
        $this->testMonitorLiberaEspacioCorrecto();
        $this->testMonitorMultiplesHidrantesIndependientes();
        $this->testMonitorBloqueoHidrantNoDisponible();
        $this->testMonitorEventosRegistran();
        $this->testMonitorActivosYDispensables();

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
        $this->assert(strpos($haystack, $needle) !== false, "$message (no contiene: \"$needle\")");
    }

    // ============ PRUEBAS MONITOR ============

    private function testMonitorNoDejaEntradasFueraCapacidad(): void
    {
        echo "TEST: Monitor no deja entrar más parcelas que capacidad del hidrante\n";
        $monitor = new Monitor();

        // Hidrante con capacidad para 2 riegos simultáneos
        $hidrante = new Hidrant('H1', 'Hidrante Principal', true, 2);
        $p1 = ['id' => 1, 'nombre' => 'P1'];
        $p2 = ['id' => 2, 'nombre' => 'P2'];
        $p3 = ['id' => 3, 'nombre' => 'P3'];

        $result1 = $monitor->solicitarRiego($p1, $hidrante);
        $this->assert($result1 === 'activo', "Primera parcela entra (resultado: $result1)");

        $result2 = $monitor->solicitarRiego($p2, $hidrante);
        $this->assert($result2 === 'activo', "Segunda parcela entra (resultado: $result2)");

        $result3 = $monitor->solicitarRiego($p3, $hidrante);
        $this->assert($result3 === 'en espera', "Tercera parcela espera (resultado: $result3, capacidad agotada)");
    }

    private function testMonitorEncolaWaitCuandoCapacidadAgotada(): void
    {
        echo "TEST: Monitor retorna 'en espera' cuando semáforo wait() falla\n";
        $monitor = new Monitor();

        $hidrante = new Hidrant('H2', 'Hidrante Secundario', true, 1);
        $p1 = ['id' => 10, 'nombre' => 'Parcela 10'];
        $p2 = ['id' => 11, 'nombre' => 'Parcela 11'];

        $monitor->solicitarRiego($p1, $hidrante);

        $result = $monitor->solicitarRiego($p2, $hidrante);

        $this->assert($result === 'en espera', "Segunda solicitud retorna 'en espera'");

        $eventos = $monitor->eventos();
        $tieneEsperaEvent = false;
        foreach ($eventos as $evento) {
            if (strpos($evento, 'hace esperar') !== false) {
                $tieneEsperaEvent = true;
                break;
            }
        }
        $this->assert($tieneEsperaEvent, "Evento registra espera por falta de espacio");
    }

    private function testMonitorRespetaCapacidadPorHidrante(): void
    {
        echo "TEST: Monitor respeta capacidad diferente por cada hidrante\n";
        $monitor = new Monitor();

        $h1 = new Hidrant('H1', 'Hidrante 1', true, 3);  // Capacidad 3
        $h2 = new Hidrant('H2', 'Hidrante 2', true, 1);  // Capacidad 1

        // Llenar H1 (3 parcelas)
        $r1 = $monitor->solicitarRiego(['id' => 1], $h1);
        $r2 = $monitor->solicitarRiego(['id' => 2], $h1);
        $r3 = $monitor->solicitarRiego(['id' => 3], $h1);
        $r4 = $monitor->solicitarRiego(['id' => 4], $h1);

        $this->assert($r1 === 'activo' && $r2 === 'activo' && $r3 === 'activo', "H1 permite 3 activas");
        $this->assert($r4 === 'en espera', "H1 rechaza 4ª (espera)");

        // H2 debe permitir solo 1
        $s1 = $monitor->solicitarRiego(['id' => 20], $h2);
        $s2 = $monitor->solicitarRiego(['id' => 21], $h2);

        $this->assert($s1 === 'activo', "H2 permite 1ª");
        $this->assert($s2 === 'en espera', "H2 rechaza 2ª (espera)");
    }

    private function testMonitorLiberaEspacioCorrecto(): void
    {
        echo "TEST: Monitor libera espacio correctamente al finalizar riego\n";
        $monitor = new Monitor();

        $hidrante = new Hidrant('H3', 'Hidrante Test', true, 2);
        $p1 = ['id' => 1, 'nombre' => 'P1'];
        $p2 = ['id' => 2, 'nombre' => 'P2'];
        $p3 = ['id' => 3, 'nombre' => 'P3'];

        $monitor->solicitarRiego($p1, $hidrante);
        $monitor->solicitarRiego($p2, $hidrante);

        $result3Before = $monitor->solicitarRiego($p3, $hidrante);
        $this->assert($result3Before === 'en espera', "P3 en espera (capacidad llena)");

        // Liberar P1
        $monitor->finalizarRiego($p1);

        // Ahora P3 debe poder entrar
        $result3After = $monitor->solicitarRiego($p3, $hidrante);
        $this->assert($result3After === 'activo', "P3 entra después de liberar P1");
    }

    private function testMonitorMultiplesHidrantesIndependientes(): void
    {
        echo "TEST: Monitor maneja múltiples hidrantes con semáforos independientes\n";
        $monitor = new Monitor();

        $h1 = new Hidrant('H1', 'Hidrante 1', true, 1);
        $h2 = new Hidrant('H2', 'Hidrante 2', true, 1);

        $r1h1 = $monitor->solicitarRiego(['id' => 1], $h1);
        $r1h2 = $monitor->solicitarRiego(['id' => 2], $h2);

        $this->assert($r1h1 === 'activo' && $r1h2 === 'activo', "H1 y H2 activas simultáneamente");

        $r2h1 = $monitor->solicitarRiego(['id' => 3], $h1);
        $r2h2 = $monitor->solicitarRiego(['id' => 4], $h2);

        $this->assert($r2h1 === 'en espera' && $r2h2 === 'en espera', "Ambos hidrantes respetan capacidades");

        $eventos = $monitor->eventos();
        $tieneH1Event = false;
        $tieneH2Event = false;
        foreach ($eventos as $evento) {
            if (strpos($evento, 'H1') !== false) $tieneH1Event = true;
            if (strpos($evento, 'H2') !== false) $tieneH2Event = true;
        }
        $this->assert($tieneH1Event && $tieneH2Event, "Eventos mencionen ambos hidrantes");
    }

    private function testMonitorBloqueoHidrantNoDisponible(): void
    {
        echo "TEST: Monitor bloquea si hidrante no disponible\n";
        $monitor = new Monitor();

        $hidrantOff = new Hidrant('H_OFF', 'Hidrante Desactivado', false, 5);
        $parcela = ['id' => 99, 'nombre' => 'Parcela Test'];

        $result = $monitor->solicitarRiego($parcela, $hidrantOff);

        $this->assert($result === 'bloqueado', "Retorna 'bloqueado' si hidrante no disponible");

        $eventos = $monitor->eventos();
        $this->assert(count($eventos) > 0, "Se genera evento");
        $this->assertStringContains("bloquea", $eventos[0], "Evento menciona bloqueo");
    }

    private function testMonitorEventosRegistran(): void
    {
        echo "TEST: Monitor registra eventos correctamente\n";
        $monitor = new Monitor();

        $hidrante = new Hidrant('H_EVENT', 'Hidrante Test', true, 1);
        $parcela = ['id' => 50, 'nombre' => 'P_Evento'];

        $monitor->solicitarRiego($parcela, $hidrante);
        $monitor->finalizarRiego($parcela);

        $eventos = $monitor->eventos();

        $this->assertCount(2, $eventos, "Se generan 2 eventos (solicitud + liberación)");
        $this->assertStringContains("concede riego", $eventos[0], "Primer evento: concesión");
        $this->assertStringContains("libera", $eventos[1], "Segundo evento: liberación");
    }

    private function testMonitorActivosYDispensables(): void
    {
        echo "TEST: Monitor registra parcelas activas y disponibles correctamente\n";
        $monitor = new Monitor();

        $hidrante = new Hidrant('H_ACTIVE', 'Test', true, 3);
        $p1 = ['id' => 'P1'];
        $p2 = ['id' => 'P2'];
        $p3 = ['id' => 'P3'];

        $monitor->solicitarRiego($p1, $hidrante);
        $monitor->solicitarRiego($p2, $hidrante);
        $monitor->solicitarRiego($p3, $hidrante);

        $activos = $monitor->activos();
        $this->assertCount(3, $activos, "Monitor registra 3 activas");
        $this->assert(in_array('P1', $activos, true), "P1 en activos");
        $this->assert(in_array('P2', $activos, true), "P2 en activos");
        $this->assert(in_array('P3', $activos, true), "P3 en activos");

        $monitor->finalizarRiego($p1);
        $activos = $monitor->activos();
        $this->assertCount(2, $activos, "Quedan 2 activas después de liberar P1");
        $this->assert(!in_array('P1', $activos, true), "P1 ya no está en activos");
    }
}

// Ejecutar pruebas
$test = new MonitorTest();
$test->run();
