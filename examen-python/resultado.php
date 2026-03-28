<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

exigirAutenticacion();

if (!isset($_SESSION['resultado_examen'])) {
    header('Location: index.php');
    exit;
}

$resultado = $_SESSION['resultado_examen'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado del examen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="contenedor">
        <div class="card">
            <h1>Resultado del examen</h1>

            <div class="fila-info">
                <div class="badge">Alumno: <?= esc($resultado['alumno']) ?></div>
                <div class="badge">Nota: <?= esc((string) $resultado['nota']) ?>/20</div>
                <div class="badge">Correctas: <?= (int) $resultado['correctas'] ?>/<?= (int) $resultado['total'] ?></div>
                <div class="badge">Advertencias: <?= (int) $resultado['advertencias'] ?></div>
            </div>

            <?php if ((int) $resultado['advertencias'] >= 3): ?>
                <p class="resultado-ko">Examen marcado por comportamiento sospechoso.</p>
            <?php else: ?>
                <p class="resultado-ok">Examen finalizado correctamente.</p>
            <?php endif; ?>

            <h2>Detalle</h2>

            <?php foreach ($resultado['detalle'] as $item): ?>
                <div class="pregunta">
                    <span class="tema"><?= esc($item['tema']) ?></span>
                    <h3><?= esc($item['pregunta']) ?></h3>
                    <p>Tu respuesta: <strong><?= esc($item['respuesta_alumno'] !== '' ? strtoupper($item['respuesta_alumno']) : 'Sin responder') ?></strong></p>
                    <p>Respuesta correcta: <strong><?= esc(strtoupper($item['respuesta_correcta'])) ?></strong></p>

                    <?php if ($item['es_correcta']): ?>
                        <p class="resultado-ok">Correcta</p>
                    <?php else: ?>
                        <p class="resultado-ko">Incorrecta</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="acciones">
                <a class="btn" href="index.php">Volver al inicio</a>
                <a class="btn btn-secundario" href="logout_seguro.php">Cerrar sesión del examen</a>
            </div>
        </div>
    </div>
</body>
</html>
