<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$proyecto_id = isset($_GET['proyecto_id']) ? (int) $_GET['proyecto_id'] : 0;

if ($proyecto_id <= 0) {
    die("Proyecto no válido.");
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
    die("Proyecto no encontrado.");
}

$error = '';
$success = '';

/* =========================
   2. Procesar corrección
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entrega_estudiante_id = isset($_POST['entrega_estudiante_id']) ? (int) $_POST['entrega_estudiante_id'] : 0;
    $nota = trim($_POST['nota'] ?? '');
    $comentario = trim($_POST['comentario'] ?? '');
    $liberada = isset($_POST['liberada']) ? 1 : 0;

    if ($entrega_estudiante_id <= 0) {
        $error = "Entrega de estudiante no válida.";
    } elseif ($nota === '') {
        $error = "La nota es obligatoria.";
    } elseif (!is_numeric($nota)) {
        $error = "La nota debe ser numérica.";
    } else {
        $notaFloat = (float)$nota;

        if ($notaFloat < 0 || $notaFloat > 10) {
            $error = "La nota debe estar entre 0 y 10.";
        } else {
            $sqlVerificar = "
                SELECT pee.id
                FROM proyecto_entregas_estudiantes pee
                INNER JOIN proyecto_entregas pe ON pee.proyecto_entrega_id = pe.id
                WHERE pee.id = ? AND pe.proyecto_id = ?
            ";
            $stmtVerificar = $pdo->prepare($sqlVerificar);
            $stmtVerificar->execute([$entrega_estudiante_id, $proyecto_id]);
            $filaVerificada = $stmtVerificar->fetch(PDO::FETCH_ASSOC);

            if (!$filaVerificada) {
                $error = "La entrega indicada no pertenece a este proyecto.";
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
                    ? "Entrega corregida y fase liberada correctamente."
                    : "Entrega corregida correctamente. La siguiente fase sigue bloqueada.";
            }
        }
    }
}

/* =========================
   3. Cargar entregas del proyecto
========================= */
$sqlEntregas = "
    SELECT
        pee.id AS entrega_estudiante_id,
        pee.nombre_archivo_original,
        pee.nombre_archivo_guardado,
        pee.ruta_archivo,
        pee.entregado_en,
        pee.nota,
        pee.comentario,
        pee.estado,
        pee.liberada,

        pe.id AS entrega_id,
        pe.titulo AS entrega_titulo,
        pe.descripcion AS entrega_descripcion,
        pe.orden_entrega,
        pe.fecha_limite,

        u.id AS estudiante_id,
        u.nombre AS estudiante_nombre,
        u.email AS estudiante_email,
        u.rol AS estudiante_rol

    FROM proyecto_entregas_estudiantes pee
    INNER JOIN proyecto_entregas pe ON pee.proyecto_entrega_id = pe.id
    INNER JOIN usuarios u ON pee.estudiante_id = u.id
    WHERE pe.proyecto_id = ?
    ORDER BY pe.orden_entrega ASC, pee.entregado_en DESC, pee.id DESC
";
$stmtEntregas = $pdo->prepare($sqlEntregas);
$stmtEntregas->execute([$proyecto_id]);
$filas = $stmtEntregas->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   4. Agrupar por fase
========================= */
$entregasAgrupadas = [];

foreach ($filas as $fila) {
    $entregaId = (int)$fila['entrega_id'];

    if (!isset($entregasAgrupadas[$entregaId])) {
        $entregasAgrupadas[$entregaId] = [
            'entrega_id' => $entregaId,
            'entrega_titulo' => $fila['entrega_titulo'],
            'entrega_descripcion' => $fila['entrega_descripcion'],
            'orden_entrega' => $fila['orden_entrega'],
            'fecha_limite' => $fila['fecha_limite'],
            'items' => []
        ];
    }

    $entregasAgrupadas[$entregaId]['items'][] = $fila;
}

function contarEstado(array $items, string $tipo): int
{
    $contador = 0;

    foreach ($items as $item) {
        if ($tipo === 'pendiente' && $item['estado'] === 'entregado') {
            $contador++;
        }

        if ($tipo === 'corregido' && $item['estado'] === 'corregido') {
            $contador++;
        }

        if ($tipo === 'liberado' && (int)$item['liberada'] === 1) {
            $contador++;
        }
    }

    return $contador;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correcciones del proyecto</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-7xl mx-auto p-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Correcciones del proyecto</h1>
                <p class="text-slate-400 mt-2">Gestiona las entregas recibidas y corrige desde una sola vista.</p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <a
                    href="admin_dashboard_proyectos.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    Dashboard proyectos
                </a>

                <a
                    href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$proyecto['id'] ?>"
                    class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold"
                >
                    Gestionar fases
                </a>

                <a
                    href="admin_proyectos.php"
                    class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold"
                >
                    Proyectos
                </a>

            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h2>
            <p class="text-slate-300 mt-3 whitespace-pre-line"><?= htmlspecialchars($proyecto['descripcion']) ?></p>

            <div class="mt-4 text-sm text-slate-400 space-y-1">
                <p><strong>Profesor:</strong> <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                <p><strong>Creado:</strong> <?= htmlspecialchars($proyecto['creado_en']) ?></p>
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

        <?php if (count($entregasAgrupadas) === 0): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <p class="text-slate-400">Todavía no hay entregas enviadas por alumnos en este proyecto.</p>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($entregasAgrupadas as $grupo): ?>
                    <?php
                    $pendientes = contarEstado($grupo['items'], 'pendiente');
                    $corregidos = contarEstado($grupo['items'], 'corregido');
                    $liberados = contarEstado($grupo['items'], 'liberado');
                    ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6 mb-6">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3 mb-3">
                                    <h2 class="text-2xl font-bold">
                                        Fase <?= (int)$grupo['orden_entrega'] ?> — <?= htmlspecialchars($grupo['entrega_titulo']) ?>
                                    </h2>

                                    <?php if ($pendientes > 0): ?>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                            <?= $pendientes ?> pendiente(s)
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                            Sin pendientes
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <p class="text-slate-300 whitespace-pre-line">
                                    <?= htmlspecialchars($grupo['entrega_descripcion']) ?>
                                </p>

                                <div class="mt-4 text-sm text-slate-400 space-y-1">
                                    <p>
                                        <strong>Fecha límite:</strong>
                                        <?= !empty($grupo['fecha_limite']) ? htmlspecialchars($grupo['fecha_limite']) : 'Sin fecha límite' ?>
                                    </p>
                                </div>
                            </div>

                            <div class="xl:w-[360px] w-full">
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Pendientes</p>
                                        <p class="text-2xl font-bold mt-1"><?= $pendientes ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Corregidas</p>
                                        <p class="text-2xl font-bold mt-1"><?= $corregidos ?></p>
                                    </div>

                                    <div class="bg-slate-800 border border-slate-700 rounded-xl p-4">
                                        <p class="text-slate-400 text-sm">Liberadas</p>
                                        <p class="text-2xl font-bold mt-1"><?= $liberados ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($grupo['items'] as $fila): ?>
                                <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                                    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-6">
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-3 mb-3">
                                                <h3 class="text-xl font-bold">
                                                    <?= htmlspecialchars($fila['estudiante_nombre']) ?>
                                                </h3>

                                                <?php if ($fila['estado'] === 'corregido' && (int)$fila['liberada'] === 1): ?>
                                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                                        Corregido y liberado
                                                    </span>
                                                <?php elseif ($fila['estado'] === 'corregido'): ?>
                                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-500/20 text-green-300 border border-green-500/30">
                                                        Corregido
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-3 py-1 rounded-full text-sm font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                                        Entregado
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="space-y-2 text-slate-300">
                                                <p><strong>Email:</strong> <?= htmlspecialchars($fila['estudiante_email']) ?></p>
                                                <p><strong>Rol:</strong> <?= htmlspecialchars($fila['estudiante_rol']) ?></p>
                                                <p><strong>Fecha de entrega:</strong> <?= htmlspecialchars($fila['entregado_en']) ?></p>
                                                <p><strong>Archivo:</strong> <?= htmlspecialchars($fila['nombre_archivo_original']) ?></p>
                                            </div>

                                            <div class="mt-4 flex flex-wrap gap-3">
                                                <a
                                                    href="<?= htmlspecialchars($fila['ruta_archivo']) ?>"
                                                    target="_blank"
                                                    class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold"
                                                >
                                                    Ver / descargar archivo
                                                </a>

                                                <a
                                                    href="admin_proyecto_entregas_alumnos.php?entrega_id=<?= (int)$fila['entrega_id'] ?>"
                                                    class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl font-semibold"
                                                >
                                                    Ver fase completa
                                                </a>
                                            </div>

                                            <?php if ($fila['nota'] !== null || !empty($fila['comentario'])): ?>
                                                <div class="mt-5 bg-slate-900 border border-slate-700 rounded-xl p-4">
                                                    <h4 class="font-bold mb-2">Corrección actual</h4>

                                                    <?php if ($fila['nota'] !== null): ?>
                                                        <p class="text-slate-300 mb-2">
                                                            <strong>Nota:</strong> <?= htmlspecialchars($fila['nota']) ?>/10
                                                        </p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($fila['comentario'])): ?>
                                                        <p class="text-slate-300 whitespace-pre-line">
                                                            <strong>Comentario:</strong><br>
                                                            <?= htmlspecialchars($fila['comentario']) ?>
                                                        </p>
                                                    <?php endif; ?>

                                                    <p class="text-slate-300 mt-2">
                                                        <strong>Liberada:</strong> <?= (int)$fila['liberada'] === 1 ? 'Sí' : 'No' ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="xl:w-[360px] w-full">
                                            <div class="bg-slate-900 border border-slate-700 rounded-2xl p-4">
                                                <h4 class="text-lg font-bold mb-4">
                                                    <?= $fila['estado'] === 'corregido' ? 'Actualizar corrección' : 'Corregir entrega' ?>
                                                </h4>

                                                <form method="POST" class="space-y-4">
                                                    <input
                                                        type="hidden"
                                                        name="entrega_estudiante_id"
                                                        value="<?= (int)$fila['entrega_estudiante_id'] ?>"
                                                    >

                                                    <div>
                                                        <label class="block mb-2 font-semibold">Nota /10</label>
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
                                                        <label class="block mb-2 font-semibold">Comentario</label>
                                                        <textarea
                                                            name="comentario"
                                                            rows="6"
                                                            class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                                                            placeholder="Escribe observaciones, mejoras o feedback para el alumno"
                                                        ><?= htmlspecialchars($fila['comentario'] ?? '') ?></textarea>
                                                    </div>

                                                    <div class="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            id="liberada_<?= (int)$fila['entrega_estudiante_id'] ?>"
                                                            name="liberada"
                                                            value="1"
                                                            <?= (int)$fila['liberada'] === 1 ? 'checked' : '' ?>
                                                            class="h-4 w-4"
                                                        >
                                                        <label for="liberada_<?= (int)$fila['entrega_estudiante_id'] ?>" class="text-sm text-slate-300">
                                                            Liberar siguiente entrega
                                                        </label>
                                                    </div>

                                                    <button
                                                        type="submit"
                                                        class="w-full bg-emerald-500 hover:bg-emerald-600 px-4 py-3 rounded-xl font-semibold"
                                                    >
                                                        Guardar corrección
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>