<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$nombreUsuario = $_SESSION['user_nombre'] ?? 'Estudiante';

if (!$usuarioId) {
    die('Usuario no identificado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Acceso no válido.');
}

$quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
$inicioTimestamp = isset($_POST['inicio_timestamp']) ? (int)$_POST['inicio_timestamp'] : 0;
$duracionSegundos = isset($_POST['duracion_segundos']) ? (int)$_POST['duracion_segundos'] : 0;
$respuestasUsuario = $_POST['respuestas'] ?? [];

if ($quizId <= 0 || $inicioTimestamp <= 0 || $duracionSegundos <= 0) {
    die('Datos incompletos.');
}

$stmt = $pdo->prepare("
    SELECT id, titulo, capitulo, duracion_minutos
    FROM quizzes
    WHERE id = :id
      AND activo = 1
    LIMIT 1
");
$stmt->execute(['id' => $quizId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die('Quiz no encontrado.');
}

$stmt = $pdo->prepare("
    SELECT id, pregunta, respuesta_correcta, explicacion
    FROM quiz_preguntas
    WHERE quiz_id = :quiz_id
    ORDER BY orden ASC, id ASC
");
$stmt->execute(['quiz_id' => $quizId]);
$preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$preguntas) {
    die('No hay preguntas para este quiz.');
}

$ahora = time();
$fueraDeTiempo = ($ahora - $inicioTimestamp) > $duracionSegundos;

$totalPreguntas = count($preguntas);
$correctas = 0;
$detalle = [];

foreach ($preguntas as $pregunta) {
    $preguntaId = (int)$pregunta['id'];
    $respuestaAlumno = strtoupper(trim($respuestasUsuario[$preguntaId] ?? ''));
    $respuestaCorrecta = strtoupper(trim($pregunta['respuesta_correcta']));

    $esCorrecta = ($respuestaAlumno !== '' && $respuestaAlumno === $respuestaCorrecta);

    if ($esCorrecta) {
        $correctas++;
    }

    $detalle[] = [
        'pregunta' => $pregunta['pregunta'],
        'respuesta_alumno' => $respuestaAlumno !== '' ? $respuestaAlumno : 'Sin responder',
        'respuesta_correcta' => $respuestaCorrecta,
        'es_correcta' => $esCorrecta,
        'explicacion' => $pregunta['explicacion'] ?? ''
    ];
}

$nota = $totalPreguntas > 0 ? round(($correctas / $totalPreguntas) * 20, 2) : 0.00;

$iniciadoEn = date('Y-m-d H:i:s', $inicioTimestamp);
$finalizadoEn = date('Y-m-d H:i:s', $ahora);

$stmt = $pdo->prepare("
    INSERT INTO quiz_intentos
    (usuario_id, quiz_id, nota, correctas, total_preguntas, iniciado_en, finalizado_en, tiempo_limite_segundos, enviado_fuera_de_tiempo)
    VALUES
    (:usuario_id, :quiz_id, :nota, :correctas, :total_preguntas, :iniciado_en, :finalizado_en, :tiempo_limite_segundos, :enviado_fuera_de_tiempo)
");
$stmt->execute([
    'usuario_id' => $usuarioId,
    'quiz_id' => $quizId,
    'nota' => $nota,
    'correctas' => $correctas,
    'total_preguntas' => $totalPreguntas,
    'iniciado_en' => $iniciadoEn,
    'finalizado_en' => $finalizadoEn,
    'tiempo_limite_segundos' => $duracionSegundos,
    'enviado_fuera_de_tiempo' => $fueraDeTiempo ? 1 : 0
]);

// Guardar también en tu tabla resultados actual, para que siga apareciendo en el dashboard
$stmt = $pdo->prepare("
    INSERT INTO resultados (alumno_nombre, nota, advertencias, fecha)
    VALUES (:alumno_nombre, :nota, :advertencias, NOW())
");
$stmt->execute([
    'alumno_nombre' => $nombreUsuario,
    'nota' => $nota,
    'advertencias' => $fueraDeTiempo ? 1 : 0
]);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado del quiz</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-5xl mx-auto p-6 md:p-8">
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8">
            <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                Resultado
            </p>
            <h1 class="text-3xl font-bold mt-2">
                <?= htmlspecialchars($quiz['titulo']) ?>
            </h1>

            <div class="grid md:grid-cols-4 gap-4 mt-8">
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <p class="text-slate-400 text-sm">Nota final</p>
                    <p class="text-3xl font-bold mt-2"><?= number_format($nota, 2) ?>/20</p>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <p class="text-slate-400 text-sm">Correctas</p>
                    <p class="text-3xl font-bold mt-2"><?= (int)$correctas ?>/<?= (int)$totalPreguntas ?></p>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <p class="text-slate-400 text-sm">Capítulo</p>
                    <p class="text-3xl font-bold mt-2"><?= (int)$quiz['capitulo'] ?></p>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                    <p class="text-slate-400 text-sm">Tiempo</p>
                    <p class="text-lg font-bold mt-2"><?= $fueraDeTiempo ? 'Fuera de tiempo' : 'Dentro del tiempo' ?></p>
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <?php foreach ($detalle as $index => $item): ?>
                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5">
                        <h2 class="text-lg font-bold">Pregunta <?= $index + 1 ?></h2>
                        <p class="text-slate-200 mt-2"><?= htmlspecialchars($item['pregunta']) ?></p>

                        <div class="mt-4 text-sm space-y-1">
                            <p><span class="text-slate-400">Tu respuesta:</span> <?= htmlspecialchars($item['respuesta_alumno']) ?></p>
                            <p><span class="text-slate-400">Respuesta correcta:</span> <?= htmlspecialchars($item['respuesta_correcta']) ?></p>
                            <p class="<?= $item['es_correcta'] ? 'text-emerald-300' : 'text-red-300' ?>">
                                <?= $item['es_correcta'] ? 'Correcta' : 'Incorrecta' ?>
                            </p>
                            <?php if (!empty($item['explicacion'])): ?>
                                <p class="text-slate-400 mt-2"><?= htmlspecialchars($item['explicacion']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 flex gap-3">
                <a href="quizzes.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    Volver a quizzes
                </a>
                <a href="dashboard.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                    Ir al dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>