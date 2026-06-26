<?php

class Monitor
{
    private array $eventos = [];
    private array $activos = [];

    public function __construct(private Semaforo $semaforo)
    {
    }

    public function solicitarRiego(Parcela $parcela, Hidrant $hidrante): string
    {
        if (!$hidrante->disponible) {
            $this->eventos[] = "Monitor bloquea {$parcela->id}: {$hidrante->id} no disponible";
            return 'bloqueado';
        }

        if (!$this->semaforo->wait()) {
            $this->eventos[] = "Monitor hace esperar {$parcela->id}: sin hidrantes libres";
            return 'en espera';
        }

        $this->activos[] = $parcela->id;
        $this->eventos[] = "Monitor concede riego a {$parcela->id} usando {$hidrante->id}";

        return 'activo';
    }

    public function finalizarRiego(Parcela $parcela): void
    {
        $this->activos = array_values(array_filter(
            $this->activos,
            fn (string $id): bool => $id !== $parcela->id
        ));
        $this->semaforo->signal();
        $this->eventos[] = "Monitor libera {$parcela->id}. Hidrantes libres: {$this->semaforo->disponibles()}";
    }

    public function eventos(): array
    {
        return $this->eventos;
    }

    public function activos(): array
    {
        return $this->activos;
    }
}
