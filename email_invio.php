<?php
// AJAX per recuperare template - DEVE essere prima di qualsiasi output
if (isset($_GET['get_template']) && isset($_GET['template_id'])) {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/db.php';
    
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$_GET['template_id']]);
    $template = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode($template);
    exit;
}

// Include PHPMailer per invio email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once __DIR__ . '/includes/email_config.php';

// Include l'header del sito (gestisce sessione e autenticazione)
require_once __DIR__ . '/includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Recupera template e clienti
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();
$clienti = $pdo->query("SELECT id, `Cognome_Ragione_sociale` as nome, Nome as nome_proprio, Mail as email FROM clienti WHERE Mail IS NOT NULL AND Mail != '' ORDER BY `Cognome_Ragione_sociale`")->fetchAll();

$message = '';
$error = '';

// Gestione invio email
if ($_POST && isset($_POST['invia_email'])) {
    $template_id = $_POST['template_id'];
    $clienti_selezionati = $_POST['clienti'] ?? [];
    $oggetto_custom = trim($_POST['oggetto_custom']);
    $corpo_custom = trim($_POST['corpo_custom']);
    
    if (empty($template_id) || empty($clienti_selezionati) || empty($oggetto_custom) || empty($corpo_custom)) {
        $error = "Tutti i campi sono obbligatori e devi selezionare almeno un cliente.";
    } else {
        $successi = 0;
        $errori = 0;
        $destinatari_email = [];
        $dettagli_errori = [];
        
        foreach ($clienti_selezionati as $cliente_id) {
            // Recupera dati cliente
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch();
            
            if ($cliente && !empty($cliente['Mail'])) {
                $destinatari_email[] = $cliente['Mail'];
                
                // Sostituisci variabili
                $nome_completo = trim(($cliente['Nome'] ?? '') . ' ' . ($cliente['Cognome_Ragione_sociale'] ?? ''));
                $oggetto_finale = str_replace(
                    ['{nome_cliente}', '{email_cliente}'],
                    [$nome_completo, $cliente['Mail']],
                    $oggetto_custom
                );
                
                $corpo_finale = str_replace(
                    ['{nome_cliente}', '{email_cliente}'],
                    [$nome_completo, $cliente['Mail']],
                    $corpo_custom
                );
                
                // Invio email con PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';
                    
                    // Recipients
                    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                    $mail->addAddress($cliente['Mail'], $nome_completo);
                    
                    // Content
                    $mail->isHTML(false); // Invio come testo semplice
                    $mail->Subject = $oggetto_finale;
                    $mail->Body    = $corpo_finale;
                    
                    $mail->send();
                    $successi++;
                    
                } catch (Exception $e) {
                    $errori++;
                    $dettagli_errori[] = $nome_completo . " (" . $cliente['Mail'] . ") - Errore: " . $mail->ErrorInfo;
                }
            }
        }
        
        // Salva log semplice in file
        $log_entry = date('Y-m-d H:i:s') . " - Invio multiplo: $successi successi, $errori errori\n";
        file_put_contents(__DIR__ . '/logs/email_invii.log', $log_entry, FILE_APPEND | LOCK_EX);
        
        if ($successi > 0) {
            $message = "✅ Email inviate con successo: <strong>$successi</strong>";
            if ($errori > 0) {
                $message .= " | ❌ Errori: <strong>$errori</strong>";
                // Log degli errori dettagliati
                $error_log = date('Y-m-d H:i:s') . " - Errori dettagliati: " . implode('; ', $dettagli_errori) . "\n";
                file_put_contents(__DIR__ . '/logs/email_errori.log', $error_log, FILE_APPEND | LOCK_EX);
            }
        } else {
            $error = "Nessuna email è stata inviata. Controlla la configurazione SMTP.";
        }
    }
}
?>

<!-- Breadcrumb e Navigazione Email -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
                <div>
                    <h4 class="mb-0 text-primary">
                        <i class="fas fa-paper-plane me-2"></i>Invio Email Multiplo
                    </h4>
                    <small class="text-muted">Invia email a più clienti contemporaneamente</small>
                </div>
                <div>
                    <a href="gestione_email_template.php" class="btn btn-outline-primary">
                        <i class="fas fa-cogs me-1"></i>Gestione Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Banner configurazione SMTP -->
    <?php if (SMTP_PASSWORD === 'xxxx xxxx xxxx xxxx'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Configurazione SMTP richiesta!</strong> 
            Per inviare email, configura la password dell'app Gmail in <code>includes/email_config.php</code>.
            <a href="https://myaccount.google.com/apppasswords" target="_blank" class="alert-link">
                Genera password app Gmail
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="emailForm">
        <div class="row">
            <!-- Selezione Template -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
                        <h6 class="mb-0">
                            <span class="badge bg-white text-dark me-2">1</span>
                            <i class="fas fa-file-alt me-2"></i>Seleziona Template
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($templates)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-excel fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Nessun template disponibile</p>
                                <a href="gestione_email_template.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Crea Template
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-list me-1 text-primary"></i>Template Email
                                </label>
                                <select class="form-select form-select-lg" name="template_id" id="templateSelect" required>
                                    <option value="">-- Seleziona template --</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Seleziona un template per iniziare
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Selezione Clienti -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <h6 class="mb-0">
                            <span class="badge bg-white text-dark me-2">2</span>
                            <i class="fas fa-users me-2"></i>Seleziona Clienti
                        </h6>
                        <span class="badge bg-white text-dark fs-6" id="clienteCount">0</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($clienti)): ?>
                            <div class="text-center py-4 px-3">
                                <i class="fas fa-users-slash fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Nessun cliente con email trovato</p>
                            </div>
                        <?php else: ?>
                            <div class="p-3 border-bottom bg-light">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="selectAll">
                                    <i class="fas fa-check-double me-1"></i>Seleziona Tutti (<?= count($clienti) ?>)
                                </button>
                            </div>
                            
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($clienti as $index => $cliente): ?>
                                    <div class="p-3 border-bottom cliente-item <?= $index % 2 == 0 ? 'bg-light' : 'bg-white' ?>">
                                        <div class="form-check">
                                            <input class="form-check-input cliente-check" type="checkbox" 
                                                   name="clienti[]" value="<?= $cliente['id'] ?>" 
                                                   id="cliente_<?= $cliente['id'] ?>">
                                            <label class="form-check-label w-100" for="cliente_<?= $cliente['id'] ?>">
                                                <div class="d-flex flex-column">
                                                    <strong class="text-dark">
                                                        <?= htmlspecialchars(trim($cliente['nome_proprio'] . ' ' . $cliente['nome'])) ?>
                                                    </strong>
                                                    <small class="text-primary">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?= htmlspecialchars($cliente['email']) ?>
                                                    </small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Personalizza Email -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0 h-100" id="emailCard" style="display: none;">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                        <h6 class="mb-0">
                            <span class="badge bg-white text-dark me-2">3</span>
                            <i class="fas fa-edit me-2"></i>Personalizza & Invia
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-heading me-1 text-primary"></i>Oggetto
                            </label>
                            <input type="text" name="oggetto_custom" id="oggettoInput" 
                                   class="form-control form-control-lg" required
                                   placeholder="Oggetto dell'email">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>Corpo Email
                            </label>
                            <textarea name="corpo_custom" id="corpoTextarea" 
                                      class="form-control" rows="8" required
                                      placeholder="Contenuto dell'email..."></textarea>
                            <div class="form-text">
                                <i class="fas fa-magic me-1"></i>
                                <strong>Variabili disponibili:</strong> 
                                <code>{nome_cliente}</code>, <code>{email_cliente}</code>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="invia_email" class="btn btn-success btn-lg" id="btnInvia">
                                <i class="fas fa-paper-plane me-2"></i>Invia Email
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Placeholder quando template non selezionato -->
                <div class="card shadow-sm border-0 h-100" id="placeholderCard">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-arrow-left fa-2x text-muted mb-3"></i>
                        <h6 class="text-muted">Seleziona un template per continuare</h6>
                        <small class="text-muted">Dopo aver scelto il template, potrai personalizzare e inviare l'email</small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.cliente-item {
    transition: all 0.2s ease;
    cursor: pointer;
}
.cliente-item:hover {
    background-color: #e3f2fd !important;
    border-left: 4px solid #3498db !important;
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    border-bottom: none;
    padding: 1rem 1.5rem;
}
.form-control:focus, .form-select:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
}
.btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    border: none;
}
.btn-success:hover {
    background: linear-gradient(135deg, #229954 0%, #1e7e34 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}
.btn-primary {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #2980b9 0%, #1f4e79 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}
#clienteCount {
    transition: all 0.3s ease;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const templateSelect = document.getElementById('templateSelect');
        const emailCard = document.getElementById('emailCard');
        const placeholderCard = document.getElementById('placeholderCard');
        const oggettoInput = document.getElementById('oggettoInput');
        const corpoTextarea = document.getElementById('corpoTextarea');
        const selectAllBtn = document.getElementById('selectAll');
        const clienteChecks = document.querySelectorAll('.cliente-check');
        const clienteCount = document.getElementById('clienteCount');
        const btnInvia = document.getElementById('btnInvia');
        
        // Carica template
        templateSelect.addEventListener('change', function() {
            if (this.value) {
                fetch(`?get_template=1&template_id=${this.value}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Errore nella richiesta');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.oggetto && data.corpo) {
                            oggettoInput.value = data.oggetto;
                            corpoTextarea.value = data.corpo;
                            emailCard.style.display = 'block';
                            placeholderCard.style.display = 'none';
                        } else {
                            throw new Error('Dati template non validi');
                        }
                    })
                    .catch(error => {
                        console.error('Errore nel caricamento del template:', error);
                        alert('Errore nel caricamento del template. Riprova.');
                    });
            } else {
                emailCard.style.display = 'none';
                placeholderCard.style.display = 'block';
            }
        });
        
        // Seleziona tutti
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                const allChecked = document.querySelectorAll('.cliente-check:checked').length === clienteChecks.length;
                clienteChecks.forEach(check => {
                    check.checked = !allChecked;
                });
                updateCount();
                
                // Aggiorna testo bottone
                this.innerHTML = allChecked ? 
                    '<i class="fas fa-check-double me-1"></i>Seleziona Tutti (<?= count($clienti) ?>)' :
                    '<i class="fas fa-times me-1"></i>Deseleziona Tutti';
            });
        }
        
        // Aggiorna contatore
        clienteChecks.forEach(check => {
            check.addEventListener('change', updateCount);
        });
        
        function updateCount() {
            const count = document.querySelectorAll('.cliente-check:checked').length;
            clienteCount.textContent = count;
            
            // Aggiorna stile badge
            if (count === 0) {
                clienteCount.className = 'badge bg-secondary text-white fs-6';
            } else if (count <= 5) {
                clienteCount.className = 'badge bg-success text-white fs-6';
            } else if (count <= 20) {
                clienteCount.className = 'badge bg-warning text-dark fs-6';
            } else {
                clienteCount.className = 'badge bg-danger text-white fs-6';
            }
        }
        
        // Validazione invio
        document.getElementById('emailForm').addEventListener('submit', function(e) {
            const count = document.querySelectorAll('.cliente-check:checked').length;
            if (count === 0) {
                e.preventDefault();
                alert('⚠️ Seleziona almeno un cliente per l\'invio.');
                return;
            }
            
            if (!confirm(`📧 Confermi l'invio dell'email a ${count} clienti?`)) {
                e.preventDefault();
                return;
            }
            
            btnInvia.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Invio in corso...';
            btnInvia.disabled = true;
        });
        
        updateCount();
    });
</script>
</body>
</html>
