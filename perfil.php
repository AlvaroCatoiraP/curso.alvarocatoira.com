<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$usuarioId) {
    die(t('user_not_identified'));
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
    die(t('user_not_found'));
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
        $error = t('name_email_required');
    } elseif (!filter_var($emailNuevo, FILTER_VALIDATE_EMAIL)) {
        $error = t('invalid_email');
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
                $error = t('email_already_used');
            } else {
                // Si quiere cambiar contraseña, verificar la actual
                $cambiarPassword = ($passwordActual !== '' || $passwordNueva !== '' || $passwordConfirmar !== '');

                if ($cambiarPassword) {
                    if ($passwordActual === '' || $passwordNueva === '' || $passwordConfirmar === '') {
                        $error = t('fill_three_password_fields');
                    } elseif ($passwordNueva !== $passwordConfirmar) {
                        $error = t('password_confirmation_mismatch');
                    } elseif (strlen($passwordNueva) < 6) {
                        $error = t('password_min_length');
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
                            $error = t('current_password_incorrect');
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

                    $mensaje = t('profile_updated_successfully');

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
            $error = t('profile_update_error') . ': ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('my_profile') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-5xl mx-auto p-6 md:p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sky-300 text-sm font-semibold uppercase tracking-wide">
                <?= t('account') ?>
            </p>
            <h1 class="text-3xl md:text-4xl font-bold mt-1">
                <?= t('my_profile') ?>
            </h1>
            <p class="text-slate-400 mt-2">
                <?= t('profile_update_desc') ?>
            </p>
        </div>

        <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold transition">
            ← <?= t('back_dashboard') ?>
        </a>
    </div>

    <div class="mt-8 grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-5"><?= t('edit_profile') ?></h2>

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
                            <?= t('name') ?>
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
                            <?= t('email') ?>
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
                        <h3 class="text-xl font-bold mb-4"><?= t('change_password') ?></h3>
                        <p class="text-slate-400 text-sm mb-4">
                            <?= t('leave_password_blank') ?>
                        </p>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <?= t('current_password') ?>
                                </label>
                                <input
                                    type="password"
                                    name="password_actual"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <?= t('new_password') ?>
                                </label>
                                <input
                                    type="password"
                                    name="password_nueva"
                                    class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    <?= t('confirm_new_password') ?>
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
                            <?= t('save_changes') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-4"><?= t('account_information') ?></h2>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-slate-400"><?= t('current_name') ?></p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['nombre']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400"><?= t('current_email') ?></p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['email']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400"><?= t('role') ?></p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['rol']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400"><?= t('status') ?></p>
                        <p class="text-white font-semibold mt-1"><?= htmlspecialchars($usuario['estado']) ?></p>
                    </div>

                    <div>
                        <p class="text-slate-400"><?= t('member_since') ?></p>
                        <p class="text-white font-semibold mt-1">
                            <?= !empty($usuario['creado_en']) ? date('d/m/Y H:i', strtotime($usuario['creado_en'])) : '-' ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-slate-900 border border-slate-800 rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-3"><?= t('tip') ?></h2>
                <p class="text-slate-400 text-sm leading-6">
                    <?= t('profile_security_tip') ?>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>