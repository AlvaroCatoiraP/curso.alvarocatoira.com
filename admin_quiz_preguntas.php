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

$quizId = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

if ($quizId <= 0) {
    die(t('invalid_quiz'));
}

/**
 * =========================================================
 * CARGAR QUIZ
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT id, capitulo, titulo, descripcion, duracion_minutos, activo
    FROM quizzes
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$quizId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die(t('quiz_not_found'));
}

$editMode = false;
$preguntaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$pregunta = [
    'id' => 0,
    'pregunta' => '',
    'opcion_a' => '',
    'opcion_b' => '',
    'opcion_c' => '',
    'opcion_d' => '',
    'respuesta_correcta' => 'A',
    'explicacion' => '',
    'orden' => 1
];

/**
 * =========================================================
 * CARGAR PREGUNTA EN MODO EDICIÓN
 * =========================================================
 */
if ($preguntaId > 0) {
    $stmt = $pdo->prepare("
        SELECT id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, explicacion, orden
        FROM quiz_preguntas
        WHERE id = ? AND quiz_id = ?
        LIMIT 1
    ");
    $stmt->execute([$preguntaId, $quizId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $pregunta = $fila;
        $editMode = true;
    } else {
        $error = t('question_not_found');
    }
}

/**
 * =========================================================
 * ELIMINAR PREGUNTA
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int)($_POST['id'] ?? 0);

    if ($deleteId <= 0) {
        $error = t('invalid_question');
    } else {
        try {
            $stmt = $pdo->prepare("
                DELETE FROM quiz_preguntas
                WHERE id = ? AND quiz_id = ?
            ");
            $stmt->execute([$deleteId, $quizId]);

            $message = t('question_deleted_successfully');

            if ($editMode && (int)$pregunta['id'] === $deleteId) {
                $pregunta = [
                    'id' => 0,
                    'pregunta' => '',
                    'opcion_a' => '',
                    'opcion_b' => '',
                    'opcion_c' => '',
                    'opcion_d' => '',
                    'respuesta_correcta' => 'A',
                    'explicacion' => '',
                    'orden' => 1
                ];
                $editMode = false;
            }
        } catch (Throwable $e) {
            $error = t('question_delete_error') . ': ' . $e->getMessage();
        }
    }
}

/**
 * =========================================================
 * CREAR / EDITAR PREGUNTA
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $idPost = (int)($_POST['id'] ?? 0);
    $textoPregunta = trim($_POST['pregunta'] ?? '');
    $opcionA = trim($_POST['opcion_a'] ?? '');
    $opcionB = trim($_POST['opcion_b'] ?? '');
    $opcionC = trim($_POST['opcion_c'] ?? '');
    $opcionD = trim($_POST['opcion_d'] ?? '');
    $respuestaCorrecta = strtoupper(trim($_POST['respuesta_correcta'] ?? 'A'));
    $explicacion = trim($_POST['explicacion'] ?? '');
    $orden = (int)($_POST['orden'] ?? 0);

    if ($textoPregunta === '') {
        $error = t('question_text_required');
    } elseif ($opcionA === '' || $opcionB === '' || $opcionC === '' || $opcionD === '') {
        $error = t('all_options_required');
    } elseif (!in_array($respuestaCorrecta, ['A', 'B', 'C', 'D'], true)) {
        $error = t('valid_correct_answer_required');
    } elseif ($orden <= 0) {
        $error = t('question_order_required');
    } else {
        try {
            if ($idPost > 0) {
                $stmt = $pdo->prepare("
                    UPDATE quiz_preguntas
                    SET pregunta = ?,
                        opcion_a = ?,
                        opcion_b = ?,
                        opcion_c = ?,
                        opcion_d = ?,
                        respuesta_correcta = ?,
                        explicacion = ?,
                        orden = ?
                    WHERE id = ? AND quiz_id = ?
                ");
                $stmt->execute([
                    $textoPregunta,
                    $opcionA,
                    $opcionB,
                    $opcionC,
                    $opcionD,
                    $respuestaCorrecta,
                    $explicacion,
                    $orden,
                    $idPost,
                    $quizId
                ]);

                $message = t('question_updated_successfully');
                $preguntaId = $idPost;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_preguntas (
                        quiz_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d,
                        respuesta_correcta, explicacion, orden
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $quizId,
                    $textoPregunta,
                    $opcionA,
                    $opcionB,
                    $opcionC,
                    $opcionD,
                    $respuestaCorrecta,
                    $explicacion,
                    $orden
                ]);

                $preguntaId = (int)$pdo->lastInsertId();
                $message = t('question_created_successfully');
            }

            $stmt = $pdo->prepare("
                SELECT id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, explicacion, orden
                FROM quiz_preguntas
                WHERE id = ? AND quiz_id = ?
                LIMIT 1
            ");
            $stmt->execute([$preguntaId, $quizId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                $pregunta = $fila;
                $editMode = true;
            }
        } catch (Throwable $e) {
            $error = t('question_save_error') . ': ' . $e->getMessage();
        }
    }
}

/**
 * =========================================================
 * LISTAR PREGUNTAS
 * =========================================================
 */
$preguntas = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, explicacion, orden
        FROM quiz_preguntas
        WHERE quiz_id = ?
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([$quizId]);
    $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = t('question_load_error') . ': ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('manage_questions') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-8">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('manage_questions') ?></h1>
                <p class="text-slate-400 mt-2"><?= h($quiz['titulo']) ?> — <?= t('chapter') ?> <?= (int)$quiz['capitulo'] ?></p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="admin_quiz.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    <?= t('back_to_quizzes') ?>
                </a>
                <a href="admin_quiz_preguntas.php?quiz_id=<?= (int)$quiz['id'] ?>" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                    <?= t('new_question') ?>
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
                    <?= $editMode ? t('edit_question') : t('create_question') ?>
                </h2>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$pregunta['id'] ?>">

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('question_text') ?></label>
                        <textarea
                            name="pregunta"
                            rows="4"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                            required
                        ><?= h($pregunta['pregunta']) ?></textarea>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 font-semibold"><?= t('option_a') ?></label>
                            <input type="text" name="opcion_a" value="<?= h($pregunta['opcion_a']) ?>" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700" required>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold"><?= t('option_b') ?></label>
                            <input type="text" name="opcion_b" value="<?= h($pregunta['opcion_b']) ?>" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700" required>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold"><?= t('option_c') ?></label>
                            <input type="text" name="opcion_c" value="<?= h($pregunta['opcion_c']) ?>" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700" required>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold"><?= t('option_d') ?></label>
                            <input type="text" name="opcion_d" value="<?= h($pregunta['opcion_d']) ?>" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700" required>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-2 font-semibold"><?= t('correct_answer') ?></label>
                            <select name="respuesta_correcta" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700">
                                <?php foreach (['A', 'B', 'C', 'D'] as $op): ?>
                                    <option value="<?= $op ?>" <?= $pregunta['respuesta_correcta'] === $op ? 'selected' : '' ?>>
                                        <?= $op ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 font-semibold"><?= t('order') ?></label>
                            <input type="number" min="1" name="orden" value="<?= h((string)$pregunta['orden']) ?>" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700" required>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold"><?= t('explanation') ?></label>
                        <textarea
                            name="explicacion"
                            rows="4"
                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        ><?= h($pregunta['explicacion']) ?></textarea>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                            <?= $editMode ? t('save_changes') : t('create_question') ?>
                        </button>

                        <?php if ($editMode): ?>
                            <a href="admin_quiz_preguntas.php?quiz_id=<?= (int)$quiz['id'] ?>" class="bg-slate-700 hover:bg-slate-600 px-5 py-3 rounded-xl font-semibold">
                                <?= t('cancel') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-6"><?= t('questions') ?></h2>

                <?php if (count($preguntas) === 0): ?>
                    <p class="text-slate-400"><?= t('no_questions_yet') ?></p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($preguntas as $item): ?>
                            <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div>
                                        <h3 class="text-xl font-bold mb-2">
                                            #<?= (int)$item['orden'] ?> — <?= h($item['pregunta']) ?>
                                        </h3>

                                        <div class="text-sm text-slate-300 space-y-1">
                                            <p><strong>A:</strong> <?= h($item['opcion_a']) ?></p>
                                            <p><strong>B:</strong> <?= h($item['opcion_b']) ?></p>
                                            <p><strong>C:</strong> <?= h($item['opcion_c']) ?></p>
                                            <p><strong>D:</strong> <?= h($item['opcion_d']) ?></p>
                                            <p class="mt-2"><strong><?= t('correct_answer') ?>:</strong> <?= h($item['respuesta_correcta']) ?></p>
                                            <?php if (!empty($item['explicacion'])): ?>
                                                <p><strong><?= t('explanation') ?>:</strong> <?= h($item['explicacion']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <a
                                            href="admin_quiz_preguntas.php?quiz_id=<?= (int)$quiz['id'] ?>&id=<?= (int)$item['id'] ?>"
                                            class="bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-xl font-semibold"
                                        >
                                            <?= t('edit') ?>
                                        </a>

                                        <form method="POST" onsubmit="return confirm('<?= h(t('confirm_delete_question')) ?>');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
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