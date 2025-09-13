<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clienti.php');
    exit;
}

// Verifica parametri
$cliente_id = $_POST['cliente_id'] ?? null;
$conferma = $_POST['conferma'] ?? null;

if (!$cliente_id || $conferma !== '1') {
    header('Location: clienti.php?error=parametri_invalidi');
    exit;
}

try {
    // Ottieni informazioni del cliente prima dell'eliminazione
    $stmt = $pdo->prepare("SELECT `Cognome_Ragione_sociale`, `Nome`, `Codice_fiscale` FROM clienti WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        throw new Exception("Cliente non trovato");
    }
    
    $nome_cliente = $cliente['Cognome_Ragione_sociale'];
    $codice_fiscale = $cliente['Codice_fiscale'];
    
    // Elimina il cliente dal database
    $stmt = $pdo->prepare("DELETE FROM clienti WHERE id = ?");
    $result = $stmt->execute([$cliente_id]);
    
    if (!$result) {
        throw new Exception("Errore durante l'eliminazione dal database");
    }
    
    // Gestione cartella cliente con nuovo formato id_cognome.nome
    // Crea nome cartella con nuovo formato
    $cliente_folder = $cliente_id . '_' . 
                     strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Cognome_Ragione_sociale']));
    
    // Aggiungi il nome se presente
    if (!empty($cliente['Nome'])) {
        $nome_clean = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Nome']));
        $cliente_folder .= '.' . $nome_clean;
    }
    
    $cartella_cliente = '/var/www/CRM/local_drive/' . $cliente_folder;
    $cartella_ex_clienti = '/var/www/CRM/local_drive/ASContabilmente/Ex_clienti';
    
    // Crea la cartella degli ex clienti se non esiste
    if (!is_dir($cartella_ex_clienti)) {
        mkdir($cartella_ex_clienti, 0755, true);
    }
    
    // Se la cartella del cliente esiste, la zippa e la sposta
    if (is_dir($cartella_cliente)) {
        $nome_zip = $cliente_folder . '_eliminato_' . date('Y-m-d_H-i-s') . '.zip';
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
                function rimuoviCartella($dir) {
                    if (!is_dir($dir)) return false;
                    
                    $files = array_diff(scandir($dir), array('.', '..'));
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? rimuoviCartella($path) : unlink($path);
                    }
                    return rmdir($dir);
                }
                
                rimuoviCartella($cartella_cliente);
                
                // Log dell'archiviazione
                error_log("Cartella cliente archiviata: $nome_zip - Cliente: $nome_cliente (ID: $cliente_id)");
                
            } else {
                error_log("Impossibile creare ZIP per cliente ID $cliente_id: errore apertura file");
            }
            
        } catch (Exception $zip_error) {
            error_log("Errore durante l'archiviazione cartella cliente ID $cliente_id: " . $zip_error->getMessage());
            // Non bloccare l'eliminazione del cliente se l'archiviazione fallisce
        }
    }
    
    // Log dell'operazione
    error_log("Cliente eliminato - ID: $cliente_id, Nome: $nome_cliente, Codice Fiscale: $codice_fiscale, Utente: " . $_SESSION['username']);
    
    // Redirect con messaggio di successo
    header('Location: clienti.php?success=eliminato&nome=' . urlencode($nome_cliente));
    exit;
    
} catch (Exception $e) {
    error_log("Errore eliminazione cliente ID $cliente_id: " . $e->getMessage());
    header('Location: clienti.php?error=eliminazione_fallita&messaggio=' . urlencode($e->getMessage()));
    exit;
}
?>
