<?php

class LectoresEscritores
{
    private int $lectores = 0;
    private bool $escritorActivo = false;
    private array $eventos = [];

    public function leer(string $recurso, callable $callback): mixed
    {
        $this->lectores++;
        $this->eventos[] = "Lector entra a {$recurso}. Lectores activos: {$this->lectores}";
        $resultado = $callback();
        $this->lectores--;
        $this->eventos[] = "Lector sale de {$recurso}. Lectores activos: {$this->lectores}";

        return $resultado;
    }

    public function escribir(string $recurso, callable $callback): mixed
    {
        if ($this->lectores > 0) {
            $this->eventos[] = "Escritor espera {$recurso}; hay {$this->lectores} lectores activos";
        }

        $this->escritorActivo = true;
        $this->eventos[] = "Escritor actualiza {$recurso}";
        $resultado = $callback();
        $this->escritorActivo = false;
        $this->eventos[] = "Escritor libera {$recurso}";

        return $resultado;
    }

    public function eventos(): array
    {
        return $this->eventos;
    }
}
