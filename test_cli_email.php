<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST INVIO EMAIL ===\n";

try {
    require_once 'includes/email_config.php';
    echo "✅ Configurazione caricata\n";
    echo "SMTP Host: " . SMTP_HOST . "\n";
    echo "SMTP User: " . SMTP_USERNAME . "\n";
    echo "SMTP Pass configurata: " . (SMTP_PASSWORD ? 'SI' : 'NO') . "\n\n";
    
    // Test invio
    echo "Invio email di test...\n";
    $result = inviaEmailSMTP(
        'gestione.ascontabilmente@gmail.com',
        'Test CLI',
        'Test Email CLI - ' . date('H:i:s'),
        'Test message from CLI - ' . date('Y-m-d H:i:s'),
        false
    );
    
    echo "Risultato:\n";
    print_r($result);
    
    if ($result['success']) {
        echo "\n✅ EMAIL INVIATA CON SUCCESSO!\n";
        echo "Controlla tutte le cartelle email (anche SPAM)\n";
    } else {
        echo "\n❌ ERRORE: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRORE PHP: " . $e->getMessage() . "\n";
}
?>
