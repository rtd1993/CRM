<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';
require_once 'includes/email_config.php';

// Verifica configurazione email
$config_email = verificaConfigurazioneEmail();

// Recupera tutti i clienti con email
$stmt = $pdo->query("SELECT id, `Cognome_Ragione_sociale` as ragione_sociale, Nome, `Codice_fiscale` as codice_fiscale, `Partita_IVA` as partita_iva, Mail FROM clienti WHERE Mail IS NOT NULL AND Mail != '' ORDER BY `Cognome_Ragione_sociale`, Nome");
$clienti = $stmt->fetchAll();

// Recupera tutti i template email
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();

$message = '';
$error = '';

// Gestione invio email
if ($_POST && isset($_POST['invia_email'])) {
    if (!$config_email['configurata']) {
        $error = $config_email['messaggio'];
    } else {
        $template_id = $_POST['template_id'];
        $clienti_selezionati = $_POST['clienti'] ?? [];
        $oggetto_modificato = trim($_POST['oggetto_modificato']);
        $corpo_modificato = trim($_POST['corpo_modificato']);
        
        if (empty($template_id) || empty($clienti_selezionati) || empty($oggetto_modificato) || empty($corpo_modificato)) {
            $error = "Tutti i campi sono obbligatori e devi selezionare almeno un cliente.";
        } else {
            $successi = 0;
            $errori = 0;
            $dettagli_errori = [];
            
            foreach ($clienti_selezionati as $cliente_id) {
                // Recupera dati cliente
                $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
                $stmt->execute([$cliente_id]);
                $cliente = $stmt->fetch();
                
                if ($cliente && !empty($cliente['Mail'])) {
                    // Sostituisci le variabili nel testo
                    $oggetto_finale = str_replace(
                        ['{nome_cliente}', '{cognome_cliente}', '{ragione_sociale}', '{codice_fiscale}', '{partita_iva}'],
                        [
                            $cliente['Nome'] ?? '',
                            $cliente['Cognome_Ragione_sociale'] ?? '',
                            $cliente['Cognome_Ragione_sociale'] ?? '',
                            $cliente['Codice_fiscale'] ?? '',
                            $cliente['Partita_IVA'] ?? ''
                        ],
                        $oggetto_modificato
                    );
                    
                    $corpo_finale = str_replace(
                        ['{nome_cliente}', '{cognome_cliente}', '{ragione_sociale}', '{codice_fiscale}', '{partita_iva}'],
                        [
                            $cliente['Nome'] ?? '',
                            $cliente['Cognome_Ragione_sociale'] ?? '',
                            $cliente['Cognome_Ragione_sociale'] ?? '',
                            $cliente['Codice_fiscale'] ?? '',
                            $cliente['Partita_IVA'] ?? ''
                        ],
                        $corpo_modificato
                    );
                    
                    // Invia email tramite PHPMailer
                    $nome_completo = trim(($cliente['Nome'] ?? '') . ' ' . ($cliente['Cognome_Ragione_sociale'] ?? ''));
                    $risultato = inviaEmailSMTP($cliente['Mail'], $nome_completo, $oggetto_finale, $corpo_finale);
                    
                    // Log dell'invio
                    $stmt = $pdo->prepare("INSERT INTO email_log (cliente_id, template_id, oggetto, corpo, destinatario_email, destinatario_nome, stato, messaggio_errore) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    if ($risultato['success']) {
                        $stmt->execute([
                            $cliente_id, 
                            $template_id, 
                            $oggetto_finale, 
                            $corpo_finale, 
                            $cliente['Mail'], 
                            $nome_completo, 
                            'inviata', 
                            null
                        ]);
                        $successi++;
                    } else {
                        $stmt->execute([
                            $cliente_id, 
                            $template_id, 
                            $oggetto_finale, 
                            $corpo_finale, 
                            $cliente['Mail'], 
                            $nome_completo, 
                            'fallita', 
                            $risultato['message']
                        ]);
                        $errori++;
                        $dettagli_errori[] = "• $nome_completo ({$cliente['Mail']}): {$risultato['message']}";
                    }
                }
            }
            
            if ($successi > 0) {
                $message = "✅ Email inviate con successo: <strong>$successi</strong>. ";
            }
            if ($errori > 0) {
                $message .= "❌ Errori: <strong>$errori</strong>.";
                if (!empty($dettagli_errori)) {
                    $error = "Dettagli errori:\n" . implode("\n", array_slice($dettagli_errori, 0, 5));
                    if (count($dettagli_errori) > 5) {
                        $error .= "\n... e altri " . (count($dettagli_errori) - 5) . " errori.";
                    }
                }
            }
            if ($successi == 0 && $errori == 0) {
                $error = "Nessuna email è stata inviata.";
            }
        }
    }
}

// AJAX per recuperare template
if (isset($_GET['get_template']) && isset($_GET['template_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$_GET['template_id']]);
    $template = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode($template);
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invio Email - CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            padding: 2rem;
            max-width: 1400px;
        }
        
        .page-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            border: none;
            border-radius: 10px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #ecf0f1;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .cliente-checkbox {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .cliente-checkbox:hover {
            background: #e9ecef;
            border-color: #3498db;
        }
        
        .cliente-checkbox input[type="checkbox"]:checked + label {
            color: #2980b9;
            font-weight: 600;
        }
        
        .email-preview {
            background: #ffffff;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .email-preview h6 {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .variable-help {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .variable-tag {
            background: #3498db;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            margin: 0.2rem;
            display: inline-block;
        }
        
        .cliente-count {
            background: #27ae60;
            color: white;
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .check-all-container {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <h1 class="page-title">
                <i class="fas fa-paper-plane me-3"></i>
                Invio Email ai Clienti
            </h1>
            
            <?php if (!$config_email['configurata']): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Configurazione email incompleta:</strong> <?php echo $config_email['messaggio']; ?>
                    <br><br>
                    <strong>Istruzioni per l'amministratore:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Vai su <a href="https://myaccount.google.com/security" target="_blank">Account Google - Sicurezza</a></li>
                        <li>Attiva la "Verifica in due passaggi"</li>
                        <li>Genera una "Password per le app" specificatamente per questa applicazione</li>
                        <li>Inserisci la password generata nel file <code>includes/email_config.php</code> nella costante <code>SMTP_PASSWORD</code></li>
                    </ol>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Errore:</strong><br>
                    <?php echo nl2br(htmlspecialchars($error)); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="emailForm">
                <div class="row">
                    <!-- Selezione Template -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-envelope-open-text me-2"></i>
                                    1. Seleziona Template Email
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($templates)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p>Nessun template disponibile.</p>
                                        <a href="gestione_email_template.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Crea il primo template
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-3">
                                        <label for="template_id" class="form-label">Template</label>
                                        <select class="form-select" id="template_id" name="template_id" required>
                                            <option value="">-- Seleziona un template --</option>
                                            <?php foreach ($templates as $template): ?>
                                                <option value="<?php echo $template['id']; ?>">
                                                    <?php echo htmlspecialchars($template['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="variable-help">
                                        <h6><i class="fas fa-info-circle me-2"></i>Variabili disponibili:</h6>
                                        <span class="variable-tag">{nome_cliente}</span>
                                        <span class="variable-tag">{cognome_cliente}</span>
                                        <span class="variable-tag">{ragione_sociale}</span>
                                        <span class="variable-tag">{codice_fiscale}</span>
                                        <span class="variable-tag">{partita_iva}</span>
                                    </div>
                                    
                                    <div class="text-center">
                                        <a href="gestione_email_template.php" class="btn btn-secondary">
                                            <i class="fas fa-cog me-2"></i>Gestisci Template
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selezione Clienti -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>
                                    2. Seleziona Clienti
                                </h5>
                                <span class="cliente-count" id="clienteCount">0 selezionati</span>
                            </div>
                            <div class="card-body">
                                <?php if (empty($clienti)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-user-slash fa-3x mb-3"></i>
                                        <p>Nessun cliente con email trovato.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="check-all-container">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label fw-bold" for="selectAll">
                                                Seleziona tutti (<?php echo count($clienti); ?> clienti)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div style="max-height: 400px; overflow-y: auto;">
                                        <?php foreach ($clienti as $cliente): ?>
                                            <div class="cliente-checkbox">
                                                <div class="form-check">
                                                    <input class="form-check-input cliente-check" type="checkbox" 
                                                           name="clienti[]" value="<?php echo $cliente['id']; ?>" 
                                                           id="cliente_<?php echo $cliente['id']; ?>">
                                                    <label class="form-check-label w-100" for="cliente_<?php echo $cliente['id']; ?>">
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <strong>
                                                                    <?php 
                                                                    $nome_completo = trim(($cliente['Nome'] ?? '') . ' ' . ($cliente['ragione_sociale'] ?? ''));
                                                                    echo htmlspecialchars($nome_completo); 
                                                                    ?>
                                                                </strong>
                                                                <br>
                                                                <small class="text-muted"><?php echo htmlspecialchars($cliente['codice_fiscale'] ?? ''); ?></small>
                                                            </div>
                                                            <div class="text-end">
                                                                <small class="text-primary"><?php echo htmlspecialchars($cliente['Mail']); ?></small>
                                                            </div>
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
                </div>
                
                <!-- Modifica Email -->
                <div class="card" id="emailEditCard" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            3. Modifica e Invia Email
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="oggetto_modificato" class="form-label">Oggetto Email</label>
                                    <input type="text" class="form-control" id="oggetto_modificato" 
                                           name="oggetto_modificato" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email mittente</label>
                                    <input type="text" class="form-control" 
                                           value="gestione.ascontabilmente@gmail.com" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="corpo_modificato" class="form-label">Corpo Email</label>
                            <textarea class="form-control" id="corpo_modificato" name="corpo_modificato" 
                                      rows="12" required></textarea>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" name="invia_email" class="btn btn-primary btn-lg" id="btnInvia"
                                    <?php echo !$config_email['configurata'] ? 'disabled title="Configurazione email incompleta"' : ''; ?>>
                                <i class="fas fa-paper-plane me-2"></i>
                                <?php echo $config_email['configurata'] ? 'Invia Email' : 'Configurazione Richiesta'; ?>
                            </button>
                            <?php if (!$config_email['configurata']): ?>
                                <br><small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Contatta l'amministratore per completare la configurazione SMTP
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
            
            <!-- Link cronologia -->
            <div class="text-center mt-4">
                <a href="cronologia_email.php" class="btn btn-secondary">
                    <i class="fas fa-history me-2"></i>
                    Visualizza Cronologia Invii
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('template_id');
            const emailEditCard = document.getElementById('emailEditCard');
            const oggettoInput = document.getElementById('oggetto_modificato');
            const corpoTextarea = document.getElementById('corpo_modificato');
            const selectAllCheckbox = document.getElementById('selectAll');
            const clienteChecks = document.querySelectorAll('.cliente-check');
            const clienteCount = document.getElementById('clienteCount');
            const btnInvia = document.getElementById('btnInvia');
            const emailForm = document.getElementById('emailForm');
            
            // Gestione selezione template
            templateSelect.addEventListener('change', function() {
                if (this.value) {
                    // Carica template via AJAX
                    fetch(`?get_template=1&template_id=${this.value}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data) {
                                oggettoInput.value = data.oggetto;
                                corpoTextarea.value = data.corpo;
                                emailEditCard.style.display = 'block';
                                emailEditCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                        })
                        .catch(error => {
                            console.error('Errore nel caricamento del template:', error);
                        });
                } else {
                    emailEditCard.style.display = 'none';
                }
            });
            
            // Gestione seleziona tutti
            selectAllCheckbox.addEventListener('change', function() {
                clienteChecks.forEach(check => {
                    check.checked = this.checked;
                });
                updateClienteCount();
            });
            
            // Gestione selezione singoli clienti
            clienteChecks.forEach(check => {
                check.addEventListener('change', function() {
                    updateClienteCount();
                    
                    // Aggiorna stato "seleziona tutti"
                    const checkedCount = document.querySelectorAll('.cliente-check:checked').length;
                    selectAllCheckbox.checked = checkedCount === clienteChecks.length;
                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < clienteChecks.length;
                });
            });
            
            // Aggiorna contatore clienti selezionati
            function updateClienteCount() {
                const checkedCount = document.querySelectorAll('.cliente-check:checked').length;
                clienteCount.textContent = `${checkedCount} selezionati`;
                
                if (checkedCount > 0) {
                    clienteCount.style.background = '#27ae60';
                } else {
                    clienteCount.style.background = '#95a5a6';
                }
            }
            
            // Gestione invio form
            emailForm.addEventListener('submit', function(e) {
                const checkedCount = document.querySelectorAll('.cliente-check:checked').length;
                
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Devi selezionare almeno un cliente per l\'invio.');
                    return;
                }
                
                if (!confirm(`Sei sicuro di voler inviare l'email a ${checkedCount} clienti?`)) {
                    e.preventDefault();
                    return;
                }
                
                // Mostra loading
                btnInvia.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Invio in corso...';
                btnInvia.disabled = true;
                emailForm.classList.add('loading');
            });
            
            // Inizializza contatore
            updateClienteCount();
        });
    </script>
</body>
</html>
