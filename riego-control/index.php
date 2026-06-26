<?php

require_once __DIR__ . '/app/Controllers/ParcelaController.php';
require_once __DIR__ . '/app/Controllers/RiegoController.php';

$parcelaController = new ParcelaController();
$riegoController = new RiegoController();

$parcelas = $parcelaController->listar();
$clima = $parcelaController->clima();
$plan = $riegoController->planificar();
$turnos = $plan['turnos'];
$hidrantes = $plan['hidrantes'];
$eventos = $plan['eventos'];

require __DIR__ . '/app/Views/dashboard.php';
