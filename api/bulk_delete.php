<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$current_path = $_POST['current_path'] ?? '';
$items = $_POST['items'] ?? [];
$types = $_POST['types'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Nessun elemento selezionato']);
    exit;
}

$base_dir = __DIR__ . '/../local_drive/';
$source_dir = realpath($base_dir . $current_path);

// Verifica sicurezza del percorso
if (!$source_dir || strpos($source_dir, realpath($base_dir)) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Percorso non valido']);
    exit;
}

$deleted_count = 0;
$errors = [];

try {
    for ($i = 0; $i < count($items); $i++) {
        $item_name = $items[$i];
        $item_type = $types[$i] ?? 'file';
        $item_path = $source_dir . '/' . $item_name;
        
        if (!file_exists($item_path)) {
            $errors[] = "Elemento non trovato: $item_name";
            continue;
        }
        
        // Verifica che l'elemento sia all'interno della directory consentita
        $real_item_path = realpath($item_path);
        if (!$real_item_path || strpos($real_item_path, realpath($base_dir)) !== 0) {
            $errors[] = "Percorso non valido per: $item_name";
            continue;
        }
        
        try {
            if ($item_type === 'folder') {
                // Elimina cartella ricorsivamente
                if (deleteDirectory($item_path)) {
                    $deleted_count++;
                    error_log("Cartella eliminata: $item_path");
                } else {
                    $errors[] = "Errore eliminando la cartella: $item_name";
                }
            } else {
                // Elimina singolo file
                if (unlink($item_path)) {
                    $deleted_count++;
                    error_log("File eliminato: $item_path");
                } else {
                    $errors[] = "Errore eliminando il file: $item_name";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Errore con $item_name: " . $e->getMessage();
            error_log("Errore elimina $item_name: " . $e->getMessage());
        }
    }
    
    $response = [
        'success' => true,
        'deleted' => $deleted_count,
        'total' => count($items),
        'message' => "$deleted_count elementi eliminati"
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] .= " (" . count($errors) . " errori)";
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Errore bulk delete: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
    ]);
}

/**
 * Elimina una cartella e tutto il suo contenuto ricorsivamente
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return false;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            if (!rmdir($file->getRealPath())) {
                return false;
            }
        } else {
            if (!unlink($file->getRealPath())) {
                return false;
            }
        }
    }
    
    return rmdir($dir);
}
?>
