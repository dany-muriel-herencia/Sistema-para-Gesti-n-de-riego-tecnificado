<?php

class ProductorConsumidor
{
    private array $cola = [];
    private array $eventos = [];

    public function producir(Parcela $parcela): void
    {
        if (!in_array($parcela->estresHidrico, ['alto', 'medio'], true)) {
            $this->eventos[] = "Productor descarta {$parcela->id}: estres {$parcela->estresHidrico}";
            return;
        }

        $this->cola[] = $parcela;
        $this->eventos[] = "Productor encola {$parcela->id}: estres {$parcela->estresHidrico}";
    }

    public function consumir(): ?Parcela
    {
        $parcela = array_shift($this->cola);

        if ($parcela === null) {
            $this->eventos[] = 'Consumidor espera: cola de riego vacia';
            return null;
        }

        $this->eventos[] = "Consumidor toma {$parcela->id} para asignar turno";
        return $parcela;
    }

    public function pendientes(): int
    {
        return count($this->cola);
    }

    public function eventos(): array
    {
        return $this->eventos;
    }
}
