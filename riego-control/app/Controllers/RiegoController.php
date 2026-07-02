<?php

require_once __DIR__ . '/../Models/TurnoRiego.php';
require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../Models/Sensor.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';

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
}
