<?php

require_once __DIR__ . '/../Models/TurnoRiego.php';
require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/ParcelaController.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';
require_once __DIR__ . '/../../backend/algorithms/ProductorConsumidor.php';
require_once __DIR__ . '/../../backend/algorithms/Monitor.php';
require_once __DIR__ . '/../../backend/monitor/Hidrant.php';
require_once __DIR__ . '/../../backend/monitor/Semaforo.php';

class RiegoController
{
    public function planificar(): array
    {
        $datos = ParcelaController::cargarDatosSimulados();
        $analyzer = new StressAnalyzer();
        $productorConsumidor = new ProductorConsumidor();
        $hidrantes = array_map(
            fn (array $data): Hidrant => Hidrant::fromArray($data),
            $datos['hidrantes'] ?? []
        );
        if ($hidrantes === []) {
            $hidrantes[] = new Hidrant('H-00', 'Hidrante simulado', true, 1);
        }
        $capacidad = array_sum(array_map(
            fn (Hidrant $hidrante): int => $hidrante->disponible ? $hidrante->capacidadSimultanea : 0,
            $hidrantes
        ));
        $monitor = new Monitor(new Semaforo(max(1, $capacidad)));
        $turnos = [];
        $parcelas = [];

        foreach ($datos['parcelas'] ?? [] as $data) {
            $parcela = $analyzer->evaluar(Parcela::fromArray($data));
            $parcelas[] = $parcela;
            $productorConsumidor->producir($parcela);
        }

        $indiceHidrante = 0;
        while (($parcela = $productorConsumidor->consumir()) !== null) {
            $hidrante = $hidrantes[$indiceHidrante % count($hidrantes)];
            $estado = $monitor->solicitarRiego($parcela, $hidrante);
            $duracion = $parcela->estresHidrico === 'alto' ? 45 : 25;
            $turnos[] = (new TurnoRiego(
                'T-' . str_pad((string) (count($turnos) + 1), 2, '0', STR_PAD_LEFT),
                $parcela->id,
                $hidrante->id,
                $duracion,
                $estado
            ))->toArray();
            $indiceHidrante++;
        }

        return [
            'parcelas' => array_map(fn (Parcela $parcela): array => $parcela->toArray(), $parcelas),
            'turnos' => $turnos,
            'hidrantes' => $datos['hidrantes'] ?? [],
            'eventos' => array_merge($productorConsumidor->eventos(), $monitor->eventos()),
        ];
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->planificar(), JSON_PRETTY_PRINT);
    }
}
