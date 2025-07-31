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
    $pdo = new PDO('mysql:host=localhost;dbname=crm;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Errore connessione database: " . $e->getMessage());
}

// Recupera cronologia con paginazione
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$cronologia = $pdo->query("SELECT c.*, t.nome as template_nome FROM email_cronologia c 
                          LEFT JOIN email_templates t ON c.template_id = t.id 
                          ORDER BY c.data_invio DESC 
                          LIMIT $per_page OFFSET $offset")->fetchAll();

$total = $pdo->query("SELECT COUNT(*) FROM email_cronologia")->fetchColumn();
$total_pages = ceil($total / $per_page);

// Statistiche
$stats = $pdo->query("SELECT 
    COUNT(*) as totale_invii,
    SUM(totale_destinatari) as totale_email,
    SUM(invii_riusciti) as totale_successi,
    SUM(invii_falliti) as totale_errori
    FROM email_cronologia")->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cronologia Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .container { margin-top: 20px; }
        .card { margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .stat-card { 
            background: linear-gradient(45deg, #007bff, #0056b3); 
            color: white; 
            text-align: center; 
            padding: 20px;
            border-radius: 10px;
        }
        .email-item {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
        }
        .email-item.success { border-left-color: #28a745; }
        .email-item.warning { border-left-color: #ffc107; }
        .email-item.danger { border-left-color: #dc3545; }
        .destinatari-list {
            max-height: 150px;
            overflow-y: auto;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>CRM - Cronologia Email
            </a>
            <div>
                <a href="email_invio.php" class="btn btn-light me-2">
                    <i class="fas fa-paper-plane me-1"></i>Invia Email
                </a>
                <a href="gestione_email_template.php" class="btn btn-outline-light">
                    <i class="fas fa-cog me-1"></i>Template
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Statistiche -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?= number_format($stats['totale_invii']) ?></h3>
                    <small>Invii Totali</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?= number_format($stats['totale_email']) ?></h3>
                    <small>Email Inviate</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <h3><?= number_format($stats['totale_successi']) ?></h3>
                    <small>Successi</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger">
                    <h3><?= number_format($stats['totale_errori']) ?></h3>
                    <small>Errori</small>
                </div>
            </div>
        </div>

        <!-- Lista Cronologia -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-history me-2"></i>Cronologia Invii (<?= number_format($total) ?> totali)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cronologia)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-4x mb-3"></i>
                        <h4>Nessun invio trovato</h4>
                        <a href="email_invio.php" class="btn btn-primary">Invia prima email</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cronologia as $invio): ?>
                        <?php
                        $classe = 'success';
                        if ($invio['invii_falliti'] > 0 && $invio['invii_riusciti'] == 0) $classe = 'danger';
                        elseif ($invio['invii_falliti'] > 0) $classe = 'warning';
                        ?>
                        <div class="email-item <?= $classe ?>">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="mb-2">
                                        <?= $invio['template_nome'] ? htmlspecialchars($invio['template_nome']) : 'Template eliminato' ?>
                                        <span class="badge bg-secondary ms-2"><?= date('d/m/Y H:i', strtotime($invio['data_invio'])) ?></span>
                                    </h6>
                                    <p class="mb-2"><strong>Oggetto:</strong> <?= htmlspecialchars($invio['oggetto']) ?></p>
                                    
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="border rounded p-2">
                                                <strong><?= $invio['totale_destinatari'] ?></strong><br>
                                                <small class="text-muted">Totale</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="border rounded p-2 bg-success text-white">
                                                <strong><?= $invio['invii_riusciti'] ?></strong><br>
                                                <small>Successi</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="border rounded p-2 bg-danger text-white">
                                                <strong><?= $invio['invii_falliti'] ?></strong><br>
                                                <small>Errori</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="border rounded p-2 bg-info text-white">
                                                <strong><?= round(($invio['invii_riusciti'] / max(1, $invio['totale_destinatari'])) * 100) ?>%</strong><br>
                                                <small>Successo</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <details>
                                        <summary class="btn btn-outline-primary btn-sm mb-2">
                                            <i class="fas fa-eye me-1"></i>Destinatari (<?= $invio['totale_destinatari'] ?>)
                                        </summary>
                                        <div class="destinatari-list">
                                            <?php
                                            $destinatari = explode(', ', $invio['destinatari']);
                                            foreach ($destinatari as $email) {
                                                echo "<div class='small'><i class='fas fa-envelope me-1'></i>" . htmlspecialchars(trim($email)) . "</div>";
                                            }
                                            ?>
                                        </div>
                                    </details>
                                    
                                    <?php if ($invio['invii_falliti'] > 0 && !empty($invio['dettagli_errori'])): ?>
                                        <details>
                                            <summary class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Errori
                                            </summary>
                                            <div class="destinatari-list">
                                                <?php
                                                $errori = explode(', ', $invio['dettagli_errori']);
                                                foreach ($errori as $errore) {
                                                    if (trim($errore)) {
                                                        echo "<div class='small text-danger'><i class='fas fa-times me-1'></i>" . htmlspecialchars(trim($errore)) . "</div>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <details class="mt-3">
                                <summary class="text-muted" style="cursor: pointer;">
                                    <i class="fas fa-file-alt me-1"></i>Contenuto email
                                </summary>
                                <div class="bg-white border rounded p-3 mt-2">
                                    <?= nl2br(htmlspecialchars($invio['corpo'])) ?>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Paginazione -->
                    <?php if ($total_pages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
