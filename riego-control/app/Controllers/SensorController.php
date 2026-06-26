<?php

require_once __DIR__ . '/../Models/Sensor.php';
require_once __DIR__ . '/ParcelaController.php';

class SensorController
{
    public function lecturas(): array
    {
        $datos = ParcelaController::cargarDatosSimulados();
        $clima = $datos['clima'] ?? [];

        return array_map(
            fn (array $parcela): array => Sensor::fromParcela($parcela, $clima)->toArray(),
            $datos['parcelas'] ?? []
        );
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'fuente' => 'database/sensores_simulados.json',
            'sensores' => $this->lecturas(),
        ], JSON_PRETTY_PRINT);
    }
}
