<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/Parsedown.php';

exiger_connexion();

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$langue = $lang ?? 'es';
$isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';

function h($v)
{
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function renderMarkdown($text)
{
    $parsedown = new Parsedown();
    $parsedown->setSafeMode(true);
    return $parsedown->text($text ?? '');
}

$sql = "
SELECT
    c.id,
    c.code,
    c.ordre_affichage,
    c.titre_es,
    c.titre_fr,
    cc.teoria_larga
FROM chapitres c
LEFT JOIN chapitres_contenu cc
    ON cc.chapitre_id = c.id
    AND cc.langue = :lang
WHERE c.visible = 1
ORDER BY c.ordre_affichage
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['lang' => $langue]);
$chapitres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentCode = $_GET['chap'] ?? '';
$currentIndex = 0;

foreach ($chapitres as $i => $c) {
    if ($c['code'] === $currentCode) {
        $currentIndex = $i;
        break;
    }
}

$currentChapitre = $chapitres[$currentIndex] ?? null;
$previousChapitre = $chapitres[$currentIndex - 1] ?? null;
$nextChapitre = $chapitres[$currentIndex + 1] ?? null;

$title = $langue === 'fr'
    ? ($currentChapitre['titre_fr'] ?? '')
    : ($currentChapitre['titre_es'] ?? '');

$theory = $currentChapitre['teoria_larga'] ?? '';

?>
<!DOCTYPE html>
<html lang="<?= h($langue) ?>">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Curso Python</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .markdown h1 {
            font-size: 32px;
            margin-top: 20px;
            color: #38bdf8;
            font-weight: 700
        }

        .markdown h2 {
            font-size: 26px;
            margin-top: 18px;
            color: #38bdf8;
            font-weight: 600
        }

        .markdown h3 {
            font-size: 22px;
            margin-top: 16px;
            color: #38bdf8;
            font-weight: 600
        }

        .markdown p {
            margin-bottom: 14px;
            color: #cbd5e1
        }

        .markdown ul {
            margin-left: 20px;
            list-style: disc;
            color: #cbd5e1
        }

        .markdown li {
            margin-bottom: 6px
        }

        .markdown code {
            background: #020617;
            padding: 4px 6px;
            border-radius: 6px;
            color: #86efac
        }

        .markdown pre {
            background: #020617;
            padding: 14px;
            border-radius: 10px;
            overflow: auto;
            margin: 14px 0
        }

        .markdown pre code {
            background: transparent;
            padding: 0
        }
    </style>

</head>

<body class="bg-slate-950 text-slate-100">

    <div class="flex min-h-screen">

        <!-- SIDEBAR -->

        <aside class="w-72 bg-slate-900 border-r border-slate-800 p-6 hidden lg:block">

            <h2 class="text-xl font-bold mb-6">
                <?= $langue === 'fr' ? 'Cours Python' : 'Curso Python' ?>
            </h2>

            <nav class="space-y-2">

                <?php foreach ($chapitres as $c): ?>

                    <a href="?lang=<?= $langue ?>&chap=<?= $c['code'] ?>" class="block px-4 py-3 rounded-xl
<?= $currentChapitre && $c['code'] == $currentChapitre['code']
            ? 'bg-sky-500/20 border border-sky-700'
            : 'hover:bg-slate-800' ?>">

                        <span class="text-sky-400 font-semibold">
                            <?= $c['ordre_affichage'] ?>.
                        </span>

                        <?= h($langue === 'fr' ? $c['titre_fr'] : $c['titre_es']) ?>

                    </a>

                <?php endforeach; ?>

            </nav>

        </aside>

        <!-- MAIN -->

        <main class="flex-1">

            <header class="border-b border-slate-800 bg-slate-950 sticky top-0">

                <div class="max-w-5xl mx-auto px-6 py-5 flex justify-between items-center">

                    <div>

                        <h1 class="text-3xl font-bold">
                            <?= $langue === 'fr' ? 'Cours Python complet' : 'Curso completo de Python' ?>
                        </h1>

                    </div>

                    <div class="flex gap-3">
                        <a href="?lang=es<?= $currentChapitre ? '&chap='.$currentChapitre['code'] : '' ?>"
                        class="px-3 py-1 rounded-lg <?= $langue==='es'?'bg-sky-500':'bg-slate-700' ?>">
                            ES
                        </a>

                        <a href="?lang=fr<?= $currentChapitre ? '&chap='.$currentChapitre['code'] : '' ?>"
                        class="px-3 py-1 rounded-lg <?= $langue==='fr'?'bg-sky-500':'bg-slate-700' ?>">
                            FR
                        </a>

                        <?php if ($isAdmin && !empty($currentChapitre) && isset($currentChapitre['id'])): ?>
                            <a
                                href="admin_chapitre_form.php?id=<?= (int)$currentChapitre['id'] ?>"
                                class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold"
                            >
                                <?= $langue === 'fr' ? 'Modifier' : 'Editar' ?>
                            </a>
                        <?php endif; ?>

                        <a href="dashboard.php"
                        class="bg-slate-700 px-4 py-2 rounded-xl">
                            Dashboard
                        </a>
                    </div>

                </div>

            </header>


            <?php if (!$currentChapitre): ?>

                <div class="max-w-5xl mx-auto px-6 py-12">

                    <p>
                        <?= $langue === 'fr'
                            ? 'Aucun chapitre disponible'
                            : 'No hay capítulos disponibles' ?>
                    </p>

                </div>

            <?php else: ?>

                <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">

                    <!-- TITULO -->

                    <section>

                        <p class="text-sky-400 uppercase text-sm">
                            <?= $langue === 'fr' ? 'Chapitre' : 'Capítulo' ?>
                            <?= $currentChapitre['ordre_affichage'] ?>
                        </p>

                        <h2 class="text-4xl font-bold mt-2">
                            <?= h($title) ?>
                        </h2>

                    </section>


                    <!-- THEORY -->

                    <section class="bg-slate-900 border border-slate-800 rounded-2xl p-6">

                        <h3 class="text-2xl font-semibold mb-4 text-sky-300">
                            <?= $langue === 'fr' ? 'Théorie' : 'Teoría' ?>
                        </h3>

                        <div class="markdown">
                            <?= renderMarkdown($theory) ?>
                        </div>

                    </section>

                    <!-- NAVIGATION -->

                    <section class="grid md:grid-cols-2 gap-4">

                        <div>

                            <?php if ($previousChapitre): ?>

                                <a href="?lang=<?= $langue ?>&chap=<?= $previousChapitre['code'] ?>"
                                    class="block bg-slate-900 border border-slate-800 p-5 rounded-xl">

                                    ← <?= h($langue === 'fr' ? 'Chapitre précédent' : 'Capítulo anterior') ?>

                                    <br>

                                    <strong>
                                        <?= h($langue === 'fr'
                                            ? $previousChapitre['titre_fr']
                                            : $previousChapitre['titre_es']) ?>
                                    </strong>

                                </a>

                            <?php endif; ?>

                        </div>

                        <div>

                            <?php if ($nextChapitre): ?>

                                <a href="?lang=<?= $langue ?>&chap=<?= $nextChapitre['code'] ?>"
                                    class="block bg-slate-900 border border-slate-800 p-5 rounded-xl text-right">

                                    <?= h($langue === 'fr' ? 'Chapitre suivant' : 'Capítulo siguiente') ?> →

                                    <br>

                                    <strong>
                                        <?= h($langue === 'fr'
                                            ? $nextChapitre['titre_fr']
                                            : $nextChapitre['titre_es']) ?>
                                    </strong>

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