<?php

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
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">La Yarada, Tacna</p>
                <h1>Centro de Control de Riego</h1>
            </div>
            <div class="weather">
                <strong><?= e($clima['condicion'] ?? 'Clima simulado') ?></strong>
                <span><?= e($clima['temperatura_ambiente'] ?? '-') ?> C - HR <?= e($clima['humedad_relativa'] ?? '-') ?>%</span>
            </div>
        </header>

        <section class="grid metrics">
            <article>
                <span>Parcelas</span>
                <strong><?= count($parcelas) ?></strong>
            </article>
            <article>
                <span>Turnos de riego</span>
                <strong><?= count($turnos) ?></strong>
            </article>
            <article>
                <span>Hidrantes disponibles</span>
                <strong><?= count(array_filter($hidrantes, fn (array $h): bool => (bool) $h['disponible'])) ?></strong>
            </article>
        </section>

        <section class="panel">
            <div class="section-title">
                <h2>Parcelas monitoreadas</h2>
                <span>Lecturas desde JSON simulado</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Parcela</th>
                            <th>Cultivo</th>
                            <th>Humedad</th>
                            <th>Temperatura</th>
                            <th>Estres hidrico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parcelas as $parcela): ?>
                            <tr>
                                <td><?= e($parcela['nombre']) ?></td>
                                <td><?= e($parcela['cultivo']) ?></td>
                                <td><?= e($parcela['humedad']) ?>%</td>
                                <td><?= e($parcela['temperatura']) ?> C</td>
                                <td><span class="badge badge-<?= e($parcela['estres_hidrico']) ?>"><?= e($parcela['estres_hidrico']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid two-columns">
            <article class="panel">
                <div class="section-title">
                    <h2>Turnos de riego</h2>
                    <span>Productor-Consumidor + Monitor</span>
                </div>
                <div class="list">
                    <?php foreach ($turnos as $turno): ?>
                        <div class="list-row">
                            <strong><?= e($turno['id']) ?> - <?= e($turno['parcela_id']) ?></strong>
                            <span><?= e($turno['hidrante_id']) ?> - <?= e($turno['duracion_minutos']) ?> min - <?= e($turno['estado']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel">
                <div class="section-title">
                    <h2>Hidrantes</h2>
                    <span>Capacidad simultanea</span>
                </div>
                <div class="list">
                    <?php foreach ($hidrantes as $hidrante): ?>
                        <div class="list-row">
                            <strong><?= e($hidrante['nombre']) ?></strong>
                            <span><?= e($hidrante['id']) ?> - capacidad <?= e($hidrante['capacidad_simultanea']) ?> - <?= $hidrante['disponible'] ? 'disponible' : 'fuera de servicio' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel console">
            <div class="section-title">
                <h2>Consola de sincronizacion</h2>
                <span>Eventos simulados</span>
            </div>
            <?php foreach ($eventos as $evento): ?>
                <code><?= e($evento) ?></code>
            <?php endforeach; ?>
        </section>
    </main>
</body>
</html>
