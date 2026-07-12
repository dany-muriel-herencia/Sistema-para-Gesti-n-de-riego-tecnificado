<?php

require_once __DIR__ . '/../../backend/config/database.php';

class Parcela
{
    private ?int $id;
    private string $nombre;
    private float $area;
    private string $cultivo;
    private string $estado;

    public function __construct(?int $id, string $nombre, float $area, string $cultivo, string $estado = 'activa')
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->area = $area;
        $this->cultivo = $cultivo;
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

    public function getArea(): float
    {
        return $this->area;
    }

    public function setArea(float $area): void
    {
        $this->area = $area;
    }

    public function getCultivo(): string
    {
        return $this->cultivo;
    }

    public function setCultivo(string $cultivo): void
    {
        $this->cultivo = $cultivo;
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
            isset($data['area']) ? (float) $data['area'] : 0.0,
            trim((string) ($data['cultivo'] ?? '')),
            trim((string) ($data['estado'] ?? 'activa'))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'area' => $this->area,
            'cultivo' => $this->cultivo,
            'estado' => $this->estado,
        ];
    }

    public function save(): bool
    {
        $db = Database::getConnection();

        if ($this->id === null) {
            $stmt = $db->prepare('INSERT INTO parcelas (nombre, area, cultivo, estado) VALUES (:nombre, :area, :cultivo, :estado)');
            $success = $stmt->execute([
                ':nombre' => $this->nombre,
                ':area' => $this->area,
                ':cultivo' => $this->cultivo,
                ':estado' => $this->estado,
            ]);

            if ($success) {
                $this->id = (int) $db->lastInsertId();
            }

            return $success;
        }

        $stmt = $db->prepare('UPDATE parcelas SET nombre = :nombre, area = :area, cultivo = :cultivo, estado = :estado WHERE id = :id');
        return $stmt->execute([
            ':nombre' => $this->nombre,
            ':area' => $this->area,
            ':cultivo' => $this->cultivo,
            ':estado' => $this->estado,
            ':id' => $this->id,
        ]);
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT id, nombre, area, cultivo, estado FROM parcelas ORDER BY id');
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => self::fromArray($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, nombre, area, cultivo, estado FROM parcelas WHERE id = :id');
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
        $stmt = $db->prepare('DELETE FROM parcelas WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }
}
