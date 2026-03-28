<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiarTexto($_POST['alumno_nombre'] ?? '');

    if ($nombre !== '') {
        $_SESSION['alumno_nombre'] = $nombre;
        header('Location: examen.php');
        exit;
    }

    $error = 'Debes escribir tu nombre antes de empezar.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen final de Python</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="contenedor">
        <div class="card">
            <h1>Examen final de Python</h1>
            <p>Temas incluidos: variables, funciones, listas, diccionarios, JSON y archivos.</p>
            <p>El examen usa temporizador, pantalla completa y control de incidencias.</p>

            <?php if (!empty($error ?? '')): ?>
                <div class="alerta" style="display:block;"><?= esc($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <label for="alumno_nombre">Nombre del alumno</label>
                <input type="text" id="alumno_nombre" name="alumno_nombre" required>
                <button type="submit">Empezar examen</button>
            </form>
        </div>
    </div>
</body>
</html>
