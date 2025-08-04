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
$search_anno = $_GET['search_anno'] ?? '';
$search_esito = $_GET['search_esito'] ?? '';

// Query base
$where_conditions = [];
$params = [];

if (!empty($search_cliente)) {
    $where_conditions[] = "c.cognome_ragione_sociale LIKE ? OR c.nome LIKE ?";
    $params[] = "%$search_cliente%";
    $params[] = "%$search_cliente%";
}

if (!empty($search_anno)) {
    $where_conditions[] = "ct.anno = ?";
    $params[] = $search_anno;
}

if (!empty($search_esito)) {
    $where_conditions[] = "ct.esito LIKE ?";
    $params[] = "%$search_esito%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query per recuperare i record
$sql = "SELECT ct.*, CONCAT(c.cognome_ragione_sociale, ' ', COALESCE(c.nome, '')) as nome_cliente
        FROM conto_termico ct 
        LEFT JOIN clienti c ON ct.cliente_id = c.id 
        $where_clause
        ORDER BY ct.anno DESC, c.cognome_ragione_sociale";

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
                            <label for="search_anno" class="form-label">Anno</label>
                            <select class="form-select" id="search_anno" name="search_anno">
                                <option value="">Tutti gli anni</option>
                                <?php for ($anno = date('Y'); $anno >= 2020; $anno--): ?>
                                    <option value="<?= $anno ?>" <?= $search_anno == $anno ? 'selected' : '' ?>>
                                        <?= $anno ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search_esito" class="form-label">Esito</label>
                            <input type="text" class="form-control" id="search_esito" name="search_esito" 
                                   value="<?= htmlspecialchars($search_esito) ?>" 
                                   placeholder="Esito...">
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
                                        <th>Anno</th>
                                        <th>Esito</th>
                                        <th>Prestazione</th>
                                        <th>Incassato</th>
                                        <th>Modello Stufa</th>
                                        <th>Data Termine</th>
                                        <th>Mese</th>
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
                                                <span class="badge bg-info"><?= $record['anno'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($record['esito']): ?>
                                                    <span class="badge bg-<?= strpos(strtolower($record['esito']), 'positiv') !== false ? 'success' : 
                                                        (strpos(strtolower($record['esito']), 'negativ') !== false ? 'danger' : 'warning') ?>">
                                                        <?= htmlspecialchars($record['esito']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= $record['prestazione'] ? '€ ' . number_format($record['prestazione'], 2, ',', '.') : '-' ?>
                                            </td>
                                            <td>
                                                <?= $record['incassato'] ? '€ ' . number_format($record['incassato'], 2, ',', '.') : '-' ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($record['modello_stufa'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <?= $record['data_termine'] ? date('d/m/Y', strtotime($record['data_termine'])) : '-' ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($record['mese'] ?? '-') ?>
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
