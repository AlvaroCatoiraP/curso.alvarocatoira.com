<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuario_id = (int)($_SESSION['user_id'] ?? 0);
$langue = $lang ?? 'es';

if (!in_array($langue, ['es', 'fr'], true)) {
    $langue = 'es';
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function extractLines(?string $text): array
{
    $text = trim((string)$text);

    if ($text === '') {
        return [];
    }

    $lines = preg_split('/\R/u', $text) ?: [];
    $result = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        $line = preg_replace('/^\d+[\).\-\:]\s*/u', '', $line);
        $line = preg_replace('/^[-*•]\s*/u', '', $line);

        if ($line !== '') {
            $result[] = $line;
        }
    }

    return $result;
}

$t = [
    'es' => [
        'page_title' => 'Prácticas',
        'practices' => 'Prácticas',
        'python_basic' => 'Ejercicios de Python',
        'intro_text' => 'Ejercicios prácticos organizados por capítulo.',
        'course_exercises' => 'Ejercicios del capítulo',
        'course_exercises_text' => 'Cada ejercicio incluye enunciado, zona de código y solución orientativa.',
        'language' => 'Idioma',
        'view_course' => 'Ver curso',
        'dashboard' => 'Dashboard',
        'progress' => 'Tu progreso',
        'completed' => 'ejercicios completados',
        'exercise' => 'Ejercicio',
        'quiz' => 'Mini quiz',
        'show_solution' => 'Ver solución',
        'hide_solution' => 'Ocultar solución',
        'mark_done' => 'Marcar como completado',
        'mark_undone' => 'Marcar como no completado',
        'done' => 'Completado',
        'pending' => 'Pendiente',
        'write_here' => 'Escribe tu código aquí...',
        'no_chapter' => 'Capítulo no encontrado',
        'no_chapter_text' => 'No se pudo cargar el capítulo solicitado.',
        'back_course' => 'Volver al curso',
        'solution_hint' => '# Escribe aquí tu solución en Python',
        'quiz_hint' => '# Escribe aquí tu respuesta o ejemplo'
    ],
    'fr' => [
        'page_title' => 'Exercices',
        'practices' => 'Exercices',
        'python_basic' => 'Exercices Python',
        'intro_text' => 'Exercices pratiques organisés par chapitre.',
        'course_exercises' => 'Exercices du chapitre',
        'course_exercises_text' => 'Chaque exercice contient un énoncé, une zone de code et une solution indicative.',
        'language' => 'Langue',
        'view_course' => 'Voir le cours',
        'dashboard' => 'Dashboard',
        'progress' => 'Votre progression',
        'completed' => 'exercices terminés',
        'exercise' => 'Exercice',
        'quiz' => 'Mini quiz',
        'show_solution' => 'Voir la solution',
        'hide_solution' => 'Cacher la solution',
        'mark_done' => 'Marquer comme terminé',
        'mark_undone' => 'Marquer comme non terminé',
        'done' => 'Terminé',
        'pending' => 'En attente',
        'write_here' => 'Écris ton code ici...',
        'no_chapter' => 'Chapitre introuvable',
        'no_chapter_text' => 'Le chapitre demandé n’a pas pu être chargé.',
        'back_course' => 'Retour au cours',
        'solution_hint' => '# Écris ici ta solution en Python',
        'quiz_hint' => '# Écris ici ta réponse ou ton exemple'
    ]
];

$tr = $t[$langue] ?? $t['es'];

$chapCode = trim((string)($_GET['chap'] ?? ''));

if ($chapCode === '') {
    header('Location: curso.php?lang=' . urlencode($langue));
    exit;
}

$sqlChapitre = "
    SELECT
        c.id,
        c.code,
        c.ordre_affichage,
        c.visible,
        CASE
            WHEN :langue = 'fr' THEN COALESCE(c.titre_fr, c.titre_es, '')
            ELSE COALESCE(c.titre_es, c.titre_fr, '')
        END AS titulo,
        cc.ejercicios_guiados,
        cc.mini_quiz
    FROM chapitres c
    LEFT JOIN chapitres_contenu cc
        ON cc.chapitre_id = c.id
        AND cc.langue = :langue
    WHERE c.code = :code
      AND c.visible = 1
    LIMIT 1
";


$stmtChapitre = $pdo->prepare($sqlChapitre);
$stmtChapitre->execute([
    'langue' => $langue,
    'code' => $chapCode
]);
$chapitre = $stmtChapitre->fetch(PDO::FETCH_ASSOC);

if (!$chapitre) {
    $chapitre = null;
    $items = [];
} else {
    $exerciseLines = extractLines($chapitre['ejercicios_guiados'] ?? '');
    $quizLines = extractLines($chapitre['mini_quiz'] ?? '');

    $items = [];

    foreach ($exerciseLines as $index => $line) {
        $num = $index + 1;
        $items[] = [
            'id' => $chapitre['code'] . '_ex' . $num,
            'type' => 'exercise',
            'number' => $num,
            'title' => $tr['exercise'] . ' ' . $num,
            'statement' => $line,
            'solution' => $tr['solution_hint'] . "\n",
            'color' => 'text-emerald-300'
        ];
    }

    foreach ($quizLines as $index => $line) {
        $num = $index + 1;
        $items[] = [
            'id' => $chapitre['code'] . '_quiz' . $num,
            'type' => 'quiz',
            'number' => $num,
            'title' => $tr['quiz'] . ' ' . $num,
            'statement' => $line,
            'solution' => $tr['quiz_hint'] . "\n",
            'color' => 'text-sky-300'
        ];
    }
}

$itemIds = array_map(fn($item) => $item['id'], $items);

$progreso_db = [];
$codigo_db = [];

if (!empty($itemIds)) {
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

    $sqlProgreso = "SELECT ejercicio_codigo, completado
                    FROM progreso
                    WHERE usuario_id = ?
                      AND ejercicio_codigo IN ($placeholders)";
    $stmtProgreso = $pdo->prepare($sqlProgreso);
    $stmtProgreso->execute(array_merge([$usuario_id], $itemIds));
    $progreso_db = $stmtProgreso->fetchAll(PDO::FETCH_KEY_PAIR);

    $sqlCodigo = "SELECT ejercicio_codigo, contenido
                  FROM codigo_usuario
                  WHERE usuario_id = ?
                    AND ejercicio_codigo IN ($placeholders)";
    $stmtCodigo = $pdo->prepare($sqlCodigo);
    $stmtCodigo->execute(array_merge([$usuario_id], $itemIds));
    $codigo_db = $stmtCodigo->fetchAll(PDO::FETCH_KEY_PAIR);
}

$totalItems = count($items);
$completedCount = 0;

foreach ($items as $item) {
    $key = $item['id'];
    if (isset($progreso_db[$key]) && (int)$progreso_db[$key] === 1) {
        $completedCount++;
    }
}

$percentage = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="<?= h($langue) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($tr['page_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
    <div class="min-h-screen flex">
        <aside class="w-72 bg-slate-900 border-r border-slate-800 p-6 sticky top-0 h-screen overflow-y-auto hidden md:block">
            <div class="mb-8">
                <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest"><?= h($tr['practices']) ?></p>
                <h1 class="text-2xl font-bold mt-2"><?= h($chapitre['titulo'] ?? $tr['python_basic']) ?></h1>
                <p class="text-slate-400 text-sm mt-3">
                    <?= h($tr['intro_text']) ?>
                </p>
            </div>

            <div class="mb-8 bg-slate-950 border border-slate-800 rounded-2xl p-4">
                <p class="text-sm text-slate-400"><?= h($tr['progress']) ?></p>
                <div class="mt-3 w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                    <div id="sidebarProgressBar" class="h-full bg-sky-500 rounded-full" style="width: <?= $percentage ?>%"></div>
                </div>
                <p id="sidebarProgressText" class="text-sky-300 text-sm mt-3 font-semibold">
                    <?= $completedCount ?> / <?= $totalItems ?> <?= h($tr['completed']) ?>
                </p>
            </div>

            <nav class="space-y-2 text-sm">
                <?php foreach ($items as $index => $item): ?>
                    <a href="#<?= h($item['id']) ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 transition">
                        <?= $index + 1 ?>. <?= h($item['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur sticky top-0 z-10">
                <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest"><?= h($tr['practices']) ?></p>
                        <h1 class="text-3xl md:text-4xl font-bold mt-2">
                            <?= h($chapitre['titulo'] ?? $tr['no_chapter']) ?>
                        </h1>
                        <p class="text-slate-400 mt-3 max-w-3xl">
                            <?= h($tr['course_exercises_text']) ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 flex items-center gap-3">
                            <span class="text-slate-300 text-sm"><?= h($tr['language']) ?>:</span>
                            <a href="?lang=es&chap=<?= urlencode($chapCode) ?>" class="text-sm px-3 py-1 rounded-lg <?= $langue === 'es' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">ES</a>
                            <a href="?lang=fr&chap=<?= urlencode($chapCode) ?>" class="text-sm px-3 py-1 rounded-lg <?= $langue === 'fr' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">FR</a>
                        </div>

                        <a href="curso.php?lang=<?= urlencode($langue) ?>&chap=<?= urlencode($chapCode) ?>" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                            <?= h($tr['view_course']) ?>
                        </a>

                        <a href="dashboard.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                            <?= h($tr['dashboard']) ?>
                        </a>
                    </div>
                </div>
            </header>

            <div class="max-w-6xl mx-auto px-6 py-10">
                <?php if (!$chapitre): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
                        <h2 class="text-2xl font-bold"><?= h($tr['no_chapter']) ?></h2>
                        <p class="text-slate-400 mt-3"><?= h($tr['no_chapter_text']) ?></p>
                        <a href="curso.php?lang=<?= urlencode($langue) ?>" class="inline-block mt-5 bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                            <?= h($tr['back_course']) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="mb-8 bg-slate-900 border border-slate-800 rounded-2xl p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold"><?= h($tr['progress']) ?></h2>
                                <p id="mainProgressText" class="text-slate-400 mt-2">
                                    <?= $completedCount ?> / <?= $totalItems ?> <?= h($tr['completed']) ?>
                                </p>
                            </div>
                            <div class="min-w-[180px] text-right">
                                <p id="mainProgressPercent" class="text-3xl font-bold text-sky-400"><?= $percentage ?>%</p>
                            </div>
                        </div>
                        <div class="mt-4 w-full h-4 bg-slate-800 rounded-full overflow-hidden">
                            <div id="mainProgressBar" class="h-full bg-sky-500 rounded-full" style="width: <?= $percentage ?>%"></div>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-6">
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $itemKey = $item['id'];
                            $isCompleted = isset($progreso_db[$itemKey]) && (int)$progreso_db[$itemKey] === 1;
                            $existingCode = $codigo_db[$itemKey] ?? '';
                            ?>
                            <section id="<?= h($itemKey) ?>" class="exercise-card bg-slate-900 border border-slate-800 rounded-2xl p-6" data-exercise="<?= h($itemKey) ?>">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs uppercase tracking-widest text-sky-400 font-semibold">
                                            <?= h($item['title']) ?>
                                        </p>
                                        <h2 class="text-xl font-bold mt-2"><?= h($item['statement']) ?></h2>
                                    </div>

                                    <span class="exercise-status text-xs px-3 py-1 rounded-full border <?= $isCompleted ? 'border-emerald-700 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 text-slate-300' ?>">
                                        <?= $isCompleted ? h($tr['done']) : h($tr['pending']) ?>
                                    </span>
                                </div>

                                <textarea
                                    data-exercise="<?= h($itemKey) ?>"
                                    class="code-editor w-full mt-5 min-h-[150px] rounded-2xl bg-slate-950 border border-slate-700 p-4 font-mono text-sm text-slate-100 focus:outline-none focus:border-sky-500"
                                    placeholder="<?= h($tr['write_here']) ?>"
                                ><?= h($existingCode) ?></textarea>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        onclick="toggleSolution('sol<?= $index + 1 ?>', this)"
                                        class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold"
                                    >
                                        <?= h($tr['show_solution']) ?>
                                    </button>

                                    <button
                                        type="button"
                                        class="progress-btn px-4 py-2 rounded-xl font-semibold <?= $isCompleted ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-slate-700 hover:bg-slate-600' ?>"
                                        data-exercise="<?= h($itemKey) ?>"
                                        data-completed="<?= $isCompleted ? '1' : '0' ?>"
                                    >
                                        <?= $isCompleted ? h($tr['mark_undone']) : h($tr['mark_done']) ?>
                                    </button>
                                </div>

                                <div id="sol<?= $index + 1 ?>" class="hidden mt-5 bg-slate-950 border border-slate-800 rounded-2xl p-4">
                                    <pre class="overflow-x-auto <?= h($item['color']) ?>"><code><?= h($item['solution']) ?></code></pre>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const totalExercises = <?= (int)$totalItems ?>;

        function toggleSolution(id, button) {
            const element = document.getElementById(id);
            element.classList.toggle('hidden');

            const isHidden = element.classList.contains('hidden');
            const lang = document.documentElement.lang;

            if (lang === 'fr') {
                button.textContent = isHidden ? 'Voir la solution' : 'Cacher la solution';
            } else {
                button.textContent = isHidden ? 'Ver solución' : 'Ocultar solución';
            }
        }

        function updateProgressDisplay() {
            const buttons = document.querySelectorAll('.progress-btn');
            let completed = 0;

            buttons.forEach(button => {
                if (button.dataset.completed === '1') {
                    completed++;
                }
            });

            const percent = totalExercises > 0 ? Math.round((completed / totalExercises) * 100) : 0;
            const lang = document.documentElement.lang;

            const mainText = document.getElementById('mainProgressText');
            const mainPercent = document.getElementById('mainProgressPercent');
            const mainBar = document.getElementById('mainProgressBar');
            const sidebarText = document.getElementById('sidebarProgressText');
            const sidebarBar = document.getElementById('sidebarProgressBar');

            if (mainText) {
                mainText.textContent = lang === 'fr'
                    ? `${completed} / ${totalExercises} exercices terminés`
                    : `${completed} / ${totalExercises} ejercicios completados`;
            }

            if (sidebarText) {
                sidebarText.textContent = lang === 'fr'
                    ? `${completed} / ${totalExercises} exercices terminés`
                    : `${completed} / ${totalExercises} ejercicios completados`;
            }

            if (mainPercent) {
                mainPercent.textContent = `${percent}%`;
            }

            if (mainBar) {
                mainBar.style.width = `${percent}%`;
            }

            if (sidebarBar) {
                sidebarBar.style.width = `${percent}%`;
            }
        }

        document.querySelectorAll('.code-editor').forEach(textarea => {
            let timeout = null;

            textarea.addEventListener('input', function () {
                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('ejercicio_codigo', this.dataset.exercise);
                    formData.append('contenido', this.value);

                    fetch('guardar_codigo.php', {
                        method: 'POST',
                        body: formData
                    }).catch(error => console.error(error));
                }, 500);
            });
        });

        document.querySelectorAll('.progress-btn').forEach(button => {
            button.addEventListener('click', function () {
                const exercise = this.dataset.exercise;
                const current = this.dataset.completed === '1' ? 1 : 0;
                const next = current === 1 ? 0 : 1;
                const lang = document.documentElement.lang;
                const card = this.closest('.exercise-card');
                const statusBadge = card.querySelector('.exercise-status');

                const formData = new FormData();
                formData.append('ejercicio_codigo', exercise);
                formData.append('completado', next);

                fetch('guardar_progreso.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.dataset.completed = String(next);

                        if (next === 1) {
                            this.classList.remove('bg-slate-700', 'hover:bg-slate-600');
                            this.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                            this.textContent = lang === 'fr'
                                ? 'Marquer comme non terminé'
                                : 'Marcar como no completado';

                            statusBadge.textContent = lang === 'fr' ? 'Terminé' : 'Completado';
                            statusBadge.className = 'exercise-status text-xs px-3 py-1 rounded-full border border-emerald-700 bg-emerald-500/10 text-emerald-300';
                        } else {
                            this.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                            this.classList.add('bg-slate-700', 'hover:bg-slate-600');
                            this.textContent = lang === 'fr'
                                ? 'Marquer comme terminé'
                                : 'Marcar como completado';

                            statusBadge.textContent = lang === 'fr' ? 'En attente' : 'Pendiente';
                            statusBadge.className = 'exercise-status text-xs px-3 py-1 rounded-full border border-slate-700 text-slate-300';
                        }

                        updateProgressDisplay();
                    }
                })
                .catch(error => console.error(error));
            });
        });

        updateProgressDisplay();
    </script>
</body>
</html>