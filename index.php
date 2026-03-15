<?php
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Curso Python</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-5xl mx-auto p-8">
        <h1 class="text-4xl font-bold">Curso básico de Python</h1>
        <p class="text-slate-400 mt-3">Bienvenido a la plataforma del curso.</p>

        <div class="mt-6 flex gap-4">
            <?php if (utilisateur_connecte()): ?>
                <a href="dashboard.php" class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">Ir al dashboard</a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-5 py-3 rounded-xl font-semibold">Cerrar sesión</a>
            <?php else: ?>
                <a href="login.php" class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">Iniciar sesión</a>
                <a href="register.php" class="bg-slate-800 hover:bg-slate-700 px-5 py-3 rounded-xl font-semibold">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
