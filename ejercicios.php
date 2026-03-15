<?php
require_once 'includes/auth.php';
require_once 'includes/lang.php';
require_once 'includes/db.php';

exiger_connexion();

$usuario_id = $_SESSION['user_id'];

$sql_progreso = "SELECT ejercicio_codigo, completado FROM progreso WHERE usuario_id = ?";
$stmt_progreso = $pdo->prepare($sql_progreso);
$stmt_progreso->execute([$usuario_id]);
$progreso_db = $stmt_progreso->fetchAll(PDO::FETCH_KEY_PAIR);

$sql_codigo = "SELECT ejercicio_codigo, contenido FROM codigo_usuario WHERE usuario_id = ?";
$stmt_codigo = $pdo->prepare($sql_codigo);
$stmt_codigo->execute([$usuario_id]);
$codigo_db = $stmt_codigo->fetchAll(PDO::FETCH_KEY_PAIR);

$exercises = [
    [
        'id' => 'ex1',
        'title' => t('hello_world'),
        'desc_es' => "Escribe un programa que muestre el texto 'Hola Python'.",
        'desc_fr' => "Écris un programme qui affiche le texte 'Bonjour Python'.",
        'solution' => 'print("Hola Python")',
        'color' => 'text-sky-300'
    ],
    [
        'id' => 'ex2',
        'title' => t('variables_types'),
        'desc_es' => "Crea una variable llamada nombre y muéstrala por pantalla.",
        'desc_fr' => "Crée une variable appelée nom et affiche-la à l’écran.",
        'solution' => "nombre = \"Alvaro\"\nprint(nombre)",
        'color' => 'text-emerald-300'
    ],
    [
        'id' => 'ex3',
        'title' => t('input_output'),
        'desc_es' => "Pide un nombre al usuario y luego muéstrale un saludo.",
        'desc_fr' => "Demande un nom à l’utilisateur puis affiche un message de bienvenue.",
        'solution' => "nombre = input(\"¿Cómo te llamas? \")\nprint(\"Hola\", nombre)",
        'color' => 'text-violet-300'
    ],
    [
        'id' => 'ex4',
        'title' => t('operators'),
        'desc_es' => "Guarda dos números en variables y muestra su suma.",
        'desc_fr' => "Stocke deux nombres dans des variables et affiche leur somme.",
        'solution' => "a = 5\nb = 7\nprint(a + b)",
        'color' => 'text-yellow-300'
    ],
    [
        'id' => 'ex5',
        'title' => t('conditions'),
        'desc_es' => "Pide una edad y muestra si la persona es mayor o menor de edad.",
        'desc_fr' => "Demande un âge et affiche si la personne est majeure ou mineure.",
        'solution' => "edad = int(input(\"Edad: \"))\nif edad >= 18:\n    print(\"Mayor de edad\")\nelse:\n    print(\"Menor de edad\")",
        'color' => 'text-amber-300'
    ],
    [
        'id' => 'ex6',
        'title' => t('loops'),
        'desc_es' => "Muestra los números del 1 al 10 usando un bucle for.",
        'desc_fr' => "Affiche les nombres de 1 à 10 avec une boucle for.",
        'solution' => "for i in range(1, 11):\n    print(i)",
        'color' => 'text-pink-300'
    ],
    [
        'id' => 'ex7',
        'title' => t('lists'),
        'desc_es' => "Crea una lista con tres frutas y recórrela para mostrarlas.",
        'desc_fr' => "Crée une liste avec trois fruits et parcours-la pour les afficher.",
        'solution' => "frutas = [\"manzana\", \"banana\", \"naranja\"]\nfor fruta in frutas:\n    print(fruta)",
        'color' => 'text-cyan-300'
    ],
    [
        'id' => 'ex8',
        'title' => t('tuples_dicts'),
        'desc_es' => "Crea un diccionario con nombre y edad, y muestra el nombre.",
        'desc_fr' => "Crée un dictionnaire avec nom et âge, puis affiche le nom.",
        'solution' => "usuario = {\"nombre\": \"Alvaro\", \"edad\": 20}\nprint(usuario[\"nombre\"])",
        'color' => 'text-indigo-300'
    ],
    [
        'id' => 'ex9',
        'title' => t('functions'),
        'desc_es' => "Crea una función saludar que reciba un nombre.",
        'desc_fr' => "Crée une fonction saluer qui reçoit un nom.",
        'solution' => "def saludar(nombre):\n    print(\"Hola\", nombre)\n\nsaludar(\"Alvaro\")",
        'color' => 'text-lime-300'
    ],
    [
        'id' => 'ex10',
        'title' => t('text_files'),
        'desc_es' => "Guarda un texto dentro de un archivo llamado mensaje.txt.",
        'desc_fr' => "Enregistre un texte dans un fichier appelé message.txt.",
        'solution' => "with open(\"mensaje.txt\", \"w\", encoding=\"utf-8\") as archivo:\n    archivo.write(\"Hola desde Python\")",
        'color' => 'text-orange-300'
    ],
    [
        'id' => 'ex11',
        'title' => t('json_intro'),
        'desc_es' => "Crea un diccionario y guárdalo en un archivo JSON.",
        'desc_fr' => "Crée un dictionnaire et enregistre-le dans un fichier JSON.",
        'solution' => "import json\n\ndatos = {\"nombre\": \"Alvaro\", \"edad\": 20}\n\nwith open(\"usuario.json\", \"w\", encoding=\"utf-8\") as archivo:\n    json.dump(datos, archivo, indent=4)",
        'color' => 'text-red-300'
    ],
    [
        'id' => 'ex12',
        'title' => t('oop'),
        'desc_es' => "Crea una clase Persona con un atributo nombre y un método saludar.",
        'desc_fr' => "Crée une classe Personne avec un attribut nom et une méthode saluer.",
        'solution' => "class Persona:\n    def __init__(self, nombre):\n        self.nombre = nombre\n\n    def saludar(self):\n        print(f\"Hola, soy {self.nombre}\")\n\np = Persona(\"Alvaro\")\np.saludar()",
        'color' => 'text-fuchsia-300'
    ]
];

$total_exercises = count($exercises);
$completed_count = 0;

foreach ($exercises as $exercise) {
    $exerciseKey = $exercise['id'];
    if (isset($progreso_db[$exerciseKey]) && (int)$progreso_db[$exerciseKey] === 1) {
        $completed_count++;
    }
}

$percentage = $total_exercises > 0 ? round(($completed_count / $total_exercises) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('practices') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100">
    <div class="min-h-screen flex">
        <aside class="w-72 bg-slate-900 border-r border-slate-800 p-6 sticky top-0 h-screen overflow-y-auto hidden md:block">
            <div class="mb-8">
                <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest"><?= t('practices') ?></p>
                <h1 class="text-2xl font-bold mt-2"><?= t('python_basic') ?></h1>
                <p class="text-slate-400 text-sm mt-3">
                    <?= $lang === 'fr'
                        ? 'Exercices pratiques classés par chapitres.'
                        : 'Ejercicios prácticos organizados por capítulos.' ?>
                </p>
            </div>

            <div class="mb-8 bg-slate-950 border border-slate-800 rounded-2xl p-4">
                <p class="text-sm text-slate-400">
                    <?= $lang === 'fr' ? 'Progression' : 'Progreso' ?>
                </p>
                <div class="mt-3 w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                    <div id="sidebarProgressBar" class="h-full bg-sky-500 rounded-full" style="width: <?= $percentage ?>%"></div>
                </div>
                <p id="sidebarProgressText" class="text-sky-300 text-sm mt-3 font-semibold">
                    <?= $completed_count ?> / <?= $total_exercises ?> <?= $lang === 'fr' ? 'exercices terminés' : 'ejercicios completados' ?>
                </p>
            </div>

            <nav class="space-y-2 text-sm">
                <?php foreach ($exercises as $index => $exercise): ?>
                    <a href="#<?= htmlspecialchars($exercise['id']) ?>" class="block rounded-xl px-4 py-3 hover:bg-slate-800 transition">
                        <?= $index + 1 ?>. <?= htmlspecialchars($exercise['title']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur sticky top-0 z-10">
                <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sky-400 text-sm font-semibold uppercase tracking-widest"><?= t('practices') ?></p>
                        <h1 class="text-3xl md:text-4xl font-bold mt-2">
                            <?= $lang === 'fr' ? 'Exercices du cours' : 'Ejercicios del curso' ?>
                        </h1>
                        <p class="text-slate-400 mt-3 max-w-3xl">
                            <?= $lang === 'fr'
                                ? 'Chaque exercice contient un énoncé, une zone de code et une solution.'
                                : 'Cada ejercicio incluye enunciado, zona de código y solución.' ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 items-center">
                        <div class="bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 flex items-center gap-3">
                            <span class="text-slate-300 text-sm"><?= t('language') ?>:</span>
                            <a href="?lang=es" class="text-sm px-3 py-1 rounded-lg <?= $lang === 'es' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">ES</a>
                            <a href="?lang=fr" class="text-sm px-3 py-1 rounded-lg <?= $lang === 'fr' ? 'bg-sky-500 text-white' : 'bg-slate-800 text-slate-300' ?>">FR</a>
                        </div>

                        <a href="curso.php" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 rounded-xl font-semibold">
                            <?= t('view_course') ?>
                        </a>

                        <a href="dashboard.php" class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold">
                            <?= t('dashboard') ?>
                        </a>
                    </div>
                </div>
            </header>

            <div class="max-w-6xl mx-auto px-6 py-10">
                <div class="mb-8 bg-slate-900 border border-slate-800 rounded-2xl p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold">
                                <?= $lang === 'fr' ? 'Votre progression' : 'Tu progreso' ?>
                            </h2>
                            <p id="mainProgressText" class="text-slate-400 mt-2">
                                <?= $completed_count ?> / <?= $total_exercises ?> <?= $lang === 'fr' ? 'exercices terminés' : 'ejercicios completados' ?>
                            </p>
                        </div>
                        <div class="min-w-[180px] text-right">
                            <p id="mainProgressPercent" class="text-3xl font-bold text-sky-400"><?= $percentage ?>%</p>
                        </div>
                    </div>
                    <div class="mt-4 w-full h-4 bg-slate-800 rounded-full overflow-hidden">
                        <div id="mainProgressBar" class="h-full bg-sky-500 rounded-full" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <?php foreach ($exercises as $index => $exercise): ?>
                        <?php
                        $exerciseKey = $exercise['id'];
                        $isCompleted = isset($progreso_db[$exerciseKey]) && (int)$progreso_db[$exerciseKey] === 1;
                        $existingCode = $codigo_db[$exerciseKey] ?? '';
                        ?>
                        <section id="<?= htmlspecialchars($exercise['id']) ?>" class="exercise-card bg-slate-900 border border-slate-800 rounded-2xl p-6" data-exercise="<?= htmlspecialchars($exerciseKey) ?>">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-widest text-sky-400 font-semibold">
                                        <?= $lang === 'fr' ? 'Exercice' : 'Ejercicio' ?> <?= $index + 1 ?>
                                    </p>
                                    <h2 class="text-xl font-bold mt-2"><?= htmlspecialchars($exercise['title']) ?></h2>
                                </div>

                                <span class="exercise-status text-xs px-3 py-1 rounded-full border <?= $isCompleted ? 'border-emerald-700 bg-emerald-500/10 text-emerald-300' : 'border-slate-700 text-slate-300' ?>">
                                    <?= $isCompleted
                                        ? ($lang === 'fr' ? 'Terminé' : 'Completado')
                                        : ($lang === 'fr' ? 'En attente' : 'Pendiente') ?>
                                </span>
                            </div>

                            <p class="text-slate-300 mt-4">
                                <?= $lang === 'fr' ? $exercise['desc_fr'] : $exercise['desc_es'] ?>
                            </p>

                            <textarea
                                data-exercise="<?= htmlspecialchars($exerciseKey) ?>"
                                class="code-editor w-full mt-5 min-h-[150px] rounded-2xl bg-slate-950 border border-slate-700 p-4 font-mono text-sm text-slate-100 focus:outline-none focus:border-sky-500"
                                placeholder="<?= $lang === 'fr' ? 'Écris ton code ici...' : 'Escribe tu código aquí...' ?>"
                            ><?= htmlspecialchars($existingCode) ?></textarea>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    onclick="toggleSolution('sol<?= $index + 1 ?>', this)"
                                    class="bg-sky-500 hover:bg-sky-600 px-4 py-2 rounded-xl font-semibold"
                                >
                                    <?= $lang === 'fr' ? 'Voir la solution' : 'Ver solución' ?>
                                </button>

                                <button
                                    type="button"
                                    class="progress-btn px-4 py-2 rounded-xl font-semibold <?= $isCompleted ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-slate-700 hover:bg-slate-600' ?>"
                                    data-exercise="<?= htmlspecialchars($exerciseKey) ?>"
                                    data-completed="<?= $isCompleted ? '1' : '0' ?>"
                                >
                                    <?= $isCompleted
                                        ? ($lang === 'fr' ? 'Marquer comme non terminé' : 'Marcar como no completado')
                                        : ($lang === 'fr' ? 'Marquer comme terminé' : 'Marcar como completado') ?>
                                </button>
                            </div>

                            <div id="sol<?= $index + 1 ?>" class="hidden mt-5 bg-slate-950 border border-slate-800 rounded-2xl p-4">
                                <pre class="overflow-x-auto <?= $exercise['color'] ?>"><code><?= htmlspecialchars($exercise['solution']) ?></code></pre>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const totalExercises = <?= (int)$total_exercises ?>;

        function toggleSolution(id, button) {
            const element = document.getElementById(id);
            element.classList.toggle('hidden');

            const isHidden = element.classList.contains('hidden');
            const lang = document.documentElement.lang;

            if (lang === 'fr') {
                button.textContent = isHidden ? 'Voir la solution' : 'Cacher la solution';
            } else {
                button.textContent = isHidden ? 'Ver solución' : 'Ocultar solución';
            }
        }

        function updateProgressDisplay() {
            const buttons = document.querySelectorAll('.progress-btn');
            let completed = 0;

            buttons.forEach(button => {
                if (button.dataset.completed === '1') {
                    completed++;
                }
            });

            const percent = totalExercises > 0 ? Math.round((completed / totalExercises) * 100) : 0;
            const lang = document.documentElement.lang;

            const mainText = document.getElementById('mainProgressText');
            const mainPercent = document.getElementById('mainProgressPercent');
            const mainBar = document.getElementById('mainProgressBar');
            const sidebarText = document.getElementById('sidebarProgressText');
            const sidebarBar = document.getElementById('sidebarProgressBar');

            if (lang === 'fr') {
                mainText.textContent = `${completed} / ${totalExercises} exercices terminés`;
                sidebarText.textContent = `${completed} / ${totalExercises} exercices terminés`;
            } else {
                mainText.textContent = `${completed} / ${totalExercises} ejercicios completados`;
                sidebarText.textContent = `${completed} / ${totalExercises} ejercicios completados`;
            }

            mainPercent.textContent = `${percent}%`;
            mainBar.style.width = `${percent}%`;
            sidebarBar.style.width = `${percent}%`;
        }

        document.querySelectorAll('.code-editor').forEach(textarea => {
            let timeout = null;

            textarea.addEventListener('input', function () {
                clearTimeout(timeout);

                timeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('ejercicio_codigo', this.dataset.exercise);
                    formData.append('contenido', this.value);

                    fetch('guardar_codigo.php', {
                        method: 'POST',
                        body: formData
                    }).catch(error => console.error(error));
                }, 500);
            });
        });

        document.querySelectorAll('.progress-btn').forEach(button => {
            button.addEventListener('click', function () {
                const exercise = this.dataset.exercise;
                const current = this.dataset.completed === '1' ? 1 : 0;
                const next = current === 1 ? 0 : 1;
                const lang = document.documentElement.lang;
                const card = this.closest('.exercise-card');
                const statusBadge = card.querySelector('.exercise-status');

                const formData = new FormData();
                formData.append('ejercicio_codigo', exercise);
                formData.append('completado', next);

                fetch('guardar_progreso.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.dataset.completed = String(next);

                        if (next === 1) {
                            this.classList.remove('bg-slate-700', 'hover:bg-slate-600');
                            this.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
                            this.textContent = lang === 'fr'
                                ? 'Marquer comme non terminé'
                                : 'Marcar como no completado';

                            statusBadge.textContent = lang === 'fr' ? 'Terminé' : 'Completado';
                            statusBadge.className = 'exercise-status text-xs px-3 py-1 rounded-full border border-emerald-700 bg-emerald-500/10 text-emerald-300';
                        } else {
                            this.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                            this.classList.add('bg-slate-700', 'hover:bg-slate-600');
                            this.textContent = lang === 'fr'
                                ? 'Marquer comme terminé'
                                : 'Marcar como completado';

                            statusBadge.textContent = lang === 'fr' ? 'En attente' : 'Pendiente';
                            statusBadge.className = 'exercise-status text-xs px-3 py-1 rounded-full border border-slate-700 text-slate-300';
                        }

                        updateProgressDisplay();
                    }
                })
                .catch(error => console.error(error));
            });
        });

        updateProgressDisplay();
    </script>
</body>
</html>