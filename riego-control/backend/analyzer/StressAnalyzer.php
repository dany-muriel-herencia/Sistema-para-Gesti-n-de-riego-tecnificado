<?php

class StressAnalyzer
{
    public function evaluar(Parcela $parcela): Parcela
    {
        $deficit = $parcela->umbralHumedad - $parcela->humedad;

        if ($deficit >= 8 || $parcela->temperatura >= 31) {
            $parcela->estresHidrico = 'alto';
            return $parcela;
        }

        if ($deficit >= 3) {
            $parcela->estresHidrico = 'medio';
            return $parcela;
        }

        $parcela->estresHidrico = 'bajo';
        return $parcela;
    }
}
