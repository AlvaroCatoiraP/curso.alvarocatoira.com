<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$proyecto_id = isset($_GET['proyecto_id']) ? (int) $_GET['proyecto_id'] : 0;

if ($proyecto_id <= 0) {
    die("Proyecto no válido.");
}

$sqlProyecto = "SELECT * FROM proyectos WHERE id = ?";
$stmtProyecto = $pdo->prepare($sqlProyecto);
$stmtProyecto->execute([$proyecto_id]);
$proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden_entrega = (int) ($_POST['orden_entrega'] ?? 0);
    $fecha_limite = trim($_POST['fecha_limite'] ?? '');

    if ($titulo === '' || $descripcion === '' || $orden_entrega <= 0) {
        $error = "Título, descripción y orden son obligatorios.";
    } else {
        $fecha_limite_sql = null;

        if ($fecha_limite !== '') {
            $fecha_limite_sql = date('Y-m-d H:i:s', strtotime($fecha_limite));
        }

        try {
            $sqlInsert = "
                INSERT INTO proyecto_entregas (proyecto_id, titulo, descripcion, orden_entrega, fecha_limite)
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                $proyecto_id,
                $titulo,
                $descripcion,
                $orden_entrega,
                $fecha_limite_sql
            ]);

            $success = "Entrega creada correctamente.";
        } catch (PDOException $e) {
            if ((int)$e->getCode() === 23000) {
                $error = "Ya existe una entrega con ese orden dentro de este proyecto.";
            } else {
                $error = "Error al crear la entrega: " . $e->getMessage();
            }
        }
    }
}

$sqlEntregas = "
    SELECT *
    FROM proyecto_entregas
    WHERE proyecto_id = ?
    ORDER BY orden_entrega ASC, creado_en ASC
";
$stmtEntregas = $pdo->prepare($sqlEntregas);
$stmtEntregas->execute([$proyecto_id]);
$entregas = $stmtEntregas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar entregas del proyecto</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Entregas del proyecto</h1>
                <p class="text-slate-400 mt-2">Añade fases secuenciales al proyecto.</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="admin_proyectos.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    Volver a proyectos
                </a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h2>
            <p class="text-slate-300 mt-3 whitespace-pre-line"><?= htmlspecialchars($proyecto['descripcion']) ?></p>
            <p class="text-sm text-slate-500 mt-4">Proyecto ID: <?= (int)$proyecto['id'] ?></p>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-10">
            <h2 class="text-2xl font-bold mb-6">Crear nueva entrega</h2>

            <?php if ($error): ?>
                <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2 font-semibold">Título de la entrega</label>
                    <input
                        type="text"
                        name="titulo"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="Ejemplo: Fase 1 - Diseño inicial"
                        required
                    >
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Descripción</label>
                    <textarea
                        name="descripcion"
                        rows="6"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="Explica qué debe hacer el alumno en esta fase"
                        required
                    ></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-semibold">Orden de la entrega</label>
                        <input
                            type="number"
                            name="orden_entrega"
                            min="1"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            placeholder="1"
                            required
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold">Fecha límite</label>
                        <input
                            type="datetime-local"
                            name="fecha_limite"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        >
                    </div>
                </div>

                <button class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                    Crear entrega
                </button>
            </form>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6">Entregas creadas</h2>

            <?php if (count($entregas) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($entregas as $entrega): ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-bold">
                                        <?= htmlspecialchars($entrega['titulo']) ?>
                                    </h3>

                                    <p class="mt-3 text-slate-300 whitespace-pre-line">
                                        <?= htmlspecialchars($entrega['descripcion']) ?>
                                    </p>

                                    <div class="mt-4 text-sm text-slate-400 space-y-1">
                                        <p><strong>Orden:</strong> <?= (int)$entrega['orden_entrega'] ?></p>
                                        <p>
                                            <strong>Fecha límite:</strong>
                                            <?= $entrega['fecha_limite'] ? htmlspecialchars($entrega['fecha_limite']) : 'Sin fecha límite' ?>
                                        </p>
                                        <p><strong>Creada:</strong> <?= htmlspecialchars($entrega['creado_en']) ?></p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <a
                                        href="admin_proyecto_entregas_alumnos.php?entrega_id=<?= (int)$entrega['id'] ?>"
                                        class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold inline-block"
                                    >
                                        Ver entregas de alumnos
                                    </a>
                                    <a
                                        href="admin_editar_proyecto_entrega.php?id=<?= (int)$entrega['id'] ?>"
                                        class="bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-xl font-semibold inline-block"
                                    >
                                        Editar entrega
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-slate-400">Todavía no hay entregas en este proyecto.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>