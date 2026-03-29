<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_admin();

$message = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($titulo === '' || $descripcion === '') {
        $message = t('project_required_fields');
    } else {
        $sql = "INSERT INTO proyectos (titulo, descripcion, creado_por) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descripcion, $_SESSION['user_id']]);

        $success = t('project_created');
    }
}

$sql = "
    SELECT p.*, u.nombre AS admin_nombre
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
    <title><?= t('manage_projects') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-6xl mx-auto p-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= t('manage_projects') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('projects_admin_desc') ?></p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-10">
            <h2 class="text-2xl font-bold mb-6"><?= t('create_project') ?></h2>

            <?php if ($message): ?>
                <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block mb-2 font-semibold"><?= t('project_title') ?></label>
                    <input
                        type="text"
                        name="titulo"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="<?= t('project_title_placeholder') ?>"
                    >
                </div>

                <div>
                    <label class="block mb-2 font-semibold"><?= t('description') ?></label>
                    <textarea
                        name="descripcion"
                        rows="8"
                        class="w-full p-3 rounded-xl bg-slate-800 border border-slate-700"
                        placeholder="<?= t('project_description_placeholder') ?>"
                    ></textarea>
                </div>

                <button class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                    <?= t('create_project_btn') ?>
                </button>
            </form>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h2 class="text-2xl font-bold mb-6"><?= t('created_projects') ?></h2>

            <div class="space-y-4">
                <?php foreach ($proyectos as $proyecto): ?>
                    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($proyecto['titulo']) ?></h3>

                        <p class="text-slate-300 mt-3 whitespace-pre-line">
                        <?php
                            $texto = htmlspecialchars($proyecto['descripcion']);

                            $texto = preg_replace_callback(
                                '/(https?:\/\/[^\s]+)/',
                                function ($matches) {
                                    return '<a href="' . $matches[0] . '" target="_blank" class="text-sky-400 underline hover:text-sky-300 font-semibold">' . t('view_documentation') . '</a>';
                                },
                                $texto
                            );

                            echo nl2br($texto);
                        ?>
                        </p>

                        <div class="mt-4 text-sm text-slate-400">
                            <p><?= t('teacher') ?>: <?= htmlspecialchars($proyecto['admin_nombre']) ?></p>
                            <p><?= t('created') ?>: <?= htmlspecialchars($proyecto['creado_en']) ?></p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a
                                href="admin_proyecto_entregas.php?proyecto_id=<?= (int)$proyecto['id'] ?>"
                                class="bg-emerald-500 hover:bg-emerald-600 px-4 py-2 rounded-xl font-semibold inline-block"
                            >
                                <?= t('manage_submissions') ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (count($proyectos) === 0): ?>
                    <p class="text-slate-400"><?= t('no_projects_yet') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>