<?php
$nombreUsuario = $_SESSION['user_nombre'] ?? 'Estudiante';
$paginaActual = basename($_SERVER['PHP_SELF']);

function claseLink($archivo, $paginaActual) {
    return $paginaActual === $archivo
        ? 'text-white bg-sky-500/20 border border-sky-500/30'
        : 'text-slate-300 hover:text-white hover:bg-slate-800';
}

function buildLangUrl(string $langCode): string {
    $query = $_GET;
    $query['lang'] = $langCode;
    return htmlspecialchars(basename($_SERVER['PHP_SELF']) . '?' . http_build_query($query));
}
?>
<nav class="bg-slate-900 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-4">
        
        <div class="flex justify-between items-center">
            <!-- IZQUIERDA -->
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="text-xl font-bold text-white">
                    <?= t('site_title') ?>
                </a>

                <!-- MENÚ DESKTOP -->
                <div class="hidden md:flex gap-2 text-sm">
                    <a href="dashboard.php" class="px-3 py-2 rounded-xl transition <?= claseLink('dashboard.php', $paginaActual) ?>">
                        <?= t('dashboard') ?>
                    </a>

                    <a href="curso.php" class="px-3 py-2 rounded-xl transition <?= claseLink('curso.php', $paginaActual) ?>">
                        <?= t('course') ?>
                    </a>

                    <a href="ejercicios.php" class="px-3 py-2 rounded-xl transition <?= claseLink('ejercicios.php', $paginaActual) ?>">
                        <?= t('practices') ?>
                    </a>

                    <a href="quizzes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('quizzes.php', $paginaActual) ?>">
                        <?= t('quizzes') ?>
                    </a>

                    <a href="deberes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('deberes.php', $paginaActual) ?>">
                        <?= t('my_homework') ?>
                    </a>

                    <a href="proyectos.php" class="px-3 py-2 rounded-xl transition <?= claseLink('proyectos.php', $paginaActual) ?>">
                        <?= t('my_projects') ?>
                    </a>

                    <a href="perfil.php" class="px-3 py-2 rounded-xl transition <?= claseLink('perfil.php', $paginaActual) ?>">
                        <?= t('profile') ?>
                    </a>
                </div>
            </div>

            <!-- DERECHA -->
            <div class="flex items-center gap-3">
                <!-- SELECTEUR DE LANGUE DESKTOP -->
                <div class="hidden md:flex items-center gap-2">
                    <span class="text-sm text-slate-400"><?= t('language') ?>:</span>

                    <a href="<?= buildLangUrl('es') ?>"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition <?= $lang === 'es'
                           ? 'bg-sky-500 text-white'
                           : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700' ?>">
                        ES
                    </a>

                    <a href="<?= buildLangUrl('fr') ?>"
                       class="px-3 py-1.5 rounded-lg text-sm font-medium transition <?= $lang === 'fr'
                           ? 'bg-sky-500 text-white'
                           : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700' ?>">
                        FR
                    </a>
                </div>

                <span class="text-sm text-slate-400 hidden md:block">
                    <?= htmlspecialchars($nombreUsuario) ?>
                </span>

                <a href="logout.php"
                   class="hidden md:inline-flex bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl text-sm font-semibold transition">
                    <?= t('logout') ?>
                </a>

                <!-- BOTÓN MÓVIL -->
                <button
                    type="button"
                    onclick="toggleMenu()"
                    class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-800 text-white hover:bg-slate-700 transition"
                    aria-label="<?= t('open_menu') ?>"
                >
                    ☰
                </button>
            </div>
        </div>

        <!-- MENÚ MÓVIL -->
        <div id="mobileMenu" class="hidden md:hidden mt-4 border-t border-slate-800 pt-4">
            <div class="flex flex-col gap-2 text-sm">
                <a href="dashboard.php" class="px-3 py-2 rounded-xl transition <?= claseLink('dashboard.php', $paginaActual) ?>">
                    <?= t('dashboard') ?>
                </a>

                <a href="curso.php" class="px-3 py-2 rounded-xl transition <?= claseLink('curso.php', $paginaActual) ?>">
                    <?= t('course') ?>
                </a>

                <a href="ejercicios.php" class="px-3 py-2 rounded-xl transition <?= claseLink('ejercicios.php', $paginaActual) ?>">
                    <?= t('practices') ?>
                </a>

                <a href="quizzes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('quizzes.php', $paginaActual) ?>">
                    <?= t('quizzes') ?>
                </a>

                <a href="deberes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('deberes.php', $paginaActual) ?>">
                    <?= t('my_homework') ?>
                </a>

                <a href="proyectos.php" class="px-3 py-2 rounded-xl transition <?= claseLink('proyectos.php', $paginaActual) ?>">
                    <?= t('my_projects') ?>
                </a>

                <a href="perfil.php" class="px-3 py-2 rounded-xl transition <?= claseLink('perfil.php', $paginaActual) ?>">
                    <?= t('profile') ?>
                </a>

                <!-- LANGUE MOBILE -->
                <div class="pt-2 border-t border-slate-800 mt-2">
                    <p class="text-slate-400 px-3 py-2"><?= t('language') ?></p>

                    <div class="flex gap-2 px-3">
                        <a href="<?= buildLangUrl('es') ?>"
                           class="px-3 py-2 rounded-xl text-sm font-medium transition <?= $lang === 'es'
                               ? 'bg-sky-500 text-white'
                               : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700' ?>">
                            ES
                        </a>

                        <a href="<?= buildLangUrl('fr') ?>"
                           class="px-3 py-2 rounded-xl text-sm font-medium transition <?= $lang === 'fr'
                               ? 'bg-sky-500 text-white'
                               : 'bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700' ?>">
                            FR
                        </a>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800 mt-2">
                    <p class="text-slate-400 px-3 py-2">
                        <?= htmlspecialchars($nombreUsuario) ?>
                    </p>

                    <a href="logout.php"
                       class="mt-2 inline-flex bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl text-sm font-semibold transition">
                        <?= t('logout') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleMenu() {
    document.getElementById('mobileMenu').classList.toggle('hidden');
}
</script>