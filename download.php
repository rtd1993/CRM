<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$path = $_GET['path'] ?? '';

// Verifica e ottieni il percorso completo
$full_path = realpath($base_dir . $path);

// Verifica sicurezza del percorso
if (!$full_path || strpos($full_path, realpath($base_dir)) !== 0) {
    http_response_code(403);
    die('Accesso negato');
}

// Verifica che il file esista e non sia una directory
if (!file_exists($full_path) || is_dir($full_path)) {
    http_response_code(404);
    die('File non trovato');
}

// Ottieni informazioni sul file
$filename = basename($full_path);
$filesize = filesize($full_path);
$mime_type = mime_content_type($full_path);

// Se non riesce a determinare il MIME type, usa un default
if (!$mime_type) {
    $mime_type = 'application/octet-stream';
}

// Imposta gli headers per il download
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Leggi e invia il file
readfile($full_path);
exit;
?>
