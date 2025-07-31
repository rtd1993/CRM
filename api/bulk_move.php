<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$current_path = $_GET['current_path'] ?? '';
$items_json = $_GET['items'] ?? '[]';
$items = json_decode($items_json, true) ?? [];
$is_modal = isset($_GET['modal']);

$base_dir = __DIR__ . '/../local_drive/';
$current_full_path = realpath($base_dir . $current_path);

// Verifica sicurezza del percorso
if (!$current_full_path || strpos($current_full_path, realpath($base_dir)) !== 0) {
    if ($is_modal) {
        echo "<script>parent.postMessage({'type': 'notification', 'message': 'Percorso non valido', 'level': 'error'}, '*'); parent.postMessage('closeModal', '*');</script>";
        exit;
    }
    header('Location: ../drive.php?error=' . urlencode('Percorso non valido'));
    exit;
}

// Gestione dello spostamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination_path = $_POST['destination'] ?? '';
    $items_to_move = $_POST['items'] ?? [];
    $move_types = $_POST['types'] ?? [];
    
    header('Content-Type: application/json');
    
    if (empty($items_to_move) || empty($destination_path)) {
        echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
        exit;
    }
    
    $dest_full_path = realpath($base_dir . $destination_path);
    
    // Verifica sicurezza destinazione
    if (!$dest_full_path || strpos($dest_full_path, realpath($base_dir)) !== 0) {
        echo json_encode(['success' => false, 'message' => 'Destinazione non valida']);
        exit;
    }
    
    if (!is_dir($dest_full_path)) {
        echo json_encode(['success' => false, 'message' => 'La destinazione non è una cartella valida']);
        exit;
    }
    
    $moved_count = 0;
    $errors = [];
    
    try {
        for ($i = 0; $i < count($items_to_move); $i++) {
            $item_name = $items_to_move[$i];
            $source_path = $current_full_path . '/' . $item_name;
            $dest_path = $dest_full_path . '/' . $item_name;
            
            if (!file_exists($source_path)) {
                $errors[] = "Elemento non trovato: $item_name";
                continue;
            }
            
            if (file_exists($dest_path)) {
                $errors[] = "Elemento già esistente nella destinazione: $item_name";
                continue;
            }
            
            // Verifica che non si stia spostando in una sottocartella di se stesso (per cartelle)
            if (is_dir($source_path) && strpos($dest_full_path, $source_path) === 0) {
                $errors[] = "Impossibile spostare la cartella dentro se stessa: $item_name";
                continue;
            }
            
            if (rename($source_path, $dest_path)) {
                $moved_count++;
                error_log("Spostato: $source_path -> $dest_path");
            } else {
                $errors[] = "Errore spostando: $item_name";
            }
        }
        
        $response = [
            'success' => true,
            'moved' => $moved_count,
            'total' => count($items_to_move),
            'message' => "$moved_count elementi spostati con successo"
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['message'] .= " (" . count($errors) . " errori)";
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Errore bulk move: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Errore durante lo spostamento: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Ottieni lista delle cartelle disponibili
function getFoldersList($dir, $base_dir, $current_path = '') {
    $folders = [];
    
    if ($handle = opendir($dir)) {
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') continue;
            
            $full_path = $dir . '/' . $entry;
            if (is_dir($full_path)) {
                $relative_path = $current_path ? $current_path . '/' . $entry : $entry;
                $folders[] = [
                    'name' => $entry,
                    'path' => $relative_path,
                    'level' => substr_count($relative_path, '/')
                ];
                
                // Aggiungi sottocartelle ricorsivamente (max 3 livelli)
                if (substr_count($relative_path, '/') < 3) {
                    $subfolders = getFoldersList($full_path, $base_dir, $relative_path);
                    $folders = array_merge($folders, $subfolders);
                }
            }
        }
        closedir($handle);
    }
    
    return $folders;
}

$all_folders = getFoldersList($base_dir, $base_dir);

// Raggruppa per percorso di primo livello
$grouped_folders = [];
foreach ($all_folders as $folder) {
    $parts = explode('/', $folder['path']);
    $root = $parts[0];
    if (!isset($grouped_folders[$root])) {
        $grouped_folders[$root] = [];
    }
    $grouped_folders[$root][] = $folder;
}

if (!$is_modal):
    include __DIR__ . '/../includes/header.php';
endif;
?>

<?php if ($is_modal): ?>
<style>
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
</style>
<?php endif; ?>

<style>
.bulk-move-container {
    padding: <?= $is_modal ? '2rem' : '2rem' ?>;
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: <?= $is_modal ? '0' : '12px' ?>;
    <?= $is_modal ? '' : 'box-shadow: 0 4px 20px rgba(0,0,0,0.1);' ?>
}

.move-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e1e5e9;
}

.move-header h2 {
    color: #667eea;
    margin: 0 0 0.5rem 0;
    font-size: 1.8rem;
}

.move-header p {
    color: #6c757d;
    margin: 0;
}

.items-to-move {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.items-to-move h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1.2rem;
}

.items-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.item-tag {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.destination-section {
    margin-bottom: 2rem;
}

.destination-section h3 {
    margin: 0 0 1rem 0;
    color: #495057;
    font-size: 1.2rem;
}

.folder-tree {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    background: white;
}

.folder-item {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid #f1f3f4;
}

.folder-item:hover {
    background: #f8f9fa;
}

.folder-item.selected {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.folder-item.current-folder {
    opacity: 0.5;
    cursor: not-allowed;
    background: #fff3cd;
}

.folder-indent {
    width: 20px;
    text-align: center;
    color: #6c757d;
}

.folder-icon {
    margin-right: 0.5rem;
    font-size: 1.1rem;
}

.folder-name {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.folder-path {
    font-size: 0.8rem;
    color: #6c757d;
    font-family: monospace;
}

.actions-section {
    padding-top: 2rem;
    border-top: 2px solid #e1e5e9;
    display: flex;
    justify-content: <?= $is_modal ? 'flex-end' : 'space-between' ?>;
    gap: 1rem;
    align-items: center;
}

.selected-destination {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    color: #155724;
    font-weight: 500;
    flex: 1;
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

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    max-width: 400px;
    animation: slideInRight 0.3s ease;
}

.notification.success { background: #28a745; }
.notification.error { background: #dc3545; }
.notification.warning { background: #ffc107; color: #212529; }

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
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

.root-folder {
    background: #fff8e1;
    border-left: 4px solid #ff9800;
    font-weight: 600;
}
</style>

<div class="bulk-move-container">
    <div class="move-header">
        <h2>📂 Sposta Elementi</h2>
        <p>Seleziona la cartella di destinazione per spostare gli elementi selezionati</p>
    </div>
    
    <div class="items-to-move">
        <h3>🎯 Elementi da spostare:</h3>
        <div class="items-list">
            <?php foreach ($items as $item): ?>
                <div class="item-tag">
                    <?= $item['type'] === 'folder' ? '📁' : '📄' ?>
                    <?= htmlspecialchars($item['name']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="destination-section">
        <h3>📍 Seleziona destinazione:</h3>
        
        <?php if (empty($all_folders)): ?>
            <div class="empty-state">
                <i>📁</i>
                <h4>Nessuna cartella disponibile</h4>
                <p>Non ci sono cartelle disponibili come destinazione</p>
            </div>
        <?php else: ?>
            <div class="folder-tree">
                <!-- Root folder -->
                <div class="folder-item root-folder" 
                     data-path="" 
                     onclick="selectDestination('', 'Root')"
                     <?= $current_path === '' ? 'title="Cartella corrente - non selezionabile"' : '' ?>>
                    <div class="folder-indent"></div>
                    <span class="folder-icon">🏠</span>
                    <span class="folder-name">Root (Cartella principale)</span>
                    <span class="folder-path">/</span>
                </div>
                
                <?php 
                // Ordina le cartelle alfabeticamente
                ksort($grouped_folders);
                foreach ($grouped_folders as $root_name => $folders): 
                ?>
                    <?php foreach ($folders as $folder): 
                        $is_current = ($folder['path'] === $current_path);
                        $indent_level = $folder['level'];
                    ?>
                        <div class="folder-item <?= $is_current ? 'current-folder' : '' ?>" 
                             data-path="<?= htmlspecialchars($folder['path']) ?>"
                             onclick="<?= $is_current ? '' : 'selectDestination(\'' . addslashes($folder['path']) . '\', \'' . addslashes($folder['name']) . '\')' ?>"
                             <?= $is_current ? 'title="Cartella corrente - non selezionabile"' : '' ?>>
                            
                            <?php for ($i = 0; $i < $indent_level; $i++): ?>
                                <div class="folder-indent">└</div>
                            <?php endfor; ?>
                            
                            <span class="folder-icon">📁</span>
                            <span class="folder-name"><?= htmlspecialchars($folder['name']) ?></span>
                            <span class="folder-path">/<?= htmlspecialchars($folder['path']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="actions-section">
        <?php if (!$is_modal): ?>
            <a href="drive.php?path=<?= urlencode($current_path) ?>" class="btn btn-secondary">
                ❌ Annulla
            </a>
        <?php endif; ?>
        
        <div class="selected-destination" id="selected-destination" style="display: none;">
            📍 Destinazione: <strong id="destination-text">Nessuna selezione</strong>
        </div>
        
        <button type="button" id="move-btn" class="btn btn-primary" disabled onclick="executeMove()">
            📂 Sposta Elementi
        </button>
    </div>
</div>

<script>
let selectedDestination = null;
let selectedDestinationName = null;

function selectDestination(path, name) {
    // Rimuovi selezione precedente
    document.querySelectorAll('.folder-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Se è la cartella corrente, non fare nulla
    if (event.target.closest('.current-folder')) {
        return;
    }
    
    selectedDestination = path;
    selectedDestinationName = name;
    
    // Evidenzia la selezione
    event.target.closest('.folder-item').classList.add('selected');
    
    // Mostra la destinazione selezionata
    const destDiv = document.getElementById('selected-destination');
    const destText = document.getElementById('destination-text');
    destDiv.style.display = 'block';
    destText.textContent = name + (path ? ` (/${path})` : ' (Root)');
    
    // Abilita il bottone di spostamento
    document.getElementById('move-btn').disabled = false;
}

function executeMove() {
    if (!selectedDestination && selectedDestination !== '') {
        showNotification('⚠️ Seleziona una destinazione', 'warning');
        return;
    }
    
    const items = <?= json_encode(array_column($items, 'name')) ?>;
    const types = <?= json_encode(array_column($items, 'type')) ?>;
    
    if (confirm(`Spostare ${items.length} elementi in "${selectedDestinationName}"?`)) {
        showNotification('📂 Spostamento in corso...', 'info');
        
        const formData = new FormData();
        formData.append('destination', selectedDestination);
        
        items.forEach(item => {
            formData.append('items[]', item);
        });
        
        types.forEach(type => {
            formData.append('types[]', type);
        });
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`✅ ${data.message}`, 'success');
                
                <?php if ($is_modal): ?>
                // Invia messaggio al parent e chiudi modal
                setTimeout(() => {
                    parent.postMessage({
                        'type': 'notification', 
                        'message': `✅ ${data.message}`, 
                        'level': 'success'
                    }, '*');
                    parent.postMessage('closeModal', '*');
                }, 2000);
                <?php else: ?>
                // Reindirizza alla cartella originale
                setTimeout(() => {
                    window.location.href = '../drive.php?path=<?= urlencode($current_path) ?>';
                }, 2000);
                <?php endif; ?>
                
            } else {
                showNotification(`❌ ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Errore durante lo spostamento:', error);
            showNotification('❌ Errore di connessione', 'error');
        });
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        document.body.removeChild(notification);
    }, 4000);
}

<?php if ($is_modal): ?>
// Gestione chiusura modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        parent.postMessage('closeModal', '*');
    }
});
<?php endif; ?>
</script>

<?php if (!$is_modal): ?>
</main>
</body>
</html>
<?php endif; ?>
