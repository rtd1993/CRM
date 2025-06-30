<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

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
        header("Location: drive.php?path=" . urlencode($relative_parent));
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>🗑️ Elimina</h2>
<p>Sei sicuro di voler eliminare <b><?= htmlspecialchars(basename($current_path)) ?></b>?</p>
<p><i><?= is_dir($current_path) ? 'Questa è una cartella. Verrà eliminata insieme a tutto il suo contenuto.' : 'Questo è un file.' ?></i></p>

<?php if ($errore): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="conferma" value="1">
    <button type="submit" style="padding: 8px 20px; background: #d9534f; color: #fff; border: none; border-radius: 4px;">Elimina definitivamente</button>
    <a href="drive.php?path=<?= urlencode($relative_parent) ?>" style="margin-left: 18px;">Annulla</a>
</form>

</main>
</body>
</html>