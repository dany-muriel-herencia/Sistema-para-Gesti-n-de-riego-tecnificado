<?php
// dashboard_db.php - Dashboard con conexión a base de datos

// ============================================
// CARGA DE DATOS DESDE BASE DE DATOS
// ============================================
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../Models/Sensor.php';
require_once __DIR__ . '/../Models/TurnoRiego.php';

// Obtener datos de la base de datos
$parcelas = Parcela::all();
$sensores = Sensor::all();
$turnos = TurnoRiego::all();

// Si no hay datos en la BD, usar JSON como respaldo
if (empty($parcelas)) {
    $jsonPath = __DIR__ . '/../../database/sensores_simulados.json';
    if (file_exists($jsonPath)) {
        $datosJson = json_decode(file_get_contents($jsonPath), true);
        $parcelasJson = $datosJson['parcelas'] ?? [];
        $hidrantesJson = $datosJson['hidrantes'] ?? [];
        $clima = $datosJson['clima'] ?? [];
    }
}

// Calcular estadísticas desde la BD
$total_parcelas = count($parcelas);
$criticas = 0;
$alertas = 0;
$optimas = 0;
$humedades = [];
$temps = [];

foreach ($parcelas as $p) {
    $sensor = Sensor::findByParcelaId($p->getId());
    if (!empty($sensor)) {
        $humedad = $sensor[0]->getHumedad();
        $temperatura = $sensor[0]->getTemperatura();
        $humedades[] = $humedad;
        $temps[] = $temperatura;
        
        if ($humedad < 22 && $temperatura > 28) $criticas++;
        elseif ($humedad < 32 || $temperatura > 30) $alertas++;
        else $optimas++;
    }
}

$humedad_promedio = count($humedades) > 0 ? round(array_sum($humedades) / count($humedades), 1) : 0;
$temp_promedio = count($temps) > 0 ? round(array_sum($temps) / count($temps), 1) : 0;

// Hidrantes (desde BD o simulados)
$hidrantes = [];
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, nombre, capacidad, estado FROM hidrantes");
    $hidrantes = $stmt->fetchAll();
    // Convertir a array asociativo con 'disponible'
    $hidrantes = array_map(function($h) {
        return [
            'id' => $h['id'],
            'nombre' => $h['nombre'],
            'disponible' => $h['estado'] === 'disponible',
            'capacidad_simultanea' => $h['capacidad']
        ];
    }, $hidrantes);
} catch (Exception $e) {
    // Si no hay tabla hidrantes, usar simulados
    $hidrantes = $hidrantesJson ?? [
        ['id' => 1, 'nombre' => 'Hidrante Norte', 'disponible' => true, 'capacidad_simultanea' => 2],
        ['id' => 2, 'nombre' => 'Hidrante Sur', 'disponible' => true, 'capacidad_simultanea' => 1],
        ['id' => 3, 'nombre' => 'Hidrante Este', 'disponible' => false, 'capacidad_simultanea' => 1]
    ];
}

$hidrantes_disponibles = count(array_filter($hidrantes, fn($h) => $h['disponible'] ?? false));

// Turnos - formatear para la vista
$turnos_list = [];
foreach ($turnos as $t) {
    $turnos_list[] = [
        'id' => $t->getId(),
        'parcela_id' => $t->getParcelaId(),
        'hidrante_id' => $t->getHidranteId(),
        'duracion_minutos' => 30, // Valor por defecto
        'estado' => $t->getEstado()
    ];
}

// Si no hay turnos en BD, usar simulados
if (empty($turnos_list)) {
    $turnos_list = [
        ['id' => 1, 'parcela_id' => 1, 'hidrante_id' => 1, 'duracion_minutos' => 45, 'estado' => 'completado'],
        ['id' => 2, 'parcela_id' => 2, 'hidrante_id' => 1, 'duracion_minutos' => 25, 'estado' => 'completado'],
        ['id' => 3, 'parcela_id' => 3, 'hidrante_id' => 2, 'duracion_minutos' => 30, 'estado' => 'pendiente'],
        ['id' => 4, 'parcela_id' => 4, 'hidrante_id' => null, 'duracion_minutos' => 0, 'estado' => 'en espera']
    ];
}

// Preparar datos para la tabla de parcelas
$parcelas_list = [];
foreach ($parcelas as $p) {
    $sensor = Sensor::findByParcelaId($p->getId());
    $humedad = !empty($sensor) ? $sensor[0]->getHumedad() : '-';
    $temperatura = !empty($sensor) ? $sensor[0]->getTemperatura() : '-';
    
    if ($humedad !== '-' && $temperatura !== '-') {
        if ($humedad < 22 && $temperatura > 28) $estres = 'critico';
        elseif ($humedad < 32 || $temperatura > 30) $estres = 'alerta';
        else $estres = 'optimo';
    } else {
        $estres = 'optimo';
    }
    
    $parcelas_list[] = [
        'id' => $p->getId(),
        'nombre' => $p->getNombre(),
        'cultivo' => $p->getCultivo(),
        'humedad' => $humedad,
        'temperatura' => $temperatura,
        'estres_hidrico' => $estres
    ];
}

// Si no hay parcelas en BD, usar JSON
if (empty($parcelas_list)) {
    $parcelas_list = $parcelasJson ?? [];
    foreach ($parcelas_list as &$p) {
        if (!isset($p['estres_hidrico'])) {
            $humedad = $p['humedad'] ?? 50;
            $temperatura = $p['temperatura'] ?? 25;
            if ($humedad < 22 && $temperatura > 28) $p['estres_hidrico'] = 'critico';
            elseif ($humedad < 32 || $temperatura > 30) $p['estres_hidrico'] = 'alerta';
            else $p['estres_hidrico'] = 'optimo';
        }
    }
    unset($p);
}

// Clima (de JSON o simulado)
$clima = $clima ?? [
    'condicion' => 'Soleado',
    'temperatura_ambiente' => $temp_promedio ?: 25,
    'humedad_relativa' => $humedad_promedio ?: 50
];

// Eventos simulados
$eventos = [
    '[SISTEMA] Conectado a la base de datos',
    '[SENSOR] Lectura de humedad actualizada',
    '[PRODUCTOR] Evaluando estrés hídrico de parcelas',
    '[MONITOR] Verificando disponibilidad de hidrantes',
    '[CONSUMIDOR] Procesando cola de riego'
];

// Función de escape
function e(string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro de Control de Riego - La Yarada</title>
    <link rel="stylesheet" href="frontend/css/styles.css">
    <style>
        /* ============================================
           ESTILOS MEJORADOS
           ============================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            color: #1a1a1a;
            padding: 20px;
            min-height: 100vh;
        }
        
        .shell {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* TOPBAR */
        .topbar {
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 24px;
            border: 1px solid #e8eaed;
        }
        
        .topbar h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .topbar .eyebrow {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .topbar .db-status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 20px;
        }
        .db-status.online { background: #e8f5e9; color: #2e7d32; }
        .db-status.offline { background: #ffebee; color: #c62828; }
        
        .weather {
            text-align: right;
            font-size: 14px;
            color: #555;
        }
        .weather strong {
            display: block;
            font-size: 16px;
            color: #1a1a1a;
        }
        
        /* MÉTRICAS */
        .grid.metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .grid.metrics article {
            background: white;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid #e8eaed;
            text-align: center;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .grid.metrics article:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        
        .grid.metrics article::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        
        .grid.metrics article:nth-child(1)::before { background: #2e7d32; }
        .grid.metrics article:nth-child(2)::before { background: #1976d2; }
        .grid.metrics article:nth-child(3)::before { background: #f57c00; }
        
        .grid.metrics article span {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .grid.metrics article strong {
            font-size: 28px;
            display: block;
            margin-top: 4px;
            color: #1a1a1a;
        }
        
        /* PANELES */
        .panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #e8eaed;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .section-title h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title span {
            font-size: 12px;
            color: #888;
        }
        
        /* TABLA */
        .table-wrap {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        th {
            text-align: left;
            padding: 10px 12px;
            background: #f8fafc;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e8eaed;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover td {
            background: #fafbfc;
        }
        
        /* BADGES */
        .badge {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-critico { background: #ffebee; color: #c62828; }
        .badge-alerta { background: #fff3e0; color: #e65100; }
        .badge-optimo { background: #e8f5e9; color: #2e7d32; }
        
        /* GRID 2 COLUMNAS */
        .grid.two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        /* LISTAS */
        .list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #2e7d32;
            transition: all 0.2s ease;
        }
        
        .list-row:hover {
            background: #f0f2f5;
        }
        
        .list-row strong {
            font-size: 14px;
            color: #1a1a1a;
        }
        
        .list-row span {
            font-size: 13px;
            color: #666;
        }
        
        /* CONSOLA */
        .console {
            background: #1a1c1e;
            border-color: #333;
        }
        
        .console .section-title h2 {
            color: #d1d4d6;
        }
        
        .console .section-title span {
            color: #666;
        }
        
        .console code {
            display: block;
            color: #4fc3f7;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            padding: 4px 0;
            border-bottom: 1px solid #2a2c2e;
        }
        
        .console code:last-child {
            border-bottom: none;
        }
        
        .console code .time {
            color: #666;
            font-size: 11px;
            margin-right: 12px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            body { padding: 12px; }
            
            .topbar {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
                text-align: center;
            }
            
            .weather { text-align: center; }
            
            .grid.metrics {
                grid-template-columns: 1fr;
            }
            
            .grid.two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .list-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <!-- ==========================================
        TOPBAR
        ========================================== -->
        <header class="topbar">
            <div>
                <p class="eyebrow">🌾 La Yarada, Tacna</p>
                <h1>Centro de Control de Riego</h1>
            </div>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap; justify-content:center;">
                <span class="db-status <?= !empty($parcelas) ? 'online' : 'offline' ?>">
                    <?= !empty($parcelas) ? '✅ Base de datos' : '⚠️ Sin conexión' ?>
                </span>
                <div class="weather">
                    <strong><?= e($clima['condicion'] ?? 'Clima simulado') ?></strong>
                    <span><?= e($clima['temperatura_ambiente'] ?? '-') ?>°C - HR <?= e($clima['humedad_relativa'] ?? '-') ?>%</span>
                </div>
            </div>
        </header>

        <!-- ==========================================
        MÉTRICAS
        ========================================== -->
        <section class="grid metrics">
            <article>
                <span>🌱 Parcelas</span>
                <strong><?= count($parcelas_list) ?></strong>
            </article>
            <article>
                <span>📋 Turnos de riego</span>
                <strong><?= count($turnos_list) ?></strong>
            </article>
            <article>
                <span>🚿 Hidrantes disponibles</span>
                <strong><?= $hidrantes_disponibles ?> / <?= count($hidrantes) ?></strong>
            </article>
        </section>

        <!-- ==========================================
        PARCELAS
        ========================================== -->
        <section class="panel">
            <div class="section-title">
                <h2>📋 Parcelas monitoreadas</h2>
                <span><?= !empty($parcelas) ? '📊 Datos desde base de datos' : '📄 Lecturas desde JSON simulado' ?></span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Parcela</th>
                            <th>Cultivo</th>
                            <th>💧 Humedad</th>
                            <th>🌡️ Temperatura</th>
                            <th>Estrés hídrico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcelas_list)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#888; padding:30px;">
                                    No hay parcelas registradas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($parcelas_list as $p): ?>
                                <tr>
                                    <td><?= e($p['id'] ?? '-') ?></td>
                                    <td><strong><?= e($p['nombre']) ?></strong></td>
                                    <td><?= e($p['cultivo']) ?></td>
                                    <td><?= e($p['humedad']) ?>%</td>
                                    <td><?= e($p['temperatura']) ?>°C</td>
                                    <td>
                                        <span class="badge badge-<?= e($p['estres_hidrico'] ?? 'optimo') ?>">
                                            <?= e($p['estres_hidrico'] ?? 'Desconocido') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ==========================================
        TURNOS E HIDRANTES
        ========================================== -->
        <section class="grid two-columns">
            <!-- TURNOS -->
            <article class="panel">
                <div class="section-title">
                    <h2>📅 Turnos de riego</h2>
                    <span>Productor-Consumidor + Monitor</span>
                </div>
                <div class="list">
                    <?php if (empty($turnos_list)): ?>
                        <div class="list-row" style="border-left-color:#888; justify-content:center;">
                            <span style="color:#888;">No hay turnos registrados</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($turnos_list as $t): ?>
                            <div class="list-row" style="border-left-color: <?= $t['estado'] === 'completado' ? '#2e7d32' : ($t['estado'] === 'pendiente' ? '#f57c00' : '#1976d2') ?>;">
                                <strong>#<?= e($t['id']) ?> - Parcela <?= e($t['parcela_id']) ?></strong>
                                <span>
                                    <?= e($t['hidrante_id'] ?? 'Sin asignar') ?> - 
                                    <?= e($t['duracion_minutos']) ?> min - 
                                    <?= e($t['estado']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <!-- HIDRANTES -->
            <article class="panel">
                <div class="section-title">
                    <h2>🚿 Hidrantes</h2>
                    <span>Capacidad simultánea</span>
                </div>
                <div class="list">
                    <?php if (empty($hidrantes)): ?>
                        <div class="list-row" style="border-left-color:#888; justify-content:center;">
                            <span style="color:#888;">No hay hidrantes registrados</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($hidrantes as $h): ?>
                            <div class="list-row" style="border-left-color: <?= ($h['disponible'] ?? false) ? '#2e7d32' : '#d32f2f' ?>;">
                                <strong><?= e($h['nombre']) ?></strong>
                                <span>
                                    ID <?= e($h['id']) ?> - 
                                    capacidad <?= e($h['capacidad_simultanea'] ?? 1) ?> - 
                                    <?= ($h['disponible'] ?? false) ? '✅ disponible' : '❌ fuera de servicio' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <!-- ==========================================
        CONSOLA
        ========================================== -->
        <section class="panel console">
            <div class="section-title">
                <h2>📡 Consola de sincronización</h2>
                <span>Eventos del sistema</span>
            </div>
            <?php foreach ($eventos as $evento): ?>
                <code><span class="time">[<?= date('H:i:s') ?>]</span> ▶ <?= e($evento) ?></code>
            <?php endforeach; ?>
        </section>
    </main>

    <!-- ==========================================
    SCRIPTS
    ========================================== -->
    <script>
        // Reloj en tiempo real
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('es-ES', { hour12: false });
            // Actualizar hora en la consola si existe
            document.querySelectorAll('.console code .time').forEach(el => {
                el.textContent = '[' + time + ']';
            });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>