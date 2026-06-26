<?php

class Parcela
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $cultivo,
        public float $humedad,
        public float $temperatura,
        public float $umbralHumedad,
        public string $sensorId,
        public string $estresHidrico = 'sin evaluar'
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['nombre'],
            $data['cultivo'],
            (float) $data['humedad'],
            (float) $data['temperatura'],
            (float) $data['umbral_humedad'],
            $data['sensor']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'cultivo' => $this->cultivo,
            'humedad' => $this->humedad,
            'temperatura' => $this->temperatura,
            'umbral_humedad' => $this->umbralHumedad,
            'sensor' => $this->sensorId,
            'estres_hidrico' => $this->estresHidrico,
        ];
    }
}
