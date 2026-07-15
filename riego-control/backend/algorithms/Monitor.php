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
        // Usar getters en lugar de acceder directamente a propiedades
        if (!$hidrante->disponible) {
            $this->eventos[] = "Monitor bloquea {$parcela->getId()}: {$hidrante->id} no disponible";
            return 'bloqueado';
        }

        if (!$this->semaforo->wait()) {
            $this->eventos[] = "Monitor hace esperar {$parcela->getId()}: sin hidrantes libres";
            return 'en espera';
        }

        $this->activos[] = $parcela->getId(); // Usar getter
        $this->eventos[] = "Monitor concede riego a {$parcela->getId()} usando {$hidrante->id}";

        return 'activo';
    }

    public function finalizarRiego(Parcela $parcela): void
    {
        // Usar getter para obtener el ID
        $parcelaId = $parcela->getId();

        $this->activos = array_values(array_filter(
            $this->activos,
            fn ($id): bool => $id !== $parcelaId
        ));
        $this->semaforo->signal();
        $this->eventos[] = "Monitor libera {$parcelaId}. Hidrantes libres: {$this->semaforo->disponibles()}";
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