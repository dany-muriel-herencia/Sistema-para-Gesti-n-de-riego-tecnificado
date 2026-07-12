<?php

require_once __DIR__ . '/../algorithms/ProductorConsumidor.php';
require_once __DIR__ . '/../algorithms/Monitor.php';
require_once __DIR__ . '/../monitor/Semaforo.php';
require_once __DIR__ . '/../monitor/Hidrant.php';
require_once __DIR__ . '/../../app/Models/Parcela.php';

class ConcurrencyTest
{
    public function testProductorConsumidor()
    {
        echo "Testing ProductorConsumidor...\n";

        $pc = new ProductorConsumidor();

        // Crear una parcela de prueba
        $parcela = new Parcela(1, 'Parcela Test', 100.0, 'olivo', 'seca');

        // Producir la parcela
        $pc->producir($parcela);

        // Verificar que se haya producido
        $eventos = $pc->eventos();
        if (count($eventos) > 0) {
            echo "✓ Evento registrado: " . $eventos[0] . "\n";
        }

        // Consumir la parcela
        $consumida = $pc->consumir();
        if ($consumida) {
            echo "✓ Parcela consumida correctamente\n";
        }

        echo "ProductorConsumidor test completado.\n\n";
    }

    public function testMonitor()
    {
        echo "Testing Monitor...\n";

        $semaforo = new Semaforo(2);
        $monitor = new Monitor($semaforo);

        // Crear objetos de prueba
        $parcela = new Parcela(1, 'Parcela Test', 100.0, 'olivo', 'seca');
        $hidrante = new Hidrant('H-01', 'Hidrante Test', true, 1);

        // Solicitar riego
        $resultado = $monitor->solicitarRiego($parcela, $hidrante);
        echo "✓ Solicitud de riego: " . $resultado . "\n";

        // Finalizar riego
        $monitor->finalizarRiego($parcela);
        echo "✓ Riego finalizado\n";

        echo "Monitor test completado.\n\n";
    }

    public function testSemaforo()
    {
        echo "Testing Semaforo...\n";

        $semaforo = new Semaforo(2);

        // Verificar disponibilidad inicial
        echo "✓ Recursos disponibles inicialmente: " . $semaforo->disponibles() . "\n";

        // Esperar (adquirir recurso)
        $resultado = $semaforo->wait();
        if ($resultado) {
            echo "✓ Recurso adquirido\n";
            echo "✓ Recursos disponibles después de adquirir: " . $semaforo->disponibles() . "\n";
        }

        // Liberar recurso
        $semaforo->signal();
        echo "✓ Recurso liberado\n";
        echo "✓ Recursos disponibles después de liberar: " . $semaforo->disponibles() . "\n";

        echo "Semaforo test completado.\n\n";
    }

    public function runAllTests()
    {
        echo "=== Iniciando Tests de Concurrencia ===\n\n";

        $this->testSemaforo();
        $this->testProductorConsumidor();
        $this->testMonitor();

        echo "=== Todos los tests completados ===\n";
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ConcurrencyTest();
    $test->runAllTests();
}