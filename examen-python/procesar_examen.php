<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funciones.php';

exigirAutenticacion();

if (!isset($_SESSION['preguntas_examen']) || !is_array($_SESSION['preguntas_examen'])) {
    header('Location: index.php');
    exit;
}

$preguntas = $_SESSION['preguntas_examen'];
$respuestasUsuario = $_POST['respuesta'] ?? [];
$alumno = obtenerNombreAlumno();

$resultado = calcularNota($preguntas, $respuestasUsuario);
$advertencias = contarAdvertencias($pdo, $alumno);

$stmt = $pdo->prepare(
    "INSERT INTO resultados (alumno_nombre, nota, correctas, total, advertencias)
     VALUES (:alumno, :nota, :correctas, :total, :advertencias)"
);
$stmt->execute([
    ':alumno' => $alumno,
    ':nota' => $resultado['nota'],
    ':correctas' => $resultado['correctas'],
    ':total' => $resultado['total'],
    ':advertencias' => $advertencias,
]);

$_SESSION['resultado_examen'] = [
    'alumno' => $alumno,
    'nota' => $resultado['nota'],
    'correctas' => $resultado['correctas'],
    'total' => $resultado['total'],
    'advertencias' => $advertencias,
    'detalle' => $resultado['detalle'],
];

unset($_SESSION['preguntas_examen'], $_SESSION['examen_iniciado_en']);

header('Location: resultado.php');
exit;
