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
            $stmt = $pdo->prepare("SELECT `Cognome/Ragione sociale`, `Codice fiscale` FROM clienti WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                $errors[] = "Cliente con ID $cliente_id non trovato";
                continue;
            }
            
            $nome_cliente = $cliente['Cognome/Ragione sociale'];
            $codice_fiscale = $cliente['Codice fiscale'];
            
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
