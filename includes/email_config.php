<?php
// Configurazione email SMTP per Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'gestione.ascontabilmente@gmail.com');
define('SMTP_PASSWORD', 'AnnaSabina01!'); // DA CONFIGURARE: Password app specifica Gmail
define('SMTP_SECURE', 'tls');
define('SMTP_FROM_EMAIL', 'gestione.ascontabilmente@gmail.com');
define('SMTP_FROM_NAME', 'AS Contabilmente');

// Funzione per inviare email utilizzando PHPMailer
function inviaEmailSMTP($destinatario_email, $destinatario_nome, $oggetto, $corpo, $mittente_email = null, $mittente_nome = null) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configurazione server SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Mittente
        $from_email = $mittente_email ?? SMTP_FROM_EMAIL;
        $from_name = $mittente_nome ?? SMTP_FROM_NAME;
        $mail->setFrom($from_email, $from_name);
        $mail->addReplyTo($from_email, $from_name);
        
        // Destinatario
        $mail->addAddress($destinatario_email, $destinatario_nome);
        
        // Contenuto
        $mail->isHTML(false); // Invio come testo semplice
        $mail->Subject = $oggetto;
        $mail->Body = $corpo;
        
        // Invia email
        $mail->send();
        return ['success' => true, 'message' => 'Email inviata con successo'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Errore nell'invio: {$mail->ErrorInfo}"];
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
