<?php

class ProductorConsumidor
{
    private array $cola = [];
    private array $eventos = [];

    /**
     * Produce (encola) una parcela si su estrés hídrico es alto o medio.
     * 
     * @param array $parcela Array con ['id' => ..., 'nombre' => ..., 'estres_hidrico' => 'alto'|'medio'|'bajo']
     *                       (no es un objeto Parcela; el estrés se calcula externamente)
     */
    public function producir(array $parcela): void
    {
        $estres = $parcela['estres_hidrico'] ?? 'bajo';

        if (!in_array($estres, ['alto', 'medio'], true)) {
            $this->eventos[] = "Productor descarta {$parcela['id']}: estres {$estres}";
            return;
        }

        $this->cola[] = $parcela;
        $this->eventos[] = "Productor encola {$parcela['id']}: estres {$estres}";
    }

    /**
     * Consume (desencola) la próxima parcela en la cola.
     * 
     * @return array|null Array con estructura de parcela, o null si cola vacía
     */
    public function consumir(): ?array
    {
        $parcela = array_shift($this->cola);

        if ($parcela === null) {
            $this->eventos[] = 'Consumidor espera: cola de riego vacia';
            return null;
        }

        $this->eventos[] = "Consumidor toma {$parcela['id']} para asignar turno";
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
