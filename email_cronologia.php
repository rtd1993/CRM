<?php
// Include l'header del sito (gestisce sessione e autenticazione)
require_once __DIR__ . '/includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
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

<!-- Breadcrumb e Navigazione Email -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
                <div>
                    <h4 class="mb-0 text-primary">
                        <i class="fas fa-history me-2"></i>Cronologia Email
                    </h4>
                    <small class="text-muted">Visualizza lo storico degli invii email</small>
                </div>
                <div>
                    <a href="email_invio.php" class="btn btn-primary me-2">
                        <i class="fas fa-paper-plane me-1"></i>Invia Email
                    </a>
                    <a href="gestione_email_template.php" class="btn btn-outline-primary">
                        <i class="fas fa-cogs me-1"></i>Gestione Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Statistiche -->
    <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; border-radius: 12px;">
                    <i class="fas fa-paper-plane fa-2x mb-3"></i>
                    <h3 class="mb-1"><?= number_format($stats['totale_invii']) ?></h3>
                    <small class="text-light">Invii Totali</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white; border-radius: 12px;">
                    <i class="fas fa-envelope fa-2x mb-3"></i>
                    <h3 class="mb-1"><?= number_format($stats['totale_email']) ?></h3>
                    <small class="text-light">Email Inviate</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; border-radius: 12px;">
                    <i class="fas fa-check-circle fa-2x mb-3"></i>
                    <h3 class="mb-1"><?= number_format($stats['totale_successi']) ?></h3>
                    <small class="text-light">Successi</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; border-radius: 12px;">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h3 class="mb-1"><?= number_format($stats['totale_errori']) ?></h3>
                    <small class="text-light">Errori</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista Cronologia -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);">
            <h5 class="mb-0">
                <i class="fas fa-clock me-2"></i>Cronologia Invii
            </h5>
            <span class="badge bg-white text-dark fs-6"><?= number_format($total) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($cronologia)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">Nessun invio trovato</h4>
                    <p class="text-muted mb-4">Non hai ancora inviato nessuna email</p>
                    <a href="email_invio.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Invia Prima Email
                    </a>
                </div>
            <?php else: ?>
                <div class="p-0">
                    <?php foreach ($cronologia as $index => $invio): ?>
                        <?php
                        $classe_colore = 'success';
                        $border_colore = '#27ae60';
                        if ($invio['invii_falliti'] > 0 && $invio['invii_riusciti'] == 0) {
                            $classe_colore = 'danger';
                            $border_colore = '#e74c3c';
                        } elseif ($invio['invii_falliti'] > 0) {
                            $classe_colore = 'warning';
                            $border_colore = '#f39c12';
                        }
                        ?>
                        <div class="border-bottom p-4 email-item <?= $index % 2 == 0 ? 'bg-light' : 'bg-white' ?>" 
                             style="border-left: 4px solid <?= $border_colore ?> !important;">
                            <div class="row">
                                <div class="col-lg-8 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="mb-0 text-primary fw-bold">
                                            <i class="fas fa-file-alt me-2"></i>
                                            <?= $invio['template_nome'] ? htmlspecialchars($invio['template_nome']) : '<span class="text-muted">Template eliminato</span>' ?>
                                        </h6>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($invio['data_invio'])) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="mb-3 text-dark">
                                        <i class="fas fa-heading me-2 text-muted"></i>
                                        <strong>Oggetto:</strong> <?= htmlspecialchars($invio['oggetto']) ?>
                                    </p>
                                    
                                    <!-- Statistiche invio -->
                                    <div class="row g-2">
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-2 border rounded bg-white">
                                                <div class="fw-bold text-primary"><?= $invio['totale_destinatari'] ?></div>
                                                <small class="text-muted">Totale</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-2 border rounded text-white" style="background: linear-gradient(135deg, #27ae60, #229954);">
                                                <div class="fw-bold"><?= $invio['invii_riusciti'] ?></div>
                                                <small>Successi</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-2 border rounded text-white" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                                                <div class="fw-bold"><?= $invio['invii_falliti'] ?></div>
                                                <small>Errori</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="text-center p-2 border rounded text-white" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                                                <div class="fw-bold"><?= round(($invio['invii_riusciti'] / max(1, $invio['totale_destinatari'])) * 100) ?>%</div>
                                                <small>Successo</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- Destinatari -->
                                    <details class="mb-3">
                                        <summary class="btn btn-outline-primary btn-sm mb-2 w-100">
                                            <i class="fas fa-users me-1"></i>Destinatari (<?= $invio['totale_destinatari'] ?>)
                                        </summary>
                                        <div class="border rounded p-3 bg-white" style="max-height: 200px; overflow-y: auto;">
                                            <?php
                                            $destinatari = explode(', ', $invio['destinatari']);
                                            foreach ($destinatari as $email) {
                                                echo "<div class='small mb-1'><i class='fas fa-envelope me-2 text-primary'></i>" . htmlspecialchars(trim($email)) . "</div>";
                                            }
                                            ?>
                                        </div>
                                    </details>
                                    
                                    <!-- Errori -->
                                    <?php if ($invio['invii_falliti'] > 0 && !empty($invio['dettagli_errori'])): ?>
                                        <details class="mb-3">
                                            <summary class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Errori (<?= $invio['invii_falliti'] ?>)
                                            </summary>
                                            <div class="border rounded p-3 bg-white" style="max-height: 150px; overflow-y: auto;">
                                                <?php
                                                $errori = explode(', ', $invio['dettagli_errori']);
                                                foreach ($errori as $errore) {
                                                    if (trim($errore)) {
                                                        echo "<div class='small mb-1 text-danger'><i class='fas fa-times me-2'></i>" . htmlspecialchars(trim($errore)) . "</div>";
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Contenuto email -->
                            <details class="mt-3">
                                <summary class="text-muted fw-semibold" style="cursor: pointer;">
                                    <i class="fas fa-file-alt me-2"></i>Visualizza contenuto email
                                </summary>
                                <div class="bg-white border rounded p-3 mt-3" style="border-left: 3px solid #3498db !important;">
                                    <div style="font-family: 'Segoe UI', sans-serif; line-height: 1.6;">
                                        <?= nl2br(htmlspecialchars($invio['corpo'])) ?>
                                    </div>
                                </div>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginazione -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-top bg-light">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                                            <i class="fas fa-chevron-left"></i> Precedente
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
                                            Successiva <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.email-item {
    transition: all 0.2s ease;
}
.email-item:hover {
    background-color: #f8f9fa !important;
    transform: translateX(2px);
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    border-bottom: none;
}
details summary {
    transition: all 0.2s ease;
}
details summary:hover {
    background-color: #f8f9fa;
}
.pagination .page-link {
    border-color: #3498db;
    color: #3498db;
}
.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    border-color: #3498db;
}
.pagination .page-link:hover {
    background-color: #e3f2fd;
    border-color: #3498db;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
