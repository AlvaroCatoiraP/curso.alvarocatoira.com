<?php
session_start();

/* =========================
   1. Usuario conectado
========================= */
function utilisateur_connecte() {
    return isset($_SESSION['user_id']);
}

/* =========================
   2. Exigir login
========================= */
function exiger_connexion() {
    if (!utilisateur_connecte()) {
        header('Location: login.php');
        exit;
    }

    // Seguridad extra: estado aprobado
    if (($_SESSION['estado'] ?? '') !== 'approved') {
        session_destroy();
        die("Tu cuenta aún no está aprobada.");
    }
}

/* =========================
   3. Exigir admin
========================= */
function exiger_admin() {
    exiger_connexion();

    if (($_SESSION['rol'] ?? '') !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}

/* =========================
   4. Exigir estudiante
========================= */
function exiger_student() {
    exiger_connexion();

    if (($_SESSION['rol'] ?? '') !== 'student') {
        header('Location: dashboard.php');
        exit;
    }
}