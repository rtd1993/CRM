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
    $stmt = $pdo->prepare("SELECT `Cognome_Ragione_sociale`, `Codice_fiscale` FROM clienti WHERE id = ?");
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
    
    // Log dell'operazione
    error_log("Cliente eliminato - ID: $cliente_id, Nome: $nome_cliente, Codice Fiscale: $codice_fiscale, Utente: " . $_SESSION['username']);
    
    // Nota: La cartella del cliente non viene eliminata automaticamente per sicurezza
    // L'amministratore può eliminarla manualmente se necessario
    
    // Redirect con messaggio di successo
    header('Location: clienti.php?success=eliminato&nome=' . urlencode($nome_cliente));
    exit;
    
} catch (Exception $e) {
    error_log("Errore eliminazione cliente ID $cliente_id: " . $e->getMessage());
    header('Location: clienti.php?error=eliminazione_fallita&messaggio=' . urlencode($e->getMessage()));
    exit;
}
?>
