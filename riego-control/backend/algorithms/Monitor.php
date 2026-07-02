<?php

class Monitor
{
    private array $eventos = [];
    private array $activos = [];
    private array $semaforos = [];  // Un Semaforo por hidrante: ['hidrant_id' => Semaforo]
    private array $asignaciones = []; // Mapeo ['parcela_id' => 'hidrant_id'] para finalizarRiego

    public function __construct()
    {
        // Monitor no recibe Semaforo: los crea según cada Hidrant
    }

    /**
     * Obtiene o crea el Semaforo para un hidrante específico.
     */
    private function obtenerSemaforoHidrant(Hidrant $hidrante): Semaforo
    {
        if (!isset($this->semaforos[$hidrante->id])) {
            $this->semaforos[$hidrante->id] = new Semaforo($hidrante->capacidadSimultanea);
        }
        return $this->semaforos[$hidrante->id];
    }

    public function solicitarRiego(array|object $parcela, Hidrant $hidrante): string
    {
        $parcelaId = $this->obtenerIdParcela($parcela);

        if (!$hidrante->disponible) {
            $this->eventos[] = "Monitor bloquea {$parcelaId}: {$hidrante->id} no disponible";
            return 'bloqueado';
        }

        $semaforo = $this->obtenerSemaforoHidrant($hidrante);

        if (!$semaforo->wait()) {
            $this->eventos[] = "Monitor hace esperar {$parcelaId}: sin espacio en {$hidrante->id}";
            return 'en espera';
        }

        $this->activos[] = $parcelaId;
        $this->asignaciones[$parcelaId] = $hidrante->id;  // Recordar qué hidrante usa
        $this->eventos[] = "Monitor concede riego a {$parcelaId} usando {$hidrante->id}";

        return 'activo';
    }

    /**
     * Finaliza el riego de una parcela, liberando espacio en su hidrante asignado.
     */
    public function finalizarRiego(array|object $parcela): void
    {
        $parcelaId = $this->obtenerIdParcela($parcela);

        $this->activos = array_values(array_filter(
            $this->activos,
            fn (int|string $id): bool => (string) $id !== (string) $parcelaId
        ));

        // Buscar en qué hidrante estaba esta parcela y liberar su semáforo
        if (isset($this->asignaciones[$parcelaId])) {
            $hidrantId = $this->asignaciones[$parcelaId];
            if (isset($this->semaforos[$hidrantId])) {
                $this->semaforos[$hidrantId]->signal();
                $disponibles = $this->semaforos[$hidrantId]->disponibles();
                $this->eventos[] = "Monitor libera {$parcelaId} del hidrante {$hidrantId}. Espacios libres: {$disponibles}";
            }
            unset($this->asignaciones[$parcelaId]);
        }
    }

    public function eventos(): array
    {
        return $this->eventos;
    }

    public function activos(): array
    {
        return $this->activos;
    }

    private function obtenerIdParcela(array|object $parcela): int|string
    {
        if (is_array($parcela)) {
            return $parcela['id'] ?? $parcela['parcela_id'] ?? 'sin-id';
        }

        if (isset($parcela->id)) {
            return $parcela->id;
        }

        if (method_exists($parcela, 'getId')) {
            return $parcela->getId() ?? 'sin-id';
        }

        return 'sin-id';
    }
}
