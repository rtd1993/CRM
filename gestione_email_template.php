<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione semplice
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Connessione database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Crea tabelle se non esistono
try {
    // Tabella template email
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        oggetto VARCHAR(500) NOT NULL,
        corpo TEXT NOT NULL,
        data_creazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Tabella cronologia invii - SEMPLIFICATA per invii multipli
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_cronologia (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT,
        oggetto VARCHAR(500) NOT NULL,
        corpo TEXT NOT NULL,
        destinatari TEXT NOT NULL,  -- Lista email separate da virgola
        totale_destinatari INT DEFAULT 0,
        invii_riusciti INT DEFAULT 0,
        invii_falliti INT DEFAULT 0,
        data_invio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        dettagli_errori TEXT
    )");
    
    // Inserisci template di esempio se non esistono
    $count = $pdo->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO email_templates (nome, oggetto, corpo) VALUES 
        ('Comunicazione Generale', 'Comunicazione importante da AS Contabilmente', 'Gentile Cliente,\n\nSperiamo che tutto proceda al meglio.\n\nCordiali saluti,\nAS Contabilmente'),
        ('Promemoria Scadenze', 'Promemoria scadenze', 'Gentile Cliente,\n\nLe ricordiamo le prossime scadenze.\n\nCordiali saluti,\nAS Contabilmente'),
        ('Richiesta Documenti', 'Richiesta documentazione', 'Gentile Cliente,\n\nAbbiamo bisogno della seguente documentazione.\n\nCordiali saluti,\nAS Contabilmente')");
    }
    
} catch (PDOException $e) {
    die("Errore creazione tabelle: " . $e->getMessage());
}

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

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestione Template Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { margin-top: 20px; }
        .card { margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>CRM - Gestione Email
            </a>
            <div>
                <a href="email_invio.php" class="btn btn-light me-2">
                    <i class="fas fa-paper-plane me-1"></i>Invia Email
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

        <div class="row">
            <!-- Form Nuovo Template -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-plus me-2"></i>Nuovo Template</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nome Template</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Oggetto Email</label>
                                <input type="text" name="oggetto" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Corpo Email</label>
                                <textarea name="corpo" class="form-control" rows="8" required></textarea>
                                <small class="text-muted">
                                    Variabili disponibili: {nome_cliente}, {email_cliente}
                                </small>
                            </div>
                            <button type="submit" name="crea_template" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Crea Template
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Lista Template -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-list me-2"></i>Template Esistenti (<?= count($templates) ?>)</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (empty($templates)): ?>
                            <p class="text-muted">Nessun template trovato.</p>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= htmlspecialchars($template['nome']) ?></h6>
                                            <p class="mb-1 text-muted small">
                                                <strong>Oggetto:</strong> <?= htmlspecialchars($template['oggetto']) ?>
                                            </p>
                                            <small class="text-muted">
                                                Creato: <?= date('d/m/Y H:i', strtotime($template['data_creazione'])) ?>
                                            </small>
                                        </div>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                            <button type="submit" name="elimina_template" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Eliminare questo template?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="mt-2 p-2 bg-white border rounded">
                                        <small><?= nl2br(htmlspecialchars(substr($template['corpo'], 0, 150))) ?>
                                        <?= strlen($template['corpo']) > 150 ? '...' : '' ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
