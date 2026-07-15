<?php

class ProductorConsumidor
{
    private array $cola = [];
    private array $eventos = [];

    public function producir(Parcela $parcela): void
    {
        // Implementar lógica de estrés hídrico basada en las propiedades reales de Parcela
        $humedad = $this->calcularHumedad($parcela);
        $temperatura = $this->calcularTemperatura($parcela);
        $estresHidrico = $this->determinarEstresHidrico($humedad, $temperatura);

        if (!in_array($estresHidrico, ['alto', 'medio'], true)) {
            $this->eventos[] = "Productor descarta {$parcela->getId()}: estres {$estresHidrico}";
            return;
        }

        $this->cola[] = $parcela;
        $this->eventos[] = "Productor encola {$parcela->getId()}: estres {$estresHidrico}";
    }

    public function consumir(): ?Parcela
    {
        $parcela = array_shift($this->cola);

        if ($parcela === null) {
            $this->eventos[] = 'Consumidor espera: cola de riego vacia';
            return null;
        }

        $this->eventos[] = "Consumidor toma {$parcela->getId()} para asignar turno";
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

    // Métodos auxiliares para calcular el estrés hídrico basado en propiedades reales
    private function calcularHumedad(Parcela $parcela): float
    {
        // Simulación de cálculo de humedad basado en el estado y cultivo
        $baseHumedad = 30.0;
        switch ($parcela->getEstado()) {
            case 'seca':
                $baseHumedad -= 15.0;
                break;
            case 'normal':
                $baseHumedad += 5.0;
                break;
            case 'humeda':
                $baseHumedad += 15.0;
                break;
        }

        // Ajuste por tipo de cultivo
        switch ($parcela->getCultivo()) {
            case 'olivo':
                $baseHumedad -= 5.0;
                break;
            case 'vid':
                $baseHumedad += 3.0;
                break;
            case 'aji':
                $baseHumedad += 8.0;
                break;
        }

        return max(10.0, min(50.0, $baseHumedad));
    }

    private function calcularTemperatura(Parcela $parcela): float
    {
        // Simulación de cálculo de temperatura basado en el estado y área
        $baseTemp = 25.0;
        if ($parcela->getEstado() === 'seca') {
            $baseTemp += 5.0;
        } elseif ($parcela->getEstado() === 'humeda') {
            $baseTemp -= 3.0;
        }

        // Ajuste por área (mayor área = más exposición al sol)
        $baseTemp += ($parcela->getArea() / 100);

        return max(15.0, min(40.0, $baseTemp));
    }

    private function determinarEstresHidrico(float $humedad, float $temperatura): string
    {
        // Lógica para determinar el estrés hídrico basado en humedad y temperatura
        if ($humedad < 20 || $temperatura > 35) {
            return 'alto';
        } elseif ($humedad < 30 || $temperatura > 30) {
            return 'medio';
        } else {
            return 'bajo';
        }
    }
}