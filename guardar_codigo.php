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
$contenido = $_POST['contenido'] ?? '';

if ($ejercicio_codigo === '') {
    echo json_encode(['success' => false, 'message' => 'Ejercicio no válido']);
    exit;
}

$sql = "
    INSERT INTO codigo_usuario (usuario_id, ejercicio_codigo, contenido)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE contenido = VALUES(contenido)
";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([$usuario_id, $ejercicio_codigo, $contenido]);

echo json_encode(['success' => $ok]);
