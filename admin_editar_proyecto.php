<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_admin();

$proyecto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($proyecto_id <= 0) {
    die(t('invalid_project'));
}

/* =========================
   1. Cargar proyecto
========================= */
$sqlProyecto = "
    SELECT p.*, u.nombre AS profesor_nombre
    FROM proyectos p
    INNER JOIN usuarios u ON p.creado_por = u.id
    WHERE p.id = ?
";
$stmtProyecto = $pdo->prepare($sqlProyecto);
$stmtProyecto->execute([$proyecto_id]);
$proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    die(t('project_not_found'));
}

$error = '';
$success = '';

/* =========================
   2. Procesar edición
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($titulo === '' || $descripcion === '') {
        $error = t('project_required_fields');
    } else {
        $sqlUpdate = "
            UPDATE proyectos
            SET titulo = ?, descripcion = ?
            WHERE id = ?
        ";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            $titulo,
            $descripcion,
            $proyecto_id
        ]);

        $success = t('project_updated_successfully');

        $stmtProyecto->execute([$proyecto_id]);
        $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('edit_project') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-5xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('edit_project') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('edit_project_desc') ?></p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a
                    href="admin_dashboard_proyectos.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    <?= t('projects_dashboard') ?>
                </a>

                <a
                    href="admin_proyectos.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    <?= t('back_to_projects') ?>
                </a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h2>

            <div class="mt-4 text-sm text-slate-400 space-y-1">
                <p><strong><?= t('project_id') ?>:</strong> <?= (int)$proyecto['id'] ?></p>
                <p><strong><?= t('teacher') ?>:</strong> <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                <p><strong><?= t('created') ?>:</strong> <?= htmlspecialchars($proyecto['creado_en']) ?></p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6"><?= t('modify_project_data') ?></h2>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block mb-2 font-semibold"><?= t('project_title') ?></label>
                    <input
                        type="text"
                        name="titulo"
                        value="<?= htmlspecialchars($proyecto['titulo']) ?>"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        required
                    >
                </div>

                <div>
                    <label class="block mb-2 font-semibold"><?= t('description') ?></label>
                    <textarea
                        name="descripcion"
                        rows="12"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        required
                    ><?= htmlspecialchars($proyecto['descripcion']) ?></textarea>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        class="bg-amber-500 hover:bg-amber-600 px-5 py-3 rounded-xl font-semibold"
                    >
                        <?= t('save_changes') ?>
                    </button>

                    <a
                        href="admin_dashboard_proyectos.php"
                        class="bg-slate-700 hover:bg-slate-600 px-5 py-3 rounded-xl font-semibold"
                    >
                        <?= t('cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>