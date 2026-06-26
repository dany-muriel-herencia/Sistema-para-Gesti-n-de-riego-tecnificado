<?php

require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../../backend/analyzer/StressAnalyzer.php';
require_once __DIR__ . '/../../backend/algorithms/LectoresEscritores.php';

class ParcelaController
{
    public static function cargarDatosSimulados(): array
    {
        $ruta = __DIR__ . '/../../database/sensores_simulados.json';
        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            throw new RuntimeException('No se pudo leer el archivo JSON simulado.');
        }

        $datos = json_decode($contenido, true);

        if (!is_array($datos)) {
            throw new RuntimeException('El archivo JSON simulado no tiene un formato valido.');
        }

        return $datos;
    }

    public function listar(): array
    {
        $sincronizador = new LectoresEscritores();
        $analyzer = new StressAnalyzer();

        return $sincronizador->leer('sensores_simulados.json', function () use ($analyzer): array {
            $datos = self::cargarDatosSimulados();

            return array_map(function (array $data) use ($analyzer): array {
                $parcela = $analyzer->evaluar(Parcela::fromArray($data));
                return $parcela->toArray();
            }, $datos['parcelas'] ?? []);
        });
    }

    public function clima(): array
    {
        $datos = self::cargarDatosSimulados();
        return $datos['clima'] ?? [];
    }

    public function hidrantes(): array
    {
        $datos = self::cargarDatosSimulados();
        return $datos['hidrantes'] ?? [];
    }

    public function respuestaJson(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'fuente' => 'database/sensores_simulados.json',
            'parcelas' => $this->listar(),
            'clima' => $this->clima(),
            'hidrantes' => $this->hidrantes(),
        ], JSON_PRETTY_PRINT);
    }
}
