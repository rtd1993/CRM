<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$base_dir = '/var/www/CRM/local_drive/';
$path = $_POST['path'] ?? '';

// Decodifica il percorso
$full_path = realpath($base_dir . $path);

// Verifica sicurezza del percorso
if (!$full_path || strpos($full_path, realpath($base_dir)) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Percorso non valido']);
    exit;
}

// Verifica che il file/cartella esista
if (!file_exists($full_path)) {
    echo json_encode(['success' => false, 'message' => 'File o cartella non trovato']);
    exit;
}

$is_directory = is_dir($full_path);
$filename = basename($full_path);

// Funzione ricorsiva per eliminare cartelle
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    return rmdir($dir);
}

try {
    if ($is_directory) {
        // Elimina cartella e tutto il contenuto
        $success = deleteDirectory($full_path);
        $message = $success ? "Cartella '{$filename}' eliminata con successo" : "Errore durante l'eliminazione della cartella";
    } else {
        // Elimina file singolo
        $success = unlink($full_path);
        $message = $success ? "File '{$filename}' eliminato con successo" : "Errore durante l'eliminazione del file";
    }
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'type' => $is_directory ? 'folder' : 'file',
            'name' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $message]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
}
?>
