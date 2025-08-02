<?php
// Test dettagliato configurazione SMTP Gmail
require_once 'includes/email_config.php';

echo "=== TEST CONFIGURAZIONE EMAIL ===\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP User: " . SMTP_USERNAME . "\n";
echo "SMTP Pass: " . (strlen(SMTP_PASSWORD) > 0 ? 'Configurata (' . strlen(SMTP_PASSWORD) . ' caratteri)' : 'NON CONFIGURATA') . "\n";
echo "\n=== TEST INVIO EMAIL ===\n";

try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Debug verboso
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'echo';
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    
    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress('gestione.ascontabilmente@gmail.com', 'Test Destinatario');
    
    // Content
    $mail->isHTML(false);
    $mail->Subject = 'Test Email CRM - ' . date('Y-m-d H:i:s');
    $mail->Body    = 'Questa è una email di test dal sistema CRM.' . "\n\n" . 
                     'Inviata il: ' . date('Y-m-d H:i:s') . "\n" .
                     'Server: ' . gethostname();
    
    $mail->send();
    echo "\n✅ EMAIL INVIATA CON SUCCESSO!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRORE NELL'INVIO: " . $mail->ErrorInfo . "\n";
    echo "Dettaglio errore: " . $e->getMessage() . "\n";
}
?>
