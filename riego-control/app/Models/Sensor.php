<?php

class Sensor
{
    public function __construct(
        public string $id,
        public string $parcelaId,
        public float $humedad,
        public float $temperatura,
        public array $clima = []
    ) {
    }

    public static function fromParcela(array $parcela, array $clima): self
    {
        return new self(
            $parcela['sensor'],
            $parcela['id'],
            (float) $parcela['humedad'],
            (float) $parcela['temperatura'],
            $clima
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parcela_id' => $this->parcelaId,
            'humedad' => $this->humedad,
            'temperatura' => $this->temperatura,
            'clima' => $this->clima,
        ];
    }
}
