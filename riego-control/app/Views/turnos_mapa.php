<?php
// turnos_mapa.php - Mapa de sectores para turnos de riego (TEMA OSCURO)

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../Models/Parcela.php';
require_once __DIR__ . '/../Models/Sensor.php';
require_once __DIR__ . '/../Models/TurnoRiego.php';
require_once __DIR__ . '/../../backend/algorithms/ProductorConsumidor.php';
require_once __DIR__ . '/../../backend/algorithms/Monitor.php';
require_once __DIR__ . '/../../backend/monitor/Semaforo.php';

// Obtener datos
$parcelas = Parcela::all();
$sensores = Sensor::all();
$turnos = TurnoRiego::all();

// Configuración de sectores
$sectores = [
    'Norte' => ['color' => '#4CAF50', 'icon' => '⬆️'],
    'Sur' => ['color' => '#2196F3', 'icon' => '⬇️'],
    'Este' => ['color' => '#FF9800', 'icon' => '➡️'],
    'Oeste' => ['color' => '#9C27B0', 'icon' => '⬅️'],
    'Centro' => ['color' => '#F44336', 'icon' => '⭐']
];

// Asignar parcelas a sectores (simulado - puedes personalizar)
$sectores_parcelas = [
    'Norte' => [],
    'Sur' => [],
    'Este' => [],
    'Oeste' => [],
    'Centro' => []
];

foreach ($parcelas as $p) {
    $sector = match($p->getId() % 5) {
        0 => 'Norte',
        1 => 'Sur',
        2 => 'Este',
        3 => 'Oeste',
        4 => 'Centro',
        default => 'Centro'
    };
    $sectores_parcelas[$sector][] = $p;
}

function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🗺️ Mapa de Turnos - La Yarada</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           TEMA OSCURO - IGUAL QUE INDEX.HTML
           ============================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0d1117;
            color: #f0f6fc;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container { max-width: 1400px; margin: 0 auto; }

        /* HEADER */
        .header {
            background: #161b22;
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
            margin-bottom: 24px;
            border: 1px solid #30363d;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #f0f6fc;
        }
        .header h1 span { font-size: 28px; }
        .header .stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
            flex-wrap: wrap;
        }
        .header .stats .stat {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            background: #1c2333;
            border: 1px solid #30363d;
            color: #8b949e;
        }
        .header .stats .stat strong {
            color: #f0f6fc;
        }
        .stat .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-regando { background: #4CAF50; }
        .dot-espera { background: #FF9800; }
        .dot-libre { background: #9E9E9E; }
        .dot-critico { background: #F44336; }

        /* MAPA DE SECTORES */
        .mapa-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .sector-card {
            background: #1c2333;
            border-radius: 16px;
            border: 1px solid #30363d;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 200px;
        }
        .sector-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
            border-color: #58a6ff;
        }
        .sector-card .sector-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #30363d;
        }
        .sector-card .sector-header h2 {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f0f6fc;
        }
        .sector-card .sector-header .badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .badge-activo { background: #1a3a1a; color: #4CAF50; }
        .badge-espera { background: #3a2a1a; color: #FF9800; }
        .badge-libre { background: #1a1a1a; color: #9E9E9E; }
        .badge-critico { background: #3a1a1a; color: #F44336; }

        /* Parcelas dentro del sector */
        .parcelas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
        }

        .parcela-item {
            background: #0d1117;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            border: 2px solid #30363d;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        .parcela-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        .parcela-item .nombre {
            font-size: 13px;
            font-weight: 600;
            color: #f0f6fc;
        }
        .parcela-item .cultivo {
            font-size: 11px;
            color: #8b949e;
        }
        .parcela-item .estado-icon {
            font-size: 20px;
            margin-top: 4px;
        }
        .parcela-item .estado-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 10px;
            margin-top: 4px;
            display: inline-block;
        }
        .parcela-item .humedad {
            font-size: 11px;
            font-weight: 600;
            color: #8b949e;
        }

        /* Estados - Tema oscuro */
        .estado-regando {
            border-color: #4CAF50;
            background: #0d1f0d;
        }
        .estado-regando .estado-label {
            background: #4CAF50;
            color: white;
        }
        .estado-espera {
            border-color: #FF9800;
            background: #1f1a0d;
        }
        .estado-espera .estado-label {
            background: #FF9800;
            color: white;
        }
        .estado-libre {
            border-color: #30363d;
            background: #0d0d0d;
        }
        .estado-libre .estado-label {
            background: #9E9E9E;
            color: white;
        }
        .estado-critico {
            border-color: #F44336;
            background: #1f0d0d;
            animation: pulse-critico 2s infinite;
        }
        .estado-critico .estado-label {
            background: #F44336;
            color: white;
        }
        @keyframes pulse-critico {
            0%, 100% { border-color: #F44336; }
            50% { border-color: #ff8a80; }
        }

        /* PANEL DE TURNOS ACTIVOS */
        .panel {
            background: #1c2333;
            border-radius: 12px;
            border: 1px solid #30363d;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .panel-header h2 {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f0f6fc;
        }
        .panel-header span {
            color: #8b949e;
            font-size: 12px;
        }

        /* LISTA DE TURNOS */
        .turnos-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
        }
        .turno-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: #0d1117;
            border-radius: 8px;
            border-left: 4px solid #4CAF50;
        }
        .turno-item .info { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        .turno-item .info strong { font-size: 14px; color: #f0f6fc; }
        .turno-item .info span { font-size: 13px; color: #8b949e; }
        .turno-item .estado {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 12px;
        }
        .estado-completado { background: #1a3a1a; color: #4CAF50; }
        .estado-pendiente { background: #3a2a1a; color: #FF9800; }
        .estado-espera { background: #1a2a3a; color: #2196F3; }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .mapa-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 12px; align-items: stretch; text-align: center; }
            .header .stats { justify-content: center; flex-wrap: wrap; }
            .mapa-grid { grid-template-columns: 1fr; }
            .parcelas-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
        }
        @media (max-width: 480px) {
            .turno-item { flex-direction: column; align-items: flex-start; gap: 8px; }
            .turno-item .info { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- ==========================================
    HEADER
    ========================================== -->
    <header class="header">
        <h1>
            <span>🗺️</span> Mapa de Turnos
            <span style="font-size:14px;color:#8b949e;font-weight:400;">La Yarada, Tacna</span>
        </h1>
        <div class="stats">
            <span class="stat"><span class="dot dot-regando"></span> Regando: <strong id="count-regando">0</strong></span>
            <span class="stat"><span class="dot dot-espera"></span> En espera: <strong id="count-espera">0</strong></span>
            <span class="stat"><span class="dot dot-libre"></span> Libres: <strong id="count-libre">0</strong></span>
            <span class="stat"><span class="dot dot-critico"></span> Crítico: <strong id="count-critico">0</strong></span>
        </div>
    </header>

    <!-- ==========================================
    MAPA DE SECTORES
    ========================================== -->
    <div class="mapa-grid" id="mapaContainer">
        <?php foreach ($sectores as $nombre => $config): 
            $parcelas_sector = $sectores_parcelas[$nombre] ?? [];
            $total = count($parcelas_sector);
            $regando = 0; $espera = 0; $libre = 0; $critico = 0;
            
            foreach ($parcelas_sector as $p) {
                $sensor = Sensor::findByParcelaId($p->getId());
                if (!empty($sensor)) {
                    $humedad = $sensor[0]->getHumedad();
                    $temperatura = $sensor[0]->getTemperatura();
                    if ($humedad < 22 && $temperatura > 28) $critico++;
                    elseif ($humedad < 32 || $temperatura > 30) $espera++;
                    else $libre++;
                }
            }
            
            $estado_clase = $critico > 0 ? 'badge-critico' : ($espera > 0 ? 'badge-espera' : 'badge-libre');
            $estado_texto = $critico > 0 ? '⚠️ Crítico' : ($espera > 0 ? '⏳ En espera' : '✅ Libre');
        ?>
        <div class="sector-card" data-sector="<?= e($nombre) ?>">
            <div class="sector-header">
                <h2>
                    <?= e($config['icon']) ?>
                    <?= e($nombre) ?>
                    <span style="font-size:12px;color:#8b949e;font-weight:400;">(<?= $total ?> parcelas)</span>
                </h2>
                <span class="badge <?= $estado_clase ?>"><?= $estado_texto ?></span>
            </div>
            <div class="parcelas-grid">
                <?php foreach ($parcelas_sector as $p):
                    $sensor = Sensor::findByParcelaId($p->getId());
                    $humedad = !empty($sensor) ? $sensor[0]->getHumedad() : '-';
                    $temperatura = !empty($sensor) ? $sensor[0]->getTemperatura() : '-';
                    
                    if ($humedad !== '-' && $temperatura !== '-') {
                        if ($humedad < 22 && $temperatura > 28) {
                            $estado = 'critico';
                            $icono = '🚨';
                            $label = 'CRÍTICO';
                        } elseif ($humedad < 32 || $temperatura > 30) {
                            $estado = 'espera';
                            $icono = '⏳';
                            $label = 'ESPERA';
                        } else {
                            $estado = 'libre';
                            $icono = '✅';
                            $label = 'LIBRE';
                        }
                    } else {
                        $estado = 'libre';
                        $icono = '✅';
                        $label = 'LIBRE';
                    }
                    
                    // Verificar si tiene turno activo
                    foreach ($turnos as $t) {
                        if ($t->getParcelaId() == $p->getId() && $t->getEstado() === 'en_curso') {
                            $estado = 'regando';
                            $icono = '💧';
                            $label = 'REGANDO';
                            break;
                        }
                    }
                ?>
                <div class="parcela-item estado-<?= $estado ?>" 
                     title="<?= e($p->getNombre()) ?> - <?= $label ?> - Humedad: <?= $humedad ?>% - Temp: <?= $temperatura ?>°C">
                    <div class="nombre"><?= e($p->getNombre()) ?></div>
                    <div class="cultivo"><?= e($p->getCultivo()) ?></div>
                    <div class="estado-icon"><?= $icono ?></div>
                    <div class="estado-label"><?= $label ?></div>
                    <div class="humedad">💧 <?= $humedad ?>%</div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($parcelas_sector)): ?>
                    <div style="grid-column:1/-1;text-align:center;color:#8b949e;padding:20px;font-size:13px;">
                        No hay parcelas en este sector
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ==========================================
    TURNOS ACTIVOS
    ========================================== -->
    <div class="panel">
        <div class="panel-header">
            <h2><span class="material-symbols-outlined">list_alt</span> 📋 Turnos Activos</h2>
            <span>Productor-Consumidor + Monitor</span>
        </div>
        <div class="turnos-list">
            <?php if (empty($turnos)): ?>
                <div style="text-align:center;padding:20px;color:#8b949e;">
                    No hay turnos de riego activos
                </div>
            <?php else: ?>
                <?php foreach ($turnos as $t): 
                    $estado_class = $t->getEstado() === 'completado' ? 'estado-completado' : 
                                    ($t->getEstado() === 'pendiente' ? 'estado-pendiente' : 'estado-espera');
                    $parcela = Parcela::find($t->getParcelaId());
                    $nombre_parcela = $parcela ? $parcela->getNombre() : 'Parcela ' . $t->getParcelaId();
                ?>
                <div class="turno-item">
                    <div class="info">
                        <strong>#<?= e($t->getId()) ?></strong>
                        <span>📍 <?= e($nombre_parcela) ?></span>
                        <span>🚿 <?= e($t->getHidranteId() ?? 'Sin asignar') ?></span>
                        <span>⏱️ <?= e($t->getInicio()) ?></span>
                    </div>
                    <span class="estado <?= $estado_class ?>"><?= e($t->getEstado()) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ==========================================
SCRIPTS
========================================== -->
<script>
    // Contar estados
    document.addEventListener('DOMContentLoaded', function() {
        const items = document.querySelectorAll('.parcela-item');
        let regando = 0, espera = 0, libre = 0, critico = 0;
        
        items.forEach(item => {
            if (item.classList.contains('estado-regando')) regando++;
            else if (item.classList.contains('estado-espera')) espera++;
            else if (item.classList.contains('estado-critico')) critico++;
            else if (item.classList.contains('estado-libre')) libre++;
        });
        
        document.getElementById('count-regando').textContent = regando;
        document.getElementById('count-espera').textContent = espera;
        document.getElementById('count-libre').textContent = libre;
        document.getElementById('count-critico').textContent = critico;
    });
    
    // Auto-refrescar cada 30 segundos
    setTimeout(() => location.reload(), 30000);
</script>
</body>
</html>