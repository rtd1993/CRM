<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';

// Ricerca rapida
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query base
$sql = "SELECT id, `Cognome/Ragione sociale` AS cognome, `Codice ditta`, Mail, PEC, Telefono, `Data di scadenza`, `Scadenza PEC`, `Codice fiscale` FROM clienti";
$params = [];

// Ricerca
if ($search !== '') {
    $sql .= " WHERE 
        `Cognome/Ragione sociale` LIKE ? OR 
        `Codice ditta` LIKE ? OR
        Mail LIKE ? OR
        PEC LIKE ? OR
        Telefono LIKE ?";
    $wild = "%$search%";
    $params = [$wild, $wild, $wild, $wild, $wild];
}

$sql .= " ORDER BY `Cognome/Ragione sociale` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Per evidenziare documenti in scadenza entro 30 giorni
$oggi = date('Y-m-d');
$entro30 = date('Y-m-d', strtotime('+30 days'));

function has_doc_alert($row, $oggi, $entro30) {
    // Carta d'identità
    if (!empty($row['Data di scadenza']) && $row['Data di scadenza'] <= $entro30) return true;
    // PEC
    if (!empty($row['Scadenza PEC']) && $row['Scadenza PEC'] <= $entro30) return true;
    return false;
}

// Calcola statistiche
$total_clienti = count($clienti);
$clienti_alert = 0;
foreach ($clienti as $c) {
    if (has_doc_alert($c, $oggi, $entro30)) $clienti_alert++;
}
?>

<style>
.clienti-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.clienti-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.clienti-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.clienti-controls {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.search-form {
    flex: 1;
    max-width: 600px;
}

.search-wrapper {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 1rem;
}

.search-input {
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    flex: 1;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.advanced-filters {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-select {
    padding: 0.5rem;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.bulk-actions-panel {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    animation: slideDown 0.3s ease;
}

.bulk-actions-content h4 {
    margin: 0 0 0.5rem 0;
    color: #856404;
}

.bulk-actions-content p {
    margin: 0 0 1rem 0;
    color: #856404;
    font-size: 0.9rem;
}

.bulk-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.bulk-selection-info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    color: #0c5460;
    font-size: 0.9rem;
    font-weight: 500;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
    transform: translateY(-2px);
}

.btn-link {
    background: none;
    color: #667eea;
    text-decoration: none;
    padding: 0.5rem 1rem;
}

.btn-link:hover {
    background: #f8f9fa;
    border-radius: 6px;
}

.stats-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
    text-align: center;
}

.stat-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.stat-number {
    font-size: 2rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 0.5rem;
}

.stat-number.alert {
    color: #dc3545;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.clienti-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    overflow: hidden;
}

.clienti-table table {
    width: 100%;
    border-collapse: collapse;
}

.clienti-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e1e5e9;
    white-space: nowrap;
}

.clienti-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.clienti-table tr:hover {
    background: #f8f9fa;
}

.clienti-table tr.alert {
    border-left: 4px solid #dc3545;
    background: #fff6f6;
}

.cliente-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    font-size: 1.1rem;
}

.cliente-link:hover {
    text-decoration: underline;
}

.alert-icon {
    color: #dc3545;
    margin-left: 0.5rem;
    font-size: 1.2rem;
    cursor: help;
}

.details-btn {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.details-btn:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.table-responsive {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .clienti-header h2 {
        font-size: 2rem;
    }
    
    .clienti-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .search-input {
        min-width: auto;
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .clienti-table th,
    .clienti-table td {
        padding: 0.8rem 0.5rem;
        font-size: 0.9rem;
    }
}
</style>

<div class="clienti-controls">
    <form method="get" class="search-form">
        <div class="search-wrapper">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="🔍 Cerca cliente, mail, telefono, codice ditta..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary">Cerca</button>
            <?php if ($search): ?>
                <a href="clienti.php" class="btn-link">✕ Cancella filtro</a>
            <?php endif; ?>
        </div>
        
        <!-- Filtri Avanzati -->
        <div class="advanced-filters" id="advanced-filters" style="display: none;">
            <div class="filter-row">
                <select name="doc_status" class="filter-select">
                    <option value="">Tutti i documenti</option>
                    <option value="scaduti">Solo scaduti</option>
                    <option value="in_scadenza">In scadenza (30gg)</option>
                    <option value="ok">Documenti OK</option>
                </select>
                <select name="order" class="filter-select">
                    <option value="cognome">Ordina per cognome</option>
                    <option value="scadenza">Ordina per scadenza</option>
                    <option value="data_creazione">Ordina per data creazione</option>
                </select>
                <button type="submit" class="btn btn-primary">Applica Filtri</button>
            </div>
        </div>
    </form>
    
    <div class="action-buttons">
        <button type="button" class="btn btn-outline-primary" onclick="toggleAdvancedFilters()">
            🔧 Filtri Avanzati
        </button>
        <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
            📊 Esporta CSV
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="toggleBulkActions()">
            ☑️ Azioni Multiple
        </button>
        <a href="crea_cliente.php" class="btn btn-success">➕ Nuovo Cliente</a>
    </div>
</div>

<!-- Pannello Azioni Multiple -->
<div id="bulk-actions-panel" class="bulk-actions-panel" style="display: none;">
    <div class="bulk-actions-content">
        <h4>🔧 Azioni Multiple</h4>
        <p>Seleziona i clienti nella tabella e scegli un'azione da eseguire:</p>
        <div class="bulk-buttons">
            <button type="button" class="btn btn-warning" onclick="bulkExport()">
                📤 Esporta Selezionati
            </button>
            <button type="button" class="btn btn-danger" onclick="bulkDelete()">
                🗑️ Elimina Selezionati
            </button>
            <button type="button" class="btn btn-secondary" onclick="toggleBulkActions()">
                ❌ Annulla
            </button>
        </div>
        <div id="bulk-selection-info" class="bulk-selection-info">
            <span id="selected-count">0</span> clienti selezionati
        </div>
    </div>
</div>

<div class="table-responsive">
    <div class="clienti-table">
        <table>
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="select-all" class="bulk-checkbox" style="display: none;">
                        <label for="select-all" id="select-all-label" style="display: none; cursor: pointer;">
                            ☑️ Tutti
                        </label>
                    </th>
                    <th>👤 Cliente</th>
                    <th>🏢 Codice Ditta</th>
                    <th>📧 Email</th>
                    <th>📬 PEC</th>
                    <th>📱 Telefono</th>
                    <th>⚙️ Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clienti)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i>👥</i>
                            <h3>Nessun cliente trovato</h3>
                            <p><?= $search ? 'Prova a modificare i termini di ricerca' : 'Inizia aggiungendo il primo cliente' ?></p>
                            <?php if (!$search): ?>
                                <a href="crea_cliente.php" class="btn btn-success">➕ Crea Primo Cliente</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clienti as $c):
                        $alert = has_doc_alert($c, $oggi, $entro30);
                    ?>
                        <tr class="<?= $alert ? 'alert' : '' ?>">
                            <td>
                                <input type="checkbox" 
                                       class="bulk-checkbox client-checkbox" 
                                       value="<?= $c['id'] ?>" 
                                       style="display: none;">
                                <label class="client-checkbox-label" 
                                       style="display: none; cursor: pointer;">
                                    ☑️
                                </label>
                            </td>
                            <td>
                                <a href="info_cliente.php?id=<?= urlencode($c['id']) ?>" class="cliente-link">
                                    <?= htmlspecialchars($c['cognome']) ?>
                                </a>
                                <?php if ($alert): ?>
                                    <span class="alert-icon" title="⚠️ Documenti in scadenza entro 30 giorni">⚠️</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="background: #f8f9fa; padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.9rem;">
                                    <?= htmlspecialchars($c['Codice ditta']) ?>
                                </code>
                            </td>
                            <td>
                                <?php if (!empty($c['Mail'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($c['Mail']) ?>" 
                                       style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($c['Mail']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-style: italic;">Non disponibile</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['PEC'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($c['PEC']) ?>" 
                                       style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($c['PEC']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-style: italic;">Non disponibile</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['Telefono'])): ?>
                                    <a href="tel:<?= htmlspecialchars($c['Telefono']) ?>" 
                                       style="color: #667eea; text-decoration: none;">
                                        <?= htmlspecialchars($c['Telefono']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-style: italic;">Non disponibile</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="info_cliente.php?id=<?= urlencode($c['id']) ?>" class="details-btn">
                                    📋 Dettagli
                                </a>
                                <?php
                                // Controlla se esiste la cartella del cliente
                                if (!empty($c['Codice fiscale'])) {
                                    $codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $c['Codice fiscale']);
                                    $cartella_path = '/var/www/CRM/local_drive/' . $codice_fiscale_clean;
                                    if (is_dir($cartella_path)) {
                                ?>
                                    <a href="drive.php?path=<?= urlencode($codice_fiscale_clean) ?>" 
                                       class="details-btn" 
                                       style="margin-left: 0.5rem;"
                                       title="Apri cartella cliente">
                                        📁 Cartella
                                    </a>
                                <?php
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Evidenzia risultati di ricerca
<?php if ($search): ?>
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?= addslashes($search) ?>';
    const rows = document.querySelectorAll('.clienti-table tbody tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
                cell.innerHTML = cell.innerHTML.replace(
                    new RegExp(`(${searchTerm})`, 'gi'),
                    '<mark style="background: #fff3cd; padding: 0.1rem 0.2rem; border-radius: 3px;">$1</mark>'
                );
            }
        });
    });
});
<?php endif; ?>

// Toggle filtri avanzati
function toggleAdvancedFilters() {
    const filters = document.getElementById('advanced-filters');
    if (filters.style.display === 'none') {
        filters.style.display = 'block';
    } else {
        filters.style.display = 'none';
    }
}

// Toggle azioni multiple
function toggleBulkActions() {
    const panel = document.getElementById('bulk-actions-panel');
    const checkboxes = document.querySelectorAll('.bulk-checkbox');
    const labels = document.querySelectorAll('.client-checkbox-label, #select-all-label');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        checkboxes.forEach(cb => cb.style.display = 'inline-block');
        labels.forEach(label => label.style.display = 'inline-block');
    } else {
        panel.style.display = 'none';
        checkboxes.forEach(cb => {
            cb.style.display = 'none';
            cb.checked = false;
        });
        labels.forEach(label => label.style.display = 'none');
        updateSelectedCount();
    }
}

// Gestione selezione multipla
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const clientCheckboxes = document.querySelectorAll('.client-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        clientCheckboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });
    
    clientCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelectedCount();
            
            // Aggiorna il checkbox "Seleziona tutti"
            const allChecked = Array.from(clientCheckboxes).every(checkbox => checkbox.checked);
            const noneChecked = Array.from(clientCheckboxes).every(checkbox => !checkbox.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });
});

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.client-checkbox:checked').length;
    document.getElementById('selected-count').textContent = selectedCount;
}

// Esporta in CSV
function exportToCSV() {
    const rows = [];
    const headers = ['Cognome/Ragione Sociale', 'Codice Ditta', 'Email', 'PEC', 'Telefono'];
    rows.push(headers.join(','));
    
    document.querySelectorAll('.clienti-table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > 1) { // Skip empty state row
            const rowData = [];
            rowData.push('"' + cells[1].textContent.trim() + '"'); // Cliente
            rowData.push('"' + cells[2].textContent.trim() + '"'); // Codice Ditta
            rowData.push('"' + cells[3].textContent.trim() + '"'); // Email
            rowData.push('"' + cells[4].textContent.trim() + '"'); // PEC
            rowData.push('"' + cells[5].textContent.trim() + '"'); // Telefono
            rows.push(rowData.join(','));
        }
    });
    
    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'clienti_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Notifica successo
        showNotification('📊 File CSV esportato con successo!', 'success');
    }
}

// Azioni multiple
function bulkExport() {
    const selected = getSelectedClients();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un cliente', 'warning');
        return;
    }
    
    // Esporta solo i clienti selezionati
    const rows = [];
    const headers = ['Cognome/Ragione Sociale', 'Codice Ditta', 'Email', 'PEC', 'Telefono'];
    rows.push(headers.join(','));
    
    selected.forEach(id => {
        const row = document.querySelector(`input[value="${id}"]`).closest('tr');
        const cells = row.querySelectorAll('td');
        const rowData = [];
        rowData.push('"' + cells[1].textContent.trim() + '"');
        rowData.push('"' + cells[2].textContent.trim() + '"');
        rowData.push('"' + cells[3].textContent.trim() + '"');
        rowData.push('"' + cells[4].textContent.trim() + '"');
        rowData.push('"' + cells[5].textContent.trim() + '"');
        rows.push(rowData.join(','));
    });
    
    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'clienti_selezionati_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showNotification(`📊 Esportati ${selected.length} clienti selezionati!`, 'success');
    }
}

function bulkDelete() {
    const selected = getSelectedClients();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un cliente', 'warning');
        return;
    }
    
    if (confirm(`⚠️ ATTENZIONE: Vuoi eliminare ${selected.length} clienti selezionati?\n\nQuesta operazione non può essere annullata!`)) {
        // Qui potresti implementare la logica per eliminazioni multiple
        showNotification('🗑️ Funzionalità in arrivo!', 'info');
    }
}

function getSelectedClients() {
    return Array.from(document.querySelectorAll('.client-checkbox:checked')).map(cb => cb.value);
}

// Sistema di notifiche
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    switch (type) {
        case 'success':
            notification.style.background = '#28a745';
            break;
        case 'warning':
            notification.style.background = '#ffc107';
            notification.style.color = '#212529';
            break;
        case 'error':
            notification.style.background = '#dc3545';
            break;
        default:
            notification.style.background = '#17a2b8';
    }
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Animazioni CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .bulk-checkbox {
        transform: scale(1.2);
        margin-right: 0.5rem;
    }
    
    .client-checkbox-label {
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }
`;
document.head.appendChild(style);

// Auto-refresh ogni 5 minuti per aggiornare alert documenti
setTimeout(() => {
    location.reload();
}, 300000);
</script>

</main>
</body>
</html>