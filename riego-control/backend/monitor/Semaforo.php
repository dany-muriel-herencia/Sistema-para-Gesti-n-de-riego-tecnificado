<?php

class Semaforo
{
    private int $disponibles;

    public function __construct(private int $capacidad)
    {
        $this->disponibles = $capacidad;
    }

    public function wait(): bool
    {
        if ($this->disponibles <= 0) {
            return false;
        }

        $this->disponibles--;
        return true;
    }

    public function signal(): void
    {
        if ($this->disponibles < $this->capacidad) {
            $this->disponibles++;
        }
    }

    public function disponibles(): int
    {
        return $this->disponibles;
    }
}
