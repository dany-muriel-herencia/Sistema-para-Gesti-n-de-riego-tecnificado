<?php

require_once __DIR__ . '/../Models/Hidrante.php';

class HidranteController
{
    public function listar(): array
    {
        return array_map(function (Hidrante $hidrante) {
            return $hidrante->toArray();
        }, Hidrante::all());
    }

    public function obtener(int $id): ?array
    {
        $hidrante = Hidrante::find($id);
        return $hidrante ? $hidrante->toArray() : null;
    }

    public function crear(array $data): array
    {
        $hidrante = Hidrante::fromArray($data);
        if (!$hidrante->save()) {
            return ['error' => 'No se pudo crear el hidrante.'];
        }

        return $hidrante->toArray();
    }

    public function actualizar(int $id, array $data): array
    {
        $hidrante = Hidrante::find($id);
        if (!$hidrante) {
            return ['error' => 'Hidrante no encontrado.'];
        }

        $hidrante->setNombre($data['nombre'] ?? $hidrante->getNombre());
        $hidrante->setCapacidad(isset($data['capacidad']) ? (int) $data['capacidad'] : $hidrante->getCapacidad());
        $hidrante->setEstado($data['estado'] ?? $hidrante->getEstado());

        if (!$hidrante->save()) {
            return ['error' => 'No se pudo actualizar el hidrante.'];
        }

        return $hidrante->toArray();
    }

    public function eliminar(int $id): array
    {
        $hidrante = Hidrante::find($id);
        if (!$hidrante) {
            return ['error' => 'Hidrante no encontrado.'];
        }

        if (!$hidrante->delete()) {
            return ['error' => 'No se pudo eliminar el hidrante.'];
        }

        return ['success' => true];
    }
}
