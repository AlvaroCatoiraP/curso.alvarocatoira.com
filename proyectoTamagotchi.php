<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_email'])) {
    die('Debes iniciar sesión para acceder a este proyecto.');
}

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN BD
|--------------------------------------------------------------------------
*/
$host = 'localhost';
$dbname = 'curso_python';
$user = 'curso_user';
$pass = 'Gaetan21.6501.494.2822.Brakel';
$charset = 'utf8mb4';

/*
|--------------------------------------------------------------------------
| PARÁMETROS
|--------------------------------------------------------------------------
*/
$proyectoId = isset($_GET['id']) ? (int) $_GET['id'] : 1;
$lang = isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'fr'], true) ? $_GET['lang'] : 'es';

$alumnoEmail = (string) $_SESSION['user_email'];
$alumnoNombre = (string) ($_SESSION['user_name'] ?? 'Alumno');

$mensajeExito = '';
$mensajeError = '';

/*
|--------------------------------------------------------------------------
| TRADUCCIONES
|--------------------------------------------------------------------------
*/
$tr = [
    'es' => [
        'login_required' => 'Debes iniciar sesión para acceder a este proyecto.',
        'connection_error' => 'Error de conexión: ',
        'invalid_delivery' => 'Entrega no válida.',
        'select_valid_file' => 'Debes seleccionar un archivo válido.',
        'delivery_not_found' => 'La entrega seleccionada no existe o no pertenece a este proyecto.',
        'blocked_upload' => 'No puedes subir esta entrega todavía. Debes completar primero la anterior.',
        'already_sent' => 'Ya has enviado esta entrega.',
        'invalid_format' => 'Formato no permitido. Solo se aceptan archivos ZIP, PY o PDF.',
        'max_size' => 'El archivo supera el tamaño máximo de 10 MB.',
        'create_dir_error' => 'No se pudo crear el directorio de subida.',
        'upload_success' => 'Entrega enviada correctamente.',
        'save_error' => 'No se pudo guardar el archivo en el servidor.',
        'project_not_found' => 'Proyecto no encontrado.',
        'course_project' => 'Proyecto del curso',
        'final_deadline' => 'Entrega final',
        'restrictions' => 'Restricciones',
        'student' => 'Alumno',
        'number_of_deliveries' => 'Número de entregas',
        'completed_deliveries' => 'Entregas realizadas',
        'next_delivery' => 'Siguiente entrega',
        'project_completed' => 'Proyecto completado',
        'delivery' => 'Entrega',
        'sent' => 'Enviada',
        'unlocked' => 'Desbloqueada',
        'blocked' => 'Bloqueada',
        'deadline' => 'Fecha límite',
        'must_complete_previous' => 'Debes completar la entrega anterior para desbloquear esta etapa.',
        'statement' => 'Enunciado',
        'functions_to_do' => 'Funciones a realizar',
        'expected_signature' => 'Firma esperada',
        'expected_docstring' => 'Docstring esperado',
        'no_functions' => 'No hay funciones registradas para esta entrega.',
        'your_sent_file' => 'Tu archivo enviado',
        'sent_on' => 'Enviado el',
        'view_file' => 'Ver archivo',
        'grade' => 'Nota',
        'teacher_comment' => 'Comentario del profesor',
        'pending_correction' => 'Pendiente de corrección',
        'upload_delivery' => 'Subir entrega',
        'file' => 'Archivo',
        'allowed_formats' => 'Formatos permitidos: ZIP, PY, PDF. Tamaño máximo: 10 MB.',
        'send_delivery' => 'Enviar entrega',
        'useful_topics' => 'Temas vistos útiles',
        'no_topics' => 'No hay temas asociados a esta entrega.',
        'spanish' => 'Español',
        'french' => 'Francés',
    ],
    'fr' => [
        'login_required' => 'Vous devez vous connecter pour accéder à ce projet.',
        'connection_error' => 'Erreur de connexion : ',
        'invalid_delivery' => 'Remise invalide.',
        'select_valid_file' => 'Vous devez sélectionner un fichier valide.',
        'delivery_not_found' => 'La remise sélectionnée n’existe pas ou n’appartient pas à ce projet.',
        'blocked_upload' => 'Vous ne pouvez pas encore envoyer cette remise. Vous devez d’abord compléter la précédente.',
        'already_sent' => 'Vous avez déjà envoyé cette remise.',
        'invalid_format' => 'Format non autorisé. Seuls les fichiers ZIP, PY ou PDF sont acceptés.',
        'max_size' => 'Le fichier dépasse la taille maximale de 10 Mo.',
        'create_dir_error' => 'Impossible de créer le dossier d’envoi.',
        'upload_success' => 'Remise envoyée avec succès.',
        'save_error' => 'Impossible d’enregistrer le fichier sur le serveur.',
        'project_not_found' => 'Projet introuvable.',
        'course_project' => 'Projet du cours',
        'final_deadline' => 'Date finale',
        'restrictions' => 'Contraintes',
        'student' => 'Étudiant',
        'number_of_deliveries' => 'Nombre de remises',
        'completed_deliveries' => 'Remises effectuées',
        'next_delivery' => 'Prochaine remise',
        'project_completed' => 'Projet terminé',
        'delivery' => 'Remise',
        'sent' => 'Envoyée',
        'unlocked' => 'Débloquée',
        'blocked' => 'Bloquée',
        'deadline' => 'Date limite',
        'must_complete_previous' => 'Vous devez compléter la remise précédente pour débloquer cette étape.',
        'statement' => 'Énoncé',
        'functions_to_do' => 'Fonctions à réaliser',
        'expected_signature' => 'Signature attendue',
        'expected_docstring' => 'Docstring attendu',
        'no_functions' => 'Aucune fonction enregistrée pour cette remise.',
        'your_sent_file' => 'Votre fichier envoyé',
        'sent_on' => 'Envoyé le',
        'view_file' => 'Voir le fichier',
        'grade' => 'Note',
        'teacher_comment' => 'Commentaire du professeur',
        'pending_correction' => 'En attente de correction',
        'upload_delivery' => 'Envoyer la remise',
        'file' => 'Fichier',
        'allowed_formats' => 'Formats autorisés : ZIP, PY, PDF. Taille maximale : 10 Mo.',
        'send_delivery' => 'Envoyer la remise',
        'useful_topics' => 'Thèmes utiles déjà vus',
        'no_topics' => 'Aucun thème associé à cette remise.',
        'spanish' => 'Espagnol',
        'french' => 'Français',
    ]
];

function t(array $tr, string $lang, string $key): string
{
    return $tr[$lang][$key] ?? $key;
}

/*
|--------------------------------------------------------------------------
| CONEXIÓN PDO
|--------------------------------------------------------------------------
*/
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(t($tr, $lang, 'connection_error') . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function escapar(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function formatearFecha(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }

    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return escapar($fecha);
    }

    return date('d/m/Y', $timestamp);
}

function convertirTemasEnLista(?string $temas): array
{
    if ($temas === null || trim($temas) === '') {
        return [];
    }

    $lineas = preg_split('/\r\n|\r|\n/', trim($temas));
    $resultado = [];

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea !== '') {
            $resultado[] = $linea;
        }
    }

    return $resultado;
}

function obtenerOrdenDesbloqueado(PDO $pdo, int $proyectoId, string $alumnoEmail): int
{
    $sql = "
        SELECT MAX(pe.orden_entrega) AS max_orden
        FROM proyecto_archivos_alumno pa
        INNER JOIN proyecto_entregas pe ON pe.id = pa.entrega_id
        WHERE pe.proyecto_id = :proyecto_id
          AND pa.alumno_email = :alumno_email
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'proyecto_id' => $proyectoId,
        'alumno_email' => $alumnoEmail,
    ]);

    $maxOrden = (int) ($stmt->fetchColumn() ?: 0);
    return $maxOrden + 1;
}

function entregaYaEnviada(PDO $pdo, int $entregaId, string $alumnoEmail): bool
{
    $sql = "
        SELECT COUNT(*)
        FROM proyecto_archivos_alumno
        WHERE entrega_id = :entrega_id
          AND alumno_email = :alumno_email
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'entrega_id' => $entregaId,
        'alumno_email' => $alumnoEmail,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

/*
|--------------------------------------------------------------------------
| SUBIDA DE ARCHIVO
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_entrega'])) {
    $entregaId = isset($_POST['entrega_id']) ? (int) $_POST['entrega_id'] : 0;

    if ($entregaId <= 0) {
        $mensajeError = t($tr, $lang, 'invalid_delivery');
    } elseif (!isset($_FILES['archivo_entrega']) || $_FILES['archivo_entrega']['error'] !== UPLOAD_ERR_OK) {
        $mensajeError = t($tr, $lang, 'select_valid_file');
    } else {
        $sqlEntrega = "
            SELECT id, proyecto_id, orden_entrega
            FROM proyecto_entregas
            WHERE id = :entrega_id
              AND proyecto_id = :proyecto_id
            LIMIT 1
        ";
        $stmtEntrega = $pdo->prepare($sqlEntrega);
        $stmtEntrega->execute([
            'entrega_id' => $entregaId,
            'proyecto_id' => $proyectoId,
        ]);
        $entregaActual = $stmtEntrega->fetch();

        if (!$entregaActual) {
            $mensajeError = t($tr, $lang, 'delivery_not_found');
        } else {
            $ordenDesbloqueado = obtenerOrdenDesbloqueado($pdo, $proyectoId, $alumnoEmail);
            $ordenEntregaActual = (int) $entregaActual['orden_entrega'];

            if ($ordenEntregaActual > $ordenDesbloqueado) {
                $mensajeError = t($tr, $lang, 'blocked_upload');
            } elseif (entregaYaEnviada($pdo, $entregaId, $alumnoEmail)) {
                $mensajeError = t($tr, $lang, 'already_sent');
            } else {
                $archivo = $_FILES['archivo_entrega'];
                $nombreOriginal = $archivo['name'];
                $tamano = (int) $archivo['size'];
                $tmp = $archivo['tmp_name'];

                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                $extensionesPermitidas = ['zip', 'py', 'pdf'];

                if (!in_array($extension, $extensionesPermitidas, true)) {
                    $mensajeError = t($tr, $lang, 'invalid_format');
                } elseif ($tamano > 10 * 1024 * 1024) {
                    $mensajeError = t($tr, $lang, 'max_size');
                } else {
                    $directorioBase = __DIR__ . '/uploads/proyectos';

                    if (!is_dir($directorioBase) && !mkdir($directorioBase, 0777, true) && !is_dir($directorioBase)) {
                        $mensajeError = t($tr, $lang, 'create_dir_error');
                    } else {
                        $nombreGuardado = uniqid('entrega_', true) . '.' . $extension;
                        $rutaFisica = $directorioBase . '/' . $nombreGuardado;
                        $rutaBD = 'uploads/proyectos/' . $nombreGuardado;

                        if (move_uploaded_file($tmp, $rutaFisica)) {
                            $sqlInsert = "
                                INSERT INTO proyecto_archivos_alumno
                                    (entrega_id, alumno_nombre, alumno_email, nombre_original, nombre_guardado, ruta_archivo)
                                VALUES
                                    (:entrega_id, :alumno_nombre, :alumno_email, :nombre_original, :nombre_guardado, :ruta_archivo)
                            ";

                            $stmtInsert = $pdo->prepare($sqlInsert);
                            $stmtInsert->execute([
                                'entrega_id' => $entregaId,
                                'alumno_nombre' => $alumnoNombre,
                                'alumno_email' => $alumnoEmail,
                                'nombre_original' => $nombreOriginal,
                                'nombre_guardado' => $nombreGuardado,
                                'ruta_archivo' => $rutaBD,
                            ]);

                            $mensajeExito = t($tr, $lang, 'upload_success');
                        } else {
                            $mensajeError = t($tr, $lang, 'save_error');
                        }
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| PROYECTO
|--------------------------------------------------------------------------
*/
$sqlProyecto = "
    SELECT id, titulo, descripcion, restricciones, entrega_final
    FROM proyectos
    WHERE id = :id
    LIMIT 1
";
$stmtProyecto = $pdo->prepare($sqlProyecto);
$stmtProyecto->execute(['id' => $proyectoId]);
$proyecto = $stmtProyecto->fetch();

if (!$proyecto) {
    die(t($tr, $lang, 'project_not_found'));
}

/*
|--------------------------------------------------------------------------
| ENTREGAS
|--------------------------------------------------------------------------
*/
$sqlEntregas = "
    SELECT id, proyecto_id, titulo, descripcion, fecha_limite, orden_entrega, temas_utiles
    FROM proyecto_entregas
    WHERE proyecto_id = :proyecto_id
    ORDER BY orden_entrega ASC
";
$stmtEntregas = $pdo->prepare($sqlEntregas);
$stmtEntregas->execute(['proyecto_id' => $proyectoId]);
$entregas = $stmtEntregas->fetchAll();

/*
|--------------------------------------------------------------------------
| FUNCIONES
|--------------------------------------------------------------------------
*/
$sqlFunciones = "
    SELECT id, entrega_id, nombre_funcion, firma, docstring
    FROM proyecto_funciones
    WHERE entrega_id IN (
        SELECT id
        FROM proyecto_entregas
        WHERE proyecto_id = :proyecto_id
    )
    ORDER BY entrega_id ASC, id ASC
";
$stmtFunciones = $pdo->prepare($sqlFunciones);
$stmtFunciones->execute(['proyecto_id' => $proyectoId]);
$funciones = $stmtFunciones->fetchAll();

$funcionesPorEntrega = [];
foreach ($funciones as $funcion) {
    $funcionesPorEntrega[(int) $funcion['entrega_id']][] = $funcion;
}

/*
|--------------------------------------------------------------------------
| ENTREGAS YA ENVIADAS POR EL ALUMNO
|--------------------------------------------------------------------------
*/
$sqlEntregasAlumno = "
    SELECT pa.entrega_id
    FROM proyecto_archivos_alumno pa
    INNER JOIN proyecto_entregas pe ON pe.id = pa.entrega_id
    WHERE pe.proyecto_id = :proyecto_id
      AND pa.alumno_email = :alumno_email
";
$stmtEntregasAlumno = $pdo->prepare($sqlEntregasAlumno);
$stmtEntregasAlumno->execute([
    'proyecto_id' => $proyectoId,
    'alumno_email' => $alumnoEmail,
]);
$entregasEnviadas = $stmtEntregasAlumno->fetchAll(PDO::FETCH_COLUMN);
$entregasEnviadas = array_map('intval', $entregasEnviadas);

$ordenDesbloqueado = obtenerOrdenDesbloqueado($pdo, $proyectoId, $alumnoEmail);

/*
|--------------------------------------------------------------------------
| ARCHIVOS DEL ALUMNO + NOTAS
|--------------------------------------------------------------------------
*/
$sqlArchivosAlumno = "
    SELECT pa.id,
           pa.entrega_id,
           pa.nombre_original,
           pa.ruta_archivo,
           pa.fecha_subida,
           pa.nota,
           pa.comentario_profesor,
           pa.fecha_correccion
    FROM proyecto_archivos_alumno pa
    INNER JOIN proyecto_entregas pe ON pe.id = pa.entrega_id
    WHERE pe.proyecto_id = :proyecto_id
      AND pa.alumno_email = :alumno_email
    ORDER BY pa.fecha_subida DESC
";
$stmtArchivosAlumno = $pdo->prepare($sqlArchivosAlumno);
$stmtArchivosAlumno->execute([
    'proyecto_id' => $proyectoId,
    'alumno_email' => $alumnoEmail,
]);
$archivosAlumno = $stmtArchivosAlumno->fetchAll();

$archivosPorEntrega = [];
foreach ($archivosAlumno as $archivo) {
    $archivosPorEntrega[(int) $archivo['entrega_id']][] = $archivo;
}

function buildLangUrl(int $proyectoId, string $lang): string
{
    return '?id=' . $proyectoId . '&lang=' . urlencode($lang);
}
?>
<!DOCTYPE html>
<html lang="<?= escapar($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($proyecto['titulo']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <header class="mb-8 rounded-3xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl">
            <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-4xl">
                    <p class="mb-2 text-sm font-semibold uppercase tracking-[0.25em] text-sky-400">
                        <?= escapar(t($tr, $lang, 'course_project')) ?>
                    </p>
                    <h1 class="mb-4 text-3xl font-bold text-white md:text-4xl">
                        <?= escapar($proyecto['titulo']) ?>
                    </h1>
                    <p class="leading-7 text-slate-300">
                        <?= nl2br(escapar($proyecto['descripcion'])) ?>
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <div class="flex overflow-hidden rounded-xl border border-slate-700">
                        <a href="<?= escapar(buildLangUrl($proyectoId, 'es')) ?>"
                           class="px-4 py-2 text-sm font-semibold <?= $lang === 'es' ? 'bg-sky-600 text-white' : 'bg-slate-900 text-slate-300 hover:bg-slate-800' ?>">
                            <?= escapar(t($tr, $lang, 'spanish')) ?>
                        </a>
                        <a href="<?= escapar(buildLangUrl($proyectoId, 'fr')) ?>"
                           class="px-4 py-2 text-sm font-semibold <?= $lang === 'fr' ? 'bg-sky-600 text-white' : 'bg-slate-900 text-slate-300 hover:bg-slate-800' ?>">
                            <?= escapar(t($tr, $lang, 'french')) ?>
                        </a>
                    </div>

                    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">
                            <?= escapar(t($tr, $lang, 'final_deadline')) ?>
                        </p>
                        <p class="mt-1 text-xl font-bold text-amber-100">
                            <?= formatearFecha($proyecto['entrega_final']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <?php if (!empty($proyecto['restricciones'])): ?>
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-5">
                    <h2 class="mb-3 text-lg font-semibold text-rose-200">
                        <?= escapar(t($tr, $lang, 'restrictions')) ?>
                    </h2>
                    <p class="leading-7 text-slate-200">
                        <?= nl2br(escapar($proyecto['restricciones'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        </header>

        <section class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400"><?= escapar(t($tr, $lang, 'student')) ?></p>
                <p class="mt-2 text-lg font-semibold text-white"><?= escapar($alumnoEmail) ?></p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400"><?= escapar(t($tr, $lang, 'number_of_deliveries')) ?></p>
                <p class="mt-2 text-3xl font-bold text-white"><?= count($entregas) ?></p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400"><?= escapar(t($tr, $lang, 'completed_deliveries')) ?></p>
                <p class="mt-2 text-3xl font-bold text-emerald-400"><?= count($entregasEnviadas) ?></p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <p class="text-sm text-slate-400"><?= escapar(t($tr, $lang, 'next_delivery')) ?></p>
                <p class="mt-2 text-lg font-semibold text-sky-400">
                    <?= $ordenDesbloqueado <= count($entregas) ? escapar(t($tr, $lang, 'delivery')) . ' ' . $ordenDesbloqueado : escapar(t($tr, $lang, 'project_completed')) ?>
                </p>
            </div>
        </section>

        <?php if ($mensajeExito !== ''): ?>
            <div class="mb-6 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-200">
                <?= escapar($mensajeExito) ?>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError !== ''): ?>
            <div class="mb-6 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-5 py-4 text-rose-200">
                <?= escapar($mensajeError) ?>
            </div>
        <?php endif; ?>

        <section class="space-y-6">
            <?php foreach ($entregas as $entrega): ?>
                <?php
                $entregaId = (int) $entrega['id'];
                $ordenEntrega = (int) $entrega['orden_entrega'];
                $yaEnviada = in_array($entregaId, $entregasEnviadas, true);
                $bloqueada = !$yaEnviada && $ordenEntrega > $ordenDesbloqueado;
                $listaTemas = convertirTemasEnLista($entrega['temas_utiles']);
                $archivosEntrega = $archivosPorEntrega[$entregaId] ?? [];
                ?>
                <article class="overflow-hidden rounded-3xl border <?= $bloqueada ? 'border-slate-800 bg-slate-900/50 opacity-70' : 'border-slate-800 bg-slate-900/90' ?> shadow-xl">
                    <div class="border-b border-slate-800 bg-slate-900 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="mb-2 flex flex-wrap items-center gap-3">
                                    <p class="text-sm font-semibold text-sky-400">
                                        <?= escapar(t($tr, $lang, 'delivery')) . ' ' . $ordenEntrega ?>
                                    </p>

                                    <?php if ($yaEnviada): ?>
                                        <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-300">
                                            <?= escapar(t($tr, $lang, 'sent')) ?>
                                        </span>
                                    <?php elseif (!$bloqueada): ?>
                                        <span class="rounded-full border border-sky-500/30 bg-sky-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-300">
                                            <?= escapar(t($tr, $lang, 'unlocked')) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-amber-300">
                                            <?= escapar(t($tr, $lang, 'blocked')) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-2xl font-bold text-white">
                                    <?= escapar($entrega['titulo']) ?>
                                </h2>
                            </div>

                            <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3">
                                <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">
                                    <?= escapar(t($tr, $lang, 'deadline')) ?>
                                </p>
                                <p class="mt-1 text-lg font-bold text-amber-100">
                                    <?= formatearFecha($entrega['fecha_limite']) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if ($bloqueada): ?>
                            <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 text-amber-100">
                                <?= escapar(t($tr, $lang, 'must_complete_previous')) ?>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                                <div class="xl:col-span-2">
                                    <div class="mb-6">
                                        <h3 class="mb-3 text-lg font-semibold text-white">
                                            <?= escapar(t($tr, $lang, 'statement')) ?>
                                        </h3>
                                        <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-5 leading-7 text-slate-200">
                                            <?= nl2br(escapar($entrega['descripcion'])) ?>
                                        </div>
                                    </div>

                                    <div class="mb-6">
                                        <h3 class="mb-4 text-lg font-semibold text-white">
                                            <?= escapar(t($tr, $lang, 'functions_to_do')) ?>
                                        </h3>

                                        <?php if (!empty($funcionesPorEntrega[$entregaId])): ?>
                                            <div class="space-y-4">
                                                <?php foreach ($funcionesPorEntrega[$entregaId] as $funcion): ?>
                                                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                                                        <h4 class="mb-3 text-base font-bold text-sky-300">
                                                            <?= escapar($funcion['nombre_funcion']) ?>
                                                        </h4>

                                                        <div class="mb-4">
                                                            <p class="mb-2 text-sm font-medium text-slate-400">
                                                                <?= escapar(t($tr, $lang, 'expected_signature')) ?>
                                                            </p>
                                                            <pre class="overflow-x-auto rounded-xl border border-slate-800 bg-black/40 p-4 text-sm text-emerald-300"><code><?= escapar($funcion['firma']) ?></code></pre>
                                                        </div>

                                                        <div>
                                                            <p class="mb-2 text-sm font-medium text-slate-400">
                                                                <?= escapar(t($tr, $lang, 'expected_docstring')) ?>
                                                            </p>
                                                            <pre class="overflow-x-auto whitespace-pre-wrap rounded-xl border border-slate-800 bg-black/40 p-4 text-sm text-slate-200"><code><?= escapar($funcion['docstring']) ?></code></pre>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-950/50 p-5 text-slate-400">
                                                <?= escapar(t($tr, $lang, 'no_functions')) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($yaEnviada): ?>
                                        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5">
                                            <h3 class="mb-4 text-lg font-semibold text-emerald-200">
                                                <?= escapar(t($tr, $lang, 'your_sent_file')) ?>
                                            </h3>

                                            <?php foreach ($archivosEntrega as $archivo): ?>
                                                <div class="mb-4 rounded-xl border border-slate-800 bg-slate-900 px-4 py-3 last:mb-0">
                                                    <p class="font-medium text-white"><?= escapar($archivo['nombre_original']) ?></p>
                                                    <p class="mt-1 text-sm text-slate-400">
                                                        <?= escapar(t($tr, $lang, 'sent_on')) . ' ' . escapar($archivo['fecha_subida']) ?>
                                                    </p>

                                                    <a
                                                        href="<?= escapar($archivo['ruta_archivo']) ?>"
                                                        target="_blank"
                                                        class="mt-3 inline-block text-sky-400 hover:text-sky-300"
                                                    >
                                                        <?= escapar(t($tr, $lang, 'view_file')) ?>
                                                    </a>

                                                    <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                                                        <?php if ($archivo['nota'] !== null): ?>
                                                            <p class="font-semibold text-emerald-300">
                                                                <?= escapar(t($tr, $lang, 'grade')) ?>: <?= escapar((string)$archivo['nota']) ?>/20
                                                            </p>

                                                            <?php if (!empty($archivo['comentario_profesor'])): ?>
                                                                <div class="mt-3">
                                                                    <p class="mb-1 text-sm font-medium text-slate-400">
                                                                        <?= escapar(t($tr, $lang, 'teacher_comment')) ?>
                                                                    </p>
                                                                    <p class="whitespace-pre-wrap text-slate-200">
                                                                        <?= escapar($archivo['comentario_profesor']) ?>
                                                                    </p>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <p class="font-medium text-amber-300">
                                                                <?= escapar(t($tr, $lang, 'pending_correction')) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-5">
                                            <h3 class="mb-4 text-lg font-semibold text-white">
                                                <?= escapar(t($tr, $lang, 'upload_delivery')) ?>
                                            </h3>

                                            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                                <input type="hidden" name="entrega_id" value="<?= $entregaId ?>">

                                                <div>
                                                    <label class="mb-2 block text-sm text-slate-300">
                                                        <?= escapar(t($tr, $lang, 'file')) ?>
                                                    </label>
                                                    <input
                                                        type="file"
                                                        name="archivo_entrega"
                                                        accept=".zip,.py,.pdf"
                                                        required
                                                        class="block w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-200 file:mr-4 file:rounded-lg file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-white hover:file:bg-sky-500"
                                                    >
                                                    <p class="mt-2 text-xs text-slate-400">
                                                        <?= escapar(t($tr, $lang, 'allowed_formats')) ?>
                                                    </p>
                                                </div>

                                                <button
                                                    type="submit"
                                                    name="subir_entrega"
                                                    class="rounded-xl bg-sky-600 px-5 py-3 font-semibold text-white transition hover:bg-sky-500"
                                                >
                                                    <?= escapar(t($tr, $lang, 'send_delivery')) ?>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <aside class="xl:col-span-1">
                                    <div class="sticky top-6 rounded-2xl border border-slate-800 bg-slate-950/60 p-5">
                                        <h3 class="mb-4 text-lg font-semibold text-white">
                                            <?= escapar(t($tr, $lang, 'useful_topics')) ?>
                                        </h3>

                                        <?php if (!empty($listaTemas)): ?>
                                            <ul class="space-y-2">
                                                <?php foreach ($listaTemas as $tema): ?>
                                                    <li class="flex items-start gap-3 text-slate-200">
                                                        <span class="mt-1 inline-block h-2.5 w-2.5 rounded-full bg-sky-400"></span>
                                                        <span><?= escapar($tema) ?></span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-slate-400">
                                                <?= escapar(t($tr, $lang, 'no_topics')) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </aside>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</body>
</html>