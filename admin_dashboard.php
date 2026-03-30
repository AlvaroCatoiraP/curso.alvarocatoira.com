<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_admin();

/**
 * Utilidad para escapar HTML
 */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve 0 si la consulta falla
 */
function fetchCount(PDO $pdo, string $sql): int
{
    try {
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Ajusta aquí el rol de alumno si en tu BD no es "student"
 * Posibles valores según tu proyecto: student / alumno
 */
$studentRole = 'student';

/**
 * Métricas
 */
$totalStudents = fetchCount($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = " . $pdo->quote($studentRole));
$pendingCount = fetchCount($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = " . $pdo->quote($studentRole) . " AND estado = 'pending'");
$approvedCount = fetchCount($pdo, "SELECT COUNT(*) FROM usuarios WHERE rol = " . $pdo->quote($studentRole) . " AND estado = 'approved'");
$totalChapitres = fetchCount($pdo, "SELECT COUNT(*) FROM chapitres");
$visibleChapitres = fetchCount($pdo, "SELECT COUNT(*) FROM chapitres WHERE visible = 1");
$totalDeberes = fetchCount($pdo, "SELECT COUNT(*) FROM deberes");
$totalProyectos = fetchCount($pdo, "SELECT COUNT(*) FROM proyectos");
$totalEntregasDeberes = fetchCount($pdo, "SELECT COUNT(*) FROM entregas_deberes");
$totalEntregasProyectos = fetchCount($pdo, "SELECT COUNT(*) FROM proyecto_entregas");
$totalProgreso = fetchCount($pdo, "SELECT COUNT(*) FROM progreso");

/**
 * Pendientes
 */
$sqlPending = "
    SELECT id, nombre, email, estado, creado_en
    FROM usuarios
    WHERE rol = :rol AND estado = 'pending'
    ORDER BY creado_en DESC
";
$stmtPending = $pdo->prepare($sqlPending);
$stmtPending->execute(['rol' => $studentRole]);
$pendingUsers = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

/**
 * Últimos estudiantes
 */
$sqlStudents = "
    SELECT id, nombre, email, estado, creado_en
    FROM usuarios
    WHERE rol = :rol
    ORDER BY creado_en DESC
";
$stmtStudents = $pdo->prepare($sqlStudents);
$stmtStudents->execute(['rol' => $studentRole]);
$students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

/**
 * Estudiantes conectados
 * Consideramos conectados los que tuvieron actividad en los últimos 5 minutos
 */
$estudiantesConectados = [];
try {
    $sqlConectados = "
        SELECT id, nombre, email, ultima_actividad
        FROM usuarios
        WHERE rol = :rol
          AND ultima_actividad IS NOT NULL
          AND ultima_actividad >= NOW() - INTERVAL 5 MINUTE
        ORDER BY ultima_actividad DESC
    ";
    $stmtConectados = $pdo->prepare($sqlConectados);
    $stmtConectados->execute(['rol' => $studentRole]);
    $estudiantesConectados = $stmtConectados->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $estudiantesConectados = [];
}

/**
 * Últimos capítulos
 */
$chapitres = [];
try {
    $stmtChapitres = $pdo->query("
        SELECT id, titre_es, titre_fr, ordre, visible
        FROM chapitres
        ORDER BY ordre ASC, id ASC
        LIMIT 10
    ");
    $chapitres = $stmtChapitres->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $chapitres = [];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_dashboard') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-6 md:p-8">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold"><?= t('admin_panel') ?></h1>
                <p class="text-slate-400 mt-2">
                    <?= t('admin_panel_desc') ?>
                </p>
            </div>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('students') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $totalStudents ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $approvedCount ?> <?= t('approved') ?> · <?= $pendingCount ?> <?= t('pending') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('chapters') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $totalChapitres ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $visibleChapitres ?> <?= t('visible_plural') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('homework_projects') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $totalDeberes + $totalProyectos ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $totalDeberes ?> <?= t('homework') ?> · <?= $totalProyectos ?> <?= t('projects') ?></p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm"><?= t('submissions_progress') ?></p>
                <p class="text-3xl font-bold mt-2"><?= $totalEntregasDeberes + $totalEntregasProyectos ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $totalProgreso ?> <?= t('progress_records') ?></p>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
            <a href="admin_chapitres.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('manage_chapters') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('manage_chapters_desc') ?></p>
            </a>

            <a href="admin_deberes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('manage_homework') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('manage_homework_desc') ?></p>
            </a>

            <a href="admin_dashboard_proyectos.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('manage_projects') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('manage_projects_desc') ?></p>
            </a>

            <a href="admin_quiz.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold"><?= t('manage_quiz') ?></h2>
                <p class="text-slate-400 mt-2"><?= t('manage_quiz_desc') ?></p>
            </a>
        </section>

        <section class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold"><?= t('pending_registrations') ?></h2>
                <span class="text-sm text-slate-400"><?= count($pendingUsers) ?> <?= t('pending_count_suffix') ?></span>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="text-left p-4"><?= t('name') ?></th>
                                <th class="text-left p-4"><?= t('email') ?></th>
                                <th class="text-left p-4"><?= t('date') ?></th>
                                <th class="text-left p-4"><?= t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendingUsers) === 0): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-slate-400"><?= t('no_pending_registrations') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingUsers as $user): ?>
                                    <tr class="border-t border-slate-800">
                                        <td class="p-4"><?= h($user['nombre']) ?></td>
                                        <td class="p-4"><?= h($user['email']) ?></td>
                                        <td class="p-4"><?= h($user['creado_en']) ?></td>
                                        <td class="p-4">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="admin_aprobar.php?id=<?= (int)$user['id'] ?>"
                                                   class="bg-emerald-500 hover:bg-emerald-600 px-3 py-2 rounded-xl font-semibold transition">
                                                    <?= t('approve') ?>
                                                </a>
                                                <a href="admin_rechazar.php?id=<?= (int)$user['id'] ?>"
                                                   class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl font-semibold transition"
                                                   onclick="return confirm('<?= h(t('confirm_reject_registration')) ?>');">
                                                    <?= t('reject') ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div>
                <h2 class="text-2xl font-bold mb-4"><?= t('latest_students') ?></h2>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[650px]">
                            <thead class="bg-slate-800">
                                <tr>
                                    <th class="text-left p-4"><?= t('name') ?></th>
                                    <th class="text-left p-4"><?= t('email') ?></th>
                                    <th class="text-left p-4"><?= t('status') ?></th>
                                    <th class="text-left p-4"><?= t('date') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="p-4 text-slate-400"><?= t('no_registered_students') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $user): ?>
                                        <tr class="border-t border-slate-800">
                                            <td class="p-4"><?= h($user['nombre']) ?></td>
                                            <td class="p-4"><?= h($user['email']) ?></td>
                                            <td class="p-4">
                                                <?php if (($user['estado'] ?? '') === 'approved'): ?>
                                                    <span class="text-emerald-400 font-semibold"><?= t('approved') ?></span>
                                                <?php elseif (($user['estado'] ?? '') === 'pending'): ?>
                                                    <span class="text-yellow-400 font-semibold"><?= t('pending_status') ?></span>
                                                <?php else: ?>
                                                    <span class="text-red-400 font-semibold"><?= t('rejected') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4"><?= h($user['creado_en']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-2xl font-bold mb-4"><?= t('connected_students') ?></h2>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[650px]">
                            <thead class="bg-slate-800">
                                <tr>
                                    <th class="text-left p-4"><?= t('name') ?></th>
                                    <th class="text-left p-4"><?= t('email') ?></th>
                                    <th class="text-left p-4"><?= t('last_activity') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($estudiantesConectados) === 0): ?>
                                    <tr>
                                        <td colspan="3" class="p-4 text-slate-400"><?= t('no_connected_students') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($estudiantesConectados as $estudiante): ?>
                                        <tr class="border-t border-slate-800">
                                            <td class="p-4"><?= h($estudiante['nombre']) ?></td>
                                            <td class="p-4"><?= h($estudiante['email']) ?></td>
                                            <td class="p-4"><?= h($estudiante['ultima_actividad']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

</body>
</html>