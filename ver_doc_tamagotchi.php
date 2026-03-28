<?php
require_once 'includes/auth.php';

exiger_connexion();

$archivo = __DIR__ . '/private_docs/tamagotchi_proyecto_completo.pdf';

if (!file_exists($archivo)) {
    die("Documento no encontrado.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="tamagotchi_guia.pdf"');
header('Content-Length: ' . filesize($archivo));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');

readfile($archivo);
exit;