<?php

require_once __DIR__ . '/../../backend/config/database.php';

class TurnoRiego
{
    private ?int $id;
    private int $parcelaId;
    private ?int $hidranteId;
    private string $inicio;
    private ?string $fin;
    private string $estado;

    public function __construct(?int $id, int $parcelaId, ?int $hidranteId, string $inicio, ?string $fin, string $estado = 'pendiente')
    {
        $this->id = $id;
        $this->parcelaId = $parcelaId;
        $this->hidranteId = $hidranteId;
        $this->inicio = $inicio;
        $this->fin = $fin;
        $this->estado = $estado;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParcelaId(): int
    {
        return $this->parcelaId;
    }

    public function setParcelaId(int $parcelaId): void
    {
        $this->parcelaId = $parcelaId;
    }

    public function getHidranteId(): ?int
    {
        return $this->hidranteId;
    }

    public function setHidranteId(?int $hidranteId): void
    {
        $this->hidranteId = $hidranteId;
    }

    public function getInicio(): string
    {
        return $this->inicio;
    }

    public function setInicio(string $inicio): void
    {
        $this->inicio = $inicio;
    }

    public function getFin(): ?string
    {
        return $this->fin;
    }

    public function setFin(?string $fin): void
    {
        $this->fin = $fin;
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
            (int) ($data['parcela_id'] ?? 0),
            isset($data['hidrante_id']) ? (int) $data['hidrante_id'] : null,
            trim((string) ($data['inicio'] ?? date('Y-m-d H:i:s'))),
            isset($data['fin']) ? trim((string) $data['fin']) : null,
            trim((string) ($data['estado'] ?? 'pendiente'))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parcela_id' => $this->parcelaId,
            'hidrante_id' => $this->hidranteId,
            'inicio' => $this->inicio,
            'fin' => $this->fin,
            'estado' => $this->estado,
        ];
    }

    public function save(): bool
    {
        $db = Database::getConnection();

        if ($this->id === null) {
            $stmt = $db->prepare('INSERT INTO turnos_riego (parcela_id, hidrante_id, inicio, fin, estado) VALUES (:parcela_id, :hidrante_id, :inicio, :fin, :estado)');
            $success = $stmt->execute([
                ':parcela_id' => $this->parcelaId,
                ':hidrante_id' => $this->hidranteId,
                ':inicio' => $this->inicio,
                ':fin' => $this->fin,
                ':estado' => $this->estado,
            ]);

            if ($success) {
                $this->id = (int) $db->lastInsertId();
            }

            return $success;
        }

        $stmt = $db->prepare('UPDATE turnos_riego SET parcela_id = :parcela_id, hidrante_id = :hidrante_id, inicio = :inicio, fin = :fin, estado = :estado WHERE id = :id');
        return $stmt->execute([
            ':parcela_id' => $this->parcelaId,
            ':hidrante_id' => $this->hidranteId,
            ':inicio' => $this->inicio,
            ':fin' => $this->fin,
            ':estado' => $this->estado,
            ':id' => $this->id,
        ]);
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT id, parcela_id, hidrante_id, inicio, fin, estado FROM turnos_riego ORDER BY id');
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => self::fromArray($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, parcela_id, hidrante_id, inicio, fin, estado FROM turnos_riego WHERE id = :id');
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
        $stmt = $db->prepare('DELETE FROM turnos_riego WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }
}
