<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$client_ids = $_POST['client_ids'] ?? [];

if (empty($client_ids) || !is_array($client_ids)) {
    echo json_encode(['success' => false, 'message' => 'Nessun cliente selezionato']);
    exit;
}

// Sanifica gli ID clienti
$client_ids = array_filter(array_map('intval', $client_ids));

if (empty($client_ids)) {
    echo json_encode(['success' => false, 'message' => 'ID clienti non validi']);
    exit;
}

$deleted_count = 0;
$errors = [];
$deleted_clients = [];

try {
    // Avvia transazione
    $pdo->beginTransaction();
    
    foreach ($client_ids as $cliente_id) {
        try {
            // Ottieni informazioni del cliente prima dell'eliminazione
            $stmt = $pdo->prepare("SELECT `Cognome_Ragione_sociale`, `Codice_fiscale` FROM clienti WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                $errors[] = "Cliente con ID $cliente_id non trovato";
                continue;
            }
            
            $nome_cliente = $cliente['Cognome_Ragione_sociale'];
            $codice_fiscale = $cliente['Codice_fiscale'];
            
            // Elimina il cliente dal database
            $stmt = $pdo->prepare("DELETE FROM clienti WHERE id = ?");
            $result = $stmt->execute([$cliente_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $deleted_count++;
                $deleted_clients[] = [
                    'id' => $cliente_id,
                    'nome' => $nome_cliente,
                    'codice_fiscale' => $codice_fiscale
                ];
                
                // Gestione cartella cliente
                $codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $codice_fiscale);
                $cartella_cliente = '/var/www/CRM/local_drive/' . $codice_fiscale_clean;
                $cartella_ex_clienti = '/var/www/CRM/local_drive/ASContabilmente/Ex_clienti';
                
                // Crea la cartella degli ex clienti se non esiste
                if (!is_dir($cartella_ex_clienti)) {
                    mkdir($cartella_ex_clienti, 0755, true);
                }
                
                // Se la cartella del cliente esiste, la zippa e la sposta
                if (is_dir($cartella_cliente)) {
                    $nome_zip = $codice_fiscale_clean . '_' . date('Y-m-d_H-i-s') . '.zip';
                    $percorso_zip = $cartella_ex_clienti . '/' . $nome_zip;
                    
                    try {
                        // Crea lo ZIP
                        $zip = new ZipArchive();
                        if ($zip->open($percorso_zip, ZipArchive::CREATE) === TRUE) {
                            
                            // Funzione ricorsiva per aggiungere tutti i file e sottocartelle
                            $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($cartella_cliente, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::SELF_FIRST
                            );
                            
                            foreach ($iterator as $file) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr($filePath, strlen($cartella_cliente) + 1);
                                
                                if ($file->isDir()) {
                                    $zip->addEmptyDir($relativePath);
                                } else {
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                            
                            $zip->close();
                            
                            // Rimuovi la cartella originale dopo aver creato lo ZIP
                            function rimuoviCartellaBulk($dir) {
                                if (!is_dir($dir)) return false;
                                
                                $files = array_diff(scandir($dir), array('.', '..'));
                                foreach ($files as $file) {
                                    $path = $dir . '/' . $file;
                                    is_dir($path) ? rimuoviCartellaBulk($path) : unlink($path);
                                }
                                return rmdir($dir);
                            }
                            
                            rimuoviCartellaBulk($cartella_cliente);
                            
                            // Log dell'archiviazione
                            error_log("Cartella cliente archiviata (bulk): $nome_zip - Cliente: $nome_cliente (ID: $cliente_id)");
                            
                        } else {
                            error_log("Impossibile creare ZIP per cliente ID $cliente_id (bulk): errore apertura file");
                        }
                        
                    } catch (Exception $zip_error) {
                        error_log("Errore durante l'archiviazione cartella cliente ID $cliente_id (bulk): " . $zip_error->getMessage());
                        // Non bloccare l'eliminazione del cliente se l'archiviazione fallisce
                    }
                }
                
                // Log dell'operazione
                error_log("Cliente eliminato (bulk) - ID: $cliente_id, Nome: $nome_cliente, Codice Fiscale: $codice_fiscale, Utente: " . $_SESSION['username']);
                
            } else {
                $errors[] = "Errore eliminando il cliente: $nome_cliente";
            }
            
        } catch (Exception $e) {
            $errors[] = "Errore con cliente ID $cliente_id: " . $e->getMessage();
            error_log("Errore eliminazione cliente ID $cliente_id: " . $e->getMessage());
        }
    }
    
    // Commit transazione se almeno un cliente è stato eliminato
    if ($deleted_count > 0) {
        $pdo->commit();
        
        $response = [
            'success' => true,
            'deleted' => $deleted_count,
            'total' => count($client_ids),
            'message' => "$deleted_count clienti eliminati con successo",
            'deleted_clients' => $deleted_clients
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['message'] .= " (" . count($errors) . " errori)";
        }
        
        echo json_encode($response);
        
    } else {
        // Rollback se nessun cliente è stato eliminato
        $pdo->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Nessun cliente è stato eliminato',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    // Rollback in caso di errore grave
    $pdo->rollBack();
    
    error_log("Errore grave eliminazione multipla clienti: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante l\'eliminazione multipla: ' . $e->getMessage()
    ]);
}
?>
