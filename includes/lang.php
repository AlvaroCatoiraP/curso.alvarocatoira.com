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
        'loops_while' => 'While',

        'site_title' => 'Curso Python',
        'login' => 'Iniciar sesión',
        'register' => 'Registrarse',
        'hero_badge' => 'Aprende programando',
        'hero_title' => 'Aprende Python paso a paso con una plataforma clara y práctica',
        'hero_desc' => 'Sigue el curso, resuelve ejercicios directamente en la web, realiza quizzes con temporizador y entrega tus proyectos en un entorno pensado para avanzar de forma progresiva.',
        'start_now' => 'Empezar ahora',
        'already_account' => 'Ya tengo cuenta',

        'chapters_count' => 'Capítulos del curso',
        'exercises_count' => 'Ejercicios prácticos',
        'quizzes_count' => 'Quizzes por capítulo',

        'student_dashboard' => 'Dashboard del alumno',
        'course_progress' => 'Progreso del curso',
        'general_progress' => 'Progreso general',
        'exercises_label' => 'Ejercicios',
        'quizzes_label' => 'Quizzes',
        'average_grade' => 'Nota media',
        'next_goal' => 'Próximo objetivo',
        'next_goal_text' => 'Completar el capítulo 10 y enviar la siguiente entrega',

        'features_badge' => 'Qué ofrece la plataforma',
        'features_title' => 'Todo en un solo lugar',
        'features_desc' => 'Una experiencia clara para estudiar, practicar, evaluarse y seguir el progreso.',
        'feature_course' => 'Curso guiado',
        'feature_course_desc' => 'Contenido organizado por capítulos para aprender de forma progresiva.',
        'feature_exercises' => 'Ejercicios prácticos',
        'feature_exercises_desc' => 'Resuelve ejercicios directamente desde la plataforma y guarda tu progreso.',
        'feature_quizzes' => 'Quizzes automáticos',
        'feature_quizzes_desc' => 'Evalúate con temporizador, corrección automática y nota final inmediata.',
        'feature_projects' => 'Proyectos y deberes',
        'feature_projects_desc' => 'Entrega archivos, sigue el calendario y consolida lo aprendido.',

        'cta_title' => 'Empieza hoy tu progreso en Python',
        'cta_desc' => 'Crea tu cuenta, accede al contenido del curso y empieza a practicar desde el primer día.',
        'create_account' => 'Crear cuenta',
        'account_status' => 'Estado'
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
        'loops_while' => 'While',

        'site_title' => 'Cours Python',
        'login' => 'Se connecter',
        'register' => "S'inscrire",
        'hero_badge' => 'Apprendre en pratiquant',
        'hero_title' => 'Apprends Python pas à pas avec une plateforme claire et pratique',
        'hero_desc' => 'Suis le cours, résous des exercices directement en ligne, réalise des quiz avec minuteur et rends tes projets dans un environnement pensé pour progresser étape par étape.',
        'start_now' => 'Commencer maintenant',
        'already_account' => "J'ai déjà un compte",

        'chapters_count' => 'Chapitres du cours',
        'exercises_count' => 'Exercices pratiques',
        'quizzes_count' => 'Quiz par chapitre',

        'student_dashboard' => "Tableau de bord de l'élève",
        'course_progress' => 'Progression du cours',
        'general_progress' => 'Progression générale',
        'exercises_label' => 'Exercices',
        'quizzes_label' => 'Quiz',
        'average_grade' => 'Note moyenne',
        'next_goal' => 'Prochain objectif',
        'next_goal_text' => 'Terminer le chapitre 10 et envoyer le prochain devoir',

        'features_badge' => 'Ce que propose la plateforme',
        'features_title' => 'Tout en un seul endroit',
        'features_desc' => 'Une expérience claire pour étudier, pratiquer, se tester et suivre sa progression.',
        'feature_course' => 'Cours guidé',
        'feature_course_desc' => 'Contenu organisé par chapitres pour apprendre progressivement.',
        'feature_exercises' => 'Exercices pratiques',
        'feature_exercises_desc' => 'Résous les exercices directement depuis la plateforme et sauvegarde ta progression.',
        'feature_quizzes' => 'Quiz automatiques',
        'feature_quizzes_desc' => 'Évalue-toi avec minuteur, correction automatique et note immédiate.',
        'feature_projects' => 'Projets et devoirs',
        'feature_projects_desc' => 'Dépose tes fichiers, suis le calendrier et consolide tes acquis.',

        'cta_title' => "Commence aujourd'hui ta progression en Python",
        'cta_desc' => 'Crée ton compte, accède au contenu du cours et commence à pratiquer dès le premier jour.',
        'create_account' => 'Créer un compte',
        'account_status' => 'Statut'
    ]
];

function t(string $key): string {
    global $translations, $lang;
    return $translations[$lang][$key] ?? $key;
}
?>