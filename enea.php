<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Gestione ENEA";

// Gestione azioni
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$success_message = '';
$error_message = '';

// Cancellazione record
if ($action === 'delete' && $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM enea WHERE id = ?");
        $stmt->execute([$id]);
        $success_message = "Record ENEA eliminato con successo!";
    } catch (Exception $e) {
        $error_message = "Errore durante l'eliminazione: " . $e->getMessage();
    }
}

// Filtri di ricerca
$search_cliente = $_GET['search_cliente'] ?? '';
$search_descrizione = $_GET['search_descrizione'] ?? '';
$search_stato = $_GET['search_stato'] ?? '';

// Query base
$where_conditions = [];
$params = [];

if (!empty($search_cliente)) {
    $where_conditions[] = "(c.`Cognome_Ragione_sociale` LIKE ? OR c.`Nome` LIKE ?)";
    $params[] = "%$search_cliente%";
    $params[] = "%$search_cliente%";
}

if (!empty($search_descrizione)) {
    $where_conditions[] = "e.descrizione LIKE ?";
    $params[] = "%$search_descrizione%";
}

if (!empty($search_stato)) {
    $where_conditions[] = "(e.copia_fatt_fornitore = ? OR e.schede_tecniche = ? OR e.visura_catastale = ? OR e.firma_notorio = ? OR e.firma_delega_ag_entr = ? OR e.firma_delega_enea = ? OR e.consenso = ? OR e.ev_atto_notorio = ?)";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $search_stato;
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query per recuperare i record
$sql = "SELECT e.*, CONCAT(c.`Cognome_Ragione_sociale`, ' ', COALESCE(c.`Nome`, '')) as nome_cliente
        FROM enea e 
        LEFT JOIN clienti c ON e.cliente_id = c.id 
        $where_clause
        ORDER BY e.created_at DESC, c.`Cognome_Ragione_sociale`";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-file-contract text-success me-2"></i><?= $page_title ?></h2>
                <a href="crea_enea.php" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>Nuovo Record ENEA
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
                        <div class="col-md-4">
                            <label for="search_descrizione" class="form-label">Descrizione</label>
                            <input type="text" class="form-control" id="search_descrizione" name="search_descrizione" 
                                   value="<?= htmlspecialchars($search_descrizione) ?>" 
                                   placeholder="Descrizione lavoro...">
                        </div>
                        <div class="col-md-2">
                            <label for="search_stato" class="form-label">Stato</label>
                            <select class="form-select" id="search_stato" name="search_stato">
                                <option value="">Tutti gli stati</option>
                                <option value="SI" <?= $search_stato == 'SI' ? 'selected' : '' ?>>Completato</option>
                                <option value="NO" <?= $search_stato == 'NO' ? 'selected' : '' ?>>Non fatto</option>
                                <option value="PENDING" <?= $search_stato == 'PENDING' ? 'selected' : '' ?>>In attesa</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Cerca
                            </button>
                            <a href="enea.php" class="btn btn-secondary">
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
                        <i class="fas fa-list me-2"></i>Record ENEA 
                        <span class="badge bg-primary"><?= count($records) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($records)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nessun record ENEA trovato</h5>
                            <p class="text-muted">Aggiungi il primo record cliccando su "Nuovo Record ENEA"</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Descrizione</th>
                                        <th>Prima Tel.</th>
                                        <th>Richiesta Doc.</th>
                                        <th>Documenti</th>
                                        <th>Stato Completamento</th>
                                        <th width="150">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <?php
                                        // Calcola percentuale completamento
                                        $campi_doc = [
                                            'copia_fatt_fornitore', 'schede_tecniche', 'visura_catastale', 
                                            'firma_notorio', 'firma_delega_ag_entr', 'firma_delega_enea', 
                                            'consenso', 'ev_atto_notorio'
                                        ];
                                        $completati = 0;
                                        foreach ($campi_doc as $campo) {
                                            if ($record[$campo] === 'SI') $completati++;
                                        }
                                        $percentuale = round(($completati / count($campi_doc)) * 100);
                                        ?>
                                        <tr>
                                            <td><strong>#<?= $record['id'] ?></strong></td>
                                            <td>
                                                <a href="info_cliente.php?id=<?= $record['cliente_id'] ?>" 
                                                   class="text-decoration-none">
                                                    <?= htmlspecialchars($record['nome_cliente']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($record['descrizione'] ?? '') ?>">
                                                    <?= htmlspecialchars(substr($record['descrizione'] ?? '', 0, 50)) ?>
                                                    <?= strlen($record['descrizione'] ?? '') > 50 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $record['prima_tel'] ? '<span class="badge bg-info">' . date('d/m/Y', strtotime($record['prima_tel'])) . '</span>' : '-' ?>
                                            </td>
                                            <td>
                                                <?= $record['richiesta_doc'] ? '<span class="badge bg-warning">' . date('d/m/Y', strtotime($record['richiesta_doc'])) . '</span>' : '-' ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach ($campi_doc as $campo): ?>
                                                        <?php
                                                        $badge_class = $record[$campo] === 'SI' ? 'success' : ($record[$campo] === 'NO' ? 'danger' : 'secondary');
                                                        $icon = $record[$campo] === 'SI' ? 'check' : ($record[$campo] === 'NO' ? 'times' : 'clock');
                                                        ?>
                                                        <span class="badge bg-<?= $badge_class ?>" title="<?= ucfirst(str_replace('_', ' ', $campo)) ?>">
                                                            <i class="fas fa-<?= $icon ?>"></i>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?= $percentuale >= 80 ? 'success' : ($percentuale >= 50 ? 'warning' : 'danger') ?>" 
                                                         role="progressbar" style="width: <?= $percentuale ?>%">
                                                        <?= $percentuale ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="modifica_enea.php?id=<?= $record['id'] ?>" 
                                                       class="btn btn-outline-primary" title="Aggiorna pratica">
                                                        <i class="fas fa-edit me-1"></i>Aggiorna pratica
                                                    </a>
                                                    <a href="?action=delete&id=<?= $record['id'] ?>" 
                                                       class="btn btn-outline-danger" title="Elimina pratica"
                                                       onclick="return confirm('Sei sicuro di voler eliminare questo record ENEA?')">
                                                        <i class="fas fa-trash me-1"></i>Elimina pratica
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
    font-size: 0.75rem;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.progress {
    background-color: #e9ecef;
}

.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<?php include 'includes/chat_widget.php'; ?>
<?php include 'includes/chat_pratiche_widget.php'; ?>
