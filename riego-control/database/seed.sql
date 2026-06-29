USE sistema_riego;

INSERT INTO parcelas (nombre, area, cultivo, estado) VALUES
('Olivos Sector A', 12.50, 'Olivo', 'activa'),
('Vid Sector B', 8.00, 'Vid', 'activa'),
('Aji Sector C', 4.20, 'Aji', 'activa'),
('Maiz Sector D', 10.00, 'Maiz', 'activa');

INSERT INTO sensores (parcela_id, temperatura, humedad, fecha_medicion) VALUES
(1, 29.6, 18.0, '2026-06-25 08:00:00'),
(2, 26.8, 34.0, '2026-06-25 08:10:00'),
(3, 31.1, 22.0, '2026-06-25 08:20:00'),
(4, 28.3, 27.0, '2026-06-25 08:30:00');

INSERT INTO hidrantes (nombre, capacidad, estado) VALUES
('Hidrante Norte', 2, 'disponible'),
('Hidrante Sur', 1, 'disponible');

INSERT INTO turnos_riego (parcela_id, hidrante_id, inicio, fin, estado) VALUES
(1, 1, '2026-06-25 09:00:00', '2026-06-25 09:45:00', 'completado'),
(2, 1, '2026-06-25 09:15:00', '2026-06-25 09:40:00', 'completado'),
(3, 2, '2026-06-25 09:30:00', NULL, 'pendiente');
