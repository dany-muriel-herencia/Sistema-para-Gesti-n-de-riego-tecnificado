<?php

class TurnoRiego
{
    public function __construct(
        public string $id,
        public string $parcelaId,
        public string $hidranteId,
        public int $duracionMinutos,
        public string $estado
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parcela_id' => $this->parcelaId,
            'hidrante_id' => $this->hidranteId,
            'duracion_minutos' => $this->duracionMinutos,
            'estado' => $this->estado,
        ];
    }
}
