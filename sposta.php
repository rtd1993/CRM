<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = __DIR__ . '/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

// Verifica se è una chiamata modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';

if (!$current_path || strpos($current_path, realpath($base_dir)) !== 0) {
    die("Percorso non valido.");
}

$current_name = basename($current_path);
$is_dir = is_dir($current_path);

// Calcola il path relativo del parent per il redirect
$parent_dir = dirname($current_path);
$relative_parent = ltrim(str_replace(realpath($base_dir), '', $parent_dir), '/');

// Ottieni tutte le directory disponibili per lo spostamento (eccetto se stessi)
function get_directories($base, $exclude_path) {
    $dirs = [];
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($rii as $file) {
        if ($file->isDir()) {
            $real = $file->getRealPath();
            if ($real !== $exclude_path && strpos($real, $exclude_path . DIRECTORY_SEPARATOR) !== 0) {
                // evita di spostare dentro se stesso o sottocartella
                $dirs[] = $real;
            }
        }
    }
    return $dirs;
}

$dirs = get_directories($base_dir, $current_path);

$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['destinazione'])) {
    $destinazione = $_POST['destinazione'];
    $dest_path = realpath($destinazione);

    if (!$dest_path || strpos($dest_path, realpath($base_dir)) !== 0 || !is_dir($dest_path)) {
        $errore = "Destinazione non valida.";
    } else {
        $new_path = $dest_path . DIRECTORY_SEPARATOR . $current_name;
        if (file_exists($new_path)) {
            $errore = "Esiste già un file/cartella con questo nome nella destinazione.";
        } else {
            if (rename($current_path, $new_path)) {
                $rel_dest = ltrim(str_replace(realpath($base_dir), '', $dest_path), '/');
                if ($is_modal) {
                    echo "<script>
                        if (window.parent && window.parent.closeModal) {
                            window.parent.closeModal();
                        } else {
                            window.location.href = 'drive.php?path=" . urlencode($rel_dest) . "';
                        }
                    </script>";
                    exit;
                } else {
                    header("Location: drive.php?path=" . urlencode($rel_dest));
                    exit;
                }
            } else {
                $errore = "Errore durante lo spostamento.";
            }
        }
    }
}

if (!$is_modal) {
    require_once __DIR__ . '/includes/header.php';
}
?>

<?php if ($is_modal): ?>
<style>
body { 
    margin: 0; 
    padding: 2rem; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8f9fa;
}
.modal-content {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.form-group {
    margin-bottom: 1.5rem;
}
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}
select {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    background: white;
}
.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin-right: 1rem;
    transition: all 0.2s ease;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-primary:hover {
    background: #0056b3;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.btn-secondary:hover {
    background: #5a6268;
}
h2 {
    margin-top: 0;
    color: #333;
}
.path-info {
    background: #e9ecef;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-family: monospace;
    font-size: 0.9rem;
}
</style>
<div class="modal-content">
<?php endif; ?>

<h2>📂 Sposta <?= $is_dir ? "Cartella" : "File" ?></h2>
<div class="path-info">
    <strong>Elemento:</strong> <?= htmlspecialchars($current_name) ?><br>
    <strong>Percorso attuale:</strong> <?= htmlspecialchars($current_path) ?>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post">
    <div class="form-group">
        <label>Seleziona destinazione:</label>
        <select name="destinazione" required>
            <option value="<?= htmlspecialchars(realpath($base_dir)) ?>">/ (Root)</option>
            <?php foreach ($dirs as $dir): ?>
                <option value="<?= htmlspecialchars($dir) ?>">
                    <?= htmlspecialchars('/' . ltrim(str_replace(realpath($base_dir), '', $dir), '/')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button type="submit" class="btn btn-primary">📂 Sposta</button>
    <?php if ($is_modal): ?>
        <button type="button" class="btn btn-secondary" onclick="window.parent.closeModal()">❌ Annulla</button>
    <?php else: ?>
        <a href="drive.php?path=<?= urlencode($relative_parent) ?>" class="btn btn-secondary">❌ Annulla</a>
    <?php endif; ?>
</form>

<?php if ($is_modal): ?>
</div>
<?php else: ?>
</main>
</body>
</html>
<?php endif; ?>