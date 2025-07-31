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

// Crea un nome univoco per l'archivio ZIP
$zip_name = 'download_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.zip';
$zip_path = sys_get_temp_dir() . '/' . $zip_name;

try {
    $zip = new ZipArchive();
    $result = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    if ($result !== TRUE) {
        throw new Exception('Impossibile creare l\'archivio ZIP: ' . $result);
    }
    
    $total_items = 0;
    $errors = [];
    
    for ($i = 0; $i < count($items); $i++) {
        $item_name = $items[$i];
        $item_type = $types[$i] ?? 'file';
        $item_path = $source_dir . '/' . $item_name;
        
        if (!file_exists($item_path)) {
            $errors[] = "Elemento non trovato: $item_name";
            continue;
        }
        
        if ($item_type === 'folder') {
            // Aggiungi cartella ricorsivamente
            $added = addFolderToZip($zip, $item_path, $item_name);
            $total_items += $added;
        } else {
            // Aggiungi singolo file
            if ($zip->addFile($item_path, $item_name)) {
                $total_items++;
            } else {
                $errors[] = "Errore aggiungendo il file: $item_name";
            }
        }
    }
    
    $zip->close();
    
    if ($total_items === 0) {
        unlink($zip_path);
        echo json_encode([
            'success' => false, 
            'message' => 'Nessun elemento valido da comprimere',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Invia il file ZIP al browser
    if (file_exists($zip_path)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_name . '"');
        header('Content-Length: ' . filesize($zip_path));
        
        // Invia il file
        readfile($zip_path);
        
        // Cancella il file temporaneo
        unlink($zip_path);
        
        // Log dell'operazione
        error_log("Download ZIP creato: $zip_name, Items: $total_items, Errors: " . count($errors));
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore nella creazione dell\'archivio']);
    }
    
} catch (Exception $e) {
    // Pulizia in caso di errore
    if (file_exists($zip_path)) {
        unlink($zip_path);
    }
    
    error_log("Errore bulk download: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
}

/**
 * Aggiunge una cartella e il suo contenuto all'archivio ZIP ricorsivamente
 */
function addFolderToZip($zip, $folder_path, $zip_folder_name) {
    $added_count = 0;
    
    // Aggiungi la cartella vuota
    $zip->addEmptyDir($zip_folder_name);
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder_path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        $file_path = $file->getRealPath();
        $relative_path = $zip_folder_name . '/' . substr($file_path, strlen($folder_path) + 1);
        
        // Normalizza i separatori di percorso per Windows
        $relative_path = str_replace('\\', '/', $relative_path);
        
        if ($file->isDir()) {
            $zip->addEmptyDir($relative_path);
        } else {
            if ($zip->addFile($file_path, $relative_path)) {
                $added_count++;
            }
        }
    }
    
    return $added_count;
}
?>
