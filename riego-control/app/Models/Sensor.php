<?php

require_once __DIR__ . '/../../backend/config/database.php';

class Sensor
{
    private ?int $id;
    private int $parcelaId;
    private float $temperatura;
    private float $humedad;
    private string $fechaMedicion;

    public function __construct(?int $id, int $parcelaId, float $temperatura, float $humedad, string $fechaMedicion)
    {
        $this->id = $id;
        $this->parcelaId = $parcelaId;
        $this->temperatura = $temperatura;
        $this->humedad = $humedad;
        $this->fechaMedicion = $fechaMedicion;
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

    public function getTemperatura(): float
    {
        return $this->temperatura;
    }

    public function setTemperatura(float $temperatura): void
    {
        $this->temperatura = $temperatura;
    }

    public function getHumedad(): float
    {
        return $this->humedad;
    }

    public function setHumedad(float $humedad): void
    {
        $this->humedad = $humedad;
    }

    public function getFechaMedicion(): string
    {
        return $this->fechaMedicion;
    }

    public function setFechaMedicion(string $fechaMedicion): void
    {
        $this->fechaMedicion = $fechaMedicion;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            (int) ($data['parcela_id'] ?? $data['parcela'] ?? 0),
            isset($data['temperatura']) ? (float) $data['temperatura'] : 0.0,
            isset($data['humedad']) ? (float) $data['humedad'] : 0.0,
            trim((string) ($data['fecha_medicion'] ?? $data['fecha'] ?? date('Y-m-d H:i:s')))
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'parcela_id' => $this->parcelaId,
            'temperatura' => $this->temperatura,
            'humedad' => $this->humedad,
            'fecha_medicion' => $this->fechaMedicion,
        ];
    }

    public function save(): bool
    {
        $db = Database::getConnection();

        if ($this->id === null) {
            $stmt = $db->prepare('INSERT INTO sensores (parcela_id, temperatura, humedad, fecha_medicion) VALUES (:parcela_id, :temperatura, :humedad, :fecha_medicion)');
            $success = $stmt->execute([
                ':parcela_id' => $this->parcelaId,
                ':temperatura' => $this->temperatura,
                ':humedad' => $this->humedad,
                ':fecha_medicion' => $this->fechaMedicion,
            ]);

            if ($success) {
                $this->id = (int) $db->lastInsertId();
            }

            return $success;
        }

        $stmt = $db->prepare('UPDATE sensores SET parcela_id = :parcela_id, temperatura = :temperatura, humedad = :humedad, fecha_medicion = :fecha_medicion WHERE id = :id');
        return $stmt->execute([
            ':parcela_id' => $this->parcelaId,
            ':temperatura' => $this->temperatura,
            ':humedad' => $this->humedad,
            ':fecha_medicion' => $this->fechaMedicion,
            ':id' => $this->id,
        ]);
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query('SELECT id, parcela_id, temperatura, humedad, fecha_medicion FROM sensores ORDER BY id');
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => self::fromArray($row), $rows);
    }

    public static function find(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, parcela_id, temperatura, humedad, fecha_medicion FROM sensores WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromArray($row) : null;
    }

    public static function findByParcelaId(int $parcelaId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, parcela_id, temperatura, humedad, fecha_medicion FROM sensores WHERE parcela_id = :parcela_id ORDER BY fecha_medicion DESC');
        $stmt->execute([':parcela_id' => $parcelaId]);
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => self::fromArray($row), $rows);
    }

    public function delete(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM sensores WHERE id = :id');
        return $stmt->execute([':id' => $this->id]);
    }
}
