<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

exiger_admin();

$message = '';
$error = '';

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getTableColumns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return is_array($columns) ? $columns : [];
    } catch (Throwable $e) {
        return [];
    }
}

function hasColumn(array $columns, string $name): bool
{
    return in_array($name, $columns, true);
}

$chapitresColumns = getTableColumns($pdo, 'chapitres');
$contentColumns = getTableColumns($pdo, 'chapitres_contenu');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim((string)($_POST['action'] ?? ''));
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            throw new Exception('Capítulo inválido.');
        }

        if ($action === 'toggle_visible') {
            $visible = isset($_POST['visible']) ? (int)$_POST['visible'] : -1;

            if (!in_array($visible, [0, 1], true)) {
                throw new Exception('Valor de visibilidad inválido.');
            }

            $stmt = $pdo->prepare("UPDATE chapitres SET visible = ? WHERE id = ?");
            $stmt->execute([$visible, $id]);

            $message = 'Visibilidad actualizada.';
        }

        if ($action === 'delete') {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM chapitres_contenu WHERE chapitre_id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM chapitres WHERE id = ?");
            $stmt->execute([$id]);

            $pdo->commit();

            $message = 'Capítulo eliminado.';
        }
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $e->getMessage();
}

$titreEsSql = hasColumn($chapitresColumns, 'titre_es')
    ? "COALESCE(c.titre_es, '')"
    : (hasColumn($contentColumns, 'titulo') ? "COALESCE(cc_es.titulo, '')" : "''");

$titreFrSql = hasColumn($chapitresColumns, 'titre_fr')
    ? "COALESCE(c.titre_fr, '')"
    : (hasColumn($contentColumns, 'titulo') ? "COALESCE(cc_fr.titulo, '')" : "''");

$ordreColumn = hasColumn($chapitresColumns, 'ordre_affichage') ? 'ordre_affichage' : 'id';

$sql = "
    SELECT
        c.*,
        $titreEsSql AS titre_es_affichage,
        $titreFrSql AS titre_fr_affichage
    FROM chapitres c
    LEFT JOIN chapitres_contenu cc_es ON cc_es.chapitre_id = c.id AND cc_es.langue = 'es'
    LEFT JOIN chapitres_contenu cc_fr ON cc_fr.chapitre_id = c.id AND cc_fr.langue = 'fr'
    ORDER BY c.$ordreColumn ASC, c.id ASC
";

$stmt = $pdo->query($sql);
$chapitres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar capítulos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-7xl mx-auto p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Gestionar capítulos</h1>
                <p class="text-slate-400 mt-2">Lista de capítulos del curso.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="admin_dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    Dashboard admin
                </a>
                <a href="admin_chapitre_form.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                    Nuevo capítulo
                </a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">
                    Cerrar sesión
                </a>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead class="bg-slate-800">
                        <tr>
                            <th class="text-left p-4">Orden</th>
                            <th class="text-left p-4">Código</th>
                            <th class="text-left p-4">Título ES</th>
                            <th class="text-left p-4">Titre FR</th>
                            <th class="text-left p-4">Visible</th>
                            <th class="text-left p-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($chapitres) === 0): ?>
                            <tr>
                                <td colspan="6" class="p-4 text-slate-400">No hay capítulos todavía.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chapitres as $chapitre): ?>
                                <tr class="border-t border-slate-800">
                                    <td class="p-4"><?= (int)($chapitre[$ordreColumn] ?? 0) ?></td>
                                    <td class="p-4 font-semibold"><?= h($chapitre['code'] ?? '') ?></td>
                                    <td class="p-4"><?= h($chapitre['titre_es_affichage'] ?? '') ?></td>
                                    <td class="p-4"><?= h($chapitre['titre_fr_affichage'] ?? '') ?></td>
                                    <td class="p-4">
                                        <?php if ((int)($chapitre['visible'] ?? 0) === 1): ?>
                                            <span class="text-emerald-400 font-semibold">Sí</span>
                                        <?php else: ?>
                                            <span class="text-red-400 font-semibold">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="admin_chapitre_form.php?id=<?= (int)$chapitre['id'] ?>"
                                               class="bg-sky-500 hover:bg-sky-600 px-3 py-2 rounded-xl font-semibold text-sm">
                                                Editar
                                            </a>

                                            <?php if ((int)($chapitre['visible'] ?? 0) === 1): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_visible">
                                                    <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                    <input type="hidden" name="visible" value="0">
                                                    <button type="submit" class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl font-semibold text-sm">
                                                        Desactivar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_visible">
                                                    <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                    <input type="hidden" name="visible" value="1">
                                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 px-3 py-2 rounded-xl font-semibold text-sm">
                                                        Activar
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" class="inline" onsubmit="return confirm('¿Seguro que quieres eliminar este capítulo?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                <button type="submit" class="bg-slate-700 hover:bg-slate-600 px-3 py-2 rounded-xl font-semibold text-sm">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>