<?php

class StressAnalyzer
{
    public function evaluarValores(float $temperatura, float $humedad): string
    {
        if ($humedad < 30 && $temperatura > 30) {
            return 'alto';
        }

        if ($humedad >= 30 && $humedad <= 60) {
            return 'medio';
        }

        return 'bajo';
    }

    public function evaluarSensor(array $lectura): array
    {
        $temperatura = isset($lectura['temperatura']) ? (float) $lectura['temperatura'] : 0.0;
        $humedad = isset($lectura['humedad']) ? (float) $lectura['humedad'] : 0.0;

        return [
            'temperatura' => $temperatura,
            'humedad' => $humedad,
            'estres_hidrico' => $this->evaluarValores($temperatura, $humedad),
        ];
    }
}
