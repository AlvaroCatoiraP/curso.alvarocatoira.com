<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $sql = "UPDATE usuarios SET estado = 'rejected' WHERE id = ? AND rol = 'student'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

header('Location: admin_dashboard.php');
exit;
