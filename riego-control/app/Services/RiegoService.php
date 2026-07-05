<?php

class RiegoService
{
    private string $scriptPath;
    private ?string $pythonExecutable;

    public function __construct()
    {
        $this->scriptPath = realpath(__DIR__ . '/../../backend/concurrency/riego_concurrente.py')
            ?: __DIR__ . '/../../backend/concurrency/riego_concurrente.py';
        $this->pythonExecutable = $this->findPythonBinary();
    }

    public function planificar(array $parcelas, array $hidrantes): array
    {
        if ($this->pythonExecutable === null) {
            return $this->buildErrorResponse('Python no está instalado o no se encuentra en la ruta del sistema.');
        }

        if (!is_file($this->scriptPath)) {
            return $this->buildErrorResponse('No se encontró el script de concurrencia backend/concurrency/riego_concurrente.py.');
        }

        return $this->executePythonScript([
            'parcelas' => $parcelas,
            'hidrantes' => $hidrantes,
        ]);
    }

    private function findPythonBinary(): ?string
    {
        foreach (['python', 'python3'] as $binary) {
            $proc = proc_open(
                escapeshellarg($binary) . ' --version',
                [
                    ["pipe", "r"],
                    ["pipe", "w"],
                    ["pipe", "w"],
                ],
                $pipes,
                __DIR__
            );

            if (!is_resource($proc)) {
                continue;
            }

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);
            if ($code === 0 && (stripos($stdout, 'python') !== false || stripos($stderr, 'python') !== false)) {
                return $binary;
            }
        }

        return null;
    }

    private function executePythonScript(array $inputData): array
    {
        $descriptorSpec = [
            ["pipe", "r"],
            ["pipe", "w"],
            ["pipe", "w"],
        ];

        $command = escapeshellarg($this->pythonExecutable) . ' ' . escapeshellarg($this->scriptPath) . ' --mode=controlled';
        $process = proc_open($command, $descriptorSpec, $pipes, __DIR__);

        if (!is_resource($process)) {
            return $this->buildErrorResponse('No se pudo iniciar el proceso Python.');
        }

        fwrite($pipes[0], json_encode($inputData, JSON_UNESCAPED_UNICODE));
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            return $this->buildErrorResponse('El script Python falló: ' . trim($stderr ?: $stdout));
        }

        $decoded = json_decode($stdout, true);
        if (!is_array($decoded)) {
            return $this->buildErrorResponse('Salida JSON inválida del script Python: ' . trim($stdout));
        }

        return $decoded;
    }

    private function buildErrorResponse(string $message): array
    {
        return [
            'turnos' => [],
            'hidrantes' => [],
            'eventos' => ["error: {$message}"],
            'error' => $message,
        ];
    }
}
