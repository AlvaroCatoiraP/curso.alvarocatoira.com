<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_admin();

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

$editMode = false;
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$quiz = [
    'id' => 0,
    'capitulo' => '',
    'titulo' => '',
    'descripcion' => '',
    'duracion_minutos' => 8,
    'activo' => 1
];

/**
 * =========================================================
 * CARGAR QUIZ EN MODO EDICIÓN
 * =========================================================
 */
if ($quizId > 0) {
    $stmt = $pdo->prepare("
        SELECT id, capitulo, titulo, descripcion, duracion_minutos, activo
        FROM quizzes
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$quizId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $quiz = $fila;
        $editMode = true;
    } else {
        $error = t('quiz_not_found');
    }
}

/**
 * =========================================================
 * ELIMINAR QUIZ
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);

    if ($deleteId <= 0) {
        $error = t('invalid_quiz');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM quiz_preguntas WHERE quiz_id = ?");
            $stmt->execute([$deleteId]);

            $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt->execute([$deleteId]);

            $pdo->commit();

            $message = t('quiz_deleted_successfully');

            if ($editMode && (int)$quiz['id'] === $deleteId) {
                $quiz = [
                    'id' => 0,
                    'capitulo' => '',
                    'titulo' => '',
                    'descripcion' => '',
                    'duracion_minutos' => 8,
                    'activo' => 1
                ];
                $editMode = false;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = t('quiz_delete_error') . ': ' . $e->getMessage();
        }
    }
}

/**
 * =========================================================
 * CREAR / ACTUALIZAR QUIZ
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $idPost = (int)($_POST['id'] ?? 0);
    $capitulo = (int)($_POST['capitulo'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $duracion = (int)($_POST['duracion_minutos'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($capitulo <= 0) {
        $error = t('chapter_required');
    } elseif ($titulo === '') {
        $error = t('quiz_title_required');
    } elseif ($duracion <= 0) {
        $error = t('quiz_duration_required');
    } else {
        try {
            if ($idPost > 0) {
                $stmt = $pdo->prepare("
                    UPDATE quizzes
                    SET capitulo = ?,
                        titulo = ?,
                        descripcion = ?,
                        duracion_minutos = ?,
                        activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $capitulo,
                    $titulo,
                    $descripcion,
                    $duracion,
                    $activo,
                    $idPost
                ]);

                $message = t('quiz_updated_successfully');
                $quizId = $idPost;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO quizzes (capitulo, titulo, descripcion, duracion_minutos, activo)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $capitulo,
                    $titulo,
                    $descripcion,
                    $duracion,
                    $activo
                ]);

                $quizId = (int)$pdo->lastInsertId();
                $message = t('quiz_created_successfully');
            }

            $stmt = $pdo->prepare("
                SELECT id, capitulo, titulo, descripcion, duracion_minutos, activo
                FROM quizzes
                WHERE id = ?
                LIMIT 1
            ");
            $stmt->execute([$quizId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                $quiz = $fila;
                $editMode = true;
            }
        } catch (Throwable $e) {
            $error = t('quiz_save_error') . ': ' . $e->getMessage();
        }
    }
}

/**
 * =========================================================
 * LISTAR QUIZZES
 * =========================================================
 */
$quizzes = [];
try {
    $stmt = $pdo->query("
        SELECT
            q.id,
            q.capitulo,
            q.titulo,
            q.descripcion,
            q.duracion_minutos,
            q.activo,
            q.creado_en,
            COUNT(qp.id) AS total_preguntas
        FROM quizzes q
        LEFT JOIN quiz_preguntas qp ON qp.quiz_id = q.id
        GROUP BY q.id, q.capitulo, q.titulo, q.descripcion, q.duracion_minutos, q.activo, q.creado_en
        ORDER BY q.capitulo ASC, q.id ASC
    ");
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = t('quiz_load_error') . ': ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('manage_quiz') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-8">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('manage_quiz') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('manage_quiz_desc') ?></p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="admin_dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    <?= t('admin_dashboard') ?>
                </a>
                <a href="admin_quiz.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                    <?= t('new_quiz') ?>
                </a>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid xl:grid-cols-2 gap-6">

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-6">
                    <?= $editMode ? t('edit_quiz') : t('create_quiz') ?>
                </h2>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$quiz['id'] ?>">

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('chapter') ?></label>
                        <input
                            type="number"
                            min="1"
                            name="capitulo"
                            value="<?= h((string)$quiz['capitulo']) ?>"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            required
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('title') ?></label>
                        <input
                            type="text"
                            name="titulo"
                            value="<?= h($quiz['titulo']) ?>"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            required
                        >
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('description') ?></label>
                        <textarea
                            name="descripcion"
                            rows="5"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        ><?= h($quiz['descripcion']) ?></textarea>
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('duration_minutes') ?></label>
                        <input
                            type="number"
                            min="1"
                            name="duracion_minutos"
                            value="<?= h((string)$quiz['duracion_minutos']) ?>"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            required
                        >
                    </div>

                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            id="activo"
                            name="activo"
                            value="1"
                            <?= (int)$quiz['activo'] === 1 ? 'checked' : '' ?>
                            class="h-4 w-4"
                        >
                        <label for="activo" class="text-sm text-slate-300">
                            <?= t('active') ?>
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button
                            type="submit"
                            class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold"
                        >
                            <?= $editMode ? t('save_changes') : t('create_quiz') ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a
                                href="admin_quiz.php"
                                class="bg-slate-700 hover:bg-slate-600 px-5 py-3 rounded-xl font-semibold"
                            >
                                <?= t('cancel') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-6"><?= t('quiz_list') ?></h2>

                <?php if (count($quizzes) === 0): ?>
                    <p class="text-slate-400"><?= t('no_quizzes_yet') ?></p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($quizzes as $item): ?>
                            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3 mb-2">
                                            <h3 class="text-xl font-bold"><?= h($item['titulo']) ?></h3>

                                            <?php if ((int)$item['activo'] === 1): ?>
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                    <?= t('active') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-500/20 text-red-300 border border-red-500/30">
                                                    <?= t('inactive') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-slate-300 mb-3">
                                            <?= h($item['descripcion'] !== '' ? $item['descripcion'] : t('quiz_default_description')) ?>
                                        </p>

                                        <div class="text-sm text-slate-400 space-y-1">
                                            <p><strong><?= t('chapter') ?>:</strong> <?= (int)$item['capitulo'] ?></p>
                                            <p><strong><?= t('duration_minutes') ?>:</strong> <?= (int)$item['duracion_minutos'] ?></p>
                                            <p><strong><?= t('questions') ?>:</strong> <?= (int)$item['total_preguntas'] ?></p>
                                            <p><strong><?= t('created') ?>:</strong> <?= h($item['creado_en']) ?></p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="admin_quiz.php?id=<?= (int)$item['id'] ?>"
                                            class="bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-xl font-semibold"
                                        >
                                            <?= t('edit') ?>
                                        </a>

                                        <a
                                            href="admin_quiz_preguntas.php?quiz_id=<?= (int)$item['id'] ?>"
                                            class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold"
                                        >
                                            <?= t('manage_questions') ?>
                                        </a>

                                        <form method="POST" onsubmit="return confirm('<?= h(t('confirm_delete_quiz')) ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button
                                                type="submit"
                                                class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold"
                                            >
                                                <?= t('delete') ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>