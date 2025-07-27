<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

// Verifica se è una chiamata modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';

if (!$current_path || strpos($current_path, realpath($base_dir)) !== 0) {
    die("Percorso non valido.");
}

// Calcola percorso del parent e nome corrente
$parent_dir = dirname($current_path);
$current_name = basename($current_path);

// Ricava il path relativo per tornare alla vista dopo la rinomina
$relative_parent = ltrim(str_replace(realpath($base_dir), '', $parent_dir), '/');

// Gestione rinomina
$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuovo_nome'])) {
    $nuovo_nome = trim($_POST['nuovo_nome']);
    if ($nuovo_nome === '' || strpos($nuovo_nome, '..') !== false || strpos($nuovo_nome, '/') !== false) {
        $errore = "Nome non valido.";
    } else {
        $nuovo_percorso = $parent_dir . '/' . $nuovo_nome;
        if (file_exists($nuovo_percorso)) {
            $errore = "Esiste già un file o una cartella con questo nome.";
        } else {
            if (rename($current_path, $nuovo_percorso)) {
                if ($is_modal) {
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
            } else {
                $errore = "Errore durante la rinomina.";
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
input[type="text"] {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    box-sizing: border-box;
}
input[type="text"]:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
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

<h2>✏️ Rinomina</h2>
<div class="path-info">
    <strong>Percorso attuale:</strong> <?= htmlspecialchars($current_path) ?>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <div class="form-group">
        <label>Nuovo nome:</label>
        <input type="text" name="nuovo_nome" required maxlength="128" value="<?= htmlspecialchars($current_name) ?>" autofocus>
    </div>
    
    <button type="submit" class="btn btn-primary">✏️ Rinomina</button>
    <?php if ($is_modal): ?>
        <button type="button" class="btn btn-secondary" onclick="window.parent.closeModal()">❌ Annulla</button>
    <?php else: ?>
        <a href="drive.php?path=<?= urlencode($relative_parent) ?>" class="btn btn-secondary">❌ Annulla</a>
    <?php endif; ?>
</form>

<?php if ($is_modal): ?>
<script>
// Auto-seleziona il nome del file senza estensione per facilità di rinomina
document.addEventListener('DOMContentLoaded', function() {
    const input = document.querySelector('input[name="nuovo_nome"]');
    const value = input.value;
    const lastDot = value.lastIndexOf('.');
    if (lastDot > 0) {
        input.setSelectionRange(0, lastDot);
    } else {
        input.select();
    }
});
</script>
</div>
<?php else: ?>
</main>
</body>
</html>
<?php endif; ?>