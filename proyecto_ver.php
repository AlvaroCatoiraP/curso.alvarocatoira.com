<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_connexion();

$usuario_id = $_SESSION['user_id'];
$usuario_rol = $_SESSION['rol'] ?? '';

if ($usuario_rol !== 'admin' && $usuario_rol !== 'student') {
    die("Rol no permitido.");
}

$proyecto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($proyecto_id <= 0) {
    die("Proyecto no válido.");
}

$sqlProyecto = "
    SELECT p.*, u.nombre AS profesor_nombre
    FROM proyectos p
    JOIN usuarios u ON p.creado_por = u.id
    WHERE p.id = ?
";
$stmtProyecto = $pdo->prepare($sqlProyecto);
$stmtProyecto->execute([$proyecto_id]);
$proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    die("Proyecto no encontrado.");
}

$sqlEntregas = "
    SELECT pe.*
    FROM proyecto_entregas pe
    WHERE pe.proyecto_id = ?
    ORDER BY pe.orden_entrega ASC, pe.creado_en ASC
";
$stmtEntregas = $pdo->prepare($sqlEntregas);
$stmtEntregas->execute([$proyecto_id]);
$entregas = $stmtEntregas->fetchAll(PDO::FETCH_ASSOC);

$entregas_estudiante = [];

if ($usuario_rol === 'student') {
    $sqlEntregasEstudiante = "
        SELECT *
        FROM proyecto_entregas_estudiantes
        WHERE estudiante_id = ?
          AND proyecto_entrega_id IN (
              SELECT id FROM proyecto_entregas WHERE proyecto_id = ?
          )
    ";
    $stmtEntregasEstudiante = $pdo->prepare($sqlEntregasEstudiante);
    $stmtEntregasEstudiante->execute([$usuario_id, $proyecto_id]);
    $filas = $stmtEntregasEstudiante->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filas as $fila) {
        $entregas_estudiante[$fila['proyecto_entrega_id']] = $fila;
    }
}

function entregaAnteriorLiberada(array $entregas, array $entregas_estudiante, int $indiceActual): bool
{
    if ($indiceActual === 0) {
        return true;
    }

    $entregaAnterior = $entregas[$indiceActual - 1];
    $datosAnterior = $entregas_estudiante[$entregaAnterior['id']] ?? null;

    if ($datosAnterior === null) {
        return false;
    }

    return $datosAnterior['estado'] === 'corregido' && (int)$datosAnterior['liberada'] === 1;
}

function obtenerEstadoEntrega(array $entrega, bool $desbloqueada, ?array $entregaAlumno): array
{
    $ahora = time();
    $fueraDePlazo = false;

    if (!empty($entrega['fecha_limite'])) {
        $fueraDePlazo = strtotime($entrega['fecha_limite']) < $ahora;
    }

    if (!$desbloqueada && $entregaAlumno === null) {
        return [
            'texto' => 'Bloqueada',
            'clase' => 'bg-slate-700 text-slate-200',
            'puede_entregar' => false
        ];
    }

    if ($entregaAlumno !== null) {
        if ($entregaAlumno['estado'] === 'corregido' && (int)$entregaAlumno['liberada'] === 1) {
            return [
                'texto' => 'Corregida y liberada',
                'clase' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                'puede_entregar' => false
            ];
        }

        if ($entregaAlumno['estado'] === 'corregido') {
            return [
                'texto' => 'Corregida',
                'clase' => 'bg-green-500/20 text-green-300 border border-green-500/30',
                'puede_entregar' => false
            ];
        }

        return [
            'texto' => 'Entregada',
            'clase' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
            'puede_entregar' => false
        ];
    }

    if ($fueraDePlazo) {
        return [
            'texto' => 'Fuera de plazo',
            'clase' => 'bg-red-500/20 text-red-300 border border-red-500/30',
            'puede_entregar' => false
        ];
    }

    return [
        'texto' => 'Disponible',
        'clase' => 'bg-sky-500/20 text-sky-300 border border-sky-500/30',
        'puede_entregar' => true
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($proyecto['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-6xl mx-auto p-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h1>
                <p class="text-slate-400 mt-2">Proyecto con entregas secuenciales.</p>
            </div>

            <div class="flex gap-3 flex-wrap">
                <?php if ($usuario_rol === 'admin'): ?>
                    <a href="admin_proyectos.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                        Volver a proyectos
                    </a>
                <?php else: ?>
                    <a href="proyectos.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                        Volver a proyectos
                    </a>
                <?php endif; ?>

                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                    Cerrar sesión
                </a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-8">
            <h2 class="text-2xl font-bold">Descripción</h2>
                <p class="text-slate-300 mt-3 whitespace-pre-line">
                    <?php
                        $texto = htmlspecialchars($proyecto['descripcion']);

                        // Reemplazar URLs por "Ver documentación"
                        $texto = preg_replace_callback(
                            '/(https?:\/\/[^\s]+)/',
                            function ($matches) {
                                return '<a href="' . $matches[0] . '" target="_blank" class="text-sky-400 underline hover:text-sky-300 font-semibold">Ver documentación</a>';
                            },
                            $texto
                        );

                        echo nl2br($texto);
                        ?>
                    </p>

            <div class="mt-4 text-sm text-slate-400 space-y-1">
                <p>Profesor: <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                <p>Creado: <?= htmlspecialchars($proyecto['creado_en']) ?></p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6">Entregas del proyecto</h2>

            <?php if (count($entregas) === 0): ?>
                <p class="text-slate-400">Este proyecto todavía no tiene entregas.</p>
            <?php else: ?>
                <div class="space-y-5">
                    <?php foreach ($entregas as $indice => $entrega): ?>
                        <?php
                        $entregaAlumno = $entregas_estudiante[$entrega['id']] ?? null;

                        if ($usuario_rol === 'admin') {
                            $desbloqueada = true;
                        } elseif ($usuario_rol === 'student') {
                            $desbloqueada = entregaAnteriorLiberada($entregas, $entregas_estudiante, $indice);
                        } else {
                            $desbloqueada = false;
                        }

                        $estado = obtenerEstadoEntrega($entrega, $desbloqueada, $entregaAlumno);
                        ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-5">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-3 mb-3">
                                        <h3 class="text-xl font-bold">
                                            Entrega <?= (int)$entrega['orden_entrega'] ?>: <?= htmlspecialchars($entrega['titulo']) ?>
                                        </h3>

                                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $estado['clase'] ?>">
                                            <?= htmlspecialchars($estado['texto']) ?>
                                        </span>
                                    </div>

                                    <p class="text-slate-300 whitespace-pre-line">
                                        <?= htmlspecialchars($entrega['descripcion']) ?>
                                    </p>

                                    <div class="mt-4 text-sm text-slate-400 space-y-1">
                                        <p>
                                            <strong>Fecha límite:</strong>
                                            <?= !empty($entrega['fecha_limite']) ? htmlspecialchars($entrega['fecha_limite']) : 'Sin fecha límite' ?>
                                        </p>

                                        <?php if ($entregaAlumno !== null): ?>
                                            <p><strong>Entregado el:</strong> <?= htmlspecialchars($entregaAlumno['entregado_en']) ?></p>
                                            <p><strong>Archivo:</strong> <?= htmlspecialchars($entregaAlumno['nombre_archivo_original']) ?></p>

                                            <?php if ($entregaAlumno['nota'] !== null): ?>
                                                <p><strong>Nota:</strong> <?= htmlspecialchars($entregaAlumno['nota']) ?></p>
                                            <?php endif; ?>

                                            <?php if (!empty($entregaAlumno['comentario'])): ?>
                                                <div class="mt-3 p-3 rounded-xl bg-slate-900 border border-slate-700">
                                                    <p class="font-semibold text-slate-200 mb-1">Comentario del profesor:</p>
                                                    <p class="text-slate-300 whitespace-pre-line">
                                                        <?= htmlspecialchars($entregaAlumno['comentario']) ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($entregaAlumno['estado'] === 'corregido' && (int)$entregaAlumno['liberada'] === 0): ?>
                                                <div class="mt-3 p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/30 text-yellow-300">
                                                    Esta fase ya fue corregida, pero todavía no ha sido liberada por el profesor.
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($usuario_rol === 'student' && $indice > 0): ?>
                                            <?php
                                            $entregaAnterior = $entregas[$indice - 1];
                                            $datosAnterior = $entregas_estudiante[$entregaAnterior['id']] ?? null;
                                            ?>
                                            <?php if ($datosAnterior !== null && $datosAnterior['estado'] === 'corregido' && (int)$datosAnterior['liberada'] === 0): ?>
                                                <div class="mt-3 p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/30 text-yellow-300">
                                                    Esperando validación del profesor para desbloquear esta entrega.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 min-w-[220px]">
                                    <?php if ($usuario_rol === 'admin'): ?>
                                        <a
                                            href="admin_proyecto_entregas_alumnos.php?entrega_id=<?= (int)$entrega['id'] ?>"
                                            class="bg-emerald-500 hover:bg-emerald-600 px-4 py-3 rounded-xl font-semibold text-center"
                                        >
                                            Ver entregas de alumnos
                                        </a>
                                    <?php else: ?>
                                        <?php if ($estado['puede_entregar']): ?>
                                            <form action="proyecto_subir_entrega.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                                                <input type="hidden" name="proyecto_entrega_id" value="<?= (int)$entrega['id'] ?>">

                                                <input
                                                    type="file"
                                                    name="archivo"
                                                    accept=".zip"
                                                    class="block w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-sky-500 file:text-white hover:file:bg-sky-600"
                                                    required
                                                >

                                                <button
                                                    type="submit"
                                                    class="w-full bg-sky-500 hover:bg-sky-600 px-4 py-3 rounded-xl font-semibold"
                                                >
                                                    Subir ZIP
                                                </button>
                                            </form>
                                        <?php elseif ($entregaAlumno !== null && !empty($entregaAlumno['ruta_archivo'])): ?>
                                            <a
                                                href="<?= htmlspecialchars($entregaAlumno['ruta_archivo']) ?>"
                                                target="_blank"
                                                class="bg-slate-700 hover:bg-slate-600 px-4 py-3 rounded-xl font-semibold text-center"
                                            >
                                                Ver archivo enviado
                                            </a>
                                        <?php else: ?>
                                            <div class="bg-slate-900 border border-slate-700 px-4 py-3 rounded-xl text-sm text-slate-400">
                                                Esta entrega no está disponible todavía.
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>