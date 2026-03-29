<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$nombreUsuario = $_SESSION['user_nombre'] ?? 'Estudiante';
$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    die(t('user_id_not_found'));
}

if($_SESSION["rol"] == "admin"){
    header("Location: admin_dashboard.php");
}

$totalEjercicios = 0;
$ejerciciosCompletados = 0;
$porcentajeCurso = 0;

$totalEntregasProyecto = 0;
$entregasRealizadas = 0;
$entregasPendientes = 0;

$notaMedia = null;
$notaMediaTexto = t('no_grade');

$ultimaActividad = null;
$ultimaActividadTexto = t('no_activity_recorded');

try {
    /**
     * =========================================================
     * 1) PROGRESO DEL CURSO
     * =========================================================
     */

    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT ejercicio_codigo) AS total
        FROM progreso
    ");
    $totalEjercicios = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ejercicio_codigo) AS completados
        FROM progreso
        WHERE usuario_id = :usuario_id
          AND completado = 1
    ");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $ejerciciosCompletados = (int)($stmt->fetch(PDO::FETCH_ASSOC)['completados'] ?? 0);

    if ($totalEjercicios > 0) {
        $porcentajeCurso = round(($ejerciciosCompletados / $totalEjercicios) * 100);
    }

    if ($porcentajeCurso > 100) {
        $porcentajeCurso = 100;
    }

    /**
     * =========================================================
     * 2) ENTREGAS DE PROYECTO
     * =========================================================
     */

    $stmt = $pdo->query("
        SELECT COUNT(*) AS total
        FROM proyecto_entregas
    ");
    $totalEntregasProyecto = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT proyecto_entrega_id) AS realizadas
        FROM proyecto_entregas_estudiantes
        WHERE estudiante_id = :estudiante_id
    ");
    $stmt->execute(['estudiante_id' => $usuarioId]);
    $entregasRealizadas = (int)($stmt->fetch(PDO::FETCH_ASSOC)['realizadas'] ?? 0);

    $entregasPendientes = max(0, $totalEntregasProyecto - $entregasRealizadas);

    /**
     * =========================================================
     * 3) NOTA MEDIA
     * =========================================================
     */

    $stmt = $pdo->prepare("
        SELECT AVG(nota) AS media_proyectos
        FROM proyecto_entregas_estudiantes
        WHERE estudiante_id = :estudiante_id
          AND nota IS NOT NULL
    ");
    $stmt->execute(['estudiante_id' => $usuarioId]);
    $mediaProyectos = $stmt->fetch(PDO::FETCH_ASSOC)['media_proyectos'] ?? null;

    $stmt = $pdo->prepare("
        SELECT AVG(nota) AS media_resultados
        FROM resultados
        WHERE alumno_nombre = :alumno_nombre
    ");
    $stmt->execute(['alumno_nombre' => $nombreUsuario]);
    $mediaResultados = $stmt->fetch(PDO::FETCH_ASSOC)['media_resultados'] ?? null;

    $notas = [];

    if ($mediaProyectos !== null) {
        $notas[] = (float)$mediaProyectos;
    }

    if ($mediaResultados !== null) {
        $notas[] = (float)$mediaResultados;
    }

    if (!empty($notas)) {
        $notaMedia = array_sum($notas) / count($notas);
        $notaMediaTexto = number_format($notaMedia, 1) . '/20';
    }

    /**
     * =========================================================
     * 4) ÚLTIMA ACTIVIDAD
     * =========================================================
     */

    $fechas = [];

    $stmt = $pdo->prepare("
        SELECT MAX(actualizado_en) AS ultima_fecha
        FROM progreso
        WHERE usuario_id = :usuario_id
    ");
    $stmt->execute(['usuario_id' => $usuarioId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($fila['ultima_fecha'])) {
        $fechas[] = $fila['ultima_fecha'];
    }

    $stmt = $pdo->prepare("
        SELECT MAX(entregado_en) AS ultima_fecha
        FROM proyecto_entregas_estudiantes
        WHERE estudiante_id = :estudiante_id
    ");
    $stmt->execute(['estudiante_id' => $usuarioId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($fila['ultima_fecha'])) {
        $fechas[] = $fila['ultima_fecha'];
    }

    $stmt = $pdo->prepare("
        SELECT MAX(fecha) AS ultima_fecha
        FROM resultados
        WHERE alumno_nombre = :alumno_nombre
    ");
    $stmt->execute(['alumno_nombre' => $nombreUsuario]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($fila['ultima_fecha'])) {
        $fechas[] = $fila['ultima_fecha'];
    }

    if (!empty($fechas)) {
        rsort($fechas);
        $ultimaActividad = $fechas[0];
        $ultimaActividadTexto = date('d/m/Y H:i', strtotime($ultimaActividad));
    }

} catch (PDOException $e) {
    die(t('dashboard_load_error') . ': ' . $e->getMessage());
}

/**
 * =========================================================
 * MENSAJE MOTIVACIONAL
 * =========================================================
 */

if ($porcentajeCurso >= 100) {
    $mensajeProgreso = t('progress_message_100');
} elseif ($porcentajeCurso >= 75) {
    $mensajeProgreso = t('progress_message_75');
} elseif ($porcentajeCurso >= 50) {
    $mensajeProgreso = t('progress_message_50');
} else {
    $mensajeProgreso = t('progress_message_start');
}
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

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-6 md:p-8">

        <!-- ENCABEZADO -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">
                    <?= t('welcome') ?>, <?= htmlspecialchars($nombreUsuario) ?>
                </h1>
                <p class="text-slate-400 mt-2"><?= t('personal_space') ?></p>
            </div>
        </div>

        <!-- HERO / PROGRESO -->
        <div class="mt-8 bg-gradient-to-r from-sky-600/20 to-indigo-600/20 border border-sky-800/40 rounded-3xl p-6 md:p-8">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-8">
                <div class="max-w-3xl">
                    <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                        <?= t('progress_dashboard') ?>
                    </p>

                    <h2 class="text-2xl md:text-3xl font-bold mt-2">
                        <?= t('python_basic_progress') ?>
                    </h2>

                    <p class="text-slate-300 mt-3">
                        <?= htmlspecialchars($mensajeProgreso) ?>
                    </p>

                    <div class="mt-5">
                        <div class="flex justify-between text-sm text-slate-300 mb-2">
                            <span><?= t('course_progress') ?></span>
                            <span><?= $porcentajeCurso ?>%</span>
                        </div>

                        <div class="w-full bg-slate-800 rounded-full h-4 overflow-hidden">
                            <div
                                class="h-4 bg-gradient-to-r from-sky-400 to-cyan-300 rounded-full transition-all duration-500"
                                style="width: <?= $porcentajeCurso ?>%;"
                            ></div>
                        </div>

                        <p class="text-sm text-slate-400 mt-2">
                            <?= sprintf(t('completed_exercises_count'), $ejerciciosCompletados, $totalEjercicios) ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 min-w-[280px]">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4">
                        <p class="text-slate-400 text-sm"><?= t('course_completed') ?></p>
                        <p class="text-2xl font-bold mt-2"><?= $porcentajeCurso ?>%</p>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4">
                        <p class="text-slate-400 text-sm"><?= t('project_submissions_done') ?></p>
                        <p class="text-2xl font-bold mt-2"><?= $entregasRealizadas ?>/<?= $totalEntregasProyecto ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TARJETAS DE ESTADÍSTICAS -->
        <div class="mt-8 grid md:grid-cols-2 xl:grid-cols-4 gap-6">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('completed_exercises') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $ejerciciosCompletados ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= sprintf(t('out_of_total'), $totalEjercicios) ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('pending_submissions_project') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $entregasPendientes ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= t('pending_to_send_or_complete') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('status') ?></p>
                <p class="text-3xl font-bold mt-2"><?= t('active') ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= t('account_enabled_to_continue') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('current_course') ?></p>
                <p class="text-xl font-bold mt-2"><?= t('python_basic') ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= t('course_foundations_final_project') ?></p>
            </div>
        </div>

        <!-- ACCESOS RÁPIDOS -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold mb-4"><?= t('quick_access') ?></h2>

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6">
                <a href="curso.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 hover:-translate-y-1 transition block">
                    <h3 class="text-xl font-bold"><?= t('view_course') ?></h3>
                    <p class="text-slate-400 mt-2"><?= t('view_course_desc') ?></p>
                </a>

                <a href="ejercicios.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 hover:-translate-y-1 transition block">
                    <h3 class="text-xl font-bold"><?= t('practices') ?></h3>
                    <p class="text-slate-400 mt-2"><?= t('practices_desc') ?></p>
                </a>

                <a href="deberes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 hover:-translate-y-1 transition block">
                    <h3 class="text-xl font-bold"><?= t('my_homework') ?></h3>
                    <p class="text-slate-400 mt-2"><?= t('my_homework_desc') ?></p>
                </a>

                <a href="proyectos.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 hover:-translate-y-1 transition block">
                    <h3 class="text-xl font-bold"><?= t('my_projects') ?></h3>
                    <p class="text-slate-400 mt-2"><?= t('my_projects_desc') ?></p>
                </a>

                <a href="quizzes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 hover:-translate-y-1 transition block">
                    <h3 class="text-xl font-bold"><?= t('quizzes') ?></h3>
                    <p class="text-slate-400 mt-2"><?= t('quizzes_desc') ?></p>
                </a>
            </div>
        </div>

        <!-- RESUMEN GENERAL -->
        <div class="mt-10 bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-4"><?= t('summary') ?></h2>

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('connected_user') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= htmlspecialchars($nombreUsuario) ?></p>
                </div>

                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('course') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= t('python_basic') ?></p>
                </div>

                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('status') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= t('active_account') ?></p>
                </div>

                <div class="bg-slate-800 rounded-xl p-4">
                    <p class="text-slate-400 text-sm"><?= t('pending') ?></p>
                    <p class="text-lg font-semibold mt-2"><?= sprintf(t('pending_submissions_count'), $entregasPendientes) ?></p>
                </div>
            </div>
        </div>

    </div>
</body>
</html>