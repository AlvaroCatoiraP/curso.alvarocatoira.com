<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_connexion();

$sql = "
    SELECT p.*, u.nombre AS profesor_nombre
    FROM proyectos p
    JOIN usuarios u ON p.creado_por = u.id
    ORDER BY p.creado_en DESC
";
$stmt = $pdo->query($sql);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-6xl mx-auto p-8">

        <!-- HEADER -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Proyectos</h1>
                <p class="text-slate-400 mt-2">
                    Trabajos largos con varias entregas secuenciales.
                </p>
            </div>

            <div class="flex gap-3">
                <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    Dashboard
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                    Cerrar sesión
                </a>
            </div>
        </div>

        <!-- LISTA DE PROYECTOS -->
        <div class="space-y-6">

            <?php foreach ($proyectos as $proyecto): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">

                    <h2 class="text-2xl font-bold">
                        <?= htmlspecialchars($proyecto['titulo']) ?>
                    </h2>

                    <p class="text-slate-300 mt-3 whitespace-pre-line">
                        <?= htmlspecialchars($proyecto['descripcion']) ?>
                    </p>

                    <div class="mt-4 text-sm text-slate-400">
                        <p>Profesor: <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                        <p>Creado: <?= htmlspecialchars($proyecto['creado_en']) ?></p>
                    </div>

                    <div class="mt-5">
                        <a
                            href="proyecto_ver.php?id=<?= (int)$proyecto['id'] ?>"
                            class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold inline-block"
                        >
                            Ver proyecto
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($proyectos) === 0): ?>
                <div class="text-slate-400">
                    No hay proyectos disponibles todavía.
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>