<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_admin();

$entrega_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($entrega_id <= 0) {
    die(t('invalid_delivery'));
}

/* =========================
   1. Cargar entrega
========================= */
$sqlEntrega = "
    SELECT pe.*, p.titulo AS proyecto_titulo
    FROM proyecto_entregas pe
    INNER JOIN proyectos p ON pe.proyecto_id = p.id
    WHERE pe.id = ?
";
$stmtEntrega = $pdo->prepare($sqlEntrega);
$stmtEntrega->execute([$entrega_id]);
$entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);

if (!$entrega) {
    die(t('delivery_not_found'));
}

$error = '';
$success = '';

/* =========================
   2. Procesar edición
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $orden_entrega = (int) ($_POST['orden_entrega'] ?? 0);
    $fecha_limite = trim($_POST['fecha_limite'] ?? '');

    if ($titulo === '' || $descripcion === '' || $orden_entrega <= 0) {
        $error = t('delivery_required_fields');
    } else {
        $fecha_limite_sql = null;

        if ($fecha_limite !== '') {
            $timestamp = strtotime($fecha_limite);

            if ($timestamp === false) {
                $error = t('invalid_deadline');
            } else {
                $fecha_limite_sql = date('Y-m-d H:i:s', $timestamp);
            }
        }

        if ($error === '') {
            try {
                $sqlUpdate = "
                    UPDATE proyecto_entregas
                    SET titulo = ?, descripcion = ?, orden_entrega = ?, fecha_limite = ?
                    WHERE id = ?
                ";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $titulo,
                    $descripcion,
                    $orden_entrega,
                    $fecha_limite_sql,
                    $entrega_id
                ]);

                $success = t('delivery_updated_successfully');

                $stmtEntrega->execute([$entrega_id]);
                $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                if ((int)$e->getCode() === 23000) {
                    $error = t('delivery_order_already_exists');
                } else {
                    $error = t('delivery_update_error') . ': ' . $e->getMessage();
                }
            }
        }
    }
}

/* =========================
   3. Valor para datetime-local
========================= */
$fecha_limite_input = '';

if (!empty($entrega['fecha_limite'])) {
    $fecha_limite_input = date('Y-m-d\TH:i', strtotime($entrega['fecha_limite']));
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('edit_delivery') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-5xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('edit_delivery') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('edit_delivery_desc') ?></p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a
                    href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$entrega['proyecto_id'] ?>"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    <?= t('back_to_deliveries') ?>
                </a>

                <a
                    href="admin_proyectos.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    <?= t('projects') ?>
                </a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($entrega['proyecto_titulo']) ?></h2>
            <div class="mt-4 text-slate-300 space-y-2">
                <p><strong><?= t('delivery_id') ?>:</strong> <?= (int)$entrega['id'] ?></p>
                <p><strong><?= t('current_order') ?>:</strong> <?= (int)$entrega['orden_entrega'] ?></p>
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
            <h2 class="text-2xl font-bold mb-6"><?= t('modify_delivery_data') ?></h2>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block mb-2 font-semibold"><?= t('delivery_title') ?></label>
                    <input
                        type="text"
                        name="titulo"
                        value="<?= htmlspecialchars($entrega['titulo']) ?>"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        required
                    >
                </div>

                <div>
                    <label class="block mb-2 font-semibold"><?= t('description') ?></label>
                    <textarea
                        name="descripcion"
                        rows="8"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        required
                    ><?= htmlspecialchars($entrega['descripcion']) ?></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-semibold"><?= t('delivery_order') ?></label>
                        <input
                            type="number"
                            name="orden_entrega"
                            min="1"
                            value="<?= (int)$entrega['orden_entrega'] ?>"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            required
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('deadline') ?></label>
                        <input
                            type="datetime-local"
                            name="fecha_limite"
                            value="<?= htmlspecialchars($fecha_limite_input) ?>"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        type="submit"
                        class="bg-amber-500 hover:bg-amber-600 px-5 py-3 rounded-xl font-semibold"
                    >
                        <?= t('save_changes') ?>
                    </button>

                    <a
                        href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$entrega['proyecto_id'] ?>"
                        class="bg-slate-700 hover:bg-slate-600 px-5 py-3 rounded-xl font-semibold"
                    >
                        <?= t('cancel') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>