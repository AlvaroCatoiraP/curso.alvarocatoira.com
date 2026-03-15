<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_connexion();

$langue = $lang ?? 'es';

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getTableColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return is_array($columns) ? $columns : [];
}

function optionalColumnSql(array $existingColumns, string $column): string {
    if (in_array($column, $existingColumns, true)) {
        return "COALESCE(cc_lang.$column, cc_es.$column) AS $column";
    }
    return "NULL AS $column";
}

function hasContent(?string $value): bool {
    return trim((string)$value) !== '';
}

function renderRichText(?string $text): string {
    $text = trim((string)$text);

    if ($text === '') {
        return '';
    }

    $lines = preg_split('/\R/u', $text);
    $html = '';
    $paragraphBuffer = [];

    $flushParagraph = function () use (&$paragraphBuffer, &$html) {
        if (!empty($paragraphBuffer)) {
            $content = implode(' ', array_map('trim', $paragraphBuffer));
            $html .= '<p class="text-slate-300 leading-8 mb-4">' . nl2br(h($content)) . '</p>';
            $paragraphBuffer = [];
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            $flushParagraph();
            continue;
        }

        $paragraphBuffer[] = $trimmed;
    }

    $flushParagraph();

    return $html;
}

function extractLines(?string $text): array {
    $text = trim((string)$text);

    if ($text === '') {
        return [];
    }

    $lines = preg_split('/\R/u', $text);
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

function buildTheory(array $chapitre): string {
    $parts = [];

    $fields = [
        'introduccion',
        'objetivos',
        'teoria_larga',
        'teoria_complementaria',
        'definiciones_clave',
        'conceptos_clave',
        'cuando_usarlo',
        'comparacion',
        'intuicion',
        'analogia',
        'explicacion_ejemplo_simple',
        'explicacion_ejemplo_avanzado',
        'errores_comunes',
        'buenas_practicas',
        'aplicacion_real',
        'preguntas_frecuentes',
        'curiosidades',
        'resumen_final'
    ];

    foreach ($fields as $field) {
        if (!empty($chapitre[$field]) && trim((string)$chapitre[$field]) !== '') {
            $parts[] = trim((string)$chapitre[$field]);
        }
    }

    return implode("\n\n", $parts);
}

function buildFallbackExercises(array $chapitre, string $langue): array {
    $titulo = trim((string)($chapitre['titulo'] ?? ''));

    if ($langue === 'fr') {
        return [
            "Explique avec tes mots ce qu’est « {$titulo} » et donne un exemple simple.",
            "Écris un petit exercice pratique lié à « {$titulo} » puis essaie de le résoudre."
        ];
    }

    return [
        "Explica con tus palabras qué es « {$titulo} » y pon un ejemplo sencillo.",
        "Haz un pequeño ejercicio práctico relacionado con « {$titulo} » e intenta resolverlo."
    ];
}

function extractTwoExercises(array $chapitre, string $langue): array {
    $exercises = [];

    $sources = [
        'ejercicios_guiados',
        'mini_quiz',
        'ejemplo_error'
    ];

    foreach ($sources as $source) {
        $lines = extractLines($chapitre[$source] ?? '');

        foreach ($lines as $line) {
            if (count($exercises) >= 2) {
                break 2;
            }
            $exercises[] = $line;
        }
    }

    if (count($exercises) < 2) {
        $fallbacks = buildFallbackExercises($chapitre, $langue);

        foreach ($fallbacks as $fallback) {
            if (count($exercises) >= 2) {
                break;
            }
            $exercises[] = $fallback;
        }
    }

    return array_slice($exercises, 0, 2);
}

$existingColumns = getTableColumns($pdo, 'chapitres_contenu');

$optionalColumns = [
    'conceptos_clave',
    'cuando_usarlo',
    'comparacion',
    'preguntas_frecuentes',
    'aplicacion_real',
    'teoria_complementaria',
    'definiciones_clave',
    'analogia',
    'ejercicios_guiados',
    'curiosidades',
    'ejemplo_error'
];

$optionalSelects = [];
foreach ($optionalColumns as $column) {
    $optionalSelects[] = optionalColumnSql($existingColumns, $column);
}

$sql = "
    SELECT
        c.id,
        c.code,
        c.ordre_affichage,

        COALESCE(cc_lang.titulo, cc_es.titulo) AS titulo,
        COALESCE(cc_lang.introduccion, cc_es.introduccion) AS introduccion,
        COALESCE(cc_lang.objetivos, cc_es.objetivos) AS objetivos,
        COALESCE(cc_lang.teoria_larga, cc_es.teoria_larga) AS teoria_larga,
        COALESCE(cc_lang.intuicion, cc_es.intuicion) AS intuicion,
        COALESCE(cc_lang.explicacion_ejemplo_simple, cc_es.explicacion_ejemplo_simple) AS explicacion_ejemplo_simple,
        COALESCE(cc_lang.explicacion_ejemplo_avanzado, cc_es.explicacion_ejemplo_avanzado) AS explicacion_ejemplo_avanzado,
        COALESCE(cc_lang.errores_comunes, cc_es.errores_comunes) AS errores_comunes,
        COALESCE(cc_lang.buenas_practicas, cc_es.buenas_practicas) AS buenas_practicas,
        COALESCE(cc_lang.resumen_final, cc_es.resumen_final) AS resumen_final,
        COALESCE(cc_lang.mini_quiz, cc_es.mini_quiz) AS mini_quiz,
        " . implode(",\n        ", $optionalSelects) . "

    FROM chapitres c

    LEFT JOIN chapitres_contenu cc_lang
        ON cc_lang.chapitre_id = c.id
        AND cc_lang.langue = ?

    LEFT JOIN chapitres_contenu cc_es
        ON cc_es.chapitre_id = c.id
        AND cc_es.langue = 'es'

    WHERE c.visible = 1
    ORDER BY c.ordre_affichage ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$langue]);
$chapitres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$t = [
    'es' => [
        'page_title' => 'Curso de Python',
        'course' => 'Curso',
        'python_full' => 'Python completo',
        'header_text' => 'Cada capítulo contiene únicamente el título, una teoría extensa y dos ejercicios.',
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
        'of' => 'de'
    ],
    'fr' => [
        'page_title' => 'Cours Python',
        'course' => 'Cours',
        'python_full' => 'Python complet',
        'header_text' => 'Chaque chapitre contient uniquement le titre, une théorie longue et deux exercices.',
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
        'of' => 'sur'
    ]
];

$tr = $t[$langue] ?? $t['es'];

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

$theoryText = $currentChapitre ? buildTheory($currentChapitre) : '';
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
                    <a href="?lang=es<?= $currentChapitre ? '&chap=' . urlencode($currentChapitre['code']) : '' ?>" class="px-3 py-2 rounded-xl text-sm font-semibold transition <?= $langue === 'es' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
                        ES
                    </a>
                    <a href="?lang=fr<?= $currentChapitre ? '&chap=' . urlencode($currentChapitre['code']) : '' ?>" class="px-3 py-2 rounded-xl text-sm font-semibold transition <?= $langue === 'fr' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' ?>">
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
                        <a href="ejercicios.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold transition">
                            <?= h($tr['practice']) ?>
                        </a>
                    </div>
                </div>
            </header>

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
                            <?= renderRichText($theoryText) ?>
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
                                <a href="?lang=<?= h($langue) ?>&chap=<?= urlencode($previousChapitre['code']) ?>" class="block bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 hover:bg-slate-800/60 transition">
                                    <p class="text-slate-400 text-sm mb-2">← <?= h($tr['previous']) ?></p>
                                    <p class="font-semibold text-lg"><?= h($previousChapitre['titulo']) ?></p>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php if ($nextChapitre): ?>
                                <a href="?lang=<?= h($langue) ?>&chap=<?= urlencode($nextChapitre['code']) ?>" class="block bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 hover:bg-slate-800/60 transition text-left md:text-right">
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