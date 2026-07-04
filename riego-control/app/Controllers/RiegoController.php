<?php

require_once __DIR__ . '/../Models/TurnoRiego.php';
require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../Models/Sensor.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';
require_once __DIR__ . '/../../backend/algorithms/ProductorConsumidor.php';
require_once __DIR__ . '/../../backend/monitor/Hidrant.php';
require_once __DIR__ . '/../../backend/algorithms/Monitor.php';

class RiegoController
{
    public function listarTurnos(): array
    {
        return array_map(fn (TurnoRiego $turno) => $turno->toArray(), TurnoRiego::all());
    }

    public function obtenerTurno(int $id): ?array
    {
        $turno = TurnoRiego::find($id);
        return $turno ? $turno->toArray() : null;
    }

    public function crearTurno(array $data): array
    {
        $turno = TurnoRiego::fromArray([
            'parcela_id' => $data['parcela_id'] ?? 0,
            'hidrante_id' => $data['hidrante_id'] ?? null,
            'inicio' => $data['inicio'] ?? date('Y-m-d H:i:s'),
            'fin' => $data['fin'] ?? null,
            'estado' => $data['estado'] ?? 'pendiente',
        ]);

        if (!$turno->save()) {
            return ['error' => 'No se pudo crear el turno de riego.'];
        }

        return $turno->toArray();
    }

    public function actualizarTurno(int $id, array $data): array
    {
        $turno = TurnoRiego::find($id);
        if (!$turno) {
            return ['error' => 'Turno no encontrado.'];
        }

        $turno->setParcelaId(isset($data['parcela_id']) ? (int) $data['parcela_id'] : $turno->getParcelaId());
        $turno->setHidranteId(isset($data['hidrante_id']) ? (int) $data['hidrante_id'] : $turno->getHidranteId());
        $turno->setInicio($data['inicio'] ?? $turno->getInicio());
        $turno->setFin($data['fin'] ?? $turno->getFin());
        $turno->setEstado($data['estado'] ?? $turno->getEstado());

        if (!$turno->save()) {
            return ['error' => 'No se pudo actualizar el turno de riego.'];
        }

        return $turno->toArray();
    }

    public function eliminarTurno(int $id): array
    {
        $turno = TurnoRiego::find($id);
        if (!$turno) {
            return ['error' => 'Turno no encontrado.'];
        }

        if (!$turno->delete()) {
            return ['error' => 'No se pudo eliminar el turno de riego.'];
        }

        return ['success' => true];
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->listarTurnos(), JSON_PRETTY_PRINT);
    }

    private function loadSimulatedData(): array
    {
        $file = __DIR__ . '/../../database/sensores_simulados.json';
        if (!is_file($file)) {
            return ['clima' => [], 'parcelas' => [], 'hidrantes' => []];
        }

        $contents = file_get_contents($file);
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return ['clima' => [], 'parcelas' => [], 'hidrantes' => []];
        }

        return $data;
    }

    private function prepareParcelas(array $rawParcelas): array
    {
        $analyzer = new StressAnalyzer();

        return array_map(function (array $parcela) use ($analyzer) {
            $temperatura = isset($parcela['temperatura']) ? (float) $parcela['temperatura'] : 0.0;
            $humedad = isset($parcela['humedad']) ? (float) $parcela['humedad'] : 0.0;

            return [
                'id' => $parcela['id'] ?? null,
                'nombre' => $parcela['nombre'] ?? '',
                'cultivo' => $parcela['cultivo'] ?? '',
                'temperatura' => $temperatura,
                'humedad' => $humedad,
                'estres_hidrico' => $analyzer->evaluarValores($temperatura, $humedad),
            ];
        }, $rawParcelas);
    }

    public function planificar(): array
    {
        $data = $this->loadSimulatedData();
        return $this->planificarDesdeDatos($data);
    }

    public function planificarDesdeJsonPorBloques(string $jsonPath, int $tamanoBloque = 1): array
    {
        if (!is_file($jsonPath)) {
            return ['turnos' => [], 'hidrantes' => [], 'eventos' => [], 'bloques' => 0];
        }

        $contents = file_get_contents($jsonPath);
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return ['turnos' => [], 'hidrantes' => [], 'eventos' => [], 'bloques' => 0];
        }

        return $this->planificarDesdeDatos($data, $tamanoBloque);
    }

    private function planificarDesdeDatos(array $data, int $tamanoBloque = 1): array
    {
        $parcelas = $this->prepareParcelas($data['parcelas'] ?? []);
        $hidrantesRaw = $data['hidrantes'] ?? [];

        $productor = new ProductorConsumidor();
        $monitor = new Monitor();
        $hidrantes = [];
        $hidrantesObjects = [];

        foreach ($hidrantesRaw as $item) {
            $hidrantes[] = [
                'id' => $item['id'] ?? null,
                'nombre' => $item['nombre'] ?? '',
                'disponible' => isset($item['disponible']) ? (bool) $item['disponible'] : false,
                'capacidad_simultanea' => isset($item['capacidad_simultanea']) ? (int) $item['capacidad_simultanea'] : 1,
            ];

            $hidrantesObjects[] = new Hidrant(
                $item['id'] ?? null,
                $item['nombre'] ?? '',
                isset($item['disponible']) ? (bool) $item['disponible'] : false,
                isset($item['capacidad_simultanea']) ? (int) $item['capacidad_simultanea'] : 1
            );
        }

        foreach ($parcelas as $parcela) {
            $productor->producir($parcela);
        }

        $turnos = [];
        $bloques = 0;
        $parcelasProcesadas = 0;

        while (($parcela = $productor->consumir()) !== null) {
            $parcelasProcesadas++;
            if ($parcelasProcesadas % max(1, $tamanoBloque) === 0) {
                $bloques++;
            }

            $turno = [
                'id' => $parcela['id'],
                'parcela_id' => $parcela['id'],
                'hidrante_id' => null,
                'duracion_minutos' => 30,
                'estado' => 'pendiente',
            ];

            $asignado = false;
            foreach ($hidrantesObjects as $hidrant) {
                $resultado = $monitor->solicitarRiego($parcela, $hidrant);
                if ($resultado !== 'bloqueado') {
                    $turno['hidrante_id'] = $hidrant->id;
                    $turno['estado'] = $resultado;
                    $asignado = true;
                    break;
                }
            }

            if (!$asignado) {
                $turno['estado'] = 'sin_hidrante';
            }

            $turnos[] = $turno;
        }

        return [
            'turnos' => $turnos,
            'hidrantes' => $hidrantes,
            'eventos' => array_merge($productor->eventos(), $monitor->eventos()),
            'bloques' => $bloques,
        ];
    }
}
