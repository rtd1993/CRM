<?php
set_time_limit(60);
ini_set('max_execution_time', 60);

require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "Test SMTP diretto con PHPMailer\n";

try {
    $mail = new PHPMailer(true);
    
    // Abilita debug verboso
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG ($level): $str\n";
    };
    
    // Configurazione server
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'gestione.ascontabilmente@gmail.com';
    $mail->Password = 'cxiglifvkiylssrk';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 30;
    
    // Destinatari
    $mail->setFrom('gestione.ascontabilmente@gmail.com', 'AS Contabilmente');
    $mail->addAddress('gestione.ascontabilmente@gmail.com', 'Test Debug');
    
    // Contenuto
    $mail->isHTML(false);
    $mail->Subject = 'Test Debug SMTP - ' . date('H:i:s');
    $mail->Body = 'Test message con debug completo - ' . date('Y-m-d H:i:s');
    
    echo "Tentativo di invio...\n";
    $mail->send();
    echo "✅ EMAIL INVIATA CON SUCCESSO!\n";
    
} catch (Exception $e) {
    echo "❌ ERRORE: " . $e->getMessage() . "\n";
    echo "Debug info: " . $mail->ErrorInfo . "\n";
}
?>
