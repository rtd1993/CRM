<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

$codice_fiscale = $_POST['codice_fiscale'] ?? '';
$cliente_id = intval($_POST['cliente_id'] ?? 0);

if (empty($codice_fiscale)) {
    echo json_encode(['success' => false, 'message' => 'Codice fiscale non fornito']);
    exit;
}

if ($cliente_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID cliente non valido']);
    exit;
}

// Sanitizza il codice fiscale per nome cartella
$codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $codice_fiscale);

if (empty($codice_fiscale_clean)) {
    echo json_encode(['success' => false, 'message' => 'Codice fiscale non valido per nome cartella']);
    exit;
}

$base_path = '/var/www/CRM/local_drive';
$cartella_path = $base_path . '/' . $codice_fiscale_clean;

try {
    // Verifica che la directory base esista
    if (!is_dir($base_path)) {
        echo json_encode(['success' => false, 'message' => 'Directory base local_drive non trovata']);
        exit;
    }

    // Verifica se la cartella esiste già
    if (is_dir($cartella_path)) {
        echo json_encode(['success' => false, 'message' => 'La cartella esiste già']);
        exit;
    }

    // Crea la cartella
    if (mkdir($cartella_path, 0755, true)) {
        // Crea file di benvenuto nella cartella
        $welcome_file = $cartella_path . '/README.txt';
        $welcome_content = "Cartella cliente: " . $codice_fiscale . "\n";
        $welcome_content .= "Creata il: " . date('d/m/Y H:i:s') . "\n";
        $welcome_content .= "Cliente ID: " . $cliente_id . "\n\n";
        $welcome_content .= "Questa cartella contiene i file relativi al cliente.\n";
        
        file_put_contents($welcome_file, $welcome_content);

        // Log dell'operazione
        error_log("Cartella creata per cliente $cliente_id: $cartella_path");

        echo json_encode([
            'success' => true, 
            'message' => 'Cartella creata con successo',
            'path' => $cartella_path
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Impossibile creare la cartella. Verificare i permessi.']);
    }

} catch (Exception $e) {
    error_log("Errore creazione cartella per cliente $cliente_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore interno: ' . $e->getMessage()]);
}
?>
