<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Email Debug</h1>";

try {
    require_once __DIR__ . '/includes/email_config.php';
    echo "<p>✅ Config caricata</p>";
    
    if (function_exists('inviaEmailSMTP')) {
        echo "<p>✅ Funzione inviaEmailSMTP disponibile</p>";
        
        $result = inviaEmailSMTP(
            'gestione.ascontabilmente@gmail.com',
            'Test Debug',
            'Test Email Debug - ' . date('Y-m-d H:i:s'),
            'Questo è un test di debug per verificare l\'invio email.',
            false
        );
        
        echo "<h3>Risultato:</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            echo "<div style='color: green; font-weight: bold;'>✅ EMAIL INVIATA CON SUCCESSO!</div>";
            echo "<p>Controlla le cartelle: Posta in arrivo, Spam, Promozioni</p>";
        } else {
            echo "<div style='color: red; font-weight: bold;'>❌ ERRORE: " . $result['message'] . "</div>";
        }
        
    } else {
        echo "<p>❌ Funzione inviaEmailSMTP non disponibile</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
}
?>
