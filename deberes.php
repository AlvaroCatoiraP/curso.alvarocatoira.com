<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_connexion();

$usuario_id = $_SESSION['user_id'];
$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deber_id = (int)($_POST['deber_id'] ?? 0);

    if ($deber_id <= 0) {
        $message = "Deber no válido.";
    } elseif (!isset($_FILES['archivo_zip']) || $_FILES['archivo_zip']['error'] !== UPLOAD_ERR_OK) {
        $message = "Debes subir un archivo ZIP válido.";
    } else {
        $archivo = $_FILES['archivo_zip'];
        $nombre_original = $archivo['name'];
        $tmp = $archivo['tmp_name'];
        $tamano = $archivo['size'];

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        if ($extension !== 'zip') {
            $message = "Solo se permiten archivos .zip";
        } elseif ($tamano > 20 * 1024 * 1024) {
            $message = "El archivo es demasiado grande. Máximo 20 MB.";
        } else {
            $nombre_guardado = uniqid('deber_', true) . '.zip';
            $ruta_relativa = 'uploads/deberes/' . $nombre_guardado;
            $ruta_absoluta = __DIR__ . '/' . $ruta_relativa;

            if (move_uploaded_file($tmp, $ruta_absoluta)) {
                $sql = "
                    INSERT INTO entregas_deberes
                    (deber_id, estudiante_id, nombre_archivo_original, nombre_archivo_guardado, ruta_archivo)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        nombre_archivo_original = VALUES(nombre_archivo_original),
                        nombre_archivo_guardado = VALUES(nombre_archivo_guardado),
                        ruta_archivo = VALUES(ruta_archivo),
                        entregado_en = CURRENT_TIMESTAMP
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$deber_id, $usuario_id, $nombre_original, $nombre_guardado, $ruta_relativa]);

                $success = "Archivo enviado correctamente.";
            } else {
                $message = "Error al guardar el archivo.";
            }
        }
    }
}

$sql = "
    SELECT d.*,
           e.id AS entrega_id,
           e.nombre_archivo_original,
           e.entregado_en
    FROM deberes d
    LEFT JOIN entregas_deberes e
        ON d.id = e.deber_id AND e.estudiante_id = ?
    ORDER BY d.creado_en DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario_id]);
$deberes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis deberes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Mis deberes</h1>
                <p class="text-slate-400 mt-2">Consulta los enunciados y entrega tus archivos ZIP.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php foreach ($deberes as $deber): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <h2 class="text-2xl font-bold"><?= htmlspecialchars($deber['titulo']) ?></h2>

                    <p class="text-slate-300 mt-4 whitespace-pre-line"><?= htmlspecialchars($deber['enunciado']) ?></p>

                    <div class="mt-4 text-sm text-slate-400">
                        <p>Creado: <?= htmlspecialchars($deber['creado_en']) ?></p>
                        <p>Fecha límite: <?= $deber['fecha_limite'] ? htmlspecialchars($deber['fecha_limite']) : 'Sin fecha límite' ?></p>
                    </div>

                    <div class="mt-5">
                        <?php if ($deber['entrega_id']): ?>
                            <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                                Entregado: <?= htmlspecialchars($deber['nombre_archivo_original']) ?> — <?= htmlspecialchars($deber['entregado_en']) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-3 md:items-center">
                            <input type="hidden" name="deber_id" value="<?= (int)$deber['id'] ?>">

                            <input type="file" name="archivo_zip" accept=".zip"
                                   class="block w-full text-sm text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-slate-700 file:text-white hover:file:bg-slate-600">

                            <button class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                                <?= $deber['entrega_id'] ? 'Reenviar ZIP' : 'Enviar ZIP' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($deberes) === 0): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <p class="text-slate-400">No hay deberes disponibles.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>