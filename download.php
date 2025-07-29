<?php
// Pulisci il buffer di output per evitare problemi con il download
ob_clean();

require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = __DIR__ . '/local_drive/';
$path = $_GET['path'] ?? '';

// Decodifica il percorso URL-encoded
$path = urldecode($path);

// Debug: verifica il percorso ricevuto
error_log("Download richiesto per: " . $path);

// Verifica e ottieni il percorso completo
$full_path = realpath($base_dir . $path);

// Debug: verifica il percorso risolto
error_log("Percorso risolto: " . ($full_path ?: 'FAILED'));

// Verifica sicurezza del percorso
if (!$full_path || strpos($full_path, realpath($base_dir)) !== 0) {
    http_response_code(403);
    die('Accesso negato al percorso: ' . htmlspecialchars($path));
}

// Verifica che il file esista e non sia una directory
if (!file_exists($full_path) || is_dir($full_path)) {
    http_response_code(404);
    die('File non trovato: ' . htmlspecialchars($path));
}

// Ottieni informazioni sul file
$filename = basename($full_path);
$filesize = filesize($full_path);

// Determina il MIME type in base all'estensione
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$mime_types = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'txt' => 'text/plain',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'zip' => 'application/zip',
    'rar' => 'application/x-rar-compressed',
    '7z' => 'application/x-7z-compressed'
];

$mime_type = $mime_types[$extension] ?? 'application/octet-stream';

// Pulisci qualsiasi output precedente
if (ob_get_length()) {
    ob_end_clean();
}

// Imposta gli headers per il download forzato
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . $filesize);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: private, no-transform, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Disabilita la compressione per evitare problemi
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

// Leggi e invia il file a blocchi per gestire file grandi
$handle = fopen($full_path, 'rb');
if ($handle === false) {
    http_response_code(500);
    die('Errore nella lettura del file');
}

// Invia il file a blocchi di 8KB
while (!feof($handle)) {
    echo fread($handle, 8192);
    flush();
}

fclose($handle);
exit;
?>
