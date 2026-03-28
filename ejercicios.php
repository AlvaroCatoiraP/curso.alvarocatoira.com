<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';

exiger_connexion();

$langue = $lang ?? 'es';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($langue, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $langue === 'fr' ? 'Pratiques' : 'Prácticas' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-6 py-16">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
            <h1 class="text-3xl font-bold mb-4">
                <?= $langue === 'fr' ? 'Page de pratiques en préparation' : 'Página de prácticas en preparación' ?>
            </h1>

            <p class="text-slate-400 leading-8">
                <?= $langue === 'fr'
                    ? 'Les exercices seront réorganisés dans une nouvelle version plus interactive.'
                    : 'Los ejercicios se reorganizarán en una nueva versión más interactiva.' ?>
            </p>

            <a href="curso.php?lang=<?= urlencode($langue) ?>" class="inline-block mt-6 bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                <?= $langue === 'fr' ? 'Retour au cours' : 'Volver al curso' ?>
            </a>
        </div>
    </div>
</body>
</html>