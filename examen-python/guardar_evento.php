<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

exigirAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$evento = limpiarTexto($_POST['evento'] ?? '');

if ($evento === '') {
    http_response_code(422);
    exit;
}

registrarEvento($pdo, obtenerNombreAlumno(), $evento);
echo 'ok';
