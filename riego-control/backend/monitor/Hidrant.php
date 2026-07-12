<?php

class Hidrant
{
    public function __construct(
        public string $id,
        public string $nombre,
        public bool $disponible,
        public int $capacidadSimultanea
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['nombre'],
            (bool) $data['disponible'],
            (int) $data['capacidad_simultanea']
        );
    }
}
