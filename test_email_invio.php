<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/email_config.php';

// Controlla autenticazione
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $email_test = $_POST['email_destinatario'];
    $nome_test = $_POST['nome_destinatario'];
    
    // Test con email HTML
    $oggetto = "Test Email Sistema CRM - " . date('d/m/Y H:i:s');
    $messaggio = "
    <h2>Test Email dal Sistema CRM</h2>
    <p>Caro/a <strong>{$nome_test}</strong>,</p>
    <p>Questa è una email di test inviata dal sistema CRM di AS Contabilmente.</p>
    <hr>
    <p><strong>Dettagli invio:</strong></p>
    <ul>
        <li>Data e ora: " . date('d/m/Y H:i:s') . "</li>
        <li>Server: " . gethostname() . "</li>
        <li>Mittente: " . SMTP_FROM_NAME . "</li>
        <li>Email mittente: " . SMTP_FROM_EMAIL . "</li>
    </ul>
    <p>Se ricevi questa email, la configurazione SMTP funziona correttamente.</p>
    <p>Cordiali saluti,<br><strong>AS Contabilmente</strong></p>
    ";
    
    $risultato = inviaEmailSMTP($email_test, $nome_test, $oggetto, $messaggio, true);
    
    if ($risultato['success']) {
        $messaggio_risultato = "<div class='alert alert-success'>✅ Email inviata con successo a <strong>{$email_test}</strong>!</div>";
    } else {
        $messaggio_risultato = "<div class='alert alert-danger'>❌ Errore: " . $risultato['message'] . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Test Invio Email</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($messaggio_risultato)) echo $messaggio_risultato; ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Configurazione SMTP:</strong><br>
                            Server: <?php echo SMTP_HOST; ?>:<?php echo SMTP_PORT; ?><br>
                            Mittente: <?php echo SMTP_FROM_NAME; ?> &lt;<?php echo SMTP_FROM_EMAIL; ?>&gt;
                        </div>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email_destinatario" class="form-label">Email Destinatario</label>
                                <input type="email" class="form-control" id="email_destinatario" name="email_destinatario" 
                                       value="<?php echo isset($_POST['email_destinatario']) ? htmlspecialchars($_POST['email_destinatario']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nome_destinatario" class="form-label">Nome Destinatario</label>
                                <input type="text" class="form-control" id="nome_destinatario" name="nome_destinatario" 
                                       value="<?php echo isset($_POST['nome_destinatario']) ? htmlspecialchars($_POST['nome_destinatario']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <button type="submit" name="test_email" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Invia Email di Test
                            </button>
                        </form>
                        
                        <hr>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nota importante:</strong> Se non ricevi l'email:
                            <ol class="mt-2 mb-0">
                                <li>Controlla la cartella <strong>SPAM</strong></li>
                                <li>Controlla tutte le cartelle (Promozioni, Social, ecc.)</li>
                                <li>Aggiungi <code><?php echo SMTP_FROM_EMAIL; ?></code> ai contatti fidati</li>
                                <li>Le email da server non verificati spesso finiscono nello spam</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
