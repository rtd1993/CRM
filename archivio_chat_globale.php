<?php
/**
 * Script per l'archiviazione automatica dei messaggi della chat globale
 * Archivia e rimuove messaggi più vecchi di 60 giorni dalla chat globale
 * 
 * Utilizzo:
 * - Da cron: php /var/www/CRM/archivio_chat_globale.php
 * - Da web: http://tuodominio.com/archivio_chat_globale.php (con autenticazione)
 */

// Se chiamato da web, richiede autenticazione
if (isset($_SERVER['HTTP_HOST'])) {
    require_once __DIR__ . '/includes/auth.php';
    require_login();
    
    // Solo admin e developer possono eseguire l'archiviazione manuale
    if (!in_array($_SESSION['ruolo'], ['admin', 'developer'])) {
        die('Accesso negato: solo admin e developer possono eseguire l\'archiviazione');
    }
    
    // Output HTML per web
    echo "<!DOCTYPE html><html><head><title>Archiviazione Chat Globale</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
    echo ".container{background:white;padding:20px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
    echo ".log{background:#f8f8f8;padding:10px;border-left:4px solid #007cba;margin:10px 0;font-family:monospace;}";
    echo ".success{border-left-color:#28a745;}.error{border-left-color:#dc3545;}.info{border-left-color:#17a2b8;}";
    echo "</style></head><body><div class='container'>";
    echo "<h2>🗄️ Archiviazione Chat Globale</h2>";
    
    $web_output = true;
} else {
    $web_output = false;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Configurazione
$archivio_dir = '/var/www/CRM/local_drive/ASContabilmente/Archivio_chat';
$log_file = '/var/www/CRM/logs/chat_archivio.log';
$giorni_limite = 60;

// Funzione di logging
function log_message($message, $type = 'info') {
    global $log_file, $web_output;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    
    // Scrive nel file di log
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    if ($web_output) {
        $class_map = [
            'success' => 'success',
            'error' => 'error', 
            'info' => 'info'
        ];
        $class = $class_map[$type] ?? 'log';
        echo "<div class='log $class'>[$timestamp] $message</div>";
        flush();
    } else {
        echo "[$timestamp] $message\n";
    }
}

// Crea directory se non esistono
if (!is_dir(dirname($log_file))) {
    mkdir(dirname($log_file), 0755, true);
}

if (!is_dir($archivio_dir)) {
    mkdir($archivio_dir, 0755, true);
}

try {
    log_message("=== INIZIO ARCHIVIAZIONE CHAT GLOBALE ===");
    
    // Calcola la data limite (60 giorni fa)
    $data_limite = date('Y-m-d H:i:s', strtotime("-$giorni_limite days"));
    $mese = date('m', strtotime("-$giorni_limite days"));
    $anno = date('Y', strtotime("-$giorni_limite days"));
    $nome_file = "chat_{$mese}_{$anno}.txt";
    $percorso_archivio = $archivio_dir . '/' . $nome_file;
    
    log_message("Data limite per archiviazione: $data_limite");
    log_message("File di destinazione: $nome_file");
    
    // Conta i messaggi da archiviare
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messaggi WHERE timestamp < ?");
    $stmt->execute([$data_limite]);
    $conta_messaggi = $stmt->fetchColumn();
    
    if ($conta_messaggi == 0) {
        log_message("Nessun messaggio da archiviare", 'info');
        log_message("=== FINE ARCHIVIAZIONE CHAT GLOBALE ===");
        
        if ($web_output) {
            echo "<div class='log info'>✅ Operazione completata: nessun messaggio da archiviare</div>";
            echo "</div></body></html>";
        }
        
        exit(0);
    }
    
    log_message("Trovati $conta_messaggi messaggi da archiviare");
    
    // Crea l'intestazione del file se non esiste
    if (!file_exists($percorso_archivio)) {
        $intestazione = "# ARCHIVIO CHAT GLOBALE - MESE $mese/$anno\n";
        $intestazione .= "# Messaggi archiviati automaticamente il " . date('Y-m-d H:i:s') . "\n";
        $intestazione .= "# Messaggi più vecchi del: $data_limite\n";
        $intestazione .= str_repeat("=", 80) . "\n\n";
        
        file_put_contents($percorso_archivio, $intestazione);
    }
    
    // Recupera i messaggi da archiviare
    $stmt = $pdo->prepare("
        SELECT 
            c.timestamp,
            u.nome,
            c.messaggio
        FROM chat_messaggi c
        JOIN utenti u ON c.utente_id = u.id
        WHERE c.timestamp < ?
        ORDER BY c.timestamp ASC
    ");
    $stmt->execute([$data_limite]);
    $messaggi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatta e salva i messaggi
    $contenuto_archivio = "\n--- BATCH ARCHIVIAZIONE " . date('Y-m-d H:i:s') . " ---\n\n";
    
    foreach ($messaggi as $msg) {
        $contenuto_archivio .= sprintf(
            "[%s] %s: %s\n",
            $msg['timestamp'],
            $msg['nome'],
            $msg['messaggio']
        );
    }
    
    $contenuto_archivio .= "\n" . str_repeat("-", 80) . "\n";
    
    // Scrive nel file di archivio
    file_put_contents($percorso_archivio, $contenuto_archivio, FILE_APPEND | LOCK_EX);
    
    log_message("✅ $conta_messaggi messaggi esportati in: $percorso_archivio", 'success');
    
    // Elimina i messaggi archiviati dal database
    $stmt_delete = $pdo->prepare("DELETE FROM chat_messaggi WHERE timestamp < ?");
    $risultato = $stmt_delete->execute([$data_limite]);
    
    if ($risultato) {
        $messaggi_eliminati = $stmt_delete->rowCount();
        log_message("✅ $messaggi_eliminati messaggi eliminati dal database", 'success');
        
        // Verifica dimensione file archivio
        $dimensione_file = number_format(filesize($percorso_archivio) / 1024, 2);
        log_message("📁 Dimensione file archivio: {$dimensione_file} KB");
        
        // Ottimizza la tabella
        $pdo->exec("OPTIMIZE TABLE chat_messaggi");
        log_message("🔧 Tabella chat_messaggi ottimizzata", 'success');
        
        // Statistiche finali
        $stmt_rimasti = $pdo->query("SELECT COUNT(*) FROM chat_messaggi");
        $messaggi_rimasti = $stmt_rimasti->fetchColumn();
        
        log_message("📊 Riepilogo: $conta_messaggi messaggi archiviati, $messaggi_rimasti messaggi rimasti nel database", 'success');
        
    } else {
        throw new Exception("Errore durante l'eliminazione dei messaggi dal database");
    }
    
    log_message("=== FINE ARCHIVIAZIONE CHAT GLOBALE ===", 'success');
    
    if ($web_output) {
        echo "<div class='log success'>✅ Archiviazione completata con successo!</div>";
        echo "<div class='log info'>📁 File creato: $nome_file</div>";
        echo "<div class='log info'>📊 Messaggi archiviati: $conta_messaggi</div>";
        echo "<div class='log info'>💾 Spazio file: {$dimensione_file} KB</div>";
        echo "</div></body></html>";
    }
    
} catch (Exception $e) {
    $error_msg = "❌ Errore durante l'archiviazione: " . $e->getMessage();
    log_message($error_msg, 'error');
    
    if ($web_output) {
        echo "<div class='log error'>$error_msg</div>";
        echo "</div></body></html>";
    }
    
    exit(1);
}
?>
