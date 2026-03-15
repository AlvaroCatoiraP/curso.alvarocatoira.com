<?php
session_start();

function utilisateur_connecte() {
    return isset($_SESSION['user_id']);
}

function exiger_connexion() {
    if (!utilisateur_connecte()) {
        header('Location: login.php');
        exit;
    }
}

function exiger_admin() {
    exiger_connexion();

    if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        header('Location: dashboard.php');
        exit;
    }
}