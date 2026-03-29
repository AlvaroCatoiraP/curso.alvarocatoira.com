<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_connexion();

$sql = "
    SELECT p.*, u.nombre AS profesor_nombre
    FROM proyectos p
    JOIN usuarios u ON p.creado_por = u.id
    ORDER BY p.creado_en DESC
";
$stmt = $pdo->query($sql);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('my_projects') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('my_projects') ?></h1>
                <p class="text-slate-400 mt-2">
                    <?= t('projects_page_desc') ?>
                </p>
            </div>

            <div class="flex gap-3">
                <a href="dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    <?= t('back_dashboard') ?>
                </a>
            </div>
        </div>

        <div class="space-y-6">

            <?php foreach ($proyectos as $proyecto): ?>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">

                    <h2 class="text-2xl font-bold">
                        <?= htmlspecialchars($proyecto['titulo']) ?>
                    </h2>

                    <p class="text-slate-300 mt-3 whitespace-pre-line">
                    <?php
                        $texto = htmlspecialchars($proyecto['descripcion']);

                        $texto = preg_replace_callback(
                            '/(https?:\/\/[^\s]+)/',
                            function ($matches) {
                                return '<a href="' . $matches[0] . '" target="_blank" class="text-sky-400 underline hover:text-sky-300 font-semibold">Ver documentación</a>';
                            },
                            $texto
                        );

                        echo nl2br($texto);
                    ?>
                    </p>

                    <div class="mt-4 text-sm text-slate-400">
                        <p><?= t('teacher') ?>: <?= htmlspecialchars($proyecto['profesor_nombre']) ?></p>
                        <p><?= t('created') ?>: <?= htmlspecialchars($proyecto['creado_en']) ?></p>
                    </div>

                    <div class="mt-5">
                        <a
                            href="proyecto_ver.php?id=<?= (int)$proyecto['id'] ?>"
                            class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold inline-block"
                        >
                            <?= t('view_project') ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($proyectos) === 0): ?>
                <div class="text-slate-400">
                    <?= t('no_projects_available') ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>