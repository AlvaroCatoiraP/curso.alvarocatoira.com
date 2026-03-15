<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_connexion();

/*
|--------------------------------------------------------------------------
| Vérification admin
|--------------------------------------------------------------------------
| Adapte cette partie selon ton système si besoin.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$estAdmin = false;

if (isset($_SESSION['utilisateur']) && is_array($_SESSION['utilisateur'])) {
    $user = $_SESSION['utilisateur'];

    if (
        (!empty($user['role']) && $user['role'] === 'admin') ||
        (!empty($user['type']) && $user['type'] === 'admin') ||
        (!empty($user['est_admin']) && (int)$user['est_admin'] === 1)
    ) {
        $estAdmin = true;
    }
}

if (!$estAdmin) {
    http_response_code(403);
    exit('Accès refusé.');
}

function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function postString(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function postInt(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

function getString(string $key): string
{
    return trim((string)($_GET[$key] ?? ''));
}

function getInt(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

function tableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columns = [];

    foreach ($rows as $row) {
        if (!empty($row['Field'])) {
            $columns[] = $row['Field'];
        }
    }

    return $columns;
}

function hasColumn(array $columns, string $column): bool
{
    return in_array($column, $columns, true);
}

function buildContentDataFromPost(array $availableColumns, string $prefix): array
{
    $allFields = [
        'titulo',
        'introduccion',
        'objetivos',
        'teoria_larga',
        'teoria_complementaria',
        'definiciones_clave',
        'conceptos_clave',
        'cuando_usarlo',
        'comparacion',
        'intuicion',
        'analogia',
        'ejemplo_simple',
        'explicacion_ejemplo_simple',
        'paso_a_paso',
        'ejemplo_avanzado',
        'explicacion_ejemplo_avanzado',
        'ejemplo_error',
        'ejercicios_guiados',
        'aplicacion_real',
        'preguntas_frecuentes',
        'errores_comunes',
        'buenas_practicas',
        'curiosidades',
        'glosario',
        'resumen_final',
        'mini_quiz'
    ];

    $data = [];

    foreach ($allFields as $field) {
        if (hasColumn($availableColumns, $field)) {
            $data[$field] = trim((string)($_POST[$prefix . $field] ?? ''));
        }
    }

    return $data;
}

function getChapitreById(PDO $pdo, int $chapitreId): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, code, ordre_affichage, visible
        FROM chapitres
        WHERE id = ?
    ");
    $stmt->execute([$chapitreId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function getChapitreContentByLang(PDO $pdo, int $chapitreId, string $langue, array $availableColumns): array
{
    $baseFields = ['chapitre_id', 'langue'];
    $fields = [];

    $possibleFields = [
        'titulo',
        'introduccion',
        'objetivos',
        'teoria_larga',
        'teoria_complementaria',
        'definiciones_clave',
        'conceptos_clave',
        'cuando_usarlo',
        'comparacion',
        'intuicion',
        'analogia',
        'ejemplo_simple',
        'explicacion_ejemplo_simple',
        'paso_a_paso',
        'ejemplo_avanzado',
        'explicacion_ejemplo_avanzado',
        'ejemplo_error',
        'ejercicios_guiados',
        'aplicacion_real',
        'preguntas_frecuentes',
        'errores_comunes',
        'buenas_practicas',
        'curiosidades',
        'glosario',
        'resumen_final',
        'mini_quiz'
    ];

    foreach ($possibleFields as $field) {
        if (hasColumn($availableColumns, $field)) {
            $fields[] = $field;
        }
    }

    $select = implode(', ', array_merge($baseFields, $fields));

    $stmt = $pdo->prepare("
        SELECT $select
        FROM chapitres_contenu
        WHERE chapitre_id = ? AND langue = ?
        LIMIT 1
    ");
    $stmt->execute([$chapitreId, $langue]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    $empty = ['chapitre_id' => $chapitreId, 'langue' => $langue];
    foreach ($fields as $field) {
        $empty[$field] = '';
    }

    return $empty;
}

function upsertChapitreContent(PDO $pdo, int $chapitreId, string $langue, array $data): void
{
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM chapitres_contenu
        WHERE chapitre_id = ? AND langue = ?
    ");
    $check->execute([$chapitreId, $langue]);
    $exists = (int)$check->fetchColumn() > 0;

    if ($exists) {
        $setParts = [];
        $values = [];

        foreach ($data as $field => $value) {
            $setParts[] = "$field = ?";
            $values[] = $value;
        }

        $values[] = $chapitreId;
        $values[] = $langue;

        $sql = "
            UPDATE chapitres_contenu
            SET " . implode(', ', $setParts) . "
            WHERE chapitre_id = ? AND langue = ?
        ";

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

        $sql = "
            INSERT INTO chapitres_contenu (" . implode(', ', $columns) . ")
            VALUES (" . implode(', ', $placeholders) . ")
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
    }
}

$contentColumns = tableColumns($pdo, 'chapitres_contenu');

$message = '';
$error = '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = postString('action');

        if ($action === 'create') {
            $code = postString('code');
            $ordre = postInt('ordre_affichage', 1);
            $visible = isset($_POST['visible']) ? 1 : 0;

            if ($code === '') {
                throw new Exception('Le code du chapitre est obligatoire.');
            }

            $check = $pdo->prepare("SELECT COUNT(*) FROM chapitres WHERE code = ?");
            $check->execute([$code]);

            if ((int)$check->fetchColumn() > 0) {
                throw new Exception('Ce code de chapitre existe déjà.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO chapitres (code, ordre_affichage, visible)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$code, $ordre, $visible]);

            $chapitreId = (int)$pdo->lastInsertId();

            $dataEs = buildContentDataFromPost($contentColumns, 'es_');
            $dataFr = buildContentDataFromPost($contentColumns, 'fr_');

            upsertChapitreContent($pdo, $chapitreId, 'es', $dataEs);
            upsertChapitreContent($pdo, $chapitreId, 'fr', $dataFr);

            $message = 'Chapitre créé avec succès.';
        }

        if ($action === 'update') {
            $chapitreId = postInt('chapitre_id');
            $code = postString('code');
            $ordre = postInt('ordre_affichage', 1);
            $visible = isset($_POST['visible']) ? 1 : 0;

            if ($chapitreId <= 0) {
                throw new Exception('Chapitre invalide.');
            }

            if ($code === '') {
                throw new Exception('Le code du chapitre est obligatoire.');
            }

            $check = $pdo->prepare("
                SELECT COUNT(*)
                FROM chapitres
                WHERE code = ? AND id <> ?
            ");
            $check->execute([$code, $chapitreId]);

            if ((int)$check->fetchColumn() > 0) {
                throw new Exception('Un autre chapitre utilise déjà ce code.');
            }

            $stmt = $pdo->prepare("
                UPDATE chapitres
                SET code = ?, ordre_affichage = ?, visible = ?
                WHERE id = ?
            ");
            $stmt->execute([$code, $ordre, $visible, $chapitreId]);

            $dataEs = buildContentDataFromPost($contentColumns, 'es_');
            $dataFr = buildContentDataFromPost($contentColumns, 'fr_');

            upsertChapitreContent($pdo, $chapitreId, 'es', $dataEs);
            upsertChapitreContent($pdo, $chapitreId, 'fr', $dataFr);

            $message = 'Chapitre mis à jour avec succès.';
        }

        if ($action === 'delete') {
            $chapitreId = postInt('chapitre_id');

            if ($chapitreId <= 0) {
                throw new Exception('Chapitre invalide.');
            }

            $stmt = $pdo->prepare("DELETE FROM chapitres_contenu WHERE chapitre_id = ?");
            $stmt->execute([$chapitreId]);

            $stmt = $pdo->prepare("DELETE FROM chapitres WHERE id = ?");
            $stmt->execute([$chapitreId]);

            $message = 'Chapitre supprimé avec succès.';
        }

        if ($action === 'toggle_visible') {
            $chapitreId = postInt('chapitre_id');
            $visible = postInt('visible', 0);

            if ($chapitreId <= 0) {
                throw new Exception('Chapitre invalide.');
            }

            $stmt = $pdo->prepare("
                UPDATE chapitres
                SET visible = ?
                WHERE id = ?
            ");
            $stmt->execute([$visible, $chapitreId]);

            $message = 'Visibilité mise à jour.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$editId = getInt('edit');
$mode = $editId > 0 ? 'edit' : 'create';

$chapitre = [
    'id' => 0,
    'code' => '',
    'ordre_affichage' => 1,
    'visible' => 1
];

$contenuEs = [];
$contenuFr = [];

if ($mode === 'edit') {
    $chapitreFound = getChapitreById($pdo, $editId);

    if ($chapitreFound) {
        $chapitre = $chapitreFound;
        $contenuEs = getChapitreContentByLang($pdo, $editId, 'es', $contentColumns);
        $contenuFr = getChapitreContentByLang($pdo, $editId, 'fr', $contentColumns);
    } else {
        $mode = 'create';
        $error = 'Chapitre introuvable.';
    }
}

if ($mode === 'create') {
    $contenuEs = getChapitreContentByLang($pdo, 0, 'es', $contentColumns);
    $contenuFr = getChapitreContentByLang($pdo, 0, 'fr', $contentColumns);
}

$chapitres = $pdo->query("
    SELECT
        c.id,
        c.code,
        c.ordre_affichage,
        c.visible,
        COALESCE(cc_es.titulo, cc_fr.titulo, c.code) AS titre_affichage
    FROM chapitres c
    LEFT JOIN chapitres_contenu cc_es
        ON cc_es.chapitre_id = c.id AND cc_es.langue = 'es'
    LEFT JOIN chapitres_contenu cc_fr
        ON cc_fr.chapitre_id = c.id AND cc_fr.langue = 'fr'
    ORDER BY c.ordre_affichage ASC, c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

function fieldValue(array $source, string $field): string
{
    return (string)($source[$field] ?? '');
}

$fieldsConfig = [
    'titulo' => ['label' => 'Título', 'type' => 'text'],
    'introduccion' => ['label' => 'Introducción', 'type' => 'textarea'],
    'objetivos' => ['label' => 'Objetivos', 'type' => 'textarea'],
    'teoria_larga' => ['label' => 'Teoría larga', 'type' => 'textarea'],
    'teoria_complementaria' => ['label' => 'Teoría complementaria', 'type' => 'textarea'],
    'definiciones_clave' => ['label' => 'Definiciones clave', 'type' => 'textarea'],
    'conceptos_clave' => ['label' => 'Conceptos clave', 'type' => 'textarea'],
    'cuando_usarlo' => ['label' => 'Cuándo usarlo', 'type' => 'textarea'],
    'comparacion' => ['label' => 'Comparación', 'type' => 'textarea'],
    'intuicion' => ['label' => 'Intuición', 'type' => 'textarea'],
    'analogia' => ['label' => 'Analogía', 'type' => 'textarea'],
    'ejemplo_simple' => ['label' => 'Ejemplo simple', 'type' => 'textarea'],
    'explicacion_ejemplo_simple' => ['label' => 'Explicación ejemplo simple', 'type' => 'textarea'],
    'paso_a_paso' => ['label' => 'Paso a paso', 'type' => 'textarea'],
    'ejemplo_avanzado' => ['label' => 'Ejemplo avanzado', 'type' => 'textarea'],
    'explicacion_ejemplo_avanzado' => ['label' => 'Explicación ejemplo avanzado', 'type' => 'textarea'],
    'ejemplo_error' => ['label' => 'Ejemplo de error', 'type' => 'textarea'],
    'ejercicios_guiados' => ['label' => 'Ejercicios guiados', 'type' => 'textarea'],
    'aplicacion_real' => ['label' => 'Aplicación real', 'type' => 'textarea'],
    'preguntas_frecuentes' => ['label' => 'Preguntas frecuentes', 'type' => 'textarea'],
    'errores_comunes' => ['label' => 'Errores comunes', 'type' => 'textarea'],
    'buenas_practicas' => ['label' => 'Buenas prácticas', 'type' => 'textarea'],
    'curiosidades' => ['label' => 'Curiosidades', 'type' => 'textarea'],
    'glosario' => ['label' => 'Glosario', 'type' => 'textarea'],
    'resumen_final' => ['label' => 'Resumen final', 'type' => 'textarea'],
    'mini_quiz' => ['label' => 'Mini quiz', 'type' => 'textarea'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de capítulos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest">Admin</p>
                <h1 class="text-3xl font-bold mt-2">Gestión de capítulos</h1>
                <p class="text-slate-400 mt-2">
                    Crear, editar, ordenar, mostrar u ocultar y eliminar capítulos del curso.
                </p>
            </div>

            <div class="flex gap-3">
                <a href="dashboard.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 transition font-semibold">
                    Dashboard
                </a>
                <a href="admin_chapitres.php" class="px-4 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 transition font-semibold">
                    Nuevo capítulo
                </a>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="mb-6 bg-emerald-950/30 border border-emerald-800 text-emerald-300 rounded-2xl px-5 py-4">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="mb-6 bg-red-950/30 border border-red-800 text-red-300 rounded-2xl px-5 py-4">
                <?= h($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid xl:grid-cols-3 gap-8">
            <section class="xl:col-span-1 bg-slate-900 border border-slate-800 rounded-3xl p-6">
                <h2 class="text-2xl font-bold mb-5">Lista de capítulos</h2>

                <div class="space-y-4">
                    <?php if (empty($chapitres)): ?>
                        <div class="bg-slate-800 border border-slate-700 rounded-2xl p-4 text-slate-300">
                            Ningún capítulo encontrado.
                        </div>
                    <?php else: ?>
                        <?php foreach ($chapitres as $item): ?>
                            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sky-400 text-sm font-semibold">
                                            #<?= (int)$item['ordre_affichage'] ?> · ID <?= (int)$item['id'] ?>
                                        </p>
                                        <h3 class="font-bold text-lg mt-1">
                                            <?= h($item['titre_affichage']) ?>
                                        </h3>
                                        <p class="text-slate-400 text-sm mt-1">
                                            Code: <?= h($item['code']) ?>
                                        </p>
                                        <p class="text-sm mt-2 <?= (int)$item['visible'] === 1 ? 'text-emerald-400' : 'text-amber-400' ?>">
                                            <?= (int)$item['visible'] === 1 ? 'Visible' : 'Oculto' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="?edit=<?= (int)$item['id'] ?>" class="px-3 py-2 rounded-xl bg-sky-500 hover:bg-sky-600 transition text-sm font-semibold">
                                        Editar
                                    </a>

                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="toggle_visible">
                                        <input type="hidden" name="chapitre_id" value="<?= (int)$item['id'] ?>">
                                        <input type="hidden" name="visible" value="<?= (int)$item['visible'] === 1 ? 0 : 1 ?>">
                                        <button type="submit" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 transition text-sm font-semibold">
                                            <?= (int)$item['visible'] === 1 ? 'Ocultar' : 'Mostrar' ?>
                                        </button>
                                    </form>

                                    <form method="post" class="inline" onsubmit="return confirm('¿Seguro que quieres eliminar este capítulo?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="chapitre_id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="px-3 py-2 rounded-xl bg-red-600 hover:bg-red-700 transition text-sm font-semibold">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="xl:col-span-2 bg-slate-900 border border-slate-800 rounded-3xl p-6">
                <h2 class="text-2xl font-bold mb-2">
                    <?= $mode === 'edit' ? 'Editar capítulo' : 'Crear capítulo' ?>
                </h2>
                <p class="text-slate-400 mb-6">
                    Aquí puedes modificar la información general y los contenidos en español y francés.
                </p>

                <form method="post" class="space-y-8">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <input type="hidden" name="chapitre_id" value="<?= (int)$chapitre['id'] ?>">

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Code</label>
                            <input
                                type="text"
                                name="code"
                                value="<?= h((string)$chapitre['code']) ?>"
                                class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-4 py-3 outline-none focus:border-sky-500"
                                required
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Ordre d'affichage</label>
                            <input
                                type="number"
                                name="ordre_affichage"
                                value="<?= (int)$chapitre['ordre_affichage'] ?>"
                                class="w-full rounded-2xl bg-slate-950 border border-slate-700 px-4 py-3 outline-none focus:border-sky-500"
                                required
                            >
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-3 rounded-2xl bg-slate-950 border border-slate-700 px-4 py-3 w-full">
                                <input type="checkbox" name="visible" value="1" <?= (int)$chapitre['visible'] === 1 ? 'checked' : '' ?>>
                                <span class="font-semibold text-slate-200">Visible</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid xl:grid-cols-2 gap-8">
                        <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5">
                            <h3 class="text-xl font-bold text-sky-300 mb-4">Contenido ES</h3>

                            <div class="space-y-4">
                                <?php foreach ($fieldsConfig as $field => $config): ?>
                                    <?php if (!hasColumn($contentColumns, $field)) continue; ?>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-300 mb-2">
                                            <?= h($config['label']) ?>
                                        </label>

                                        <?php if ($config['type'] === 'textarea'): ?>
                                            <textarea
                                                name="es_<?= h($field) ?>"
                                                rows="5"
                                                class="w-full rounded-2xl bg-slate-900 border border-slate-700 px-4 py-3 outline-none focus:border-sky-500 resize-y"
                                            ><?= h(fieldValue($contenuEs, $field)) ?></textarea>
                                        <?php else: ?>
                                            <input
                                                type="text"
                                                name="es_<?= h($field) ?>"
                                                value="<?= h(fieldValue($contenuEs, $field)) ?>"
                                                class="w-full rounded-2xl bg-slate-900 border border-slate-700 px-4 py-3 outline-none focus:border-sky-500"
                                            >
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="bg-slate-950 border border-slate-800 rounded-3xl p-5">
                            <h3 class="text-xl font-bold text-violet-300 mb-4">Contenu FR</h3>

                            <div class="space-y-4">
                                <?php foreach ($fieldsConfig as $field => $config): ?>
                                    <?php if (!hasColumn($contentColumns, $field)) continue; ?>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-300 mb-2">
                                            <?= h($config['label']) ?>
                                        </label>

                                        <?php if ($config['type'] === 'textarea'): ?>
                                            <textarea
                                                name="fr_<?= h($field) ?>"
                                                rows="5"
                                                class="w-full rounded-2xl bg-slate-900 border border-slate-700 px-4 py-3 outline-none focus:border-violet-500 resize-y"
                                            ><?= h(fieldValue($contenuFr, $field)) ?></textarea>
                                        <?php else: ?>
                                            <input
                                                type="text"
                                                name="fr_<?= h($field) ?>"
                                                value="<?= h(fieldValue($contenuFr, $field)) ?>"
                                                class="w-full rounded-2xl bg-slate-900 border border-slate-700 px-4 py-3 outline-none focus:border-violet-500"
                                            >
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="px-5 py-3 rounded-2xl bg-sky-500 hover:bg-sky-600 transition font-semibold">
                            <?= $mode === 'edit' ? 'Guardar cambios' : 'Crear capítulo' ?>
                        </button>

                        <a href="admin_chapitres.php" class="px-5 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 transition font-semibold">
                            Reset
                        </a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</body>
</html>