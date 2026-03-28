<?php
$nombreUsuario = $_SESSION['user_nombre'] ?? 'Estudiante';
$paginaActual = basename($_SERVER['PHP_SELF']);

function claseLink($archivo, $paginaActual) {
    return $paginaActual === $archivo
        ? 'text-white bg-sky-500/20 border border-sky-500/30'
        : 'text-slate-300 hover:text-white hover:bg-slate-800';
}
?>
<nav class="bg-slate-900 border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-6 py-4">
        
        <div class="flex justify-between items-center">
            <!-- IZQUIERDA -->
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="text-xl font-bold text-white">
                    Curso Python
                </a>

                <!-- MENÚ DESKTOP -->
                <div class="hidden md:flex gap-2 text-sm">
                    <a href="dashboard.php" class="px-3 py-2 rounded-xl transition <?= claseLink('dashboard.php', $paginaActual) ?>">
                        Dashboard
                    </a>

                    <a href="curso.php" class="px-3 py-2 rounded-xl transition <?= claseLink('curso.php', $paginaActual) ?>">
                        Curso
                    </a>

                    <a href="ejercicios.php" class="px-3 py-2 rounded-xl transition <?= claseLink('ejercicios.php', $paginaActual) ?>">
                        Ejercicios
                    </a>

                    <a href="quizzes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('quizzes.php', $paginaActual) ?>">
                        Quizzes
                    </a>

                    <a href="deberes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('deberes.php', $paginaActual) ?>">
                        Deberes
                    </a>

                    <a href="proyectos.php" class="px-3 py-2 rounded-xl transition <?= claseLink('proyectos.php', $paginaActual) ?>">
                        Proyectos
                    </a>
                    <a href="perfil.php" class="px-3 py-2 rounded-xl transition <?= claseLink('perfil.php', $paginaActual) ?>">
                        Perfil
                    </a>
                </div>
            </div>

            <!-- DERECHA -->
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-400 hidden md:block">
                    <?= htmlspecialchars($nombreUsuario) ?>
                </span>

                <a href="logout.php"
                   class="hidden md:inline-flex bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl text-sm font-semibold transition">
                    Logout
                </a>

                <!-- BOTÓN MÓVIL -->
                <button
                    type="button"
                    onclick="toggleMenu()"
                    class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-800 text-white hover:bg-slate-700 transition"
                    aria-label="Abrir menú"
                >
                    ☰
                </button>
            </div>
        </div>

        <!-- MENÚ MÓVIL -->
        <div id="mobileMenu" class="hidden md:hidden mt-4 border-t border-slate-800 pt-4">
            <div class="flex flex-col gap-2 text-sm">
                <a href="dashboard.php" class="px-3 py-2 rounded-xl transition <?= claseLink('dashboard.php', $paginaActual) ?>">
                    Dashboard
                </a>

                <a href="curso.php" class="px-3 py-2 rounded-xl transition <?= claseLink('curso.php', $paginaActual) ?>">
                    Curso
                </a>

                <a href="ejercicios.php" class="px-3 py-2 rounded-xl transition <?= claseLink('ejercicios.php', $paginaActual) ?>">
                    Ejercicios
                </a>

                <a href="quizzes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('quizzes.php', $paginaActual) ?>">
                    Quizzes
                </a>

                <a href="deberes.php" class="px-3 py-2 rounded-xl transition <?= claseLink('deberes.php', $paginaActual) ?>">
                    Deberes
                </a>

                <a href="proyectos.php" class="px-3 py-2 rounded-xl transition <?= claseLink('proyectos.php', $paginaActual) ?>">
                    Proyectos
                </a>
                <a href="perfil.php" class="px-3 py-2 rounded-xl transition <?= claseLink('perfil.php', $paginaActual) ?>">
                    Perfil
                </a>

                <div class="pt-2 border-t border-slate-800 mt-2">
                    <p class="text-slate-400 px-3 py-2">
                        <?= htmlspecialchars($nombreUsuario) ?>
                    </p>

                    <a href="logout.php"
                       class="mt-2 inline-flex bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl text-sm font-semibold transition">
                        Logout
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