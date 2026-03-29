<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db.php';

exiger_connexion();

$usuarioId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$nombreUsuario = $_SESSION['user_nombre'] ?? t('student');

if (!$usuarioId) {
    die(t('user_not_identified'));
}

/**
 * =========================================================
 * OBTENER EJERCICIOS + PROGRESO + TRADUCCIONES
 * =========================================================
 */

$stmt = $pdo->prepare("
    SELECT 
        e.codigo,
        COALESCE(et.titulo, e.titulo) AS titulo,
        COALESCE(et.descripcion, e.descripcion) AS descripcion,
        e.capitulo,
        e.orden,
        COALESCE(p.completado, 0) AS completado
    FROM ejercicios e
    LEFT JOIN ejercicios_traducciones et
        ON et.ejercicio_id = e.id
       AND et.lang = :lang
    LEFT JOIN progreso p
        ON p.ejercicio_codigo = e.codigo
       AND p.usuario_id = :usuario_id
    WHERE e.activo = 1
    ORDER BY e.capitulo, e.orden
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'lang' => $lang
]);
$ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * =========================================================
 * CALCULAR PROGRESO
 * =========================================================
 */

$total = count($ejercicios);
$completados = 0;

foreach ($ejercicios as $e) {
    if ($e['completado']) {
        $completados++;
    }
}

$pendientes = $total - $completados;
$porcentaje = $total > 0 ? round(($completados / $total) * 100) : 0;

/**
 * =========================================================
 * AGRUPAR POR CAPÍTULO
 * =========================================================
 */

$grupos = [];

foreach ($ejercicios as $e) {
    $cap = t('chapter') . ' ' . $e['capitulo'];

    if (!isset($grupos[$cap])) {
        $grupos[$cap] = [];
    }

    $grupos[$cap][] = $e;
}
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= t('practices') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">

<div class="max-w-7xl mx-auto p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold"><?= t('practices') ?></h1>
            <p class="text-slate-400"><?= t('hello_user') ?> <?= htmlspecialchars($nombreUsuario) ?></p>
        </div>
    </div>

    <!-- PROGRESO -->
    <div class="mt-8 bg-slate-900 p-6 rounded-2xl border border-slate-800">
        <div class="flex justify-between text-sm mb-2">
            <span><?= t('progress') ?></span>
            <span><?= $porcentaje ?>%</span>
        </div>

        <div class="w-full bg-slate-800 rounded-full h-4">
            <div class="bg-sky-400 h-4 rounded-full" style="width: <?= $porcentaje ?>%"></div>
        </div>

        <div class="grid grid-cols-3 gap-4 mt-4 text-center">
            <div>
                <p class="text-xl font-bold"><?= $total ?></p>
                <p class="text-slate-400 text-sm"><?= t('total') ?></p>
            </div>

            <div>
                <p class="text-xl font-bold text-green-400"><?= $completados ?></p>
                <p class="text-slate-400 text-sm"><?= t('completed') ?></p>
            </div>

            <div>
                <p class="text-xl font-bold text-yellow-400"><?= $pendientes ?></p>
                <p class="text-slate-400 text-sm"><?= t('pending') ?></p>
            </div>
        </div>
    </div>

    <!-- LISTA -->
    <div class="mt-10 space-y-8">

        <?php foreach ($grupos as $capitulo => $items): ?>

            <div>
                <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($capitulo) ?></h2>

                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">

                    <?php foreach ($items as $e): ?>

                        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl hover:border-sky-500 transition">

                            <!-- ESTADO -->
                            <div class="flex justify-between items-start">
                                <span class="text-xs text-sky-300"><?= htmlspecialchars($e['codigo']) ?></span>

                                <?php if ($e['completado']): ?>
                                    <span class="text-xs bg-green-500/20 text-green-300 px-2 py-1 rounded">
                                        ✔
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs bg-slate-700 px-2 py-1 rounded">
                                        <?= t('pending_lower') ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- TITULO -->
                            <h3 class="text-lg font-bold mt-3">
                                <?= htmlspecialchars($e['titulo']) ?>
                            </h3>

                            <!-- DESCRIPCIÓN -->
                            <p class="text-slate-400 text-sm mt-2">
                                <?= htmlspecialchars(substr($e['descripcion'], 0, 120)) ?>...
                            </p>

                            <!-- BOTÓN -->
                            <div class="mt-4 text-right">
                                <a href="ejercicio.php?codigo=<?= urlencode($e['codigo']) ?>"
                                   class="bg-sky-500 px-4 py-2 rounded-xl hover:bg-sky-600 text-sm">
                                    <?= t('open') ?>
                                </a>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>
            </div>

        <?php endforeach; ?>

    </div>

</div>

</body>
</html>