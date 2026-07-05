<?php

require_once __DIR__ . '/../Models/TurnoRiego.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';
require_once __DIR__ . '/../Services/RiegoService.php';

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
        $parcelas = $this->prepareParcelas($data['parcelas'] ?? []);
        $hidrantes = $data['hidrantes'] ?? [];

        $service = new RiegoService();
        return $service->planificar($parcelas, $hidrantes);
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

        $parcelas = $this->prepareParcelas($data['parcelas'] ?? []);
        $hidrantes = $data['hidrantes'] ?? [];

        $service = new RiegoService();
        $resultado = $service->planificar($parcelas, $hidrantes);
        $resultado['bloques'] = $tamanoBloque > 0 ? max(1, (int) ceil(count($parcelas) / $tamanoBloque)) : 0;
        return $resultado;
    }
}
