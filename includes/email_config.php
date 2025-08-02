<?php
// Configurazione email SMTP per Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'gestione.ascontabilmente@gmail.com');
define('SMTP_PASSWORD', 'cxiglifvkiylssrk'); // CONFIGURARE: Password app specifica Gmail (16 caratteri)
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'gestione.ascontabilmente@gmail.com');
define('SMTP_FROM_NAME', 'AS Contabilmente');

// Include PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Funzione per inviare email utilizzando PHPMailer
function inviaEmailSMTP($destinatario, $nome_destinatario, $oggetto, $messaggio, $isHTML = true) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true; 
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30; // Timeout di 30 secondi
        
        // Impostazioni per migliorare la deliverability
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';
        $mail->WordWrap   = 70;
        
        // Headers personalizzati per evitare spam
        $mail->addCustomHeader('X-Priority', '3');
        $mail->addCustomHeader('X-MSMail-Priority', 'Normal');
        $mail->addCustomHeader('X-Mailer', 'CRM AS Contabilmente v1.0');
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario, $nome_destinatario);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $oggetto;
        $mail->Body    = $messaggio;
        
        // Se è HTML, aggiungi anche versione testo
        if ($isHTML) {
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $messaggio));
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email inviata con successo'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Errore invio email: ' . $e->getMessage()];
    }
}

// Funzione per verificare la configurazione email
function verificaConfigurazioneEmail() {
    if (empty(SMTP_PASSWORD)) {
        return [
            'configurata' => false,
            'messaggio' => 'Password SMTP non configurata. Contatta l\'amministratore per completare la configurazione.'
        ];
    }
    
    return [
        'configurata' => true,
        'messaggio' => 'Configurazione email completa'
    ];
}
?>
