<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

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
                header("Location: drive.php?path=" . urlencode($rel_dest));
                exit;
            } else {
                $errore = "Errore durante lo spostamento.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>📂 Sposta <?= $is_dir ? "Cartella" : "File" ?></h2>
<p>Elemento da spostare: <b><?= htmlspecialchars($current_name) ?></b></p>
<p>Percorso attuale: <b><?= htmlspecialchars($current_path) ?></b></p>

<?php if ($errore): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post">
    <label>Seleziona destinazione:</label><br>
    <select name="destinazione" required style="min-width:350px; padding:7px;">
        <option value="<?= htmlspecialchars(realpath($base_dir)) ?>">/ (Root)</option>
        <?php foreach ($dirs as $dir): ?>
            <option value="<?= htmlspecialchars($dir) ?>">
                <?= htmlspecialchars('/' . ltrim(str_replace(realpath($base_dir), '', $dir), '/')) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br><br>
    <button type="submit" style="padding: 8px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px;">Sposta</button>
    <a href="drive.php?path=<?= urlencode($relative_parent) ?>" style="margin-left: 18px;">Annulla</a>
</form>

</main>
</body>
</html>