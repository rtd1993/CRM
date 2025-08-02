<?php
// Test rapido per verificare il caricamento della pagina email_invio.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Email Invio</h2>";

try {
    // Test 1: Configurazione email
    echo "1. Test configurazione email: ";
    require_once 'includes/email_config.php';
    echo "✅ OK - SMTP Host: " . SMTP_HOST . "<br>";
    
    // Test 2: Database connection
    echo "2. Test database: ";
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    $templates = $pdo->query("SELECT COUNT(*) as count FROM email_templates")->fetch();
    echo "✅ OK - " . $templates['count'] . " template trovati<br>";
    
    // Test 3: PHPMailer
    echo "3. Test PHPMailer: ";
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    echo "✅ OK - PHPMailer caricato<br>";
    
    echo "<br><strong>Tutti i test passati! La pagina dovrebbe funzionare.</strong>";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "<br>";
}
?>
