<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Gestione Conto Termico";

// Gestione azioni
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$success_message = '';
$error_message = '';

// Cancellazione record
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM conto_termico WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Record eliminato con successo!";
    } catch (Exception $e) {
        $error_message = "Errore durante l'eliminazione: " . $e->getMessage();
    }
}

// Carica lista clienti per il dropdown
$clienti = $pdo->query("SELECT id, CONCAT(`Cognome_Ragione_sociale`, ' ', COALESCE(`Nome`, '')) as nome_completo FROM clienti ORDER BY `Cognome_Ragione_sociale`, `Nome`")->fetchAll();

// Filtri di ricerca
$search_cliente = $_GET['search_cliente'] ?? '';
$search_stato = $_GET['search_stato'] ?? '';
$search_tipo = $_GET['search_tipo'] ?? '';

// Query base
$where_conditions = [];
$params = [];

if (!empty($search_cliente)) {
    $where_conditions[] = "(c.Cognome_Ragione_sociale LIKE ? OR c.Nome LIKE ?)";
    $params[] = "%$search_cliente%";
    $params[] = "%$search_cliente%";
}

if (!empty($search_stato)) {
    $where_conditions[] = "ct.stato = ?";
    $params[] = $search_stato;
}

if (!empty($search_tipo)) {
    $where_conditions[] = "ct.tipo_intervento LIKE ?";
    $params[] = "%$search_tipo%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query per recuperare i record
$sql = "SELECT ct.*, CONCAT(c.Cognome_Ragione_sociale, ' ', COALESCE(c.Nome, '')) as nome_cliente
        FROM conto_termico ct 
        LEFT JOIN clienti c ON ct.cliente_id = c.id 
        $where_clause
        ORDER BY ct.data_presentazione DESC, c.Cognome_Ragione_sociale";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-fire text-danger me-2"></i><?= $page_title ?></h2>
                <a href="crea_conto_termico.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Nuovo Record
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtri di Ricerca -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Filtri di Ricerca</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search_cliente" class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="search_cliente" name="search_cliente" 
                                   value="<?= htmlspecialchars($search_cliente) ?>" 
                                   placeholder="Nome o cognome cliente...">
                        </div>
                        <div class="col-md-3">
                            <label for="search_stato" class="form-label">Stato</label>
                            <select class="form-select" id="search_stato" name="search_stato">
                                <option value="">Tutti gli stati</option>
                                <option value="bozza" <?= $search_stato == 'bozza' ? 'selected' : '' ?>>Bozza</option>
                                <option value="presentata" <?= $search_stato == 'presentata' ? 'selected' : '' ?>>Presentata</option>
                                <option value="istruttoria" <?= $search_stato == 'istruttoria' ? 'selected' : '' ?>>In Istruttoria</option>
                                <option value="accettata" <?= $search_stato == 'accettata' ? 'selected' : '' ?>>Accettata</option>
                                <option value="respinta" <?= $search_stato == 'respinta' ? 'selected' : '' ?>>Respinta</option>
                                <option value="liquidata" <?= $search_stato == 'liquidata' ? 'selected' : '' ?>>Liquidata</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search_tipo" class="form-label">Tipo Intervento</label>
                            <input type="text" class="form-control" id="search_tipo" name="search_tipo" 
                                   value="<?= htmlspecialchars($_GET['search_tipo'] ?? '') ?>" 
                                   placeholder="Tipo intervento...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Cerca
                            </button>
                            <a href="conto_termico.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabella Record -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Record Conto Termico 
                        <span class="badge bg-primary"><?= count($records) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($records)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nessun record trovato</h5>
                            <p class="text-muted">Aggiungi il primo record cliccando su "Nuovo Record"</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Numero Pratica</th>
                                        <th>Data Presentazione</th>
                                        <th>Tipo Intervento</th>
                                        <th>Importo Ammissibile</th>
                                        <th>Contributo</th>
                                        <th>Stato</th>
                                        <th width="150">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><strong>#<?= $record['id'] ?></strong></td>
                                            <td>
                                                <a href="info_cliente.php?id=<?= $record['cliente_id'] ?>" 
                                                   class="text-decoration-none">
                                                    <?= htmlspecialchars($record['nome_cliente']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($record['numero_pratica'] ?? '-') ?></span>
                                            </td>
                                            <td>
                                                <?= $record['data_presentazione'] ? date('d/m/Y', strtotime($record['data_presentazione'])) : '-' ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($record['tipo_intervento'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <?= $record['importo_ammissibile'] ? '€ ' . number_format($record['importo_ammissibile'], 2, ',', '.') : '-' ?>
                                            </td>
                                            <td>
                                                <?= $record['contributo'] ? '€ ' . number_format($record['contributo'], 2, ',', '.') : '-' ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $stato_colors = [
                                                    'bozza' => 'secondary',
                                                    'presentata' => 'primary',
                                                    'istruttoria' => 'warning',
                                                    'accettata' => 'success',
                                                    'respinta' => 'danger',
                                                    'liquidata' => 'info'
                                                ];
                                                $color = $stato_colors[$record['stato']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= ucfirst($record['stato']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="modifica_conto_termico.php?id=<?= $record['id'] ?>" 
                                                       class="btn btn-outline-primary" title="Modifica">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?= $record['id'] ?>" 
                                                       class="btn btn-outline-danger" title="Elimina"
                                                       onclick="return confirm('Sei sicuro di voler eliminare questo record?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
    font-size: 0.9rem;
}

.badge {
    font-size: 0.8rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
</style>

<?php include 'includes/chat_widget.php'; ?>
<?php include 'includes/chat_pratiche_widget.php'; ?>
