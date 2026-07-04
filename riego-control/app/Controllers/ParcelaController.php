<?php

require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';

class ParcelaController
{
    public function listar(): array
    {
        return array_map(function (Parcela $parcela) {
            return $parcela->toArray();
        }, Parcela::all());
    }

    public function obtener(int $id): ?array
    {
        $parcela = Parcela::find($id);
        return $parcela ? $parcela->toArray() : null;
    }

    public function crear(array $data): array
    {
        $parcela = Parcela::fromArray($data);
        if (!$parcela->save()) {
            return ['error' => 'No se pudo crear la parcela.'];
        }

        return $parcela->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return ['error' => 'Parcela no encontrada.'];
        }

        $parcela->setNombre($data['nombre'] ?? $parcela->getNombre());
        $parcela->setArea(isset($data['area']) ? (float) $data['area'] : $parcela->getArea());
        $parcela->setCultivo($data['cultivo'] ?? $parcela->getCultivo());
        $parcela->setEstado($data['estado'] ?? $parcela->getEstado());

        if (!$parcela->save()) {
            return ['error' => 'No se pudo actualizar la parcela.'];
        }

        return $parcela->toArray();
    }

    public function eliminar(int $id): array
    {
        $parcela = Parcela::find($id);
        if (!$parcela) {
            return ['error' => 'Parcela no encontrada.'];
        }

        if (!$parcela->delete()) {
            return ['error' => 'No se pudo eliminar la parcela.'];
        }

        return ['success' => true];
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

    public function clima(): array
    {
        $data = $this->loadSimulatedData();
        return $data['clima'] ?? [];
    }

    public function listarSimulada(): array
    {
        $data = $this->loadSimulatedData();
        $analyzer = new StressAnalyzer();
        $parcelas = $data['parcelas'] ?? [];

        return array_map(function (array $parcela) use ($analyzer) {
            $temperatura = isset($parcela['temperatura']) ? (float) $parcela['temperatura'] : 0.0;
            $humedad = isset($parcela['humedad']) ? (float) $parcela['humedad'] : 0.0;

            return [
                'id' => $parcela['id'] ?? null,
                'nombre' => $parcela['nombre'] ?? '',
                'cultivo' => $parcela['cultivo'] ?? '',
                'humedad' => $humedad,
                'temperatura' => $temperatura,
                'estres_hidrico' => $analyzer->evaluarValores($temperatura, $humedad),
            ];
        }, $parcelas);
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->listar(), JSON_PRETTY_PRINT);
    }
}
