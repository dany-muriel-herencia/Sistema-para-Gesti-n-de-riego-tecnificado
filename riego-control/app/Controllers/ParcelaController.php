<?php

require_once __DIR__ . '/../Models/Parcela.php';

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

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode($this->listar(), JSON_PRETTY_PRINT);
    }
}
