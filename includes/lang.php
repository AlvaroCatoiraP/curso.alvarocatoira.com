<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'fr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'es';

$translations = [
    'es' => [
        'dashboard' => 'Dashboard',
        'welcome' => 'Bienvenido',
        'personal_space' => 'Tu espacio personal del curso Python.',
        'logout' => 'Cerrar sesión',
        'view_course' => 'Ver el curso',
        'view_course_desc' => 'Acceder a todos los capítulos del curso de Python.',
        'practices' => 'Prácticas',
        'practices_desc' => 'Resolver ejercicios por capítulos con soluciones.',
        'progress' => 'Progreso',
        'progress_desc' => 'Aquí mostraremos pronto tu progreso guardado en MySQL.',
        'summary' => 'Resumen',
        'connected_user' => 'Usuario conectado',
        'course' => 'Curso',
        'python_basic' => 'Python básico',
        'status' => 'Estado',
        'active_account' => 'Cuenta activa',
        'language' => 'Idioma',

        'course_complete' => 'Curso completo',
        'learn_python_zero' => 'Aprender Python desde cero',
        'course_intro_desc' => 'Curso estructurado por capítulos con ejemplos claros y progresivos.',
        'go_practice' => 'Ir a prácticas',
        'back_dashboard' => 'Dashboard',

        'intro' => 'Introducción',
        'installation' => 'Instalación',
        'hello_world' => 'Hola mundo',
        'variables_types' => 'Variables y tipos',
        'input_output' => 'Input y output',
        'operators' => 'Operadores',
        'conditions' => 'Condiciones',
        'loops' => 'Bucles',
        'lists' => 'Listas',
        'tuples_dicts' => 'Tuplas y diccionarios',
        'functions' => 'Funciones',
        'strings' => 'Strings',
        'text_files' => 'Archivos de texto',
        'json_intro' => 'JSON',
        'errors_exceptions' => 'Errores y excepciones',
        'modules' => 'Módulos',
        'oop' => 'Programación orientada a objetos',
        'mini_project' => 'Mini proyecto',

        'intro_text' => 'Python es un lenguaje fácil de leer, potente y muy utilizado. Sirve para automatización, desarrollo web, inteligencia artificial, scripts, ciencia de datos y mucho más.',
        'installation_text' => 'Instala Python y comprueba que funciona correctamente en la terminal.',
        'hello_world_text' => 'El primer programa tradicional muestra un mensaje por pantalla.',
        'variables_text' => 'Las variables permiten guardar datos. Python detecta el tipo automáticamente.',
        'input_output_text' => 'Con input() pides datos al usuario y con print() los muestras.',
        'loops_for' => 'For',
        'loops_while' => 'While'
    ],
    'fr' => [
        'dashboard' => 'Tableau de bord',
        'welcome' => 'Bienvenue',
        'personal_space' => 'Votre espace personnel du cours Python.',
        'logout' => 'Se déconnecter',
        'view_course' => 'Voir le cours',
        'view_course_desc' => 'Accéder à tous les chapitres du cours Python.',
        'practices' => 'Exercices',
        'practices_desc' => 'Résoudre des exercices par chapitres avec solutions.',
        'progress' => 'Progression',
        'progress_desc' => 'Ici, nous afficherons bientôt votre progression enregistrée dans MySQL.',
        'summary' => 'Résumé',
        'connected_user' => 'Utilisateur connecté',
        'course' => 'Cours',
        'python_basic' => 'Python débutant',
        'status' => 'Statut',
        'active_account' => 'Compte actif',
        'language' => 'Langue',

        'course_complete' => 'Cours complet',
        'learn_python_zero' => 'Apprendre Python depuis zéro',
        'course_intro_desc' => 'Cours structuré par chapitres avec des exemples clairs et progressifs.',
        'go_practice' => 'Aller aux exercices',
        'back_dashboard' => 'Tableau de bord',

        'intro' => 'Introduction',
        'installation' => 'Installation',
        'hello_world' => 'Bonjour le monde',
        'variables_types' => 'Variables et types',
        'input_output' => 'Entrée et sortie',
        'operators' => 'Opérateurs',
        'conditions' => 'Conditions',
        'loops' => 'Boucles',
        'lists' => 'Listes',
        'tuples_dicts' => 'Tuples et dictionnaires',
        'functions' => 'Fonctions',
        'strings' => 'Chaînes de caractères',
        'text_files' => 'Fichiers texte',
        'json_intro' => 'JSON',
        'errors_exceptions' => 'Erreurs et exceptions',
        'modules' => 'Modules',
        'oop' => 'Programmation orientée objet',
        'mini_project' => 'Mini projet',

        'intro_text' => 'Python est un langage facile à lire, puissant et très utilisé. Il sert à l’automatisation, au développement web, à l’intelligence artificielle, aux scripts, à la science des données et bien plus encore.',
        'installation_text' => 'Installez Python et vérifiez qu’il fonctionne correctement dans le terminal.',
        'hello_world_text' => 'Le premier programme classique affiche un message à l’écran.',
        'variables_text' => 'Les variables permettent de stocker des données. Python détecte automatiquement le type.',
        'input_output_text' => 'Avec input(), vous demandez des données à l’utilisateur et avec print(), vous les affichez.',
        'loops_for' => 'For',
        'loops_while' => 'While'
    ]
];

function t(string $key): string {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
?>