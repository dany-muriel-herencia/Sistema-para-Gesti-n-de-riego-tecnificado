<?php

require_once __DIR__ . '/../../backend/config/database.php';

class Hidrante
{
    private ?int $id;
    private string $nombre;
    private int $capacidad;
    private string $estado;

    public function __construct(?int $id, string $nombre, int $capacidad, string $estado = 'disponible')
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->capacidad = $capacidad;
        $this->estado = $estado;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getCapacidad(): int
    {
        return $this->capacidad;
    }

    public function setCapacidad(int $capacidad): void
    {
        $this->capacidad = $capacidad;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            trim((string) ($data['nombre'] ?? '')),
            isset($data['capacidad']) ? (int) $data['capacidad'] : 1,
            trim((string) ($data['estado'] ?? 'disponible'))
        );
    }

    public function toArray(): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'capacidad' => $this->capacidad,
            'estado'    => $this->estado,
        ];
    }

    public function save(): bool
    {
        $db = Database::getConnection();

        if ($this->id === null) {
            $stmt = $db->prepare('INSERT INTO hidrantes (nombre, capacidad, estado) VALUES (:nombre, :capacidad, :estado)');
            $success = $stmt->execute([
                ':nombre'    => $this->nombre,
                ':capacidad' => $this->capacidad,
                ':estado'    => $this->estado,
            ]);

            if ($success) {
                $this->id = (int) $db->lastInsertId();
            }

            return $success;
        }

        $stmt = $db->prepare('UPDATE hidrantes SET nombre = :nombre, capacidad = :capacidad, estado = :estado WHERE id = :id');
        return $stmt->execute([
            ':nombre'    => $this->nombre,
            ':capacidad' => $this->capacidad,
            ':estado'    => $this->estado,
            ':id'        => $this->id,
        ]);
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT id, nombre, capacidad, estado FROM hidrantes ORDER BY id');
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => self::fromArray($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, nombre, capacidad, estado FROM hidrantes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromArray($row) : null;
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM hidrantes WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }
}
