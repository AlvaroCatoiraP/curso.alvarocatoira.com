<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$langue = $lang ?? 'es';
if (!in_array($langue, ['es', 'fr'], true)) {
    $langue = 'es';
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function hasContent(?string $value): bool
{
    return trim((string)$value) !== '';
}

function renderRichText(?string $text): string
{
    $text = trim((string)$text);

    if ($text === '') {
        return '';
    }

    $paragraphs = preg_split('/\R{2,}/u', $text) ?: [];
    $html = '';

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }

        $html .= '<p class="text-slate-300 leading-8 mb-4">' . nl2br(h($paragraph)) . '</p>';
    }

    return $html;
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

function buildFallbackExercises(array $chapitre, string $langue): array
{
    $titulo = trim((string)($chapitre['titulo'] ?? ''));

    if ($langue === 'fr') {
        return [
            "Explique avec tes mots le concept « {$titulo} » et donne un exemple simple.",
            "Réalise un petit exercice pratique lié à « {$titulo} »."
        ];
    }

    return [
        "Explica con tus palabras el concepto « {$titulo} » y da un ejemplo simple.",
        "Haz un pequeño ejercicio práctico relacionado con « {$titulo} »."
    ];
}

function extractTwoExercises(array $chapitre, string $langue): array
{
    $exercises = [];

    foreach (['ejercicios_guiados', 'mini_quiz'] as $source) {
        $lines = extractLines($chapitre[$source] ?? '');

        foreach ($lines as $line) {
            if (count($exercises) >= 2) {
                break 2;
            }
            $exercises[] = $line;
        }
    }

    if (count($exercises) < 2) {
        foreach (buildFallbackExercises($chapitre, $langue) as $fallback) {
            if (count($exercises) >= 2) {
                break;
            }
            $exercises[] = $fallback;
        }
    }

    return array_slice($exercises, 0, 2);
}

$t = [
    'es' => [
        'page_title' => 'Curso de Python',
        'course' => 'Curso',
        'python_full' => 'Python completo',
        'header_text' => 'Cada capítulo contiene teoría clara y ejercicios prácticos.',
        'language' => 'Idioma',
        'dashboard' => 'Dashboard',
        'practice' => 'Prácticas',
        'no_chapters' => 'No hay capítulos visibles',
        'no_chapters_text' => 'Activa capítulos desde el panel de administración.',
        'chapter' => 'Capítulo',
        'theory' => 'Teoría',
        'exercises' => 'Ejercicios',
        'exercise' => 'Ejercicio',
        'untitled' => 'Sin título',
        'previous' => 'Capítulo anterior',
        'next' => 'Capítulo siguiente',
        'progress' => 'Progreso',
        'of' => 'de',
        'no_theory' => 'Este capítulo todavía no tiene teoría.'
    ],
    'fr' => [
        'page_title' => 'Cours Python',
        'course' => 'Cours',
        'python_full' => 'Python complet',
        'header_text' => 'Chaque chapitre contient une théorie claire et des exercices pratiques.',
        'language' => 'Langue',
        'dashboard' => 'Dashboard',
        'practice' => 'Exercices',
        'no_chapters' => 'Aucun chapitre visible',
        'no_chapters_text' => 'Active les chapitres depuis le panneau d’administration.',
        'chapter' => 'Chapitre',
        'theory' => 'Théorie',
        'exercises' => 'Exercices',
        'exercise' => 'Exercice',
        'untitled' => 'Sans titre',
        'previous' => 'Chapitre précédent',
        'next' => 'Chapitre suivant',
        'progress' => 'Progression',
        'of' => 'sur',
        'no_theory' => 'Ce chapitre n’a pas encore de théorie.'
    ]
];

$tr = $t[$langue] ?? $t['es'];

$sql = "
    SELECT
        c.id,
        c.code,
        c.ordre_affichage,
        c.visible,
        CASE
            WHEN :langue = 'fr' THEN COALESCE(c.titre_fr, c.titre_es, '')
            ELSE COALESCE(c.titre_es, c.titre_fr, '')
        END AS titulo,
        cc.teoria_larga,
        cc.ejercicios_guiados,
        cc.mini_quiz
    FROM chapitres c
    LEFT JOIN chapitres_contenu cc
        ON cc.chapitre_id = c.id
        AND cc.langue = :langue
    WHERE c.visible = 1
    ORDER BY c.ordre_affichage ASC, c.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['langue' => $langue]);
$chapitres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentCode = isset($_GET['chap']) ? trim((string)$_GET['chap']) : '';
$currentIndex = 0;

if (!empty($chapitres) && $currentCode !== '') {
    foreach ($chapitres as $index => $chap) {
        if (($chap['code'] ?? '') === $currentCode) {
            $currentIndex = $index;
            break;
        }
    }
}

$currentChapitre = $chapitres[$currentIndex] ?? null;
$previousChapitre = $chapitres[$currentIndex - 1] ?? null;
$nextChapitre = $chapitres[$currentIndex + 1] ?? null;

$theoryText = $currentChapitre['teoria_larga'] ?? '';
$exercises = $currentChapitre ? extractTwoExercises($currentChapitre, $langue) : [];
?>
<!DOCTYPE html>
<html lang="<?= h($langue) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($tr['page_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 selection:bg-sky-500 selection:text-white">
    <div class="min-h-screen flex">
        <aside class="w-80 bg-slate-900 border-r border-slate-800 p-6 sticky top-0 h-screen overflow-y-auto hidden lg:block">
            <div class="mb-8">
                <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">
                    <?= h($tr['course']) ?>
                </p>
                <h1 class="text-2xl font-bold mt-2">
                    <?= h($tr['python_full']) ?>
                </h1>
                <p class="text-slate-400 text-sm mt-3 leading-7">
                    <?= h($tr['header_text']) ?>
                </p>
            </div>

            <div class="mb-6 bg-slate-950 border border-slate-800 rounded-2xl p-4">
                <p class="text-slate-300 text-sm font-semibold">
                    <?= h($tr['language']) ?>
                </p>
                <div class="flex gap-2 mt-3">
                    <a href="?lang=es<?= $currentChapitre ? '&chap=' . urlencode($currentChapitre['code']) : '' ?>"
                       class="px-3 py-2 rounded-xl text-sm font-semibold transition <?= $langue === 'es' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                        ES
                    </a>
                    <a href="?lang=fr<?= $currentChapitre ? '&chap=' . urlencode($currentChapitre['code']) : '' ?>"
                       class="px-3 py-2 rounded-xl text-sm font-semibold transition <?= $langue === 'fr' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                        FR
                    </a>
                </div>
            </div>

            <nav class="space-y-2 text-sm">
                <?php foreach ($chapitres as $chapitre): ?>
                    <a
                        href="?lang=<?= h($langue) ?>&chap=<?= urlencode($chapitre['code']) ?>"
                        class="block rounded-xl px-4 py-3 transition border <?= ($currentChapitre && $chapitre['code'] === $currentChapitre['code']) ? 'bg-sky-500/15 border-sky-800 text-white' : 'hover:bg-slate-800 border-transparent hover:border-slate-700 text-slate-300' ?>"
                    >
                        <span class="text-sky-400 font-semibold"><?= (int)$chapitre['ordre_affichage'] ?>.</span>
                        <?= h($chapitre['titulo'] ?: $tr['untitled']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur sticky top-0 z-20">
                <div class="max-w-5xl mx-auto px-6 py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">
                            <?= h($tr['course']) ?>
                        </p>
                        <h1 class="text-3xl md:text-4xl font-bold mt-2">
                            <?= h($tr['python_full']) ?>
                        </h1>
                        <p class="text-slate-400 mt-3 max-w-3xl leading-7">
                            <?= h($tr['header_text']) ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                            <?= h($tr['dashboard']) ?>
                        </a>

                        <?php if ($currentChapitre): ?>
                            <a href="ejercicios.php?chap=<?= urlencode($currentChapitre['code']) ?>&lang=<?= h($langue) ?>"
                               class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold transition">
                                <?= h($tr['practice']) ?>
                            </a>
                        <?php else: ?>
                            <span class="bg-slate-800 text-slate-400 px-4 py-2 rounded-xl font-semibold">
                                <?= h($tr['practice']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <div class="lg:hidden max-w-5xl mx-auto px-6 pt-6">
                <?php if (!empty($chapitres)): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4">
                        <label for="chapitre_mobile" class="block text-sm font-semibold text-slate-300 mb-2">
                            <?= h($tr['chapter']) ?>
                        </label>
                        <select
                            id="chapitre_mobile"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3"
                            onchange="if(this.value) window.location.href=this.value;"
                        >
                            <?php foreach ($chapitres as $chapitre): ?>
                                <option
                                    value="?lang=<?= h($langue) ?>&chap=<?= urlencode($chapitre['code']) ?>"
                                    <?= ($currentChapitre && $chapitre['code'] === $currentChapitre['code']) ? 'selected' : '' ?>
                                >
                                    <?= (int)$chapitre['ordre_affichage'] ?>. <?= h($chapitre['titulo'] ?: $tr['untitled']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($chapitres) || !$currentChapitre): ?>
                <div class="max-w-5xl mx-auto px-6 py-10">
                    <section class="bg-slate-900 border border-slate-800 rounded-3xl p-8">
                        <h2 class="text-2xl font-bold mb-4">
                            <?= h($tr['no_chapters']) ?>
                        </h2>
                        <p class="text-slate-300 leading-8">
                            <?= h($tr['no_chapters_text']) ?>
                        </p>
                    </section>
                </div>
            <?php else: ?>
                <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
                    <section class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-xl shadow-black/20">
                        <div class="mb-8">
                            <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">
                                <?= h($tr['chapter']) ?> <?= (int)$currentChapitre['ordre_affichage'] ?>
                            </p>
                            <h2 class="text-3xl font-bold mt-2">
                                <?= h($currentChapitre['titulo']) ?>
                            </h2>
                            <p class="text-slate-400 mt-3">
                                <?= h($tr['progress']) ?> <?= $currentIndex + 1 ?> <?= h($tr['of']) ?> <?= count($chapitres) ?>
                            </p>
                        </div>

                        <div class="bg-slate-800/60 border border-slate-700 rounded-2xl p-6 mb-8">
                            <h3 class="text-2xl font-semibold text-sky-300 mb-4">
                                <?= h($tr['theory']) ?>
                            </h3>

                            <?php if (hasContent($theoryText)): ?>
                                <?= renderRichText($theoryText) ?>
                            <?php else: ?>
                                <p class="text-slate-400"><?= h($tr['no_theory']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="bg-emerald-950/20 border border-emerald-900 rounded-2xl p-6">
                            <h3 class="text-2xl font-semibold text-emerald-300 mb-4">
                                <?= h($tr['exercises']) ?>
                            </h3>

                            <div class="space-y-4">
                                <?php foreach ($exercises as $index => $exercise): ?>
                                    <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5">
                                        <h4 class="text-lg font-semibold text-emerald-300 mb-2">
                                            <?= h($tr['exercise']) ?> <?= $index + 1 ?>
                                        </h4>
                                        <p class="text-slate-300 leading-8">
                                            <?= h($exercise) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>

                    <section class="grid md:grid-cols-2 gap-4">
                        <div>
                            <?php if ($previousChapitre): ?>
                                <a href="?lang=<?= h($langue) ?>&chap=<?= urlencode($previousChapitre['code']) ?>"
                                   class="block bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 hover:bg-slate-800/60 transition">
                                    <p class="text-slate-400 text-sm mb-2">← <?= h($tr['previous']) ?></p>
                                    <p class="font-semibold text-lg"><?= h($previousChapitre['titulo']) ?></p>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($nextChapitre): ?>
                                <a href="?lang=<?= h($langue) ?>&chap=<?= urlencode($nextChapitre['code']) ?>"
                                   class="block bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 hover:bg-slate-800/60 transition text-left md:text-right">
                                    <p class="text-slate-400 text-sm mb-2"><?= h($tr['next']) ?> →</p>
                                    <p class="font-semibold text-lg"><?= h($nextChapitre['titulo']) ?></p>
                                </a>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>