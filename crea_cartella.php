<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_dir = realpath($base_dir . $relative_path);

// Verifica se è una chiamata modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';

if (!$current_dir || strpos($current_dir, realpath($base_dir)) !== 0) {
    $current_dir = $base_dir;
    $relative_path = '';
}

$errore = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_cartella = trim($_POST['nome_cartella'] ?? '');
    if ($nome_cartella === '' || strpos($nome_cartella, '..') !== false || strpos($nome_cartella, '/') !== false) {
        $errore = "Nome cartella non valido.";
    } else {
        $nuova_cartella = $current_dir . '/' . $nome_cartella;
        if (is_dir($nuova_cartella)) {
            $errore = "La cartella esiste già.";
        } else {
            if (mkdir($nuova_cartella, 0775, true)) {
                $success = true;
                if ($is_modal) {
                    echo "<script>
                        if (window.parent && window.parent.closeModal) {
                            window.parent.closeModal();
                        } else {
                            window.location.href = 'drive.php?path=" . urlencode(trim($relative_path, '/')) . "';
                        }
                    </script>";
                    exit;
                } else {
                    header("Location: drive.php?path=" . urlencode(trim($relative_path, '/')));
                    exit;
                }
            } else {
                $errore = "Errore nella creazione della cartella.";
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
.folder-icon {
    font-size: 3rem;
    text-align: center;
    margin-bottom: 1rem;
    opacity: 0.7;
}
</style>
<div class="modal-content">
<?php endif; ?>

<h2>📁 Crea Nuova Cartella</h2>

<?php if ($is_modal): ?>
<div class="folder-icon">📁</div>
<?php endif; ?>

<div class="path-info">
    <strong>Percorso di destinazione:</strong> <?= htmlspecialchars($current_dir) ?>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <div class="form-group">
        <label>Nome nuova cartella:</label>
        <input type="text" name="nome_cartella" required maxlength="64" placeholder="Inserisci il nome della cartella..." autofocus>
    </div>
    
    <button type="submit" class="btn btn-primary">📁 Crea Cartella</button>
    <?php if ($is_modal): ?>
        <button type="button" class="btn btn-secondary" onclick="window.parent.closeModal()">❌ Annulla</button>
    <?php else: ?>
        <a href="drive.php?path=<?= urlencode(trim($relative_path, '/')) ?>" class="btn btn-secondary">❌ Annulla</a>
    <?php endif; ?>
</form>

<?php if ($is_modal): ?>
<script>
// Auto-focus sul campo nome e gestione Enter
document.addEventListener('DOMContentLoaded', function() {
    const input = document.querySelector('input[name="nome_cartella"]');
    if (input) {
        input.focus();
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