<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
exiger_connexion();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('dashboard') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">
                    <?= t('welcome') ?>, <?= htmlspecialchars($_SESSION['user_nombre']) ?>
                </h1>
                <p class="text-slate-400 mt-2"><?= t('personal_space') ?></p>
            </div>

            <div class="flex flex-wrap gap-3 items-center">
                <div class="bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 flex items-center gap-3">
                    <span class="text-slate-300 text-sm"><?= t('language') ?>:</span>
                    <a href="?lang=es" class="text-sm px-3 py-1 rounded-lg <?= $lang === 'es' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">
                        ES
                    </a>
                    <a href="?lang=fr" class="text-sm px-3 py-1 rounded-lg <?= $lang === 'fr' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">
                        FR
                    </a>
                </div>

                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold w-fit">
                    <?= t('logout') ?>
                </a>
            </div>
        </div>

        <div class="mt-8 grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            <a href="curso.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('view_course') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('view_course_desc') ?></p>
            </a>

            <a href="ejercicios.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('practices') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('practices_desc') ?></p>
            </a>
            <a href="deberes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Mis deberes</h2>
                <p class="text-slate-400 mt-2">Ver enunciados y subir entregas en ZIP.</p>
            </a>
        </div>

        <div class="mt-10 bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-4"><?= t('summary') ?></h2>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('connected_user') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= htmlspecialchars($_SESSION['user_nombre']) ?></p>
                </div>

                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('course') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= t('python_basic') ?></p>
                </div>

                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('status') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= t('active_account') ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>