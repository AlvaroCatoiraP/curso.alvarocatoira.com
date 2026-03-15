<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$entrega_id = (int)($_GET['id'] ?? 0);

if ($entrega_id <= 0) {
    die("Entrega no válida.");
}

$sql = "SELECT * FROM entregas_deberes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$entrega_id]);
$entrega = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entrega) {
    die("Entrega no encontrada.");
}

$ruta_absoluta = __DIR__ . '/' . $entrega['ruta_archivo'];

if (!file_exists($ruta_absoluta)) {
    die("Archivo no encontrado en el servidor.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . basename($entrega['nombre_archivo_original']) . '"');
header('Content-Length: ' . filesize($ruta_absoluta));
readfile($ruta_absoluta);
exit;