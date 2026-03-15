<?php
$host = 'localhost';
$dbname = 'curso_python';
$user = 'curso_user';
$pass = 'Gaetan21.6501.494.2822.Brakel';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
