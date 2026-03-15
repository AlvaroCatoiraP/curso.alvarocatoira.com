<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

exiger_admin();

$message = '';
$error = '';

function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getTableColumns(PDO $pdo, string $table): array {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function hasColumn(array $columns, string $name): bool {
    return in_array($name, $columns, true);
}

function getPostString(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

function getPostInt(string $key, int $default = 0): int {
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function getChapterContent(PDO $pdo, int $chapitreId, string $langue, array $contentColumns): array {
    $fields = ['chapitre_id', 'langue'];

    $possibleFields = [
        'titulo',
        'teoria_larga',
        'ejercicios_guiados',
        'mini_quiz'
    ];

    foreach ($possibleFields as $field) {
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

    if ($row) {
        return $row;
    }

    $empty = [
        'chapitre_id' => $chapitreId,
        'langue' => $langue,
        'titulo' => '',
        'teoria_larga' => '',
        'ejercicios_guiados' => '',
        'mini_quiz' => ''
    ];

    return $empty;
}

function upsertChapterContent(PDO $pdo, int $chapitreId, string $langue, array $data): void {
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

        $sql = "UPDATE chapitres_contenu SET " . implode(', ', $sets) . " WHERE chapitre_id = ? AND langue = ?";
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

function buildTwoExercises(string $textA, string $textB): string {
    $items = [];

    foreach ([$textA, $textB] as $text) {
        $lines = preg_split('/\R/u', trim($text));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^\d+[\).\-\:]\s*/u', '', $line);
            $line = preg_replace('/^[-*•]\s*/u', '', $line);
            if ($line !== '') {
                $items[] = $line;
            }
        }
    }

    $items = array_slice($items, 0, 2);

    return implode("\n", $items);
}

$contentColumns = getTableColumns($pdo, 'chapitres_contenu');
$chapitresColumns = getTableColumns($pdo, 'chapitres');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = getPostString('action');

        if ($action === 'toggle_visible') {
            $id = getPostInt('id');
            $visible = getPostInt('visible', -1);

            if ($id > 0 && ($visible === 0 || $visible === 1)) {
                $sql = "UPDATE chapitres SET visible = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$visible, $id]);
                $message = "Chapitre mis à jour.";
            } else {
                $error = "Valeurs invalides.";
            }
        }

        if ($action === 'create') {
            $code = getPostString('code');
            $ordre = getPostInt('ordre_affichage', 1);
            $visible = isset($_POST['visible']) ? 1 : 0;

            if ($code === '') {
                throw new Exception("Le code du chapitre est obligatoire.");
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE code = ?");
            $stmt->execute([$code]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception("Ce code existe déjà.");
            }

            $insertFields = ['code', 'ordre_affichage', 'visible'];
            $insertValues = [$code, $ordre, $visible];
            $insertPlaceholders = ['?', '?', '?'];

            if (hasColumn($chapitresColumns, 'titre_es')) {
                $insertFields[] = 'titre_es';
                $insertValues[] = getPostString('es_titulo');
                $insertPlaceholders[] = '?';
            }

            if (hasColumn($chapitresColumns, 'titre_fr')) {
                $insertFields[] = 'titre_fr';
                $insertValues[] = getPostString('fr_titulo');
                $insertPlaceholders[] = '?';
            }

            $sql = "INSERT INTO chapitres (" . implode(', ', $insertFields) . ")
                    VALUES (" . implode(', ', $insertPlaceholders) . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($insertValues);

            $chapitreId = (int)$pdo->lastInsertId();

            $esData = [];
            $frData = [];

            if (hasColumn($contentColumns, 'titulo')) {
                $esData['titulo'] = getPostString('es_titulo');
                $frData['titulo'] = getPostString('fr_titulo');
            }

            if (hasColumn($contentColumns, 'teoria_larga')) {
                $esData['teoria_larga'] = getPostString('es_teoria');
                $frData['teoria_larga'] = getPostString('fr_teoria');
            }

            $esExercises = buildTwoExercises(getPostString('es_ejercicio_1'), getPostString('es_ejercicio_2'));
            $frExercises = buildTwoExercises(getPostString('fr_ejercicio_1'), getPostString('fr_ejercicio_2'));

            if (hasColumn($contentColumns, 'ejercicios_guiados')) {
                $esData['ejercicios_guiados'] = $esExercises;
                $frData['ejercicios_guiados'] = $frExercises;
            } elseif (hasColumn($contentColumns, 'mini_quiz')) {
                $esData['mini_quiz'] = $esExercises;
                $frData['mini_quiz'] = $frExercises;
            }

            upsertChapterContent($pdo, $chapitreId, 'es', $esData);
            upsertChapterContent($pdo, $chapitreId, 'fr', $frData);

            $message = "Chapitre créé avec succès.";
        }

        if ($action === 'update') {
            $id = getPostInt('id');
            $code = getPostString('code');
            $ordre = getPostInt('ordre_affichage', 1);
            $visible = isset($_POST['visible']) ? 1 : 0;

            if ($id <= 0) {
                throw new Exception("Chapitre invalide.");
            }

            if ($code === '') {
                throw new Exception("Le code du chapitre est obligatoire.");
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE code = ? AND id <> ?");
            $stmt->execute([$code, $id]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception("Un autre chapitre utilise déjà ce code.");
            }

            $sets = ['code = ?', 'ordre_affichage = ?', 'visible = ?'];
            $values = [$code, $ordre, $visible];

            if (hasColumn($chapitresColumns, 'titre_es')) {
                $sets[] = 'titre_es = ?';
                $values[] = getPostString('es_titulo');
            }

            if (hasColumn($chapitresColumns, 'titre_fr')) {
                $sets[] = 'titre_fr = ?';
                $values[] = getPostString('fr_titulo');
            }

            $values[] = $id;

            $sql = "UPDATE chapitres SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $esData = [];
            $frData = [];

            if (hasColumn($contentColumns, 'titulo')) {
                $esData['titulo'] = getPostString('es_titulo');
                $frData['titulo'] = getPostString('fr_titulo');
            }

            if (hasColumn($contentColumns, 'teoria_larga')) {
                $esData['teoria_larga'] = getPostString('es_teoria');
                $frData['teoria_larga'] = getPostString('fr_teoria');
            }

            $esExercises = buildTwoExercises(getPostString('es_ejercicio_1'), getPostString('es_ejercicio_2'));
            $frExercises = buildTwoExercises(getPostString('fr_ejercicio_1'), getPostString('fr_ejercicio_2'));

            if (hasColumn($contentColumns, 'ejercicios_guiados')) {
                $esData['ejercicios_guiados'] = $esExercises;
                $frData['ejercicios_guiados'] = $frExercises;
            } elseif (hasColumn($contentColumns, 'mini_quiz')) {
                $esData['mini_quiz'] = $esExercises;
                $frData['mini_quiz'] = $frExercises;
            }

            upsertChapterContent($pdo, $id, 'es', $esData);
            upsertChapterContent($pdo, $id, 'fr', $frData);

            $message = "Chapitre mis à jour avec succès.";
        }

        if ($action === 'delete') {
            $id = getPostInt('id');

            if ($id <= 0) {
                throw new Exception("Chapitre invalide.");
            }

            $stmt = $pdo->prepare("DELETE FROM chapitres_contenu WHERE chapitre_id = ?");
            $stmt->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM chapitres WHERE id = ?");
            $stmt->execute([$id]);

            $message = "Chapitre supprimé.";
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editMode = $editId > 0;

$chapterToEdit = [
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
    $stmt->execute([$editId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $chapterToEdit = $row;
        $esContent = getChapterContent($pdo, $editId, 'es', $contentColumns);
        $frContent = getChapterContent($pdo, $editId, 'fr', $contentColumns);
    } else {
        $error = "Chapitre introuvable.";
        $editMode = false;
    }
}

$sql = "
    SELECT 
        c.*,
        " . (hasColumn($chapitresColumns, 'titre_es') ? "c.titre_es" : "COALESCE(cc_es.titulo, '')") . " AS titre_es_affichage,
        " . (hasColumn($chapitresColumns, 'titre_fr') ? "c.titre_fr" : "COALESCE(cc_fr.titulo, '')") . " AS titre_fr_affichage
    FROM chapitres c
    LEFT JOIN chapitres_contenu cc_es ON cc_es.chapitre_id = c.id AND cc_es.langue = 'es'
    LEFT JOIN chapitres_contenu cc_fr ON cc_fr.chapitre_id = c.id AND cc_fr.langue = 'fr'
    ORDER BY c.ordre_affichage ASC
";
$stmt = $pdo->query($sql);
$chapitres = $stmt->fetchAll(PDO::FETCH_ASSOC);

function splitExercises(array $content): array {
    $text = trim((string)($content['ejercicios_guiados'] ?? $content['mini_quiz'] ?? ''));
    if ($text === '') {
        return ['', ''];
    }

    $lines = preg_split('/\R/u', $text);
    $items = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $line = preg_replace('/^\d+[\).\-\:]\s*/u', '', $line);
        $line = preg_replace('/^[-*•]\s*/u', '', $line);
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return [
        $items[0] ?? '',
        $items[1] ?? ''
    ];
}

[$esEj1, $esEj2] = splitExercises($esContent);
[$frEj1, $frEj2] = splitExercises($frContent);

$esTituloForm = $chapterToEdit['titre_es'] ?? $esContent['titulo'] ?? '';
$frTituloForm = $chapterToEdit['titre_fr'] ?? $frContent['titulo'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les chapitres</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen">
    <div class="max-w-7xl mx-auto p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Gérer les chapitres</h1>
                <p class="text-slate-400 mt-2">Activer, désactiver, créer, modifier et supprimer les chapitres.</p>
            </div>
            <div class="flex gap-3">
                <a href="admin_dashboard.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">Admin dashboard</a>
                <a href="admin_chapitres.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">Nouveau chapitre</a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-xl font-semibold">Se déconnecter</a>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 bg-sky-500/10 border border-sky-500/30 text-sky-300 px-4 py-3 rounded-xl">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid xl:grid-cols-3 gap-8">
            <div class="xl:col-span-1">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-800">
                            <tr>
                                <th class="text-left p-4">Ordre</th>
                                <th class="text-left p-4">Code</th>
                                <th class="text-left p-4">Visible</th>
                                <th class="text-left p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($chapitres as $chapitre): ?>
                                <tr class="border-t border-slate-800 align-top">
                                    <td class="p-4"><?= (int)$chapitre['ordre_affichage'] ?></td>
                                    <td class="p-4">
                                        <div class="font-semibold"><?= h($chapitre['code']) ?></div>
                                        <div class="text-slate-400 text-sm mt-1"><?= h($chapitre['titre_es_affichage']) ?></div>
                                        <div class="text-slate-500 text-sm"><?= h($chapitre['titre_fr_affichage']) ?></div>
                                    </td>
                                    <td class="p-4">
                                        <?php if ((int)$chapitre['visible'] === 1): ?>
                                            <span class="text-emerald-400 font-semibold">Oui</span>
                                        <?php else: ?>
                                            <span class="text-red-400 font-semibold">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-2">
                                            <a href="?edit=<?= (int)$chapitre['id'] ?>" class="bg-sky-500 hover:bg-sky-600 px-3 py-2 rounded-xl font-semibold text-center">
                                                Éditer
                                            </a>

                                            <?php if ((int)$chapitre['visible'] === 1): ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_visible">
                                                    <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                    <input type="hidden" name="visible" value="0">
                                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 px-3 py-2 rounded-xl font-semibold">
                                                        Désactiver
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="toggle_visible">
                                                    <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                    <input type="hidden" name="visible" value="1">
                                                    <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 px-3 py-2 rounded-xl font-semibold">
                                                        Activer
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" onsubmit="return confirm('Supprimer ce chapitre ?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$chapitre['id'] ?>">
                                                <button type="submit" class="w-full bg-slate-700 hover:bg-slate-600 px-3 py-2 rounded-xl font-semibold">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($chapitres) === 0): ?>
                                <tr>
                                    <td colspan="4" class="p-4 text-slate-400">Aucun chapitre.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="xl:col-span-2">
                <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-8">
                    <input type="hidden" name="action" value="<?= $editMode ? 'update' : 'create' ?>">
                    <input type="hidden" name="id" value="<?= (int)$chapterToEdit['id'] ?>">

                    <div>
                        <h2 class="text-2xl font-bold"><?= $editMode ? 'Modifier un chapitre' : 'Créer un chapitre' ?></h2>
                        <p class="text-slate-400 mt-2">Version simple : titre, théorie et 2 exercices par langue.</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Code</label>
                            <input
                                type="text"
                                name="code"
                                value="<?= h((string)$chapterToEdit['code']) ?>"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Ordre</label>
                            <input
                                type="number"
                                name="ordre_affichage"
                                value="<?= (int)$chapterToEdit['ordre_affichage'] ?>"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3"
                                required
                            >
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-3 w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3">
                                <input type="checkbox" name="visible" value="1" <?= (int)$chapterToEdit['visible'] === 1 ? 'checked' : '' ?>>
                                <span class="font-semibold">Visible</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid xl:grid-cols-2 gap-6">
                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 space-y-4">
                            <h3 class="text-xl font-bold text-sky-300">Contenu ES</h3>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Título</label>
                                <input
                                    type="text"
                                    name="es_titulo"
                                    value="<?= h($esTituloForm) ?>"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Teoría</label>
                                <textarea
                                    name="es_teoria"
                                    rows="10"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($esContent['teoria_larga'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Ejercicio 1</label>
                                <textarea
                                    name="es_ejercicio_1"
                                    rows="3"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($esEj1) ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Ejercicio 2</label>
                                <textarea
                                    name="es_ejercicio_2"
                                    rows="3"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($esEj2) ?></textarea>
                            </div>
                        </div>

                        <div class="bg-slate-950 border border-slate-800 rounded-2xl p-5 space-y-4">
                            <h3 class="text-xl font-bold text-violet-300">Contenu FR</h3>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Titre</label>
                                <input
                                    type="text"
                                    name="fr_titulo"
                                    value="<?= h($frTituloForm) ?>"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Théorie</label>
                                <textarea
                                    name="fr_teoria"
                                    rows="10"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($frContent['teoria_larga'] ?? '') ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Exercice 1</label>
                                <textarea
                                    name="fr_ejercicio_1"
                                    rows="3"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($frEj1) ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Exercice 2</label>
                                <textarea
                                    name="fr_ejercicio_2"
                                    rows="3"
                                    class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3"
                                ><?= h($frEj2) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-5 py-3 rounded-xl font-semibold">
                            <?= $editMode ? 'Enregistrer les modifications' : 'Créer le chapitre' ?>
                        </button>

                        <a href="admin_chapitres.php" class="bg-slate-800 hover:bg-slate-700 px-5 py-3 rounded-xl font-semibold">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>