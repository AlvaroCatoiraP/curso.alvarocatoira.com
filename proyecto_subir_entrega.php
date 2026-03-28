<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_student();

$usuario_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido.");
}

$proyecto_entrega_id = isset($_POST['proyecto_entrega_id']) ? (int) $_POST['proyecto_entrega_id'] : 0;

if ($proyecto_entrega_id <= 0) {
    die("Entrega no válida.");
}

$sql = "SELECT * FROM proyecto_entregas WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$proyecto_entrega_id]);
$entrega = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entrega) {
    die("Entrega no encontrada.");
}

$sqlTodas = "
    SELECT id
    FROM proyecto_entregas
    WHERE proyecto_id = ?
    ORDER BY orden_entrega ASC
";
$stmtTodas = $pdo->prepare($sqlTodas);
$stmtTodas->execute([$entrega['proyecto_id']]);
$lista = $stmtTodas->fetchAll(PDO::FETCH_ASSOC);

$indiceActual = null;

foreach ($lista as $index => $e) {
    if ((int)$e['id'] === $proyecto_entrega_id) {
        $indiceActual = $index;
        break;
    }
}

if ($indiceActual === null) {
    die("Error interno.");
}

if ($indiceActual > 0) {
    $entregaAnteriorId = $lista[$indiceActual - 1]['id'];

    $sqlCheck = "
        SELECT *
        FROM proyecto_entregas_estudiantes
        WHERE proyecto_entrega_id = ? AND estudiante_id = ?
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$entregaAnteriorId, $usuario_id]);
    $anterior = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$anterior) {
        die("Debes completar la entrega anterior primero.");
    }

    if ($anterior['estado'] !== 'corregido' || (int)$anterior['liberada'] !== 1) {
        die("La entrega anterior aún no ha sido corregida y liberada por el profesor.");
    }
}

if (!empty($entrega['fecha_limite']) && strtotime($entrega['fecha_limite']) < time()) {
    die("La fecha límite ha pasado.");
}

$archivo = $_FILES['archivo'] ?? null;

if (!$archivo) {
    die("No se recibió ningún archivo.");
}

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    $erroresUpload = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera MAX_FILE_SIZE del formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo solo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal de PHP.',
        UPLOAD_ERR_CANT_WRITE => 'PHP no pudo escribir el archivo temporal en disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.'
    ];

    $codigo = $archivo['error'];
    $mensaje = $erroresUpload[$codigo] ?? ('Error de subida desconocido. Código: ' . $codigo);
    die($mensaje);
}

$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if ($ext !== 'zip') {
    die("Solo se permiten archivos ZIP.");
}

$uploadDirFisico = __DIR__ . '/uploads/proyectos/';
$uploadDirWeb = 'uploads/proyectos/';

if (!is_dir($uploadDirFisico)) {
    if (!mkdir($uploadDirFisico, 0775, true)) {
        die("No se pudo crear la carpeta de subida.");
    }
}

if (!is_writable($uploadDirFisico)) {
    die("La carpeta destino no es escribible: " . $uploadDirFisico);
}

if (!is_uploaded_file($archivo['tmp_name'])) {
    die("PHP no reconoce el archivo como subida válida.");
}

$nombreOriginal = basename($archivo['name']);
$nombreLimpio = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombreOriginal);
$nombreGuardado = uniqid('', true) . "_" . $nombreLimpio;

$rutaFisica = $uploadDirFisico . $nombreGuardado;
$rutaWeb = $uploadDirWeb . $nombreGuardado;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
    die("Error al guardar el archivo.");
}

$sqlCheck = "
    SELECT id
    FROM proyecto_entregas_estudiantes
    WHERE proyecto_entrega_id = ? AND estudiante_id = ?
";
$stmtCheck = $pdo->prepare($sqlCheck);
$stmtCheck->execute([$proyecto_entrega_id, $usuario_id]);
$existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($existente) {
    $sqlUpdate = "
        UPDATE proyecto_entregas_estudiantes
        SET nombre_archivo_original = ?,
            nombre_archivo_guardado = ?,
            ruta_archivo = ?,
            entregado_en = NOW(),
            nota = NULL,
            comentario = NULL,
            estado = 'entregado',
            liberada = 0
        WHERE id = ?
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        $nombreOriginal,
        $nombreGuardado,
        $rutaWeb,
        $existente['id']
    ]);
} else {
    $sqlInsert = "
        INSERT INTO proyecto_entregas_estudiantes
        (proyecto_entrega_id, estudiante_id, nombre_archivo_original, nombre_archivo_guardado, ruta_archivo, estado, liberada)
        VALUES (?, ?, ?, ?, ?, 'entregado', 0)
    ";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $proyecto_entrega_id,
        $usuario_id,
        $nombreOriginal,
        $nombreGuardado,
        $rutaWeb
    ]);
}

header("Location: proyecto_ver.php?id=" . (int)$entrega['proyecto_id']);
exit;