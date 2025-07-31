<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Connessione database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm;charset=utf8mb4', 'crmuser', 'Admin123!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Recupera template e clienti
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();
$clienti = $pdo->query("SELECT id, `Cognome/Ragione sociale` as nome, Nome as nome_proprio, Mail as email FROM clienti WHERE Mail IS NOT NULL AND Mail != '' ORDER BY `Cognome/Ragione sociale`")->fetchAll();

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
                $nome_completo = trim(($cliente['Nome'] ?? '') . ' ' . ($cliente['Cognome/Ragione sociale'] ?? ''));
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
                
                // Simulazione invio email (sostituire con vero invio)
                $esito_invio = mail($cliente['Mail'], $oggetto_finale, $corpo_finale, 
                    "From: gestione.ascontabilmente@gmail.com\r\n" .
                    "Content-Type: text/plain; charset=UTF-8\r\n"
                );
                
                if ($esito_invio) {
                    $successi++;
                } else {
                    $errori++;
                    $dettagli_errori[] = $nome_completo . " (" . $cliente['Mail'] . ")";
                }
            }
        }
        
        // Salva nella cronologia (una riga per tutto l'invio multiplo)
        $stmt = $pdo->prepare("INSERT INTO email_cronologia (template_id, oggetto, corpo, destinatari, totale_destinatari, invii_riusciti, invii_falliti, dettagli_errori) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $template_id,
            $oggetto_custom,
            $corpo_custom,
            implode(', ', $destinatari_email),
            count($clienti_selezionati),
            $successi,
            $errori,
            implode(', ', $dettagli_errori)
        ]);
        
        if ($successi > 0) {
            $message = "✅ Email inviate con successo: <strong>$successi</strong>";
            if ($errori > 0) {
                $message .= " | ❌ Errori: <strong>$errori</strong>";
            }
        } else {
            $error = "Nessuna email è stata inviata.";
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Invio Email Multiplo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { margin-top: 20px; }
        .card { margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .cliente-item { 
            padding: 8px; 
            border-radius: 5px; 
            margin-bottom: 5px; 
            background: #f8f9fa;
        }
        .cliente-item:hover { background: #e9ecef; }
        .cliente-count {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>CRM - Invio Email
            </a>
            <div>
                <a href="gestione_email_template.php" class="btn btn-light me-2">
                    <i class="fas fa-cog me-1"></i>Template
                </a>
                <a href="email_cronologia.php" class="btn btn-outline-light">
                    <i class="fas fa-history me-1"></i>Cronologia
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible">
                <i class="fas fa-check me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible">
                <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="emailForm">
            <div class="row">
                <!-- Selezione Template -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6><i class="fas fa-file-alt me-2"></i>1. Seleziona Template</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($templates)): ?>
                                <p class="text-muted">Nessun template disponibile.</p>
                                <a href="gestione_email_template.php" class="btn btn-primary btn-sm">Crea Template</a>
                            <?php else: ?>
                                <select class="form-select" name="template_id" id="templateSelect" required>
                                    <option value="">-- Seleziona template --</option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Selezione Clienti -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between">
                            <h6><i class="fas fa-users me-2"></i>2. Seleziona Clienti</h6>
                            <span class="cliente-count" id="clienteCount">0</span>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($clienti)): ?>
                                <p class="text-muted">Nessun cliente con email trovato.</p>
                            <?php else: ?>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100" id="selectAll">
                                        <i class="fas fa-check-double me-1"></i>Seleziona Tutti (<?= count($clienti) ?>)
                                    </button>
                                </div>
                                
                                <?php foreach ($clienti as $cliente): ?>
                                    <div class="cliente-item">
                                        <div class="form-check">
                                            <input class="form-check-input cliente-check" type="checkbox" 
                                                   name="clienti[]" value="<?= $cliente['id'] ?>" 
                                                   id="cliente_<?= $cliente['id'] ?>">
                                            <label class="form-check-label w-100" for="cliente_<?= $cliente['id'] ?>">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <strong><?= htmlspecialchars(trim($cliente['nome_proprio'] . ' ' . $cliente['nome'])) ?></strong>
                                                    </div>
                                                </div>
                                                <small class="text-primary"><?= htmlspecialchars($cliente['email']) ?></small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Personalizza Email -->
                <div class="col-md-4">
                    <div class="card" id="emailCard" style="display: none;">
                        <div class="card-header bg-success text-white">
                            <h6><i class="fas fa-edit me-2"></i>3. Personalizza & Invia</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Oggetto</label>
                                <input type="text" name="oggetto_custom" id="oggettoInput" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Corpo Email</label>
                                <textarea name="corpo_custom" id="corpoTextarea" class="form-control" rows="8" required></textarea>
                                <small class="text-muted">Variabili: {nome_cliente}, {email_cliente}</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="invia_email" class="btn btn-success" id="btnInvia">
                                    <i class="fas fa-paper-plane me-1"></i>Invia Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.getElementById('templateSelect');
            const emailCard = document.getElementById('emailCard');
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
                        .then(response => response.json())
                        .then(data => {
                            oggettoInput.value = data.oggetto;
                            corpoTextarea.value = data.corpo;
                            emailCard.style.display = 'block';
                        });
                } else {
                    emailCard.style.display = 'none';
                }
            });
            
            // Seleziona tutti
            selectAllBtn.addEventListener('click', function() {
                const allChecked = document.querySelectorAll('.cliente-check:checked').length === clienteChecks.length;
                clienteChecks.forEach(check => {
                    check.checked = !allChecked;
                });
                updateCount();
            });
            
            // Aggiorna contatore
            clienteChecks.forEach(check => {
                check.addEventListener('change', updateCount);
            });
            
            function updateCount() {
                const count = document.querySelectorAll('.cliente-check:checked').length;
                clienteCount.textContent = count;
                clienteCount.style.background = count > 0 ? '#28a745' : '#6c757d';
            }
            
            // Validazione invio
            document.getElementById('emailForm').addEventListener('submit', function(e) {
                const count = document.querySelectorAll('.cliente-check:checked').length;
                if (count === 0) {
                    e.preventDefault();
                    alert('Seleziona almeno un cliente per l\'invio.');
                    return;
                }
                
                if (!confirm(`Inviare l'email a ${count} clienti?`)) {
                    e.preventDefault();
                    return;
                }
                
                btnInvia.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Invio...';
                btnInvia.disabled = true;
            });
            
            updateCount();
        });
    </script>
</body>
</html>
