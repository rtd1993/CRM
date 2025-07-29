<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = __DIR__ . '/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

// Verifica se è una chiamata modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';

// Sicurezza: assicura che il percorso sia valido e sotto la directory base
if (!$current_path || strpos($current_path, realpath($base_dir)) !== 0) {
    die("Percorso non valido.");
}

// Ricava il path relativo del parent per il redirect
$parent_dir = dirname($current_path);
$relative_parent = ltrim(str_replace(realpath($base_dir), '', $parent_dir), '/');

$errore = '';
$success = false;

// Funzione ricorsiva per cancellare directory e contenuto
function elimina_dir($dir) {
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            elimina_dir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conferma']) && $_POST['conferma'] === '1') {
    if (is_dir($current_path)) {
        elimina_dir($current_path);
        $success = true;
    } else {
        if (unlink($current_path)) {
            $success = true;
        } else {
            $errore = "Errore nell'eliminazione del file.";
        }
    }
    if ($success) {
        if ($is_modal) {
            // Chiudi il modal e ricarica la pagina principale
            echo "<script>
                if (window.parent && window.parent.closeModal) {
                    window.parent.closeModal();
                } else {
                    window.location.href = 'drive.php?path=" . urlencode($relative_parent) . "';
                }
            </script>";
            exit;
        } else {
            header("Location: drive.php?path=" . urlencode($relative_parent));
            exit;
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
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-danger:hover {
    background: #c82333;
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
</style>
<div class="modal-content">
<?php endif; ?>

<h2>🗑️ Elimina</h2>
<p>Sei sicuro di voler eliminare <b><?= htmlspecialchars(basename($current_path)) ?></b>?</p>
<p><i><?= is_dir($current_path) ? 'Questa è una cartella. Verrà eliminata insieme a tutto il suo contenuto.' : 'Questo è un file.' ?></i></p>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="conferma" value="1">
    <button type="submit" class="btn btn-danger">🗑️ Elimina definitivamente</button>
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