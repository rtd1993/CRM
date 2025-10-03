<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$base_dir = __DIR__ . '/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_dir = realpath($base_dir . $relative_path);

if (!$current_dir || strpos($current_dir, realpath($base_dir)) !== 0) {
    $current_dir = $base_dir;
    $relative_path = '';
}

// Percorso attuale (breadcrumb)
$breadcrumbs = [];
$tmp = '';
foreach (explode('/', trim($relative_path, '/')) as $part) {
    if ($part === '') continue;
    $tmp .= '/' . $part;
    $breadcrumbs[] = [
        'name' => $part,
        'path' => ltrim($tmp, '/')
    ];
}

// Ricerca e ordinamento
$search = trim($_GET['search'] ?? '');
$order = $_GET['order'] ?? 'name';

// Leggi contenuto directory
$contents = [];
if ($handle = opendir($current_dir)) {
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if ($search && stripos($entry, $search) === false) continue;
        $full_path = $current_dir . '/' . $entry;
        $is_dir = is_dir($full_path);
        $size = $is_dir ? '' : filesize($full_path);

        // Associa cliente se il nome cartella corrisponde al nuovo formato id_cognome.nome o vecchio CF
        $cliente = '';
        if ($is_dir) {
            // Prova prima con il nuovo formato id_cognome.nome
            if (preg_match('/^(\d+)_/', $entry, $matches)) {
                $cliente_id = $matches[1];
                $stmt = $pdo->prepare("SELECT `Cognome_Ragione_sociale`, `Nome` FROM clienti WHERE id = ?");
                $stmt->execute([$cliente_id]);
                if ($row = $stmt->fetch()) {
                    $cliente = $row['Cognome_Ragione_sociale'] . ' ' . ($row['Nome'] ?? '');
                }
            } else {
                // Fallback: prova con il vecchio formato basato su codice fiscale
                $stmt = $pdo->prepare("SELECT `Cognome_Ragione_sociale`, `Nome` FROM clienti WHERE `Codice_fiscale` = ?");
                $stmt->execute([$entry]);
                if ($row = $stmt->fetch()) {
                    $cliente = $row['Cognome_Ragione_sociale'] . ' ' . ($row['Nome'] ?? '');
                }
            }
        }

        $contents[] = [
            'name' => $entry,
            'is_dir' => $is_dir,
            'size' => $size,
            'cliente' => $cliente,
            'modified' => filemtime($full_path)
        ];
    }
    closedir($handle);
}

// Ordinamento
usort($contents, function($a, $b) use ($order) {
    if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1; // Cartelle prima
    if ($order === 'size') return ($a['size'] ?? 0) <=> ($b['size'] ?? 0);
    if ($order === 'cliente') return strcmp($a['cliente'], $b['cliente']);
    if ($order === 'modified') return $b['modified'] <=> $a['modified'];
    return strcasecmp($a['name'], $b['name']); // Default: nome
});

// Calcola statistiche
$total_files = 0;
$total_dirs = 0;
$total_size = 0;
foreach ($contents as $c) {
    if ($c['is_dir']) {
        $total_dirs++;
    } else {
        $total_files++;
        $total_size += $c['size'];
    }
}
?>

<style>
.drive-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.drive-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.drive-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.breadcrumb {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e1e5e9;
}

.breadcrumb a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb .separator {
    color: #6c757d;
    margin: 0 0.5rem;
}

.breadcrumb .current {
    color: #495057;
    font-weight: 600;
}

.drive-controls {
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

.advanced-filters {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
    animation: slideDown 0.3s ease;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.btn-group {
    display: flex;
    gap: 0.25rem;
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

.drop-zone {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(102, 126, 234, 0.9);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.drop-zone-content {
    background: white;
    padding: 3rem;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}

.drop-zone-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.8;
}

.drop-zone-content h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.drop-zone-content p {
    margin: 0;
    color: #666;
}

.file-row.selected {
    background: #e3f2fd !important;
    border-left: 4px solid #2196f3;
}

.bulk-checkbox {
    transform: scale(1.2);
    margin-right: 0.5rem;
}

.item-checkbox-label {
    font-size: 1.2rem;
    margin-right: 0.5rem;
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

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.form-control {
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    text-align: center;
}

.stat-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.drive-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    overflow: hidden;
}

.drive-table table {
    width: 100%;
    border-collapse: collapse;
}

.drive-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e1e5e9;
}

.drive-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.drive-table tr:hover {
    background: #f8f9fa;
}

/* Grid View Styles */
.drive-grid {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    padding: 2rem;
    display: none;
}

.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.grid-item {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.grid-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #667eea;
}

.grid-item.selected {
    background: #e3f2fd;
    border-color: #2196f3;
    border-width: 2px;
}

.grid-item-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.grid-item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    word-break: break-word;
    line-height: 1.3;
    font-size: 0.9rem;
}

.grid-item-info {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 1rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.25rem;
}

.grid-item-cliente {
    color: #28a745;
    font-weight: 500;
    font-size: 0.75rem;
    margin-bottom: 0.5rem;
}

.grid-item-actions {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    flex-wrap: wrap;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.grid-item:hover .grid-item-actions {
    opacity: 1;
}

.grid-item-checkbox {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    transform: scale(1.2);
    z-index: 10;
}

.grid-item-checkbox-label {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 10;
}

.grid-empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.grid-empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.view-toggle {
    display: flex;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.25rem;
    border: 1px solid #e1e5e9;
}

.view-toggle button {
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #6c757d;
    font-weight: 500;
}

.view-toggle button.active {
    background: white;
    color: #667eea;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.view-toggle button:hover:not(.active) {
    background: #e9ecef;
}

.file-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.file-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
}

.file-link:hover {
    text-decoration: underline;
}

.file-size {
    color: #6c757d;
    font-family: monospace;
    font-size: 0.9rem;
}

.cliente-info {
    color: #28a745;
    font-weight: 500;
    font-size: 0.9rem;
}

.menu-trigger {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    color: #6c757d;
    position: relative;
}

.menu-trigger:hover {
    background: #e9ecef;
    color: #495057;
    transform: scale(1.1);
}

.menu-trigger:active {
    background: #dee2e6;
}

.action-buttons-group {
    display: flex;
    gap: 0.25rem;
    align-items: center;
    flex-wrap: wrap;
}

.action-btn {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0.4rem;
    border-radius: 6px;
    transition: all 0.2s ease;
    color: #6c757d;
    position: relative;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    min-height: 32px;
}

.action-btn:hover {
    background: #e9ecef;
    color: #495057;
    transform: scale(1.1);
    text-decoration: none;
}

.action-btn.danger {
    color: #dc3545;
}

.action-btn.danger:hover {
    background: #f8d7da;
    color: #721c24;
}

.action-btn.primary {
    color: #007bff;
}

.action-btn.primary:hover {
    background: #e3f2fd;
    color: #0056b3;
}

.action-btn.warning {
    color: #ffc107;
}

.action-btn.warning:hover {
    background: #fff3cd;
    color: #856404;
}

.action-btn.success {
    color: #28a745;
}

.action-btn.success:hover {
    background: #d4edda;
    color: #155724;
}

/* Tooltip styles */
.tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    pointer-events: none;
    margin-bottom: 5px;
}

.tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #333;
}

.action-btn:hover .tooltip {
    opacity: 1;
    visibility: visible;
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

.empty-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.empty-action-link {
    background: none;
    border: none;
    color: #667eea;
    text-decoration: underline;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.empty-action-link:hover {
    background: #e3f2fd;
    text-decoration: none;
    transform: translateY(-1px);
}

.empty-action-link:active {
    transform: translateY(0);
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease;
}

.modal-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 800px;
    max-height: 90%;
    display: flex;
    flex-direction: column;
    animation: slideInModal 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e1e5e9;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.3rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    color: #6c757d;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #e9ecef;
    color: #495057;
    transform: scale(1.1);
}

.modal-body {
    flex: 1;
    overflow: hidden;
    border-radius: 0 0 12px 12px;
}

.modal-body iframe {
    width: 100%;
    height: 600px;
    border: none;
    border-radius: 0 0 12px 12px;
}

@keyframes slideInModal {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .modal-container {
        width: 95%;
        max-height: 95%;
    }
    
    .modal-header {
        padding: 1rem;
    }
    
    .modal-body iframe {
        height: 500px;
    }
}
</style>



<div class="breadcrumb">
    <span style="color: #6c757d; font-weight: 500;">📍 Percorso:</span>
    <a href="drive.php">🏠 Root</a>
    <?php foreach ($breadcrumbs as $i => $b): ?>
        <span class="separator">/</span>
        <?php if ($i === count($breadcrumbs) - 1): ?>
            <span class="current"><?= htmlspecialchars($b['name']) ?></span>
        <?php else: ?>
            <a href="drive.php?path=<?= urlencode($b['path']) ?>"><?= htmlspecialchars($b['name']) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>



<div class="drive-controls">
    <form method="get" class="search-form">
        <input type="hidden" name="path" value="<?= htmlspecialchars($relative_path) ?>">
        <div class="search-wrapper">
            <input type="text" 
                   name="search" 
                   class="form-control" 
                   value="<?= htmlspecialchars($search) ?>" 
                   placeholder="🔍 Cerca file o cartella..." 
                   style="min-width: 250px;">
            <select name="order" class="form-control">
                <option value="name"<?= $order==='name'?' selected':'' ?>>📝 Nome</option>
                <option value="cliente"<?= $order==='cliente'?' selected':'' ?>>👤 Cliente</option>
                <option value="size"<?= $order==='size'?' selected':'' ?>>📏 Dimensione</option>
                <option value="modified"<?= $order==='modified'?' selected':'' ?>>🕒 Modificato</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Cerca</button>
        </div>
        
        <!-- Filtri Avanzati -->
        <div class="advanced-filters" id="advanced-filters" style="display: none;">
            <div class="filter-row">
                <select name="file_type" class="form-control">
                    <option value="">Tutti i tipi</option>
                    <option value="pdf">📄 PDF</option>
                    <option value="doc">📝 Documenti</option>
                    <option value="img">🖼️ Immagini</option>
                    <option value="archive">📦 Archivi</option>
                    <option value="folders">📁 Solo Cartelle</option>
                </select>
                <select name="size_filter" class="form-control">
                    <option value="">Tutte le dimensioni</option>
                    <option value="small">< 1MB</option>
                    <option value="medium">1-10MB</option>
                    <option value="large">> 10MB</option>
                </select>
                <input type="date" name="date_from" class="form-control" placeholder="Data da">
                <input type="date" name="date_to" class="form-control" placeholder="Data a">
                <button type="submit" class="btn btn-primary">Applica Filtri</button>
            </div>
        </div>
    </form>
    
    <div class="action-buttons">
        <button type="button" class="btn btn-outline-primary" onclick="toggleAdvancedFilters()">
            🔧 Filtri Avanzati
        </button>
        <button onclick="openModal('Crea Cartella', 'crea_cartella.php?path=<?= urlencode($relative_path) ?>')" class="btn btn-outline-success">
            📁 Crea Cartella
        </button>
        <button onclick="openModal('Upload File', 'upload.php?path=<?= urlencode($relative_path) ?>')" class="btn btn-outline-success">
            ⬆️ Upload File
        </button>
        <div class="btn-group">
            <div class="view-toggle">
                <button type="button" id="table-view-btn" class="active" onclick="setViewMode('table')">
                    📋 Tabella
                </button>
                <button type="button" id="grid-view-btn" onclick="setViewMode('grid')">
                    🔳 Griglia
                </button>
            </div>
            <button type="button" class="btn btn-outline-warning" onclick="toggleBulkActions()">
                ☑️ Selezione Multipla
            </button>
        </div>
    </div>
</div>

<!-- Pannello Azioni Multiple -->
<div id="bulk-actions-panel" class="bulk-actions-panel" style="display: none;">
    <div class="bulk-actions-content">
        <h4>🔧 Azioni Multiple</h4>
        <p>Seleziona elementi e scegli un'azione:</p>
        <div class="bulk-buttons">
            <button type="button" class="btn btn-primary" onclick="bulkDownload()">
                📥 Download ZIP
            </button>
            <button type="button" class="btn btn-warning" onclick="bulkMove()">
                📂 Sposta Selezionati
            </button>
            <button type="button" class="btn btn-danger" onclick="bulkDelete()">
                🗑️ Elimina Selezionati
            </button>
            <button type="button" class="btn btn-secondary" onclick="toggleBulkActions()">
                ❌ Annulla
            </button>
        </div>
        <div id="bulk-selection-info" class="bulk-selection-info">
            <span id="selected-count">0</span> elementi selezionati
        </div>
    </div>
</div>

<!-- Drop Zone per Upload -->
<div id="drop-zone" class="drop-zone" style="display: none;">
    <div class="drop-zone-content">
        <div class="drop-zone-icon">📤</div>
        <h3>Rilascia i file qui</h3>
        <p>per caricarli nella cartella corrente</p>
    </div>
</div>

<div class="drive-table" id="table-view">
    <table>
        <thead>
            <tr>
                <th>
                    <input type="checkbox" id="select-all" class="bulk-checkbox" style="display: none;">
                    <label for="select-all" id="select-all-label" style="display: none; cursor: pointer;">
                        ☑️
                    </label>
                </th>
                <th>📄 Nome</th>
                <th>👤 Cliente</th>
                <th>📏 Dimensione</th>
                <th>🕒 Modificato</th>
                <th>⚙️ Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contents)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <i>📁</i>
                        <h3>Cartella vuota</h3>
                        <p><?= $search ? 'Nessun file corrisponde alla ricerca' : 'Questa cartella non contiene file o sottocartelle' ?></p>
                        <div style="margin-top: 1.5rem;">
                            <p style="color: #6c757d; margin-bottom: 1rem;">Per iniziare, puoi:</p>
                            <div class="empty-actions">
                                <button onclick="openModal('Crea Cartella', 'crea_cartella.php?path=<?= urlencode($relative_path) ?>')" 
                                        class="empty-action-link">
                                    📁 Creare una cartella
                                </button>
                                <span style="color: #6c757d; margin: 0 0.5rem;">oppure</span>
                                <button onclick="openModal('Upload File', 'upload.php?path=<?= urlencode($relative_path) ?>')" 
                                        class="empty-action-link">
                                    ⬆️ Caricare un file
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($contents as $c):
                    $icon = $c['is_dir'] ? '📁' : '📄';
                    $size_str = $c['is_dir'] ? '-' : number_format($c['size']/1024, 1) . ' KB';
                    $modified_str = date('d/m/Y H:i', $c['modified']);
                    
                    // Determina l'icona del file in base all'estensione
                    if (!$c['is_dir']) {
                        $ext = strtolower(pathinfo($c['name'], PATHINFO_EXTENSION));
                        switch ($ext) {
                            case 'pdf': $icon = '📄'; break;
                            case 'doc':
                            case 'docx': $icon = '📝'; break;
                            case 'xls':
                            case 'xlsx': $icon = '📊'; break;
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif': $icon = '🖼️'; break;
                            case 'zip':
                            case 'rar':
                            case '7z': $icon = '📦'; break;
                            case 'txt': $icon = '📃'; break;
                            default: $icon = '📄';
                        }
                    }
                ?>
                    <tr class="file-row">
                        <td>
                            <input type="checkbox" 
                                   class="bulk-checkbox item-checkbox" 
                                   value="<?= htmlspecialchars($c['name']) ?>" 
                                   data-type="<?= $c['is_dir'] ? 'folder' : 'file' ?>"
                                   style="display: none;">
                            <label class="item-checkbox-label" 
                                   style="display: none; cursor: pointer;">
                                ☑️
                            </label>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center;">
                                <span class="file-icon"><?= $icon ?></span>
                                <?php if ($c['is_dir']): ?>
                                    <a href="drive.php?path=<?= urlencode(trim($relative_path . '/' . $c['name'], '/')) ?>" 
                                       class="file-link">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file-link" style="color: #495057;">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($c['cliente']): ?>
                                <span class="cliente-info"><?= htmlspecialchars($c['cliente']) ?></span>
                            <?php else: ?>
                                <span style="color: #6c757d; font-style: italic;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="file-size"><?= $size_str ?></span>
                        </td>
                        <td>
                            <span style="color: #6c757d; font-size: 0.9rem;"><?= $modified_str ?></span>
                        </td>
                        <td>
                            <div class="action-buttons-group">
                                <?php if ($c['is_dir']): ?>
                                    
                                    <button onclick="openModal('Rinomina', 'rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                            class="action-btn warning">
                                        ✏️
                                        <span class="tooltip">Rinomina</span>
                                    </button>
                                    <button onclick="openModal('Sposta', 'sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                            class="action-btn">
                                        📂
                                        <span class="tooltip">Sposta</span>
                                    </button>
                                    <button onclick="confirmDelete('<?= addslashes($c['name']) ?>', '<?= urlencode($relative_path . '/' . $c['name']) ?>', true)" 
                                            class="action-btn danger">
                                        🗑️
                                        <span class="tooltip">Elimina cartella</span>
                                    </button>
                                <?php else: ?>
                                    <button onclick="downloadFile('<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                            class="action-btn primary">
                                        ⬇️
                                        <span class="tooltip">Download file</span>
                                    </button>
                                    <button onclick="openModal('Rinomina File', 'rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                            class="action-btn warning">
                                        ✏️
                                        <span class="tooltip">Rinomina file</span>
                                    </button>
                                    <button onclick="openModal('Sposta File', 'sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                            class="action-btn">
                                        📂
                                        <span class="tooltip">Sposta file</span>
                                    </button>
                                    <button onclick="confirmDelete('<?= addslashes($c['name']) ?>', '<?= urlencode($relative_path . '/' . $c['name']) ?>', false)" 
                                            class="action-btn danger">
                                        🗑️
                                        <span class="tooltip">Elimina file</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Grid View -->
<div class="drive-grid" id="grid-view">
    <div class="grid-container">
        <?php if (empty($contents)): ?>
            <div class="grid-empty-state">
                <i>📁</i>
                <h3>Cartella vuota</h3>
                <p><?= $search ? 'Nessun file corrisponde alla ricerca' : 'Questa cartella non contiene file o sottocartelle' ?></p>
                <div style="margin-top: 1.5rem;">
                    <p style="color: #6c757d; margin-bottom: 1rem;">Per iniziare, puoi:</p>
                    <div class="empty-actions">
                        <button onclick="openModal('Crea Cartella', 'crea_cartella.php?path=<?= urlencode($relative_path) ?>')" 
                                class="empty-action-link">
                            📁 Creare una cartella
                        </button>
                        <span style="color: #6c757d; margin: 0 0.5rem;">oppure</span>
                        <button onclick="openModal('Upload File', 'upload.php?path=<?= urlencode($relative_path) ?>')" 
                                class="empty-action-link">
                            ⬆️ Caricare un file
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($contents as $c):
                $icon = $c['is_dir'] ? '📁' : '📄';
                $size_str = $c['is_dir'] ? '-' : number_format($c['size']/1024, 1) . ' KB';
                $modified_str = date('d/m/Y H:i', $c['modified']);
                
                // Determina l'icona del file in base all'estensione
                if (!$c['is_dir']) {
                    $ext = strtolower(pathinfo($c['name'], PATHINFO_EXTENSION));
                    switch ($ext) {
                        case 'pdf': $icon = '📄'; break;
                        case 'doc':
                        case 'docx': $icon = '📝'; break;
                        case 'xls':
                        case 'xlsx': $icon = '📊'; break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif': $icon = '🖼️'; break;
                        case 'zip':
                        case 'rar':
                        case '7z': $icon = '📦'; break;
                        case 'txt': $icon = '📃'; break;
                        default: $icon = '📄';
                    }
                }
            ?>
                <div class="grid-item" 
                     data-name="<?= htmlspecialchars($c['name']) ?>"
                     data-type="<?= $c['is_dir'] ? 'folder' : 'file' ?>"
                     <?php if ($c['is_dir']): ?>
                         onclick="window.location.href='drive.php?path=<?= urlencode(trim($relative_path . '/' . $c['name'], '/')) ?>'"
                     <?php endif; ?>>
                    
                    <input type="checkbox" 
                           class="bulk-checkbox item-checkbox grid-item-checkbox" 
                           value="<?= htmlspecialchars($c['name']) ?>" 
                           data-type="<?= $c['is_dir'] ? 'folder' : 'file' ?>"
                           style="display: none;"
                           onclick="event.stopPropagation();">
                    <label class="item-checkbox-label grid-item-checkbox-label" 
                           style="display: none; cursor: pointer;"
                           onclick="event.stopPropagation();">
                        ☑️
                    </label>
                    
                    <div class="grid-item-icon"><?= $icon ?></div>
                    
                    <div class="grid-item-name"><?= htmlspecialchars($c['name']) ?></div>
                    
                    <?php if ($c['cliente']): ?>
                        <div class="grid-item-cliente"><?= htmlspecialchars($c['cliente']) ?></div>
                    <?php endif; ?>
                    
                    <div class="grid-item-info">
                        <div><?= $size_str ?></div>
                        <div><?= $modified_str ?></div>
                    </div>
                    
                    <div class="grid-item-actions" onclick="event.stopPropagation();">
                        <?php if ($c['is_dir']): ?>
                            <a href="drive.php?path=<?= urlencode(trim($relative_path . '/' . $c['name'], '/')) ?>" 
                               class="action-btn primary">
                                📂
                                <span class="tooltip">Apri cartella</span>
                            </a>
                            <button onclick="openModal('Crea Cartella', 'crea_cartella.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn success">
                                ➕
                                <span class="tooltip">Crea cartella</span>
                            </button>
                            <button onclick="openModal('Upload File', 'upload.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn success">
                                ⬆️
                                <span class="tooltip">Upload file</span>
                            </button>
                            <button onclick="openModal('Rinomina', 'rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn warning">
                                ✏️
                                <span class="tooltip">Rinomina</span>
                            </button>
                            <button onclick="openModal('Sposta', 'sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn">
                                📂
                                <span class="tooltip">Sposta</span>
                            </button>
                            <button onclick="confirmDelete('<?= addslashes($c['name']) ?>', '<?= urlencode($relative_path . '/' . $c['name']) ?>', true)" 
                                    class="action-btn danger">
                                🗑️
                                <span class="tooltip">Elimina cartella</span>
                            </button>
                        <?php else: ?>
                            <button onclick="downloadFile('<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn primary">
                                ⬇️
                                <span class="tooltip">Download file</span>
                            </button>
                            <button onclick="openModal('Rinomina File', 'rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn warning">
                                ✏️
                                <span class="tooltip">Rinomina file</span>
                            </button>
                            <button onclick="openModal('Sposta File', 'sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>')" 
                                    class="action-btn">
                                📂
                                <span class="tooltip">Sposta file</span>
                            </button>
                            <button onclick="confirmDelete('<?= addslashes($c['name']) ?>', '<?= urlencode($relative_path . '/' . $c['name']) ?>', false)" 
                                    class="action-btn danger">
                                🗑️
                                <span class="tooltip">Elimina file</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Container -->
<div id="modal-overlay" class="modal-overlay" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modal-title">Azione</h3>
            <button class="modal-close" onclick="closeModal()">✖️</button>
        </div>
        <div class="modal-body">
            <iframe id="modal-iframe" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<script>
let viewMode = 'table'; // 'table' or 'grid'

// Toggle filtri avanzati
function toggleAdvancedFilters() {
    const filters = document.getElementById('advanced-filters');
    if (filters.style.display === 'none') {
        filters.style.display = 'block';
    } else {
        filters.style.display = 'none';
    }
}

// Toggle modalità visualizzazione
function toggleViewMode() {
    const icon = document.getElementById('view-mode-icon');
    const table = document.querySelector('.drive-table');
    
    if (viewMode === 'table') {
        viewMode = 'grid';
        icon.textContent = '🔳';
        // Implementa vista griglia (futuro sviluppo)
        showNotification('🔳 Vista griglia in sviluppo!', 'info');
    } else {
        viewMode = 'table';
        icon.textContent = '📋';
        showNotification('📋 Vista tabella attiva', 'info');
    }
}

// Toggle azioni multiple
function toggleBulkActions() {
    const panel = document.getElementById('bulk-actions-panel');
    const checkboxes = document.querySelectorAll('.bulk-checkbox');
    const labels = document.querySelectorAll('.item-checkbox-label, #select-all-label');
    
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
        document.querySelectorAll('.file-row').forEach(row => row.classList.remove('selected'));
        updateSelectedCount();
    }
}

// Gestione selezione multipla
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            const row = cb.closest('.file-row');
            if (this.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        });
        updateSelectedCount();
    });
    
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const row = this.closest('.file-row');
            if (this.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
            
            updateSelectedCount();
            
            // Aggiorna il checkbox "Seleziona tutti"
            const allChecked = Array.from(itemCheckboxes).every(checkbox => checkbox.checked);
            const noneChecked = Array.from(itemCheckboxes).every(checkbox => !checkbox.checked);
            
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = !allChecked && !noneChecked;
        });
    });
});

function updateSelectedCount() {
    const selectedCount = document.querySelectorAll('.item-checkbox:checked').length;
    document.getElementById('selected-count').textContent = selectedCount;
}

// Azioni multiple
function bulkDownload() {
    const selected = getSelectedItems();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un elemento', 'warning');
        return;
    }
    
    if (confirm(`Vuoi scaricare ${selected.length} elementi come archivio ZIP?`)) {
        showNotification('📥 Creazione archivio ZIP in corso...', 'info');
        
        // Crea form per il download ZIP
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'api/bulk_download.php';
        form.target = '_blank';
        
        // Aggiungi percorso corrente
        const pathInput = document.createElement('input');
        pathInput.type = 'hidden';
        pathInput.name = 'current_path';
        pathInput.value = <?= json_encode($relative_path) ?>;
        form.appendChild(pathInput);
        
        // Aggiungi elementi selezionati
        selected.forEach(item => {
            const itemInput = document.createElement('input');
            itemInput.type = 'hidden';
            itemInput.name = 'items[]';
            itemInput.value = item.name;
            form.appendChild(itemInput);
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'types[]';
            typeInput.value = item.type;
            form.appendChild(typeInput);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        setTimeout(() => {
            showNotification('✅ Download avviato!', 'success');
        }, 1000);
    }
}

function bulkMove() {
    const selected = getSelectedItems();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un elemento', 'warning');
        return;
    }
    
    // Apri modal per selezione destinazione
    const itemNames = selected.map(item => item.name).join(', ');
    const title = `Sposta ${selected.length} elementi`;
    const url = `api/bulk_move.php?current_path=${encodeURIComponent(<?= json_encode($relative_path) ?>)}&items=${encodeURIComponent(JSON.stringify(selected))}`;
    openModal(title, url);
}

function bulkDelete() {
    const selected = getSelectedItems();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un elemento', 'warning');
        return;
    }
    
    const itemsList = selected.map(item => `${item.type === 'folder' ? '📁' : '📄'} ${item.name}`).join('\n');
    
    if (confirm(`⚠️ ATTENZIONE: Vuoi eliminare ${selected.length} elementi selezionati?\n\n${itemsList}\n\nQuesta operazione non può essere annullata!`)) {
        showNotification('🗑️ Eliminazione in corso...', 'info');
        
        // Invia richiesta AJAX per eliminazione multipla
        const formData = new FormData();
        formData.append('current_path', <?= json_encode($relative_path) ?>);
        
        selected.forEach(item => {
            formData.append('items[]', item.name);
            formData.append('types[]', item.type);
        });
        
        fetch('api/bulk_delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✅ ${data.deleted} elementi eliminati con successo!`, 'success');
                
                if (data.errors && data.errors.length > 0) {
                    setTimeout(() => {
                        showNotification(`⚠️ ${data.errors.length} errori durante l'eliminazione`, 'warning');
                    }, 2000);
                }
                
                // Ricarica la pagina dopo 3 secondi
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showNotification(`❌ Errore: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Errore durante l\'eliminazione multipla:', error);
            showNotification('❌ Errore di connessione durante l\'eliminazione', 'error');
        });
    }
}

function getSelectedItems() {
    return Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => ({
        name: cb.value,
        type: cb.dataset.type
    }));
}

// Evidenzia risultati di ricerca
<?php if ($search): ?>
document.addEventListener('DOMContentLoaded', function() {
    const searchTerm = '<?= addslashes($search) ?>';
    const fileLinks = document.querySelectorAll('.file-link');
    
    fileLinks.forEach(link => {
        if (link.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            link.innerHTML = link.innerHTML.replace(
                new RegExp(`(${searchTerm})`, 'gi'),
                '<mark style="background: #fff3cd; padding: 0.1rem 0.2rem; border-radius: 3px;">$1</mark>'
            );
        }
    });
});
<?php endif; ?>

// Drag and drop avanzato per upload
document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('.drive-table');
    const dropZone = document.getElementById('drop-zone');
    let dragCounter = 0;
    
    // Gestione drag and drop
    document.addEventListener('dragenter', function(e) {
        e.preventDefault();
        dragCounter++;
        dropZone.style.display = 'flex';
    });
    
    document.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dragCounter--;
        if (dragCounter <= 0) {
            dropZone.style.display = 'none';
        }
    });
    
    document.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    document.addEventListener('drop', function(e) {
        e.preventDefault();
        dragCounter = 0;
        dropZone.style.display = 'none';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Reindirizza alla pagina upload con informazioni sui file
            const currentPath = '<?= addslashes($relative_path) ?>';
            showNotification(`📤 Rilevati ${files.length} file. Reindirizzamento alla pagina upload...`, 'info');
            
            setTimeout(() => {
                window.location.href = `upload.php?path=${encodeURIComponent(currentPath)}`;
            }, 2000);
        }
    });
});

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
        animation: slideInRight 0.3s ease;
        max-width: 400px;
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
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}

// Scorciatoie da tastiera
document.addEventListener('keydown', function(e) {
    // Ctrl+A per selezionare tutto
    if (e.ctrlKey && e.key === 'a') {
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox.style.display !== 'none') {
            e.preventDefault();
            selectAllCheckbox.checked = true;
            selectAllCheckbox.dispatchEvent(new Event('change'));
        }
    }
    
    // Escape per chiudere pannelli
    if (e.key === 'Escape') {
        if (document.getElementById('bulk-actions-panel').style.display === 'block') {
            toggleBulkActions();
        }
        
        if (document.getElementById('advanced-filters').style.display === 'block') {
            toggleAdvancedFilters();
        }
    }
});

// Animazioni CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes fadeInMenu {
        from {
            opacity: 0;
            transform: translateY(-10px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    @keyframes fadeOutLeft {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(-100%);
        }
    }
    
    @keyframes fadeOutScale {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.8);
        }
    }
`;
document.head.appendChild(style);

// Inizializza lo stato
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza la vista di default
    initializeViewMode();
    
    // Event listeners per i bottoni di cambio vista
    document.getElementById('table-view-btn').addEventListener('click', function() {
        showTableView();
    });
    
    document.getElementById('grid-view-btn').addEventListener('click', function() {
        showGridView();
    });
    
    // Event listener per il bottone di selezione multipla
    document.getElementById('select-btn').addEventListener('click', function() {
        toggleSelectionMode();
    });
    
    // Event listener per seleziona tutto
    document.getElementById('select-all').addEventListener('change', function() {
        toggleSelectAll(this.checked);
    });
    
    // Event listeners per checkbox individuali
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkActions();
        });
    });
    
    // Event listeners per le azioni di bulk
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkMoveBtn = document.getElementById('bulk-move-btn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            handleBulkDelete();
        });
    }
    
    if (bulkMoveBtn) {
        bulkMoveBtn.addEventListener('click', function() {
            handleBulkMove();
        });
    }
    
    // Mostra notifica di benvenuto se è la prima volta
    if (localStorage.getItem('driveWelcome') !== 'shown') {
        showNotification('💡 Usa il drag & drop per caricare file velocemente!', 'info');
        localStorage.setItem('driveWelcome', 'shown');
    }
});

// Variabili globali per la vista
let currentView = 'table';
let isSelectionMode = false;

// Funzioni per la gestione della vista
function initializeViewMode() {
    const savedView = localStorage.getItem('drive-view-preference');
    if (savedView === 'grid') {
        showGridView();
    } else {
        showTableView();
    }
}

function showTableView() {
    currentView = 'table';
    document.getElementById('table-view').style.display = 'block';
    document.getElementById('grid-view').style.display = 'none';
    
    // Aggiorna lo stato dei bottoni
    document.getElementById('table-view-btn').classList.add('active');
    document.getElementById('grid-view-btn').classList.remove('active');
    
    // Salva la preferenza nel localStorage
    localStorage.setItem('drive-view-preference', 'table');
}

function showGridView() {
    currentView = 'grid';
    document.getElementById('table-view').style.display = 'none';
    document.getElementById('grid-view').style.display = 'block';
    
    // Aggiorna lo stato dei bottoni
    document.getElementById('table-view-btn').classList.remove('active');
    document.getElementById('grid-view-btn').classList.add('active');
    
    // Salva la preferenza nel localStorage
    localStorage.setItem('drive-view-preference', 'grid');
}

// Funzioni per la selezione multipla
function toggleSelectionMode() {
    isSelectionMode = !isSelectionMode;
    
    const selectBtn = document.getElementById('select-btn');
    const bulkActions = document.getElementById('bulk-actions');
    const checkboxes = document.querySelectorAll('.bulk-checkbox');
    const checkboxLabels = document.querySelectorAll('.item-checkbox-label');
    
    if (isSelectionMode) {
        // Attiva modalità selezione
        selectBtn.classList.add('active');
        selectBtn.innerHTML = '❌ <span class="tooltip">Annulla selezione</span>';
        if (bulkActions) bulkActions.style.display = 'flex';
        
        // Mostra le checkbox
        checkboxes.forEach(cb => cb.style.display = 'block');
        checkboxLabels.forEach(label => label.style.display = 'block');
        
    } else {
        // Disattiva modalità selezione
        selectBtn.classList.remove('active');
        selectBtn.innerHTML = '☑️ <span class="tooltip">Selezione multipla</span>';
        if (bulkActions) bulkActions.style.display = 'none';
        
        // Nascondi le checkbox e resetta le selezioni
        checkboxes.forEach(cb => {
            cb.style.display = 'none';
            cb.checked = false;
        });
        checkboxLabels.forEach(label => label.style.display = 'none');
        
        // Rimuovi selezioni visive
        document.querySelectorAll('.file-row, .grid-item').forEach(item => {
            item.classList.remove('selected');
        });
    }
}

function toggleSelectAll(checked) {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(checkbox => {
        checkbox.checked = checked;
        
        // Aggiorna l'aspetto visivo
        const row = checkbox.closest('.file-row') || checkbox.closest('.grid-item');
        if (row) {
            if (checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        }
    });
    
    updateBulkActions();
}

function updateSelectAllState() {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all');
    
    if (checkedBoxes.length === 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedBoxes.length === itemCheckboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    }
    
    // Aggiorna l'aspetto visivo degli elementi selezionati
    itemCheckboxes.forEach(checkbox => {
        const row = checkbox.closest('.file-row') || checkbox.closest('.grid-item');
        if (row) {
            if (checkbox.checked) {
                row.classList.add('selected');
            } else {
                row.classList.remove('selected');
            }
        }
    });
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkMoveBtn = document.getElementById('bulk-move-btn');
    
    if (bulkDeleteBtn && bulkMoveBtn) {
        if (checkedBoxes.length > 0) {
            bulkDeleteBtn.disabled = false;
            bulkMoveBtn.disabled = false;
            
            // Aggiorna il testo dei bottoni con il numero di elementi selezionati
            bulkDeleteBtn.innerHTML = `🗑️ Elimina (${checkedBoxes.length})`;
            bulkMoveBtn.innerHTML = `📂 Sposta (${checkedBoxes.length})`;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkMoveBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '🗑️ Elimina';
            bulkMoveBtn.innerHTML = '📂 Sposta';
        }
    }
}

// Funzioni per azioni di bulk
function handleBulkDelete() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    const items = Array.from(checkedBoxes).map(cb => cb.value);
    const itemsText = items.join(', ');
    
    if (confirm(`Eliminare definitivamente i seguenti elementi?\n\n${itemsText}`)) {
        // Crea un form e invia i dati
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'elimina.php';
        
        // Aggiungi path corrente
        const pathInput = document.createElement('input');
        pathInput.type = 'hidden';
        pathInput.name = 'current_path';
        pathInput.value = '<?= $relative_path ?>';
        form.appendChild(pathInput);
        
        // Aggiungi elementi da eliminare
        items.forEach(item => {
            const itemInput = document.createElement('input');
            itemInput.type = 'hidden';
            itemInput.name = 'items[]';
            itemInput.value = item;
            form.appendChild(itemInput);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

function handleBulkMove() {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    if (checkedBoxes.length === 0) return;
    
    const items = Array.from(checkedBoxes).map(cb => cb.value);
    
    // Reindirizza alla pagina di spostamento con gli elementi selezionati
    const params = new URLSearchParams();
    params.append('current_path', '<?= $relative_path ?>');
    items.forEach(item => {
        params.append('items[]', item);
    });
    
    window.location.href = 'sposta.php?' + params.toString();
}

// Funzioni per il modal
function openModal(title, url) {
    const modal = document.getElementById('modal-overlay');
    const modalTitle = document.getElementById('modal-title');
    const modalIframe = document.getElementById('modal-iframe');
    
    modalTitle.textContent = title;
    modalIframe.src = url + (url.includes('?') ? '&modal=1' : '?modal=1');
    modal.style.display = 'flex';
    
    // Aggiungi listener per chiudere con ESC
    document.addEventListener('keydown', handleModalKeydown);
}

function closeModal() {
    const modal = document.getElementById('modal-overlay');
    const modalIframe = document.getElementById('modal-iframe');
    
    modal.style.display = 'none';
    modalIframe.src = '';
    
    // Rimuovi listener ESC
    document.removeEventListener('keydown', handleModalKeydown);
    
    // Ricarica la pagina per aggiornare il contenuto
    window.location.reload();
}

function handleModalKeydown(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
}

// Funzione per download diretto
function downloadFile(path) {
    // Usa window.open per mantenere i cookie di sessione
    window.open('download.php?path=' + encodeURIComponent(path), '_blank');
    
    showNotification('📥 Download avviato!', 'success');
}

// Funzione per conferma eliminazione
function confirmDelete(fileName, path, isFolder) {
    const message = isFolder 
        ? `Eliminare la cartella "${fileName}" e tutto il suo contenuto?`
        : `Eliminare definitivamente il file "${fileName}"?`;
    
    if (confirm(`⚠️ ATTENZIONE\n\n${message}\n\nQuesta operazione non può essere annullata!`)) {
        // Mostra indicatore di caricamento
        showNotification('🔄 Eliminazione in corso...', 'info');
        
        // Crea FormData per la richiesta AJAX
        const formData = new FormData();
        formData.append('path', decodeURIComponent(path));
        
        // Invia richiesta AJAX
        fetch('api/elimina_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                
                // Rimuovi l'elemento dalla vista senza ricaricare la pagina
                removeItemFromView(fileName, isFolder);
                
                // Ricarica la pagina dopo 2 secondi per aggiornare le statistiche
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showNotification(`❌ ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Errore durante l\'eliminazione:', error);
            showNotification('❌ Errore di connessione durante l\'eliminazione', 'error');
        });
    }
}

// Funzione per rimuovere visivamente un elemento dalla vista
function removeItemFromView(fileName, isFolder) {
    // Rimuovi dalla vista tabella
    const tableRows = document.querySelectorAll('#table-view .file-row');
    tableRows.forEach(row => {
        const nameCell = row.querySelector('.file-link');
        if (nameCell && nameCell.textContent.trim() === fileName) {
            row.style.animation = 'fadeOutLeft 0.5s ease';
            setTimeout(() => {
                row.remove();
            }, 500);
        }
    });
    
    // Rimuovi dalla vista griglia
    const gridItems = document.querySelectorAll('#grid-view .grid-item');
    gridItems.forEach(item => {
        const nameDiv = item.querySelector('.grid-item-name');
        if (nameDiv && nameDiv.textContent.trim() === fileName) {
            item.style.animation = 'fadeOutScale 0.5s ease';
            setTimeout(() => {
                item.remove();
            }, 500);
        }
    });
    
    // Mostra messaggio se non ci sono più elementi
    setTimeout(() => {
        const remainingTableRows = document.querySelectorAll('#table-view .file-row').length;
        const remainingGridItems = document.querySelectorAll('#grid-view .grid-item').length;
        
        if (remainingTableRows === 0 || remainingGridItems === 0) {
            const emptyMessage = `
                <div class="empty-state-dynamic" style="text-align: center; padding: 3rem; color: #6c757d;">
                    <i style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">📁</i>
                    <h3>Cartella vuota</h3>
                    <p>Tutti gli elementi sono stati eliminati</p>
                </div>
            `;
            
            if (currentView === 'table') {
                const tableBody = document.querySelector('#table-view tbody');
                tableBody.innerHTML = `<tr><td colspan="6">${emptyMessage}</td></tr>`;
            } else {
                const gridContainer = document.querySelector('#grid-view .grid-container');
                gridContainer.innerHTML = `<div class="grid-empty-state">${emptyMessage}</div>`;
            }
        }
    }, 600);
}

// Gestione messaggi dal modal
window.addEventListener('message', function(event) {
    // Verifica che il messaggio provenga dal modal iframe
    if (event.source === document.getElementById('modal-iframe').contentWindow) {
        if (event.data === 'closeModal') {
            closeModal();
        } else if (event.data.type === 'notification') {
            showNotification(event.data.message, event.data.level);
        }
    }
});

// Chiudi modal cliccando sull'overlay
document.addEventListener('DOMContentLoaded', function() {
    const modalOverlay = document.getElementById('modal-overlay');
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>