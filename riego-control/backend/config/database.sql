-- Schema para la base de datos del sistema de riego

-- Tabla de parcelas
CREATE TABLE IF NOT EXISTS parcelas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    area REAL NOT NULL,
    cultivo TEXT NOT NULL,
    estado TEXT NOT NULL DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de sensores
CREATE TABLE IF NOT EXISTS sensores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parcela_id INTEGER NOT NULL,
    humedad REAL NOT NULL,
    temperatura REAL NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcela_id) REFERENCES parcelas (id)
);

-- Tabla de turnos de riego
CREATE TABLE IF NOT EXISTS turnos_riego (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parcela_id INTEGER NOT NULL,
    hidrante_id INTEGER,
    duracion_minutos INTEGER NOT NULL,
    estado TEXT NOT NULL DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcela_id) REFERENCES parcelas (id)
);

-- Insertar datos de ejemplo
INSERT OR IGNORE INTO parcelas (id, nombre, area, cultivo, estado) VALUES
(1, 'Parcela A', 100.0, 'Olivo', 'activa'),
(2, 'Parcela B', 150.0, 'Vid', 'activa'),
(3, 'Parcela C', 80.0, 'Aji', 'seca'),
(4, 'Parcela D', 120.0, 'Maiz', 'humeda'),
(5, 'Parcela E', 90.0, 'Papa', 'normal');

INSERT OR IGNORE INTO sensores (id, parcela_id, humedad, temperatura) VALUES
(1, 1, 18.5, 29.6),
(2, 2, 34.2, 26.8),
(3, 3, 22.1, 31.1),
(4, 4, 27.3, 28.3),
(5, 5, 15.7, 33.2);

INSERT OR IGNORE INTO turnos_riego (id, parcela_id, hidrante_id, duracion_minutos, estado) VALUES
(1, 1, 1, 45, 'completado'),
(2, 2, 1, 25, 'completado'),
(3, 3, 2, 30, 'pendiente'),
(4, 4, NULL, 0, 'en espera');