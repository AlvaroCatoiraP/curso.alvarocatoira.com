<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$deber_id = (int)($_GET['id'] ?? 0);

if ($deber_id <= 0) {
    die("Deber no válido.");
}

$sql_deber = "SELECT * FROM deberes WHERE id = ?";
$stmt_deber = $pdo->prepare($sql_deber);
$stmt_deber->execute([$deber_id]);
$deber = $stmt_deber->fetch(PDO::FETCH_ASSOC);

if (!$deber) {
    die("Deber no encontrado.");
}

$sql_entregas = "
    SELECT e.*, u.nombre, u.email
    FROM entregas_deberes e
    JOIN usuarios u ON e.estudiante_id = u.id
    WHERE e.deber_id = ?
    ORDER BY e.entregado_en DESC
";
$stmt_entregas = $pdo->prepare($sql_entregas);
$stmt_entregas->execute([$deber_id]);
$entregas = $stmt_entregas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas del deber</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Entregas del deber</h1>
                <p class="text-slate-400 mt-2"><?= htmlspecialchars($deber['titulo']) ?></p>
            </div>
            <a href="admin_deberes.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                Volver
            </a>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-800">
                    <tr>
                        <th class="text-left p-4">Estudiante</th>
                        <th class="text-left p-4">Email</th>
                        <th class="text-left p-4">Archivo</th>
                        <th class="text-left p-4">Fecha</th>
                        <th class="text-left p-4">Descarga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($entregas) === 0): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-slate-400">Aún no hay entregas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $entrega): ?>
                            <tr class="border-t border-slate-800">
                                <td class="p-4"><?= htmlspecialchars($entrega['nombre']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($entrega['email']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($entrega['nombre_archivo_original']) ?></td>
                                <td class="p-4"><?= htmlspecialchars($entrega['entregado_en']) ?></td>
                                <td class="p-4">
                                    <a href="descargar_entrega.php?id=<?= (int)$entrega['id'] ?>"
                                       class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold inline-block">
                                        Descargar ZIP
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>