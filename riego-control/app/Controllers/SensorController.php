<?php

require_once __DIR__ . '/../Models/Sensor.php';

class SensorController
{
    public function listar(): array
    {
        return array_map(fn (Sensor $sensor) => $sensor->toArray(), Sensor::all());
    }

    public function obtener(int $id): ?array
    {
        $sensor = Sensor::find($id);
        return $sensor ? $sensor->toArray() : null;
    }

    public function crear(array $data): array
    {
        $sensor = Sensor::fromArray($data);
        if (!$sensor->save()) {
            return ['error' => 'No se pudo crear el sensor.'];
        }

        return $sensor->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $sensor = Sensor::find($id);
        if (!$sensor) {
            return ['error' => 'Sensor no encontrado.'];
        }

        $sensor->setParcelaId(isset($data['parcela_id']) ? (int) $data['parcela_id'] : $sensor->getParcelaId());
        $sensor->setTemperatura(isset($data['temperatura']) ? (float) $data['temperatura'] : $sensor->getTemperatura());
        $sensor->setHumedad(isset($data['humedad']) ? (float) $data['humedad'] : $sensor->getHumedad());
        $sensor->setFechaMedicion($data['fecha_medicion'] ?? $sensor->getFechaMedicion());

        if (!$sensor->save()) {
            return ['error' => 'No se pudo actualizar el sensor.'];
        }

        return $sensor->toArray();
    }

    public function eliminar(int $id): array
    {
        $sensor = Sensor::find($id);
        if (!$sensor) {
            return ['error' => 'Sensor no encontrado.'];
        }

        if (!$sensor->delete()) {
            return ['error' => 'No se pudo eliminar el sensor.'];
        }

        return ['success' => true];
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->listar(), JSON_PRETTY_PRINT);
    }
}
