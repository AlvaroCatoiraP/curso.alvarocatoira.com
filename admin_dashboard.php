<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$sql_pending = "SELECT * FROM usuarios WHERE rol = 'student' AND estado = 'pending' ORDER BY creado_en DESC";
$stmt_pending = $pdo->query($sql_pending);
$pending_users = $stmt_pending->fetchAll(PDO::FETCH_ASSOC);

$sql_students = "SELECT * FROM usuarios WHERE rol = 'student' ORDER BY creado_en DESC";
$stmt_students = $pdo->query($sql_students);
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="max-w-7xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Panel administrador</h1>
                <p class="text-slate-400 mt-2">Gestiona estudiantes y aprueba o rechaza inscripciones.</p>
            </div>

            <div class="flex gap-3">
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                    Cerrar sesión
                </a>
                <a href="dashboard.php" class="bg-blue-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                   Panel Estudiante
                </a>
                <a href="admin_deberes.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar deberes</h2>
                    <p class="text-slate-400 mt-2">Crear deberes y revisar entregas ZIP.</p>
                </a>
            </div>
            <a href="admin_chapitres.php" class="bg-slate-900 border border-slate-800 p-6 rounded-2xl hover:border-sky-500 transition block">
                <h2 class="text-xl font-bold">Gestionar capítulos</h2>
                <p class="text-slate-400 mt-2">Activar o desactivar capítulos visibles.</p>
            </a>
        </div>

        <section class="mb-10">
            <h2 class="text-2xl font-bold mb-4">Inscripciones pendientes</h2>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-800">
                        <tr>
                            <th class="text-left p-4">Nombre</th>
                            <th class="text-left p-4">Email</th>
                            <th class="text-left p-4">Fecha</th>
                            <th class="text-left p-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pending_users) === 0): ?>
                            <tr>
                                <td colspan="4" class="p-4 text-slate-400">No hay inscripciones pendientes.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_users as $user): ?>
                                <tr class="border-t border-slate-800">
                                    <td class="p-4"><?= htmlspecialchars($user['nombre']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($user['email']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($user['creado_en']) ?></td>
                                    <td class="p-4">
                                        <div class="flex gap-2">
                                            <a href="admin_aprobar.php?id=<?= $user['id'] ?>"
                                               class="bg-emerald-500 hover:bg-emerald-600 px-3 py-2 rounded-xl font-semibold">
                                                Aprobar
                                            </a>
                                            <a href="admin_rechazar.php?id=<?= $user['id'] ?>"
                                               class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl font-semibold">
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
        </section>

        <section>
            <h2 class="text-2xl font-bold mb-4">Todos los estudiantes</h2>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-800">
                        <tr>
                            <th class="text-left p-4">Nombre</th>
                            <th class="text-left p-4">Email</th>
                            <th class="text-left p-4">Estado</th>
                            <th class="text-left p-4">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $user): ?>
                            <tr class="border-t border-slate-800">
                                <td class="p-4"><?= htmlspecialchars($user['nombre']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="p-4">
                                    <?php if ($user['estado'] === 'approved'): ?>
                                        <span class="text-emerald-400 font-semibold">Aprobado</span>
                                    <?php elseif ($user['estado'] === 'pending'): ?>
                                        <span class="text-yellow-400 font-semibold">Pendiente</span>
                                    <?php else: ?>
                                        <span class="text-red-400 font-semibold">Rechazado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4"><?= htmlspecialchars($user['creado_en']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
