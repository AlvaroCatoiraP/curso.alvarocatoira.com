<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($titulo === '' || $descripcion === '') {
        $message = "El título y la descripción son obligatorios.";
    } else {
        $sql = "INSERT INTO proyectos (titulo, descripcion, creado_por) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descripcion, $_SESSION['user_id']]);

        $success = "Proyecto creado correctamente.";
    }
}

$sql = "
    SELECT p.*, u.nombre AS admin_nombre
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
    <title>Gestionar proyectos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Gestionar proyectos</h1>
                <p class="text-slate-400 mt-2">Crea proyectos formados por varias entregas secuenciales.</p>
            </div>
            <div class="flex gap-3">
                <a href="admin_dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    Admin dashboard
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                    Cerrar sesión
                </a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-10">
            <h2 class="text-2xl font-bold mb-6">Crear un proyecto</h2>

            <?php if ($message): ?>
                <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2 font-semibold">Título del proyecto</label>
                    <input
                        type="text"
                        name="titulo"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="Ejemplo: Proyecto final Python"
                    >
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Descripción</label>
                    <textarea
                        name="descripcion"
                        rows="8"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="Describe el proyecto, objetivos, requisitos, etc."
                    ></textarea>
                </div>

                <button class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                    Crear proyecto
                </button>
            </form>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6">Proyectos creados</h2>

            <div class="space-y-4">
                <?php foreach ($proyectos as $proyecto): ?>
                    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h3>

                        <p class="text-slate-300 mt-3 whitespace-pre-line">
                            <?= htmlspecialchars($proyecto['descripcion']) ?>
                        </p>

                        <div class="mt-4 text-sm text-slate-400">
                            <p>Profesor: <?= htmlspecialchars($proyecto['admin_nombre']) ?></p>
                            <p>Creado: <?= htmlspecialchars($proyecto['creado_en']) ?></p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a
                                href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$proyecto['id'] ?>"
                                class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold inline-block"
                            >
                                Gestionar entregas
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($proyectos) === 0): ?>
                    <p class="text-slate-400">No hay proyectos todavía.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>