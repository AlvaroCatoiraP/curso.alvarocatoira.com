<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $enunciado = trim($_POST['enunciado'] ?? '');
    $fecha_limite = trim($_POST['fecha_limite'] ?? '');

    if ($titulo === '' || $enunciado === '') {
        $message = "El título y el enunciado son obligatorios.";
    } else {
        $fecha_limite_sql = $fecha_limite !== '' ? $fecha_limite : null;

        $sql = "INSERT INTO deberes (titulo, enunciado, fecha_limite, creado_por) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $enunciado, $fecha_limite_sql, $_SESSION['user_id']]);

        $success = "Deber creado correctamente.";
    }
}

$sql = "SELECT d.*, u.nombre AS admin_nombre
        FROM deberes d
        JOIN usuarios u ON d.creado_por = u.id
        ORDER BY d.creado_en DESC";
$stmt = $pdo->query($sql);
$deberes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar deberes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Gestionar deberes</h1>
                <p class="text-slate-400 mt-2">Crea deberes y publica enunciados para tus estudiantes.</p>
            </div>
            <div class="flex gap-3">
                <a href="admin_dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">Admin dashboard</a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">Cerrar sesión</a>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-10">
            <h2 class="text-2xl font-bold mb-6">Crear un deber</h2>

            <?php if ($message): ?>
                <p class="mb-4 text-red-400"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="mb-4 text-emerald-400"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2 font-semibold">Título</label>
                    <input type="text" name="titulo" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700">
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Enunciado</label>
                    <textarea name="enunciado" rows="8" class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"></textarea>
                </div>

                <div>
                    <label class="block mb-2 font-semibold">Fecha límite</label>
                    <input type="datetime-local" name="fecha_limite" class="p-3 rounded-xl bg-slate-800 border border-slate-700">
                </div>

                <button class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                    Crear deber
                </button>
            </form>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6">Deberes publicados</h2>

            <div class="space-y-4">
                <?php foreach ($deberes as $deber): ?>
                    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($deber['titulo']) ?></h3>
                        <p class="text-slate-300 mt-3 whitespace-pre-line"><?= htmlspecialchars($deber['enunciado']) ?></p>
                        <div class="mt-4 text-sm text-slate-400">
                            <p>Profesor: <?= htmlspecialchars($deber['admin_nombre']) ?></p>
                            <p>Creado: <?= htmlspecialchars($deber['creado_en']) ?></p>
                            <p>Fecha límite: <?= $deber['fecha_limite'] ? htmlspecialchars($deber['fecha_limite']) : 'Sin fecha límite' ?></p>
                        </div>
                        <div class="mt-4">
                            <a href="admin_entregas_deber.php?id=<?= (int)$deber['id'] ?>" class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold inline-block">
                                Ver entregas
                            </a>
                            <a href="admin_eliminar_deber.php?id=<?= (int)$deber['id'] ?>"
                                onclick="return confirm('¿Eliminar este deber y todas las entregas?')"
                                class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                                Eliminar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($deberes) === 0): ?>
                    <p class="text-slate-400">No hay deberes todavía.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>