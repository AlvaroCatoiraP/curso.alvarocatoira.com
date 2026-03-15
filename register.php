<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        $message = "Todos los campos son obligatorios.";
    } else {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = "Este email ya existe.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado) VALUES (?, ?, ?, 'student', 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $password_hash]);

            $success = "Inscripción enviada. Tu cuenta debe ser aprobada por el administrador.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center">
    <form method="POST" class="bg-slate-900 p-8 rounded-2xl w-full max-w-md border border-slate-800">
        <h1 class="text-2xl font-bold mb-6">Crear cuenta</h1>

        <?php if ($message): ?>
            <p class="mb-4 text-red-400"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="mb-4 text-emerald-400"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <input name="nombre" type="text" placeholder="Nombre" class="w-full mb-4 p-3 rounded-xl bg-slate-800 border border-slate-700">
        <input name="email" type="email" placeholder="Email" class="w-full mb-4 p-3 rounded-xl bg-slate-800 border border-slate-700">
        <input name="password" type="password" placeholder="Contraseña" class="w-full mb-4 p-3 rounded-xl bg-slate-800 border border-slate-700">

        <button class="w-full bg-sky-500 hover:bg-sky-600 p-3 rounded-xl font-semibold">Registrarse</button>

        <p class="mt-4 text-slate-400">
            ¿Ya tienes cuenta?
            <a href="login.php" class="text-sky-400">Iniciar sesión</a>
        </p>
    </form>
</body>
</html>