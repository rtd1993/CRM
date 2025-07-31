<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/header.php';

// Parametri per paginazione e filtri
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filter_cliente = $_GET['cliente'] ?? '';
$filter_template = $_GET['template'] ?? '';
$filter_stato = $_GET['stato'] ?? '';
$filter_data_da = $_GET['data_da'] ?? '';
$filter_data_a = $_GET['data_a'] ?? '';

// Costruisci query con filtri
$where_conditions = [];
$params = [];

if (!empty($filter_cliente)) {
    $where_conditions[] = "(c.`Cognome_Ragione_sociale` LIKE ? OR c.Nome LIKE ?)";
    $params[] = "%$filter_cliente%";
    $params[] = "%$filter_cliente%";
}

if (!empty($filter_template)) {
    $where_conditions[] = "et.nome LIKE ?";
    $params[] = "%$filter_template%";
}

if (!empty($filter_stato)) {
    $where_conditions[] = "el.stato = ?";
    $params[] = $filter_stato;
}

if (!empty($filter_data_da)) {
    $where_conditions[] = "DATE(el.data_invio) >= ?";
    $params[] = $filter_data_da;
}

if (!empty($filter_data_a)) {
    $where_conditions[] = "DATE(el.data_invio) <= ?";
    $params[] = $filter_data_a;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Query principale
$sql = "SELECT el.*, 
               c.`Cognome_Ragione_sociale` as ragione_sociale, 
               c.Nome,
               et.nome as template_nome
        FROM email_log el
        LEFT JOIN clienti c ON el.cliente_id = c.id
        LEFT JOIN email_templates et ON el.template_id = et.id
        $where_clause
        ORDER BY el.data_invio DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$emails = $stmt->fetchAll();

// Conta totale per paginazione
$count_sql = "SELECT COUNT(*) 
              FROM email_log el
              LEFT JOIN clienti c ON el.cliente_id = c.id
              LEFT JOIN email_templates et ON el.template_id = et.id
              $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Statistiche
$stats_sql = "SELECT 
                COUNT(*) as totale,
                SUM(CASE WHEN stato = 'inviata' THEN 1 ELSE 0 END) as inviate,
                SUM(CASE WHEN stato = 'fallita' THEN 1 ELSE 0 END) as fallite,
                COUNT(DISTINCT cliente_id) as clienti_unici,
                COUNT(DISTINCT DATE(data_invio)) as giorni_attivi
              FROM email_log el
              LEFT JOIN clienti c ON el.cliente_id = c.id
              LEFT JOIN email_templates et ON el.template_id = et.id
              $where_clause";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Recupera lista clienti e template per i filtri
$clienti_list = $pdo->query("SELECT DISTINCT `Cognome_Ragione_sociale`, Nome FROM clienti WHERE Mail IS NOT NULL AND Mail != '' ORDER BY `Cognome_Ragione_sociale`")->fetchAll();
$templates_list = $pdo->query("SELECT id, nome FROM email_templates ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cronologia Email - CRM</title>
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
        
        .stats-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .stat-item {
            margin: 0 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .filters-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .email-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid transparent;
        }
        
        .email-item.success {
            border-left-color: #27ae60;
        }
        
        .email-item.error {
            border-left-color: #e74c3c;
        }
        
        .email-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-inviata {
            background: #d4edda;
            color: #155724;
        }
        
        .status-fallita {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            border: none;
            border-radius: 8px;
        }
        
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 0.2rem;
            border: none;
            color: #3498db;
        }
        
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
        }
        
        .email-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            max-height: 150px;
            overflow-y: auto;
            font-size: 0.9rem;
        }
        
        .email-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <h1 class="page-title">
                <i class="fas fa-history me-3"></i>
                Cronologia Invii Email
            </h1>
            
            <!-- Statistiche -->
            <div class="stats-card">
                <div class="row">
                    <div class="col">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['totale']); ?></span>
                            <span class="stat-label">Email Totali</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['inviate']); ?></span>
                            <span class="stat-label">Inviate</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['fallite']); ?></span>
                            <span class="stat-label">Fallite</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['clienti_unici']); ?></span>
                            <span class="stat-label">Clienti Raggiunti</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['giorni_attivi']); ?></span>
                            <span class="stat-label">Giorni Attivi</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtri -->
            <div class="filters-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" name="cliente" 
                               value="<?php echo htmlspecialchars($filter_cliente); ?>" 
                               placeholder="Nome o ragione sociale">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Template</label>
                        <select class="form-select" name="template">
                            <option value="">Tutti</option>
                            <?php foreach ($templates_list as $template): ?>
                                <option value="<?php echo htmlspecialchars($template['nome']); ?>"
                                        <?php echo $filter_template === $template['nome'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($template['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stato</label>
                        <select class="form-select" name="stato">
                            <option value="">Tutti</option>
                            <option value="inviata" <?php echo $filter_stato === 'inviata' ? 'selected' : ''; ?>>Inviata</option>
                            <option value="fallita" <?php echo $filter_stato === 'fallita' ? 'selected' : ''; ?>>Fallita</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Da</label>
                        <input type="date" class="form-control" name="data_da" 
                               value="<?php echo htmlspecialchars($filter_data_da); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">A</label>
                        <input type="date" class="form-control" name="data_a" 
                               value="<?php echo htmlspecialchars($filter_data_a); ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <?php if ($filter_cliente || $filter_template || $filter_stato || $filter_data_da || $filter_data_a): ?>
                    <div class="mt-2">
                        <a href="cronologia_email.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Rimuovi filtri
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Lista Email -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($emails)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-4x mb-3"></i>
                            <h4>Nessuna email trovata</h4>
                            <p>Non ci sono email che corrispondono ai criteri di ricerca.</p>
                            <a href="email.php" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Invia prima email
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($emails as $email): ?>
                            <div class="email-item <?php echo $email['stato'] === 'inviata' ? 'success' : 'error'; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php 
                                                    $nome_completo = trim(($email['Nome'] ?? '') . ' ' . ($email['ragione_sociale'] ?? ''));
                                                    echo htmlspecialchars($nome_completo); 
                                                    ?>
                                                </h6>
                                                <div class="email-meta">
                                                    <i class="fas fa-envelope me-1"></i>
                                                    <?php echo htmlspecialchars($email['destinatario_email']); ?>
                                                    
                                                    <?php if ($email['template_nome']): ?>
                                                        | <i class="fas fa-file-alt me-1"></i>
                                                        <?php echo htmlspecialchars($email['template_nome']); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="email-status status-<?php echo $email['stato']; ?>">
                                                    <?php if ($email['stato'] === 'inviata'): ?>
                                                        <i class="fas fa-check me-1"></i>Inviata
                                                    <?php else: ?>
                                                        <i class="fas fa-times me-1"></i>Fallita
                                                    <?php endif; ?>
                                                </span>
                                                <div class="email-meta mt-1">
                                                    <?php echo date('d/m/Y H:i', strtotime($email['data_invio'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <strong>Oggetto:</strong> <?php echo htmlspecialchars($email['oggetto']); ?>
                                        </div>
                                        
                                        <?php if ($email['stato'] === 'fallita' && $email['messaggio_errore']): ?>
                                            <div class="alert alert-danger py-2 mb-2">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <strong>Errore:</strong> <?php echo htmlspecialchars($email['messaggio_errore']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <details>
                                            <summary class="text-muted" style="cursor: pointer;">
                                                <i class="fas fa-eye me-1"></i>Visualizza contenuto email
                                            </summary>
                                            <div class="email-preview">
                                                <?php echo nl2br(htmlspecialchars($email['corpo'])); ?>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Paginazione -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Paginazione email">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            
                            <div class="text-center text-muted">
                                Pagina <?php echo $page; ?> di <?php echo $total_pages; ?> 
                                (<?php echo number_format($total_records); ?> email totali)
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Link alle altre pagine -->
            <div class="text-center mt-4">
                <a href="email.php" class="btn btn-primary me-2">
                    <i class="fas fa-paper-plane me-2"></i>Invia Email
                </a>
                <a href="gestione_email_template.php" class="btn btn-secondary">
                    <i class="fas fa-cog me-2"></i>Gestisci Template
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
