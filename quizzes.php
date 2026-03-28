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

$stmt = $pdo->prepare("
    SELECT 
        q.id,
        q.capitulo,
        q.titulo,
        q.descripcion,
        q.duracion_minutos,
        (
            SELECT MAX(qi.nota)
            FROM quiz_intentos qi
            WHERE qi.quiz_id = q.id
              AND qi.usuario_id = :usuario_id
        ) AS mejor_nota
    FROM quizzes q
    WHERE q.activo = 1
    ORDER BY q.capitulo ASC
");
$stmt->execute(['usuario_id' => $usuarioId]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-6 md:p-8">
        <div class="flex justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold">Quizzes por capítulo</h1>
                <p class="text-slate-400 mt-2">Hola <?= htmlspecialchars($nombreUsuario) ?>, aquí puedes evaluar tu progreso.</p>
            </div>

            <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                ← Dashboard
            </a>
        </div>

        <div class="mt-8 grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-sky-500 transition">
                    <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                        Capítulo <?= htmlspecialchars($quiz['capitulo']) ?>
                    </p>

                    <h2 class="text-xl font-bold mt-2">
                        <?= htmlspecialchars($quiz['titulo']) ?>
                    </h2>

                    <p class="text-slate-400 mt-3 text-sm">
                        <?= htmlspecialchars($quiz['descripcion'] ?? 'Quiz del capítulo.') ?>
                    </p>

                    <div class="mt-4 space-y-2 text-sm text-slate-300">
                        <p><span class="text-slate-400">Duración:</span> <?= (int)$quiz['duracion_minutos'] ?> min</p>
                        <p>
                            <span class="text-slate-400">Mejor nota:</span>
                            <?= $quiz['mejor_nota'] !== null ? number_format((float)$quiz['mejor_nota'], 1) . '/20' : 'Sin intento' ?>
                        </p>
                    </div>

                    <div class="mt-5">
                        <a href="quiz.php?id=<?= (int)$quiz['id'] ?>" class="inline-block bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                            Empezar quiz
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>