<?php
/**
 * Adaptador de autenticación.
 * Caso 1: si tu sitio ya tiene login, reemplaza la lógica por tu propia sesión.
 * Caso 2: si no tienes login, el módulo usa un nombre temporal enviado desde index.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function usuarioAutenticado(): bool
{
    return isset($_SESSION['alumno_nombre']) && trim((string) $_SESSION['alumno_nombre']) !== '';
}

function obtenerNombreAlumno(): string
{
    return $_SESSION['alumno_nombre'] ?? 'Invitado';
}

function exigirAutenticacion(): void
{
    if (!usuarioAutenticado()) {
        header('Location: index.php');
        exit;
    }
}
