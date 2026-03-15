<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_connexion();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$ejercicio_codigo = trim($_POST['ejercicio_codigo'] ?? '');
$completado = isset($_POST['completado']) ? (int)$_POST['completado'] : 0;

if ($ejercicio_codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Ejercicio no válido']);
    exit;
}

$sql = "
    INSERT INTO progreso (usuario_id, ejercicio_codigo, completado)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE completado = VALUES(completado)
";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$usuario_id, $ejercicio_codigo, $completado]);

echo json_encode(['success' => $ok]);
