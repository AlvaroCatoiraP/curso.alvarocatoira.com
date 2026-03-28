<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';

if (utilisateur_connecte()) {
    $rol = $_SESSION['user_rol'] ?? 'student';

    if ($rol === 'admin') {
        header('Location: admin_dashboard.php');
        exit;
    }

    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('site_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<!-- NAVBAR -->
<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

        <div class="flex items-center gap-4">
            <a href="index.php" class="text-xl font-bold">
                <?= t('site_title') ?>
            </a>

            <!-- SWITCH LANG -->
            <div class="flex gap-2 ml-4">
                <a href="?lang=es" class="text-sm px-2 py-1 rounded <?= $lang === 'es' ? 'bg-sky-500' : 'bg-slate-800' ?>">ES</a>
                <a href="?lang=fr" class="text-sm px-2 py-1 rounded <?= $lang === 'fr' ? 'bg-sky-500' : 'bg-slate-800' ?>">FR</a>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="login.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700">
                <?= t('login') ?>
            </a>
            <a href="register.php" class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 font-semibold">
                <?= t('register') ?>
            </a>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="max-w-7xl mx-auto px-6 py-16 md:py-24">
    <div class="grid lg:grid-cols-2 gap-12 items-center">

        <div>
            <p class="text-sky-300 text-sm font-semibold uppercase tracking-[0.2em]">
                <?= t('hero_badge') ?>
            </p>

            <h1 class="text-4xl md:text-6xl font-bold mt-4">
                <?= t('hero_title') ?>
            </h1>

            <p class="text-slate-400 text-lg mt-6">
                <?= t('hero_desc') ?>
            </p>

            <div class="mt-8 flex gap-4">
                <a href="register.php" class="bg-sky-500 hover:bg-sky-600 px-6 py-3 rounded-2xl font-semibold">
                    <?= t('start_now') ?>
                </a>
                <a href="login.php" class="bg-slate-800 hover:bg-slate-700 px-6 py-3 rounded-2xl">
                    <?= t('already_account') ?>
                </a>
            </div>

            <div class="mt-10 grid sm:grid-cols-3 gap-4">
                <div class="bg-slate-900 p-4 rounded-2xl">
                    <p class="text-2xl font-bold">14</p>
                    <p class="text-slate-400 text-sm"><?= t('chapters_count') ?></p>
                </div>

                <div class="bg-slate-900 p-4 rounded-2xl">
                    <p class="text-2xl font-bold">50+</p>
                    <p class="text-slate-400 text-sm"><?= t('exercises_count') ?></p>
                </div>

                <div class="bg-slate-900 p-4 rounded-2xl">
                    <p class="text-2xl font-bold">1</p>
                    <p class="text-slate-400 text-sm"><?= t('quizzes_count') ?></p>
                </div>
            </div>
        </div>

        <!-- MOCK DASHBOARD -->
        <div class="bg-slate-900 rounded-2xl p-6">
            <h2 class="text-xl font-bold"><?= t('student_dashboard') ?></h2>

            <div class="mt-4">
                <p class="text-slate-400 text-sm"><?= t('general_progress') ?></p>
                <div class="w-full bg-slate-800 h-3 rounded-full mt-2">
                    <div class="bg-sky-400 h-3 rounded-full" style="width:68%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-6">
                <div>
                    <p class="text-slate-400 text-sm"><?= t('exercises_label') ?></p>
                    <p class="text-xl font-bold">34/50</p>
                </div>

                <div>
                    <p class="text-slate-400 text-sm"><?= t('quizzes_label') ?></p>
                    <p class="text-xl font-bold">9/14</p>
                </div>

                <div>
                    <p class="text-slate-400 text-sm"><?= t('average_grade') ?></p>
                    <p class="text-xl font-bold">15.5/20</p>
                </div>

                <div>
                    <p class="text-slate-400 text-sm"><?= t('account_status') ?></p>
                    <p class="text-xl font-bold">Activo</p>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- FEATURES -->
<section class="max-w-7xl mx-auto px-6 pb-20">
    <div class="text-center mb-12">
        <p class="text-sky-300 text-sm"><?= t('features_badge') ?></p>
        <h2 class="text-3xl font-bold mt-4"><?= t('features_title') ?></h2>
        <p class="text-slate-400 mt-4"><?= t('features_desc') ?></p>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="bg-slate-900 p-6 rounded-2xl">
            <h3 class="font-bold"><?= t('feature_course') ?></h3>
            <p class="text-slate-400 mt-2"><?= t('feature_course_desc') ?></p>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl">
            <h3 class="font-bold"><?= t('feature_exercises') ?></h3>
            <p class="text-slate-400 mt-2"><?= t('feature_exercises_desc') ?></p>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl">
            <h3 class="font-bold"><?= t('feature_quizzes') ?></h3>
            <p class="text-slate-400 mt-2"><?= t('feature_quizzes_desc') ?></p>
        </div>

        <div class="bg-slate-900 p-6 rounded-2xl">
            <h3 class="font-bold"><?= t('feature_projects') ?></h3>
            <p class="text-slate-400 mt-2"><?= t('feature_projects_desc') ?></p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="max-w-7xl mx-auto px-6 pb-20 text-center">
    <h2 class="text-3xl font-bold"><?= t('cta_title') ?></h2>
    <p class="text-slate-400 mt-4"><?= t('cta_desc') ?></p>

    <div class="mt-6 flex justify-center gap-4">
        <a href="register.php" class="bg-sky-500 px-6 py-3 rounded-xl">
            <?= t('create_account') ?>
        </a>
        <a href="login.php" class="bg-slate-800 px-6 py-3 rounded-xl">
            <?= t('login') ?>
        </a>
    </div>
</section>

</body>
</html>