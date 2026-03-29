<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_admin();

$entrega_id = isset($_GET['entrega_id']) ? (int) $_GET['entrega_id'] : 0;

if ($entrega_id <= 0) {
    die(t('invalid_delivery'));
}

$sqlEntrega = "
    SELECT pe.*, p.id AS proyecto_id, p.titulo AS proyecto_titulo
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrega_estudiante_id = isset($_POST['entrega_estudiante_id']) ? (int) $_POST['entrega_estudiante_id'] : 0;
    $nota = trim($_POST['nota'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $liberada = isset($_POST['liberada']) ? 1 : 0;

    if ($entrega_estudiante_id <= 0) {
        $error = t('invalid_student_submission');
    } elseif ($nota === '') {
        $error = t('grade_required');
    } elseif (!is_numeric($nota)) {
        $error = t('grade_must_be_numeric');
    } else {
        $notaFloat = (float)$nota;

        if ($notaFloat < 0 || $notaFloat > 10) {
            $error = t('grade_range_0_10');
        } else {
            $sqlVerificar = "
                SELECT id
                FROM proyecto_entregas_estudiantes
                WHERE id = ? AND proyecto_entrega_id = ?
            ";
            $stmtVerificar = $pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([$entrega_estudiante_id, $entrega_id]);
            $filaVerificada = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

            if (!$filaVerificada) {
                $error = t('student_submission_not_exists_for_phase');
            } else {
                $sqlUpdate = "
                    UPDATE proyecto_entregas_estudiantes
                    SET nota = ?, comentario = ?, estado = 'corregido', liberada = ?
                    WHERE id = ?
                ";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    $notaFloat,
                    $comentario,
                    $liberada,
                    $entrega_estudiante_id
                ]);

                $success = $liberada
                    ? t('submission_corrected_released_success')
                    : t('submission_corrected_next_locked');
            }
        }
    }
}

$sqlEntregasAlumnos = "
    SELECT 
        pee.*,
        u.nombre,
        u.email,
        u.rol
    FROM proyecto_entregas_estudiantes pee
    INNER JOIN usuarios u ON pee.estudiante_id = u.id
    WHERE pee.proyecto_entrega_id = ?
    ORDER BY pee.entregado_en DESC
";
$stmtEntregasAlumnos = $pdo->prepare($sqlEntregasAlumnos);
$stmtEntregasAlumnos->execute([$entrega_id]);
$entregasAlumnos = $stmtEntregasAlumnos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('student_deliveries') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('student_deliveries') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('student_deliveries_desc') ?></p>
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
            <h2 class="text-2xl font-bold">
                <?= htmlspecialchars($entrega['proyecto_titulo']) ?>
            </h2>

            <div class="mt-4 space-y-2 text-slate-300">
                <p>
                    <strong><?= t('phase') ?>:</strong>
                    <?= (int)$entrega['orden_entrega'] ?> - <?= htmlspecialchars($entrega['titulo']) ?>
                </p>

                <p class="whitespace-pre-line">
                    <?= htmlspecialchars($entrega['descripcion']) ?>
                </p>

                <p class="text-sm text-slate-400">
                    <strong><?= t('deadline') ?>:</strong>
                    <?= !empty($entrega['fecha_limite']) ? htmlspecialchars($entrega['fecha_limite']) : t('no_deadline') ?>
                </p>
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
            <h2 class="text-2xl font-bold mb-6"><?= t('received_deliveries') ?></h2>

            <?php if (count($entregasAlumnos) === 0): ?>
                <p class="text-slate-400"><?= t('no_student_deliveries_for_phase_yet') ?></p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($entregasAlumnos as $fila): ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                            <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <h3 class="text-xl font-bold">
                                            <?= htmlspecialchars($fila['nombre']) ?>
                                        </h3>

                                        <?php if ($fila['estado'] === 'corregido' && (int)$fila['liberada'] === 1): ?>
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                <?= t('corrected_and_released') ?>
                                            </span>
                                        <?php elseif ($fila['estado'] === 'corregido'): ?>
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-500/20 text-green-300 border border-green-500/30">
                                                <?= t('corrected_status') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                <?= t('submitted') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="space-y-2 text-slate-300">
                                        <p><strong><?= t('email') ?>:</strong> <?= htmlspecialchars($fila['email']) ?></p>
                                        <p><strong><?= t('role') ?>:</strong> <?= htmlspecialchars($fila['rol']) ?></p>
                                        <p><strong><?= t('submission_date') ?>:</strong> <?= htmlspecialchars($fila['entregado_en']) ?></p>
                                        <p><strong><?= t('file') ?>:</strong> <?= htmlspecialchars($fila['nombre_archivo_original']) ?></p>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <a
                                            href="<?= htmlspecialchars($fila['ruta_archivo']) ?>"
                                            target="_blank"
                                            class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold"
                                        >
                                            <?= t('view_download_file') ?>
                                        </a>
                                    </div>

                                    <?php if ($fila['nota'] !== null || !empty($fila['comentario'])): ?>
                                        <div class="mt-5 bg-slate-900 border border-slate-700 rounded-xl p-4">
                                            <h4 class="font-bold mb-2"><?= t('current_correction') ?></h4>

                                            <?php if ($fila['nota'] !== null): ?>
                                                <p class="text-slate-300 mb-2">
                                                    <strong><?= t('grade') ?>:</strong> <?= htmlspecialchars($fila['nota']) ?>/10
                                                </p>
                                            <?php endif; ?>

                                            <?php if (!empty($fila['comentario'])): ?>
                                                <p class="text-slate-300 whitespace-pre-line">
                                                    <strong><?= t('comment') ?>:</strong><br>
                                                    <?= htmlspecialchars($fila['comentario']) ?>
                                                </p>
                                            <?php endif; ?>

                                            <p class="text-slate-300 mt-2">
                                                <strong><?= t('released') ?>:</strong> <?= (int)$fila['liberada'] === 1 ? t('yes') : t('no') ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="xl:w-[360px] w-full">
                                    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-4">
                                        <h4 class="text-lg font-bold mb-4">
                                            <?= $fila['estado'] === 'corregido' ? t('update_correction') : t('correct_submission') ?>
                                        </h4>

                                        <form method="POST" class="space-y-4">
                                            <input
                                                type="hidden"
                                                name="entrega_estudiante_id"
                                                value="<?= (int)$fila['id'] ?>"
                                            >

                                            <div>
                                                <label class="block mb-2 font-semibold"><?= t('grade_out_of_10') ?></label>
                                                <input
                                                    type="number"
                                                    name="nota"
                                                    min="0"
                                                    max="10"
                                                    step="0.01"
                                                    value="<?= $fila['nota'] !== null ? htmlspecialchars($fila['nota']) : '' ?>"
                                                    class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block mb-2 font-semibold"><?= t('comment') ?></label>
                                                <textarea
                                                    name="comentario"
                                                    rows="6"
                                                    class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                                                    placeholder="<?= t('correction_comment_placeholder') ?>"
                                                ><?= htmlspecialchars($fila['comentario'] ?? '') ?></textarea>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <input
                                                    type="checkbox"
                                                    id="liberada_<?= (int)$fila['id'] ?>"
                                                    name="liberada"
                                                    value="1"
                                                    <?= (int)$fila['liberada'] === 1 ? 'checked' : '' ?>
                                                    class="h-4 w-4"
                                                >
                                                <label for="liberada_<?= (int)$fila['id'] ?>" class="text-sm text-slate-300">
                                                    <?= t('release_next_delivery') ?>
                                                </label>
                                            </div>

                                            <button
                                                type="submit"
                                                class="w-full bg-emerald-500 hover:bg-emerald-600 px-4 py-3 rounded-xl font-semibold"
                                            >
                                                <?= t('save_correction') ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>