<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$base_dir = '/var/www/CRM/local_drive/';
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

        // Associa cliente se il nome cartella corrisponde al codice fiscale
        $cliente = '';
        if ($is_dir) {
            $stmt = $pdo->prepare("SELECT `Cognome/Ragione sociale`, `Nome` FROM clienti WHERE `Codice fiscale` = ?");
            $stmt->execute([$entry]);
            if ($row = $stmt->fetch()) {
                $cliente = $row['Cognome/Ragione sociale'] . ' ' . $row['Nome'];
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
    border-radius: 4px;
    transition: background 0.3s ease;
}

.menu-trigger:hover {
    background: #f8f9fa;
}

.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    min-width: 160px;
    z-index: 1000;
}

.dropdown-menu a {
    display: block;
    padding: 0.75rem 1rem;
    color: #495057;
    text-decoration: none;
    transition: background 0.3s ease;
    font-size: 0.9rem;
}

.dropdown-menu a:hover {
    background: #f8f9fa;
}

.dropdown-menu a.danger {
    color: #dc3545;
}

.dropdown-menu a.danger:hover {
    background: #f8d7da;
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

.actions-menu {
    position: relative;
    display: inline-block;
}

@media (max-width: 768px) {
    .drive-header h2 {
        font-size: 2rem;
    }
    
    .drive-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .form-control {
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .drive-table th,
    .drive-table td {
        padding: 0.8rem 0.5rem;
        font-size: 0.9rem;
    }
}
</style>

<div class="drive-header">
    <h2>📂 Drive Documentale</h2>
    <p>Gestione file e documenti clienti</p>
</div>

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

<div class="stats-bar">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?= $total_dirs ?></div>
            <div class="stat-label">Cartelle</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= $total_files ?></div>
            <div class="stat-label">File</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= number_format($total_size / 1024 / 1024, 1) ?></div>
            <div class="stat-label">MB Totali</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= count($contents) ?></div>
            <div class="stat-label">Elementi</div>
        </div>
    </div>
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
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="toggleViewMode()">
                <span id="view-mode-icon">📋</span> Vista
            </button>
            <button type="button" class="btn btn-outline-warning" onclick="toggleBulkActions()">
                ☑️ Selezione Multipla
            </button>
        </div>
        <div class="btn-group">
            <a href="crea_cartella.php?path=<?= urlencode($relative_path) ?>" class="btn btn-primary">📁 Nuova Cartella</a>
            <a href="upload.php?path=<?= urlencode($relative_path) ?>" class="btn btn-success">⬆️ Upload File</a>
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

<div class="drive-table">
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
                        <div style="margin-top: 1rem;">
                            <a href="crea_cartella.php?path=<?= urlencode($relative_path) ?>" class="btn btn-primary">📁 Crea Cartella</a>
                            <a href="upload.php?path=<?= urlencode($relative_path) ?>" class="btn btn-primary">⬆️ Upload File</a>
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
                            <div class="actions-menu">
                                <button type="button" class="menu-trigger" onclick="toggleMenu(this)">⋮</button>
                                <div class="dropdown-menu">
                                    <?php if ($c['is_dir']): ?>
                                        <a href="drive.php?path=<?= urlencode(trim($relative_path . '/' . $c['name'], '/')) ?>">📂 Apri</a>
                                        <a href="crea_cartella.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">➕ Crea Cartella</a>
                                        <a href="upload.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">⬆️ Upload</a>
                                        <a href="rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">✏️ Rinomina</a>
                                        <a href="sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">📂 Sposta</a>
                                        <a href="elimina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" 
                                           class="danger" 
                                           onclick="return confirm('Eliminare la cartella e tutto il suo contenuto?')">�️ Elimina</a>
                                    <?php else: ?>
                                        <a href="download.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">⬇️ Download</a>
                                        <a href="rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">✏️ Rinomina</a>
                                        <a href="sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>">📂 Sposta</a>
                                        <a href="elimina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" 
                                           class="danger" 
                                           onclick="return confirm('Eliminare definitivamente questo file?')">�️ Elimina</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
let viewMode = 'table'; // 'table' or 'grid'

function toggleMenu(btn) {
    // Chiudi altri menu aperti
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        if (menu !== btn.nextElementSibling) {
            menu.style.display = 'none';
        }
    });
    
    // Alterna questo menu
    const menu = btn.nextElementSibling;
    const isVisible = menu.style.display === 'block';
    menu.style.display = isVisible ? 'none' : 'block';
    
    // Chiudi al click fuori
    if (!isVisible) {
        document.addEventListener('click', function handler(e) {
            if (!menu.contains(e.target) && e.target !== btn) {
                menu.style.display = 'none';
                document.removeEventListener('click', handler);
            }
        });
    }
}

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
        // Implementa download ZIP
        showNotification('📥 Funzionalità download ZIP in arrivo!', 'info');
    }
}

function bulkMove() {
    const selected = getSelectedItems();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un elemento', 'warning');
        return;
    }
    
    if (confirm(`Vuoi spostare ${selected.length} elementi selezionati?`)) {
        // Implementa spostamento multiplo
        showNotification('📂 Funzionalità spostamento multiplo in arrivo!', 'info');
    }
}

function bulkDelete() {
    const selected = getSelectedItems();
    if (selected.length === 0) {
        showNotification('⚠️ Seleziona almeno un elemento', 'warning');
        return;
    }
    
    if (confirm(`⚠️ ATTENZIONE: Vuoi eliminare ${selected.length} elementi selezionati?\n\nQuesta operazione non può essere annullata!`)) {
        // Implementa eliminazione multipla
        showNotification('🗑️ Funzionalità eliminazione multipla in arrivo!', 'info');
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
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
        
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
`;
document.head.appendChild(style);

// Inizializza lo stato
document.addEventListener('DOMContentLoaded', function() {
    // Mostra notifica di benvenuto se è la prima volta
    if (localStorage.getItem('driveWelcome') !== 'shown') {
        showNotification('💡 Usa il drag & drop per caricare file velocemente!', 'info');
        localStorage.setItem('driveWelcome', 'shown');
    }
});
</script>

</main>
</body>
</html>