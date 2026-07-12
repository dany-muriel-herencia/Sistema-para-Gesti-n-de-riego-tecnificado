<?php

class Semaforo
{
    private int $disponibles;
    private string $lockFile;

    public function __construct(private int $capacidad)
    {
        $this->disponibles = $capacidad;
        $this->lockFile = sys_get_temp_dir() . '/semaphore_' . uniqid() . '.lock';
        // Crear el archivo de bloqueo si no existe
        if (!file_exists($this->lockFile)) {
            file_put_contents($this->lockFile, $this->disponibles);
        }
    }

    public function wait(): bool
    {
        // Obtener un lock exclusivo en el archivo
        $fp = fopen($this->lockFile, 'c+');
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return false;
        }

        // Leer el valor actual
        $this->disponibles = (int) file_get_contents($this->lockFile);

        if ($this->disponibles <= 0) {
            // Liberar el lock y retornar false
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        // Decrementar y guardar
        $this->disponibles--;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) $this->disponibles);
        fflush($fp);

        // Liberar el lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public function signal(): void
    {
        // Obtener un lock exclusivo en el archivo
        $fp = fopen($this->lockFile, 'c+');
        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }

        // Leer el valor actual
        $this->disponibles = (int) file_get_contents($this->lockFile);

        // Incrementar si no hemos alcanzado la capacidad máxima
        if ($this->disponibles < $this->capacidad) {
            $this->disponibles++;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string) $this->disponibles);
            fflush($fp);
        }

        // Liberar el lock
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function disponibles(): int
    {
        // Obtener un lock compartido para lectura
        $fp = fopen($this->lockFile, 'r');
        if (!flock($fp, LOCK_SH)) {
            fclose($fp);
            return 0;
        }

        // Leer el valor actual
        $valor = (int) file_get_contents($this->lockFile);

        // Liberar el lock
        flock($fp, LOCK_UN);
        fclose($fp);

        return $valor;
    }

    public function __destruct()
    {
        // Limpiar el archivo de lock al destruir el objeto
        if (file_exists($this->lockFile)) {
            unlink($this->lockFile);
        }
    }
}