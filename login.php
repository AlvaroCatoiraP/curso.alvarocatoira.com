<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = "Tous les champs sont obligatoires.";
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            if ($usuario['estado'] === 'pending') {
                $message = "Tu inscripción está pendiente de aprobación.";
            } elseif ($usuario['estado'] === 'rejected') {
                $message = "Tu inscripción ha sido rechazada.";
            } elseif ($usuario['estado'] !== 'approved') {
                $message = "Tu cuenta no está autorizada.";
            } else {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['rol'] = $usuario['rol'];
                $_SESSION['estado'] = $usuario['estado'];

                if ($usuario['rol'] === 'admin') {
                    header('Location: admin_dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            }
        } else {
            $message = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen flex items-center justify-center">
    <form method="POST" class="bg-slate-900 p-8 rounded-2xl w-full max-w-md border border-slate-800">
        <h1 class="text-2xl font-bold mb-6">Iniciar sesión</h1>

        <?php if ($message): ?>
            <p class="mb-4 text-red-400"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <input name="email" type="email" placeholder="Email" class="w-full mb-4 p-3 rounded-xl bg-slate-800 border border-slate-700">
        <input name="password" type="password" placeholder="Contraseña" class="w-full mb-4 p-3 rounded-xl bg-slate-800 border border-slate-700">

        <button class="w-full bg-sky-500 hover:bg-sky-600 p-3 rounded-xl font-semibold">Entrar</button>

        <p class="mt-4 text-slate-400">
            ¿No tienes cuenta?
            <a href="register.php" class="text-sky-400">Registrarse</a>
        </p>
    </form>
</body>
</html>