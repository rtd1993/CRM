<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// Gestione delle azioni
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['add_template'])) {
        $nome = trim($_POST['nome']);
        $oggetto = trim($_POST['oggetto']);
        $corpo = trim($_POST['corpo']);
        
        if (!empty($nome) && !empty($oggetto) && !empty($corpo)) {
            $stmt = $pdo->prepare("INSERT INTO email_templates (nome, oggetto, corpo) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $oggetto, $corpo])) {
                $message = "Template email creato con successo!";
            } else {
                $error = "Errore durante la creazione del template.";
            }
        } else {
            $error = "Tutti i campi sono obbligatori.";
        }
    }
    
    if (isset($_POST['update_template'])) {
        $id = $_POST['template_id'];
        $nome = trim($_POST['nome']);
        $oggetto = trim($_POST['oggetto']);
        $corpo = trim($_POST['corpo']);
        
        if (!empty($nome) && !empty($oggetto) && !empty($corpo)) {
            $stmt = $pdo->prepare("UPDATE email_templates SET nome = ?, oggetto = ?, corpo = ? WHERE id = ?");
            if ($stmt->execute([$nome, $oggetto, $corpo, $id])) {
                $message = "Template email aggiornato con successo!";
            } else {
                $error = "Errore durante l'aggiornamento del template.";
            }
        } else {
            $error = "Tutti i campi sono obbligatori.";
        }
    }
    
    if (isset($_POST['delete_template'])) {
        $id = $_POST['template_id'];
        $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Template email eliminato con successo!";
        } else {
            $error = "Errore durante l'eliminazione del template.";
        }
    }
}

// Recupera tutti i template
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();

// Recupera template da modificare se richiesto
$edit_template = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_template = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Template Email - CRM</title>
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
            max-width: 1200px;
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
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
            border-radius: 10px;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            border: none;
            border-radius: 10px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #ecf0f1;
            padding: 0.75rem;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .template-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .template-preview {
            background: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            white-space: pre-wrap;
        }
        
        .variable-help {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .variable-help h6 {
            color: #2980b9;
            font-weight: 600;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <h1 class="page-title">
                <i class="fas fa-envelope-open-text me-3"></i>
                Gestione Template Email
            </h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <!-- Form per aggiungere/modificare template -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>
                                <?php echo $edit_template ? 'Modifica Template' : 'Nuovo Template Email'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Guida variabili -->
                            <div class="variable-help">
                                <h6><i class="fas fa-info-circle me-2"></i>Variabili disponibili:</h6>
                                <span class="variable-tag">{nome_cliente}</span>
                                <span class="variable-tag">{cognome_cliente}</span>
                                <span class="variable-tag">{ragione_sociale}</span>
                                <span class="variable-tag">{codice_fiscale}</span>
                                <span class="variable-tag">{partita_iva}</span>
                                <small class="d-block mt-2 text-muted">
                                    Le variabili verranno sostituite automaticamente con i dati del cliente selezionato.
                                </small>
                            </div>
                            
                            <form method="POST">
                                <?php if ($edit_template): ?>
                                    <input type="hidden" name="template_id" value="<?php echo $edit_template['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Template</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo $edit_template ? htmlspecialchars($edit_template['nome']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="oggetto" class="form-label">Oggetto Email</label>
                                    <input type="text" class="form-control" id="oggetto" name="oggetto" 
                                           value="<?php echo $edit_template ? htmlspecialchars($edit_template['oggetto']) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="corpo" class="form-label">Corpo Email</label>
                                    <textarea class="form-control" id="corpo" name="corpo" rows="10" required><?php echo $edit_template ? htmlspecialchars($edit_template['corpo']) : ''; ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <?php if ($edit_template): ?>
                                        <button type="submit" name="update_template" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Aggiorna Template
                                        </button>
                                        <a href="gestione_email_template.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Annulla
                                        </a>
                                    <?php else: ?>
                                        <button type="submit" name="add_template" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Crea Template
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Lista template esistenti -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Template Esistenti (<?php echo count($templates); ?>)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($templates)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Nessun template trovato.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($templates as $template): ?>
                                    <div class="template-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($template['nome']); ?></h6>
                                                <p class="mb-1 text-muted">
                                                    <strong>Oggetto:</strong> <?php echo htmlspecialchars($template['oggetto']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    Creato: <?php echo date('d/m/Y H:i', strtotime($template['data_creazione'])); ?>
                                                </small>
                                            </div>
                                            <div class="btn-group" role="group">
                                                <a href="?action=edit&id=<?php echo $template['id']; ?>" 
                                                   class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Sei sicuro di voler eliminare questo template?');">
                                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                                    <button type="submit" name="delete_template" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Anteprima corpo email -->
                                        <div class="template-preview">
                                            <?php echo nl2br(htmlspecialchars(substr($template['corpo'], 0, 200))); ?>
                                            <?php if (strlen($template['corpo']) > 200): ?>
                                                <span class="text-muted">...</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="email.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>
                    Vai alla Pagina Invio Email
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
