<?php
require_once __DIR__ . '/includes/auth.php';
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
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-7xl mx-auto p-6 md:p-8">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold">Panel administrador</h1>
                <p class="text-slate-400 mt-2">
                    Gestiona estudiantes, capítulos, deberes, proyectos y el progreso del curso.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-xl font-semibold transition">
                    Panel estudiante
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold transition">
                    Cerrar sesión
                </a>
            </div>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm">Estudiantes</p>
                <p class="text-3xl font-bold mt-2"><?= $totalStudents ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $approvedCount ?> aprobados · <?= $pendingCount ?> pendientes</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm">Capítulos</p>
                <p class="text-3xl font-bold mt-2"><?= $totalChapitres ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $visibleChapitres ?> visibles</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm">Deberes y proyectos</p>
                <p class="text-3xl font-bold mt-2"><?= $totalDeberes + $totalProyectos ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $totalDeberes ?> deberes · <?= $totalProyectos ?> proyectos</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
                <p class="text-slate-400 text-sm">Entregas / progreso</p>
                <p class="text-3xl font-bold mt-2"><?= $totalEntregasDeberes + $totalEntregasProyectos ?></p>
                <p class="text-slate-500 text-sm mt-2"><?= $totalProgreso ?> registros de progreso</p>
            </div>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
            <a href="admin_chapitres.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar capítulos</h2>
                <p class="text-slate-400 mt-2">Crear, editar, ordenar y activar capítulos visibles.</p>
            </a>

            <a href="admin_deberes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar deberes</h2>
                <p class="text-slate-400 mt-2">Crear deberes y revisar entregas de los alumnos.</p>
            </a>

            <a href="admin_proyectos.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar proyectos</h2>
                <p class="text-slate-400 mt-2">Administrar proyectos y sus entregas.</p>
            </a>

            <a href="admin_quiz.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar quiz</h2>
                <p class="text-slate-400 mt-2">Administrar preguntas y actividades por capítulo.</p>
            </a>
        </section>

        <section class="mb-10">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold">Inscripciones pendientes</h2>
                <span class="text-sm text-slate-400"><?= count($pendingUsers) ?> pendiente(s)</span>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="text-left p-4">Nombre</th>
                                <th class="text-left p-4">Email</th>
                                <th class="text-left p-4">Fecha</th>
                                <th class="text-left p-4">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendingUsers) === 0): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-slate-400">No hay inscripciones pendientes.</td>
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
                                                    Aprobar
                                                </a>
                                                <a href="admin_rechazar.php?id=<?= (int)$user['id'] ?>"
                                                   class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl font-semibold transition"
                                                   onclick="return confirm('¿Seguro que quieres rechazar esta inscripción?');">
                                                    Rechazar
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
                <h2 class="text-2xl font-bold mb-4">Últimos estudiantes</h2>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[650px]">
                            <thead class="bg-slate-800">
                                <tr>
                                    <th class="text-left p-4">Nombre</th>
                                    <th class="text-left p-4">Email</th>
                                    <th class="text-left p-4">Estado</th>
                                    <th class="text-left p-4">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="p-4 text-slate-400">No hay estudiantes registrados.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $user): ?>
                                        <tr class="border-t border-slate-800">
                                            <td class="p-4"><?= h($user['nombre']) ?></td>
                                            <td class="p-4"><?= h($user['email']) ?></td>
                                            <td class="p-4">
                                                <?php if (($user['estado'] ?? '') === 'approved'): ?>
                                                    <span class="text-emerald-400 font-semibold">Aprobado</span>
                                                <?php elseif (($user['estado'] ?? '') === 'pending'): ?>
                                                    <span class="text-yellow-400 font-semibold">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="text-red-400 font-semibold">Rechazado</span>
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
                <h2 class="text-2xl font-bold mb-4">Resumen de capítulos</h2>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead class="bg-slate-800">
                                <tr>
                                    <th class="text-left p-4">Orden</th>
                                    <th class="text-left p-4">Título ES</th>
                                    <th class="text-left p-4">Título FR</th>
                                    <th class="text-left p-4">Visible</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($chapitres) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="p-4 text-slate-400">No hay capítulos todavía.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($chapitres as $chapitre): ?>
                                        <tr class="border-t border-slate-800">
                                            <td class="p-4"><?= (int)$chapitre['ordre'] ?></td>
                                            <td class="p-4"><?= h($chapitre['titre_es']) ?></td>
                                            <td class="p-4"><?= h($chapitre['titre_fr']) ?></td>
                                            <td class="p-4">
                                                <?php if ((int)$chapitre['visible'] === 1): ?>
                                                    <span class="text-emerald-400 font-semibold">Sí</span>
                                                <?php else: ?>
                                                    <span class="text-red-400 font-semibold">No</span>
                                                <?php endif; ?>
                                            </td>
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
</body>
</html>