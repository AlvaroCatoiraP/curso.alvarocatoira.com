<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE usuarios
    SET ultima_actividad = NOW()
    WHERE id = ?
");
$stmt->execute([$usuarioId]);

header('Content-Type: application/json');
echo json_encode(['ok' => true]);