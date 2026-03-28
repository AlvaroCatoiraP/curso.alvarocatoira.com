<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    die('Usuario no identificado.');
}

$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quizId <= 0) {
    die('Quiz no válido.');
}

$stmt = $pdo->prepare("
    SELECT id, capitulo, titulo, descripcion, duracion_minutos
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
    SELECT id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, orden
    FROM quiz_preguntas
    WHERE quiz_id = :quiz_id
    ORDER BY orden ASC, id ASC
");
$stmt->execute(['quiz_id' => $quizId]);
$preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$preguntas) {
    die('Este quiz no tiene preguntas todavía.');
}

$inicioTimestamp = time();
$duracionSegundos = ((int)$quiz['duracion_minutos']) * 60;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-5xl mx-auto p-6 md:p-8">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div>
                <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                    Capítulo <?= htmlspecialchars($quiz['capitulo']) ?>
                </p>
                <h1 class="text-3xl font-bold mt-1"><?= htmlspecialchars($quiz['titulo']) ?></h1>
                <p class="text-slate-400 mt-2"><?= htmlspecialchars($quiz['descripcion'] ?? '') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl px-5 py-4 text-center">
                <p class="text-slate-400 text-sm">Tiempo restante</p>
                <p id="timer" class="text-2xl font-bold mt-1"><?= (int)$quiz['duracion_minutos'] ?>:00</p>
            </div>
        </div>

        <form id="quizForm" method="POST" action="procesar_quiz.php" class="mt-8 space-y-6">
            <input type="hidden" name="quiz_id" value="<?= (int)$quiz['id'] ?>">
            <input type="hidden" name="inicio_timestamp" value="<?= (int)$inicioTimestamp ?>">
            <input type="hidden" name="duracion_segundos" value="<?= (int)$duracionSegundos ?>">

            <?php foreach ($preguntas as $index => $pregunta): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold">
                        Pregunta <?= $index + 1 ?>
                    </h2>
                    <p class="text-slate-200 mt-3">
                        <?= htmlspecialchars($pregunta['pregunta']) ?>
                    </p>

                    <div class="mt-5 space-y-3">
                        <?php foreach (['a', 'b', 'c', 'd'] as $letra): ?>
                            <label class="flex items-start gap-3 bg-slate-950 border border-slate-800 rounded-xl p-4 cursor-pointer hover:border-sky-500">
                                <input
                                    type="radio"
                                    name="respuestas[<?= (int)$pregunta['id'] ?>]"
                                    value="<?= strtoupper($letra) ?>"
                                    class="mt-1"
                                >
                                <span>
                                    <span class="font-semibold"><?= strtoupper($letra) ?>.</span>
                                    <?= htmlspecialchars($pregunta['opcion_' . $letra]) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="flex justify-end">
                <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-6 py-3 rounded-xl font-semibold">
                    Entregar quiz
                </button>
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('quizForm');
        const timerElement = document.getElementById('timer');
        let remaining = <?= (int)$duracionSegundos ?>;

        function formatTime(seconds) {
            const min = Math.floor(seconds / 60);
            const sec = seconds % 60;
            return `${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
        }

        timerElement.textContent = formatTime(remaining);

        const interval = setInterval(() => {
            remaining--;
            timerElement.textContent = formatTime(Math.max(remaining, 0));

            if (remaining <= 0) {
                clearInterval(interval);
                form.submit();
            }
        }, 1000);
    </script>
</body>
</html>