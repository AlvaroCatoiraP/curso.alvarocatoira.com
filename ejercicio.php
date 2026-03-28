<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$nombreUsuario = $_SESSION['user_nombre'] ?? 'Estudiante';

if (!$usuarioId) {
    die("Error: usuario no identificado.");
}

$codigo = $_GET['codigo'] ?? null;

if (!$codigo) {
    die("Error: ejercicio no especificado.");
}

$mensaje = null;
$error = null;

/**
 * =========================================================
 * CARGAR EJERCICIO
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT 
        id,
        codigo,
        titulo,
        descripcion,
        capitulo,
        orden,
        activo
    FROM ejercicios
    WHERE codigo = :codigo
      AND activo = 1
    LIMIT 1
");
$stmt->execute(['codigo' => $codigo]);
$ejercicio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ejercicio) {
    die("Ejercicio no encontrado.");
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

            $yaCompletado = isset($respuestaExistente['completado']) && (int)$respuestaExistente['completado'] === 1;
            $mensaje = "Respuesta guardada correctamente.";
        }

        if ($accion === 'enviar') {
            if ($respuestaTexto === '') {
                $error = "Debes escribir una respuesta antes de enviar el ejercicio.";
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

                /**
                 * Sincronizar también la tabla progreso
                 */
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM progreso
                    WHERE usuario_id = :usuario_id
                      AND ejercicio_codigo = :ejercicio_codigo
                    LIMIT 1
                ");
                $stmt->execute([
                    'usuario_id' => $usuarioId,
                    'ejercicio_codigo' => $codigo
                ]);
                $progresoExistente = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($progresoExistente) {
                    $stmt = $pdo->prepare("
                        UPDATE progreso
                        SET completado = 1,
                            actualizado_en = CURRENT_TIMESTAMP
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        'id' => $progresoExistente['id']
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO progreso (usuario_id, ejercicio_codigo, completado)
                        VALUES (:usuario_id, :ejercicio_codigo, 1)
                    ");
                    $stmt->execute([
                        'usuario_id' => $usuarioId,
                        'ejercicio_codigo' => $codigo
                    ]);
                }

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

                $respuestaTexto = $respuestaExistente['respuesta'] ?? $respuestaTexto;
                $yaCompletado = true;
                $mensaje = "Ejercicio enviado y marcado como completado.";
            }
        }
    } catch (PDOException $e) {
        $error = "No se pudo guardar la respuesta: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ejercicio['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-6 md:p-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                    Capítulo <?= htmlspecialchars($ejercicio['capitulo']) ?>
                </p>
                <h1 class="text-3xl md:text-4xl font-bold mt-1">
                    <?= htmlspecialchars($ejercicio['titulo']) ?>
                </h1>
                <p class="text-slate-400 mt-2">
                    Código: <?= htmlspecialchars($ejercicio['codigo']) ?>
                </p>
            </div>

            <div class="flex gap-3">
                <a href="ejercicios.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                    ← Volver a ejercicios
                </a>
                <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
                    Dashboard
                </a>
            </div>
        </div>

        <div class="mt-8 grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold mb-4">Enunciado</h2>
                    <div class="text-slate-300 leading-7 whitespace-pre-line">
                        <?= htmlspecialchars($ejercicio['descripcion'] ?: 'Este ejercicio todavía no tiene descripción.') ?>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold mb-4">Tu solución</h2>

                    <?php if ($mensaje): ?>
                        <div class="mb-4 bg-sky-500/10 border border-sky-500/20 text-sky-300 rounded-xl p-4 text-sm">
                            <?= htmlspecialchars($mensaje) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="mb-4 bg-red-500/10 border border-red-500/20 text-red-300 rounded-xl p-4 text-sm">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <textarea
                            name="respuesta"
                            rows="18"
                            class="w-full bg-slate-950 border border-slate-700 rounded-2xl p-4 text-sm text-slate-200 font-mono focus:outline-none focus:ring-2 focus:ring-sky-500"
                            placeholder="Escribe aquí tu solución en Python..."
                        ><?= htmlspecialchars($respuestaTexto) ?></textarea>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                type="submit"
                                name="accion"
                                value="guardar"
                                class="bg-slate-700 hover:bg-slate-600 px-5 py-3 rounded-xl font-semibold transition"
                            >
                                Guardar borrador
                            </button>

                            <button
                                type="submit"
                                name="accion"
                                value="enviar"
                                class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold transition"
                            >
                                Enviar ejercicio
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-4">Estado</h2>

                    <?php if ($yaCompletado): ?>
                        <div class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 rounded-xl p-4">
                            ✔ Ejercicio completado.
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-500/10 border border-yellow-500/20 text-yellow-300 rounded-xl p-4">
                            Pendiente.
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 space-y-3 text-sm text-slate-400">
                        <p><span class="text-slate-300 font-semibold">Alumno:</span> <?= htmlspecialchars($nombreUsuario) ?></p>
                        <p><span class="text-slate-300 font-semibold">Capítulo:</span> <?= htmlspecialchars($ejercicio['capitulo']) ?></p>
                        <p><span class="text-slate-300 font-semibold">Orden:</span> <?= htmlspecialchars($ejercicio['orden']) ?></p>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-3">Cómo usar esta página</h2>
                    <ul class="text-sm text-slate-400 space-y-2">
                        <li>• Escribe tu solución en el editor.</li>
                        <li>• Usa “Guardar borrador” para continuar más tarde.</li>
                        <li>• Usa “Enviar ejercicio” cuando lo hayas terminado.</li>
                        <li>• Al enviarlo, se marcará como completado en tu progreso.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>