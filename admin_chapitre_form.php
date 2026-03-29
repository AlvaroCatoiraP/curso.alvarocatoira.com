<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/lang.php';
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

function postString(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function postInt(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function normalizeText(string $text): string
{
    $text = str_replace("\r\n", "\n", trim($text));
    return preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
}

function getContent(PDO $pdo, int $chapitreId, string $langue, array $contentColumns): array
{
    $fields = ['chapitre_id', 'langue'];

    foreach (['titulo', 'teoria_larga', 'ejercicios_guiados', 'mini_quiz'] as $field) {
        if (hasColumn($contentColumns, $field)) {
            $fields[] = $field;
        }
    }

    $sql = "SELECT " . implode(', ', $fields) . "
            FROM chapitres_contenu
            WHERE chapitre_id = ? AND langue = ?
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$chapitreId, $langue]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: [
        'chapitre_id' => $chapitreId,
        'langue' => $langue,
        'titulo' => '',
        'teoria_larga' => '',
        'ejercicios_guiados' => '',
        'mini_quiz' => ''
    ];
}

function splitTwoLines(?string $text): array
{
    $text = trim((string)$text);
    if ($text === '') {
        return ['', ''];
    }

    $lines = preg_split('/\R/u', $text) ?: [];
    $lines = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));

    return [
        $lines[0] ?? '',
        $lines[1] ?? ''
    ];
}

function joinTwoLines(string $a, string $b): string
{
    $items = [];
    foreach ([$a, $b] as $item) {
        $item = trim($item);
        if ($item !== '') {
            $items[] = $item;
        }
    }
    return implode("\n", $items);
}

function upsertChapterContent(PDO $pdo, int $chapitreId, string $langue, array $data): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres_contenu WHERE chapitre_id = ? AND langue = ?");
    $stmt->execute([$chapitreId, $langue]);
    $exists = (int)$stmt->fetchColumn() > 0;

    if ($exists) {
        $sets = [];
        $values = [];

        foreach ($data as $field => $value) {
            $sets[] = "$field = ?";
            $values[] = $value;
        }

        $values[] = $chapitreId;
        $values[] = $langue;

        $sql = "UPDATE chapitres_contenu
                SET " . implode(', ', $sets) . "
                WHERE chapitre_id = ? AND langue = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    } else {
        $columns = ['chapitre_id', 'langue'];
        $placeholders = ['?', '?'];
        $values = [$chapitreId, $langue];

        foreach ($data as $field => $value) {
            $columns[] = $field;
            $placeholders[] = '?';
            $values[] = $value;
        }

        $sql = "INSERT INTO chapitres_contenu (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
}

$contentColumns = getTableColumns($pdo, 'chapitres_contenu');
$chapitresColumns = getTableColumns($pdo, 'chapitres');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editMode = $id > 0;

$chapter = [
    'id' => 0,
    'code' => '',
    'ordre_affichage' => 1,
    'visible' => 1,
    'titre_es' => '',
    'titre_fr' => ''
];

$esContent = [
    'titulo' => '',
    'teoria_larga' => '',
    'ejercicios_guiados' => '',
    'mini_quiz' => ''
];

$frContent = [
    'titulo' => '',
    'teoria_larga' => '',
    'ejercicios_guiados' => '',
    'mini_quiz' => ''
];

if ($editMode) {
    $stmt = $pdo->prepare("SELECT * FROM chapitres WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die(t('chapter_not_found'));
    }

    $chapter = $row;
    $esContent = getContent($pdo, $id, 'es', $contentColumns);
    $frContent = getContent($pdo, $id, 'fr', $contentColumns);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $idPost = postInt('id');
        $editModePost = $idPost > 0;

        $code = postString('code');
        $ordre = max(1, postInt('ordre_affichage', 1));
        $visible = isset($_POST['visible']) ? 1 : 0;

        $esTitulo = postString('es_titulo');
        $frTitulo = postString('fr_titulo');
        $esTeoria = normalizeText(postString('es_teoria'));
        $frTeoria = normalizeText(postString('fr_teoria'));
        $esEj1 = normalizeText(postString('es_ejercicio_1'));
        $esEj2 = normalizeText(postString('es_ejercicio_2'));
        $frEj1 = normalizeText(postString('fr_ejercicio_1'));
        $frEj2 = normalizeText(postString('fr_ejercicio_2'));
        $esQuiz = normalizeText(postString('es_mini_quiz'));
        $frQuiz = normalizeText(postString('fr_mini_quiz'));

        if ($code === '') {
            throw new Exception(t('chapter_code_required'));
        }

        $pdo->beginTransaction();

        if ($editModePost) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE code = ? AND id <> ?");
            $stmt->execute([$code, $idPost]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception(t('chapter_code_used_by_other'));
            }

            $sets = ['code = ?', 'ordre_affichage = ?', 'visible = ?'];
            $values = [$code, $ordre, $visible];

            if (hasColumn($chapitresColumns, 'titre_es')) {
                $sets[] = 'titre_es = ?';
                $values[] = $esTitulo;
            }

            if (hasColumn($chapitresColumns, 'titre_fr')) {
                $sets[] = 'titre_fr = ?';
                $values[] = $frTitulo;
            }

            $values[] = $idPost;

            $sql = "UPDATE chapitres SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $chapitreId = $idPost;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE code = ?");
            $stmt->execute([$code]);

            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception(t('chapter_code_exists'));
            }

            $fields = ['code', 'ordre_affichage', 'visible'];
            $values = [$code, $ordre, $visible];
            $holders = ['?', '?', '?'];

            if (hasColumn($chapitresColumns, 'titre_es')) {
                $fields[] = 'titre_es';
                $values[] = $esTitulo;
                $holders[] = '?';
            }

            if (hasColumn($chapitresColumns, 'titre_fr')) {
                $fields[] = 'titre_fr';
                $values[] = $frTitulo;
                $holders[] = '?';
            }

            $sql = "INSERT INTO chapitres (" . implode(', ', $fields) . ")
                    VALUES (" . implode(', ', $holders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $chapitreId = (int)$pdo->lastInsertId();
        }

        $esData = [];
        $frData = [];

        if (hasColumn($contentColumns, 'titulo')) {
            $esData['titulo'] = $esTitulo;
            $frData['titulo'] = $frTitulo;
        }

        if (hasColumn($contentColumns, 'teoria_larga')) {
            $esData['teoria_larga'] = $esTeoria;
            $frData['teoria_larga'] = $frTeoria;
        }

        if (hasColumn($contentColumns, 'ejercicios_guiados')) {
            $esData['ejercicios_guiados'] = joinTwoLines($esEj1, $esEj2);
            $frData['ejercicios_guiados'] = joinTwoLines($frEj1, $frEj2);
        }

        if (hasColumn($contentColumns, 'mini_quiz')) {
            $esData['mini_quiz'] = $esQuiz;
            $frData['mini_quiz'] = $frQuiz;
        }

        upsertChapterContent($pdo, $chapitreId, 'es', $esData);
        upsertChapterContent($pdo, $chapitreId, 'fr', $frData);

        $pdo->commit();

        header('Location: admin_chapitres.php');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

[$esEj1, $esEj2] = splitTwoLines($esContent['ejercicios_guiados'] ?? '');
[$frEj1, $frEj2] = splitTwoLines($frContent['ejercicios_guiados'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $editMode ? t('edit_chapter') : t('create_chapter') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="max-w-7xl mx-auto p-6">
    <div class="max-w-5xl mx-auto p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= $editMode ? t('edit_chapter') : t('create_chapter') ?></h1>
                <p class="text-slate-400 mt-2"><?= t('bilingual_chapter_form') ?></p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="admin_chapitres.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                    <?= t('back_to_list') ?>
                </a>
                <a href="admin_dashboard.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                    <?= t('admin_dashboard') ?>
                </a>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-8">
            <input type="hidden" name="id" value="<?= (int)($chapter['id'] ?? 0) ?>">

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('code') ?></label>
                    <input type="text" name="code" value="<?= h($chapter['code'] ?? '') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('order') ?></label>
                    <input type="number" min="1" name="ordre_affichage" value="<?= (int)($chapter['ordre_affichage'] ?? 1) ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3" required>
                </div>

                <div class="flex items-end">
                    <label class="inline-flex items-center gap-3 w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3">
                        <input type="checkbox" name="visible" value="1" <?= (int)($chapter['visible'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <span class="font-semibold"><?= t('visible') ?></span>
                    </label>
                </div>
            </div>

            <div class="grid xl:grid-cols-2 gap-6">
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <h2 class="text-xl font-bold text-sky-300"><?= t('content_es') ?></h2>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('title') ?></label>
                        <input type="text" name="es_titulo" value="<?= h($chapter['titre_es'] ?? $esContent['titulo'] ?? '') ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('theory') ?></label>
                        <textarea name="es_teoria" rows="6" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($esContent['teoria_larga'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('exercise_1') ?></label>
                        <textarea name="es_ejercicio_1" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($esEj1) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('exercise_2') ?></label>
                        <textarea name="es_ejercicio_2" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($esEj2) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('mini_quiz') ?></label>
                        <textarea name="es_mini_quiz" rows="4" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($esContent['mini_quiz'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 space-y-4">
                    <h2 class="text-xl font-bold text-violet-300"><?= t('content_fr') ?></h2>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('title_fr_label') ?></label>
                        <input type="text" name="fr_titulo" value="<?= h($chapter['titre_fr'] ?? $frContent['titulo'] ?? '') ?>" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('theory_fr') ?></label>
                        <textarea name="fr_teoria" rows="6" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($frContent['teoria_larga'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('exercise_1_fr') ?></label>
                        <textarea name="fr_ejercicio_1" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($frEj1) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('exercise_2_fr') ?></label>
                        <textarea name="fr_ejercicio_2" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($frEj2) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-300 mb-2"><?= t('mini_quiz') ?></label>
                        <textarea name="fr_mini_quiz" rows="4" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"><?= h($frContent['mini_quiz'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                    <?= $editMode ? t('save_changes') : t('create_chapter') ?>
                </button>

                <a href="admin_chapitres.php" class="bg-slate-800 hover:bg-slate-700 px-5 py-3 rounded-xl font-semibold">
                    <?= t('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>