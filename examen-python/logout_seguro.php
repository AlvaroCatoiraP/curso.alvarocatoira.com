<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset(
    $_SESSION['alumno_nombre'],
    $_SESSION['preguntas_examen'],
    $_SESSION['resultado_examen'],
    $_SESSION['examen_iniciado_en']
);

header('Location: index.php');
exit;
