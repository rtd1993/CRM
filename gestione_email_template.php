<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione semplice
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Include l'header del sito
require_once __DIR__ . '/includes/header.php';

// Gestione azioni
$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['crea_template'])) {
        $nome = trim($_POST['nome']);
        $oggetto = trim($_POST['oggetto']);
        $corpo = trim($_POST['corpo']);
        
        if ($nome && $oggetto && $corpo) {
            $stmt = $pdo->prepare("INSERT INTO email_templates (nome, oggetto, corpo) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $oggetto, $corpo])) {
                $message = "Template creato con successo!";
            } else {
                $error = "Errore nella creazione del template.";
            }
        } else {
            $error = "Tutti i campi sono obbligatori.";
        }
    }
    
    if (isset($_POST['elimina_template'])) {
        $id = $_POST['template_id'];
        $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Template eliminato!";
        }
    }
}

// Recupera template
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();
?>

<!-- Breadcrumb e Navigazione Email -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
                <div>
                    <h4 class="mb-0 text-primary">
                        <i class="fas fa-envelope-open-text me-2"></i>Gestione Template Email
                    </h4>
                    <small class="text-muted">Crea e gestisci i template per le email</small>
                </div>
                <div>
                    <a href="email_invio.php" class="btn btn-primary me-2">
                        <i class="fas fa-paper-plane me-1"></i>Invia Email
                    </a>
                    <a href="email_cronologia.php" class="btn btn-outline-primary">
                        <i class="fas fa-history me-1"></i>Cronologia
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
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

    <div class="row">
        <!-- Form Nuovo Template -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Nuovo Template
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-tag me-1 text-primary"></i>Nome Template
                            </label>
                            <input type="text" name="nome" class="form-control form-control-lg" 
                                   placeholder="Es. Comunicazione Generale" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-heading me-1 text-primary"></i>Oggetto Email
                            </label>
                            <input type="text" name="oggetto" class="form-control form-control-lg" 
                                   placeholder="Es. Comunicazione importante" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-align-left me-1 text-primary"></i>Corpo Email
                            </label>
                            <textarea name="corpo" class="form-control" rows="10" 
                                      placeholder="Scrivi qui il contenuto dell'email..." required></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>Variabili disponibili:</strong> 
                                <code>{nome_cliente}</code>, <code>{email_cliente}</code>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="crea_template" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Crea Template
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista Template -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>Template Esistenti
                    </h5>
                    <span class="badge bg-white text-dark fs-6"><?= count($templates) ?></span>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($templates)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessun template trovato</p>
                                <small class="text-muted">Crea il tuo primo template usando il form a sinistra</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($templates as $index => $template): ?>
                                <div class="border-bottom p-3 <?= $index % 2 == 0 ? 'bg-light' : 'bg-white' ?> template-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2 text-primary fw-bold">
                                                <i class="fas fa-file-alt me-2"></i>
                                                <?= htmlspecialchars($template['nome']) ?>
                                            </h6>
                                            <p class="mb-2 text-dark">
                                                <strong class="text-muted">Oggetto:</strong> 
                                                <?= htmlspecialchars($template['oggetto']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Creato: <?= date('d/m/Y H:i', strtotime($template['data_creazione'])) ?>
                                            </small>
                                        </div>
                                        <form method="POST" class="ms-2">
                                            <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                            <button type="submit" name="elimina_template" 
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Sei sicuro di voler eliminare questo template?')"
                                                    title="Elimina template">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="mt-3 p-3 bg-white border rounded">
                                        <small class="text-dark" style="line-height: 1.4;">
                                            <?= nl2br(htmlspecialchars(substr($template['corpo'], 0, 200))) ?>
                                            <?= strlen($template['corpo']) > 200 ? '<span class="text-muted">...</span>' : '' ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.template-item {
    transition: all 0.2s ease;
}
.template-item:hover {
    background-color: #f8f9fa !important;
    border-left: 4px solid #3498db;
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    border-bottom: none;
    padding: 1rem 1.5rem;
}
.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
