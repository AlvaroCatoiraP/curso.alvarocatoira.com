<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$sql = "
    SELECT
        p.id,
        p.titulo,
        p.descripcion,
        p.creado_en,
        u.nombre AS profesor_nombre,
        COUNT(DISTINCT pe.id) AS total_fases,
        COUNT(pee.id) AS total_entregas_recibidas,
        SUM(CASE WHEN pee.estado = 'corregido' THEN 1 ELSE 0 END) AS total_corregidas,
        SUM(CASE WHEN pee.liberada = 1 THEN 1 ELSE 0 END) AS total_liberadas,
        SUM(CASE WHEN pee.estado = 'entregado' THEN 1 ELSE 0 END) AS total_pendientes
    FROM proyectos p
    INNER JOIN usuarios u ON p.creado_por = u.id
    LEFT JOIN proyecto_entregas pe ON pe.proyecto_id = p.id
    LEFT JOIN proyecto_entregas_estudiantes pee ON pee.proyecto_entrega_id = pe.id
    GROUP BY p.id, p.titulo, p.descripcion, p.creado_en, u.nombre
    ORDER BY p.creado_en DESC, p.id DESC
";
$stmt = $pdo->query($sql);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function valorEntero($valor): int
{
    return (int)($valor ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de proyectos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Dashboard de proyectos</h1>
                <p class="text-slate-400 mt-2">
                    Vista general de proyectos, fases y correcciones pendientes.
                </p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a
                    href="admin_proyectos.php"
                    class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold"
                >
                    Gestionar proyectos
                </a>

                <a
                    href="admin_dashboard.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    Admin dashboard
                </a>

            </div>
        </div>

        <?php if (count($proyectos) === 0): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <p class="text-slate-400">Todavía no hay proyectos creados.</p>
            </div>
        <?php else: ?>

            <div class="grid gap-6">
                <?php foreach ($proyectos as $proyecto): ?>
                    <?php
                    $totalFases = valorEntero($proyecto['total_fases']);
                    $totalEntregas = valorEntero($proyecto['total_entregas_recibidas']);
                    $totalCorregidas = valorEntero($proyecto['total_corregidas']);
                    $totalLiberadas = valorEntero($proyecto['total_liberadas']);
                    $totalPendientes = valorEntero($proyecto['total_pendientes']);
                    ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <h2 class="text-2xl font-bold">
                                        <?= htmlspecialchars($proyecto['titulo']) ?>
                                    </h2>

                                    <?php if ($totalPendientes > 0): ?>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            <?= $totalPendientes ?> pendiente(s)
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            Sin pendientes
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-slate-300 whitespace-pre-line">
                                    <?= htmlspecialchars($proyecto['descripcion']) ?>
                                </p>

                                <div class="mt-4 text-sm text-slate-400 space-y-1">
                                    <p><strong>Profesor:</strong> <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                                    <p><strong>Creado:</strong> <?= htmlspecialchars($proyecto['creado_en']) ?></p>
                                </div>
                            </div>

                            <div class="xl:w-[420px] w-full">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Fases</p>
                                        <p class="text-2xl font-bold mt-1"><?= $totalFases ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Entregas recibidas</p>
                                        <p class="text-2xl font-bold mt-1"><?= $totalEntregas ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Corregidas</p>
                                        <p class="text-2xl font-bold mt-1"><?= $totalCorregidas ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Liberadas</p>
                                        <p class="text-2xl font-bold mt-1"><?= $totalLiberadas ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4 col-span-2">
                                        <p class="text-slate-400 text-sm">Pendientes de corrección</p>
                                        <p class="text-2xl font-bold mt-1"><?= $totalPendientes ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <a
                                href="proyecto_ver.php?id=<?= (int)$proyecto['id'] ?>"
                                class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl font-semibold"
                            >
                                Ver proyecto
                            </a>

                            <a
                                href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$proyecto['id'] ?>"
                                class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold"
                            >
                                Gestionar fases
                            </a>

                            <a
                                href="admin_editar_proyecto.php?id=<?= (int)$proyecto['id'] ?>"
                                class="bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-xl font-semibold"
                            >
                                Editar proyecto
                            </a>

                            <a
                                href="admin_correcciones_proyecto.php?proyecto_id=<?= (int)$proyecto['id'] ?>"
                                class="bg-violet-500 hover:bg-violet-600 px-4 py-2 rounded-xl font-semibold"
                            >
                                Ver correcciones
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>