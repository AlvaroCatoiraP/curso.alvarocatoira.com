<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$deber_id = (int)($_GET['id'] ?? 0);

if ($deber_id <= 0) {
    header("Location: admin_deberes.php");
    exit;
}

/* récupérer les fichiers à supprimer */

$sql_files = "SELECT ruta_archivo FROM entregas_deberes WHERE deber_id = ?";
$stmt = $pdo->prepare($sql_files);
$stmt->execute([$deber_id]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file['ruta_archivo'];
    if (file_exists($path)) {
        unlink($path);
    }
}

/* supprimer les entrées */

$sql_delete_entregas = "DELETE FROM entregas_deberes WHERE deber_id = ?";
$stmt = $pdo->prepare($sql_delete_entregas);
$stmt->execute([$deber_id]);

/* supprimer le devoir */

$sql_delete_deber = "DELETE FROM deberes WHERE id = ?";
$stmt = $pdo->prepare($sql_delete_deber);
$stmt->execute([$deber_id]);

header("Location: admin_deberes.php");
exit;