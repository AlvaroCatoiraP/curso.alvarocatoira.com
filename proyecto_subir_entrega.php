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
        SELECT id
        FROM proyecto_entregas_estudiantes
        WHERE proyecto_entrega_id = ? AND estudiante_id = ?
    ";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$entregaAnteriorId, $usuario_id]);

    if (!$stmtCheck->fetch(PDO::FETCH_ASSOC)) {
        die("Debes completar la entrega anterior primero.");
    }
}

if (!empty($entrega['fecha_limite']) && strtotime($entrega['fecha_limite']) < time()) {
    die("La fecha límite ha pasado.");
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    die("Error al subir el archivo.");
}

$archivo = $_FILES['archivo'];
$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if ($ext !== 'zip') {
    die("Solo se permiten archivos ZIP.");
}

$uploadDir = 'uploads/proyectos/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$nombreOriginal = basename($archivo['name']);
$nombreGuardado = uniqid('', true) . "_" . $nombreOriginal;
$rutaCompleta = $uploadDir . $nombreGuardado;

if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
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
        SET nombre_archivo_original = ?, nombre_archivo_guardado = ?, ruta_archivo = ?, entregado_en = NOW(), estado = 'entregado'
        WHERE id = ?
    ";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute([
        $nombreOriginal,
        $nombreGuardado,
        $rutaCompleta,
        $existente['id']
    ]);
} else {
    $sqlInsert = "
        INSERT INTO proyecto_entregas_estudiantes
        (proyecto_entrega_id, estudiante_id, nombre_archivo_original, nombre_archivo_guardado, ruta_archivo)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $proyecto_entrega_id,
        $usuario_id,
        $nombreOriginal,
        $nombreGuardado,
        $rutaCompleta
    ]);
}

header("Location: proyecto_ver.php?id=" . (int)$entrega['proyecto_id']);
exit;