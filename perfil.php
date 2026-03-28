<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    die('Usuario no identificado.');
}

$mensaje = null;
$error = null;

/**
 * =========================================================
 * CARGAR DATOS DEL USUARIO
 * =========================================================
 */
$stmt = $pdo->prepare("
    SELECT id, nombre, email, creado_en, rol, estado
    FROM usuarios
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $usuarioId]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die('Usuario no encontrado.');
}

$nombre = $usuario['nombre'];
$email = $usuario['email'];

/**
 * =========================================================
 * PROCESAR FORMULARIO
 * =========================================================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreNuevo = trim($_POST['nombre'] ?? '');
    $emailNuevo = trim($_POST['email'] ?? '');
    $passwordActual = $_POST['password_actual'] ?? '';
    $passwordNueva = $_POST['password_nueva'] ?? '';
    $passwordConfirmar = $_POST['password_confirmar'] ?? '';

    if ($nombreNuevo === '' || $emailNuevo === '') {
        $error = 'El nombre y el email son obligatorios.';
    } elseif (!filter_var($emailNuevo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        try {
            // Verificar si el email ya lo usa otro usuario
            $stmt = $pdo->prepare("
                SELECT id
                FROM usuarios
                WHERE email = :email
                  AND id <> :id
                LIMIT 1
            ");
            $stmt->execute([
                'email' => $emailNuevo,
                'id' => $usuarioId
            ]);
            $emailExistente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($emailExistente) {
                $error = 'Ese email ya está siendo usado por otro usuario.';
            } else {
                // Si quiere cambiar contraseña, verificar la actual
                $cambiarPassword = ($passwordActual !== '' || $passwordNueva !== '' || $passwordConfirmar !== '');

                if ($cambiarPassword) {
                    if ($passwordActual === '' || $passwordNueva === '' || $passwordConfirmar === '') {
                        $error = 'Para cambiar la contraseña debes completar los tres campos.';
                    } elseif ($passwordNueva !== $passwordConfirmar) {
                        $error = 'La nueva contraseña y la confirmación no coinciden.';
                    } elseif (strlen($passwordNueva) < 6) {
                        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT password_hash
                            FROM usuarios
                            WHERE id = :id
                            LIMIT 1
                        ");
                        $stmt->execute(['id' => $usuarioId]);
                        $filaPassword = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$filaPassword || !password_verify($passwordActual, $filaPassword['password_hash'])) {
                            $error = 'La contraseña actual no es correcta.';
                        }
                    }
                }

                if (!$error) {
                    if ($cambiarPassword) {
                        $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            UPDATE usuarios
                            SET nombre = :nombre,
                                email = :email,
                                password_hash = :password_hash
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'nombre' => $nombreNuevo,
                            'email' => $emailNuevo,
                            'password_hash' => $nuevoHash,
                            'id' => $usuarioId
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios
                            SET nombre = :nombre,
                                email = :email
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'nombre' => $nombreNuevo,
                            'email' => $emailNuevo,
                            'id' => $usuarioId
                        ]);
                    }

                    // Actualizar sesión
                    $_SESSION['user_nombre'] = $nombreNuevo;
                    if (isset($_SESSION['user_email'])) {
                        $_SESSION['user_email'] = $emailNuevo;
                    }

                    $mensaje = 'Perfil actualizado correctamente.';

                    // Recargar datos actualizados
                    $stmt = $pdo->prepare("
                        SELECT id, nombre, email, creado_en, rol, estado
                        FROM usuarios
                        WHERE id = :id
                        LIMIT 1
                    ");
                    $stmt->execute(['id' => $usuarioId]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                    $nombre = $usuario['nombre'];
                    $email = $usuario['email'];
                }
            }
        } catch (PDOException $e) {
            $error = 'Error al actualizar el perfil: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto p-6 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                Cuenta
            </p>
            <h1 class="text-3xl md:text-4xl font-bold mt-1">
                Mi perfil
            </h1>
            <p class="text-slate-400 mt-2">
                Aquí puedes actualizar tus datos personales y tu contraseña.
            </p>
        </div>

        <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
            ← Dashboard
        </a>
    </div>

    <div class="mt-8 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-5">Editar perfil</h2>

                <?php if ($mensaje): ?>
                    <div class="mb-5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 rounded-xl p-4 text-sm">
                        <?= htmlspecialchars($mensaje) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="mb-5 bg-red-500/10 border border-red-500/20 text-red-300 rounded-xl p-4 text-sm">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Nombre
                        </label>
                        <input
                            type="text"
                            name="nombre"
                            value="<?= htmlspecialchars($nombre) ?>"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                            required
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">
                            Email
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                            required
                        >
                    </div>

                    <div class="border-t border-slate-800 pt-5">
                        <h3 class="text-xl font-bold mb-4">Cambiar contraseña</h3>
                        <p class="text-slate-400 text-sm mb-4">
                            Deja estos campos vacíos si no quieres cambiar la contraseña.
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Contraseña actual
                                </label>
                                <input
                                    type="password"
                                    name="password_actual"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Nueva contraseña
                                </label>
                                <input
                                    type="password"
                                    name="password_nueva"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Confirmar nueva contraseña
                                </label>
                                <input
                                    type="password"
                                    name="password_confirmar"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button
                            type="submit"
                            class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold transition"
                        >
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-4">Información de la cuenta</h2>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-slate-400">Nombre actual</p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['nombre']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400">Email actual</p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['email']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400">Rol</p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['rol']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400">Estado</p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['estado']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400">Miembro desde</p>
                        <p class="text-white font-semibold mt-1">
                            <?= !empty($usuario['creado_en']) ? date('d/m/Y H:i', strtotime($usuario['creado_en'])) : '-' ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-3">Consejo</h2>
                <p class="text-slate-400 text-sm leading-6">
                    Usa una contraseña segura y un email al que tengas acceso, para no perder tu cuenta.
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>