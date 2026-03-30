<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$nombreUsuario = $_SESSION['user_nombre'] ?? t('student');

if (!$usuarioId) {
    die(t('user_not_identified'));
}

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die(t('exercise_not_specified'));
}

$mensaje = null;
$error = null;

/**
 * =========================================================
 * CARGAR EJERCICIO (CON TRADUCCIÓN)
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT 
        e.id,
        e.codigo,
        COALESCE(et.titulo, e.titulo) AS titulo,
        COALESCE(et.descripcion, e.descripcion) AS descripcion,
        e.capitulo,
        e.orden,
        e.activo
    FROM ejercicios e
    LEFT JOIN ejercicios_traducciones et
        ON et.ejercicio_id = e.id
       AND et.lang = :lang
    WHERE e.codigo = :codigo
      AND e.activo = 1
    LIMIT 1
");
$stmt->execute([
    'codigo' => $codigo,
    'lang' => $lang
]);
$ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ejercicio) {
    die(t('exercise_not_found'));
}

/**
 * =========================================================
 * CARGAR RESPUESTA EXISTENTE
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT id, respuesta, completado
    FROM ejercicio_respuestas
    WHERE usuario_id = :usuario_id
      AND ejercicio_codigo = :ejercicio_codigo
    LIMIT 1
");
$stmt->execute([
    'usuario_id' => $usuarioId,
    'ejercicio_codigo' => $codigo
]);
$respuestaExistente = $stmt->fetch(PDO::FETCH_ASSOC);

$respuestaTexto = $respuestaExistente['respuesta'] ?? '';
$yaCompletado = isset($respuestaExistente['completado']) && (int)$respuestaExistente['completado'] === 1;

/**
 * =========================================================
 * PROCESAR FORMULARIO
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $respuestaTexto = trim($_POST['respuesta'] ?? '');

    try {
        if ($accion === 'guardar') {
            if ($respuestaExistente) {
                $stmt = $pdo->prepare("
                    UPDATE ejercicio_respuestas
                    SET respuesta = :respuesta
                    WHERE id = :id
                ");
                $stmt->execute([
                    'respuesta' => $respuestaTexto,
                    'id' => $respuestaExistente['id']
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO ejercicio_respuestas (usuario_id, ejercicio_codigo, respuesta, completado)
                    VALUES (:usuario_id, :ejercicio_codigo, :respuesta, 0)
                ");
                $stmt->execute([
                    'usuario_id' => $usuarioId,
                    'ejercicio_codigo' => $codigo,
                    'respuesta' => $respuestaTexto
                ]);
            }

            $mensaje = t('response_saved_successfully');
        }

        if ($accion === 'enviar') {
            if ($respuestaTexto === '') {
                $error = t('write_answer_before_submit');
            } else {
                if ($respuestaExistente) {
                    $stmt = $pdo->prepare("
                        UPDATE ejercicio_respuestas
                        SET respuesta = :respuesta,
                            completado = 1
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'respuesta' => $respuestaTexto,
                        'id' => $respuestaExistente['id']
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO ejercicio_respuestas (usuario_id, ejercicio_codigo, respuesta, completado)
                        VALUES (:usuario_id, :ejercicio_codigo, :respuesta, 1)
                    ");
                    $stmt->execute([
                        'usuario_id' => $usuarioId,
                        'ejercicio_codigo' => $codigo,
                        'respuesta' => $respuestaTexto
                    ]);
                }

                $yaCompletado = true;
                $mensaje = t('exercise_sent_completed');
            }
        }
    } catch (PDOException $e) {
        $error = t('could_not_save_response') . ': ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($ejercicio['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">

    <h1 class="text-3xl font-bold mb-2">
        <?= htmlspecialchars($ejercicio['titulo']) ?>
    </h1>

    <p class="text-slate-400 mb-6">
        <?= t('chapter') ?> <?= htmlspecialchars($ejercicio['capitulo']) ?> —
        <?= t('code') ?> <?= htmlspecialchars($ejercicio['codigo']) ?>
    </p>

    <div class="bg-slate-900 p-6 rounded-xl mb-6">
        <h2 class="text-xl font-bold mb-3"><?= t('statement') ?></h2>
        <p class="whitespace-pre-line text-slate-300">
            <?= htmlspecialchars($ejercicio['descripcion']) ?>
        </p>
    </div>

    <?php if ($mensaje): ?>
        <div class="bg-green-500/20 p-4 mb-4 rounded-xl">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-500/20 p-4 mb-4 rounded-xl">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <textarea
            name="respuesta"
            rows="15"
            class="w-full bg-slate-900 border border-slate-700 p-4 rounded-xl font-mono"
        ><?= htmlspecialchars($respuestaTexto) ?></textarea>

        <div class="flex gap-3">
            <button name="accion" value="guardar" class="bg-gray-600 px-4 py-2 rounded-xl">
                <?= t('save_draft') ?>
            </button>

            <button name="accion" value="enviar" class="bg-sky-500 px-4 py-2 rounded-xl">
                <?= t('send_exercise') ?>
            </button>
        </div>
    </form>

    <div class="mt-6">
        <?php if ($yaCompletado): ?>
            <span class="text-green-400">✔ <?= t('exercise_completed') ?></span>
        <?php else: ?>
            <span class="text-yellow-400"><?= t('pending_dot') ?></span>
        <?php endif; ?>
    </div>

</div>
</body>
</html>