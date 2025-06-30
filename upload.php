<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_dir = realpath($base_dir . $relative_path);

if (!$current_dir || strpos($current_dir, realpath($base_dir)) !== 0) {
    $current_dir = $base_dir;
    $relative_path = '';
}

$errore = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        // Proteggi da nomi pericolosi
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            $errore = "Nome file non valido.";
        } else {
            $target = $current_dir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $success = true;
                header("Location: drive.php?path=" . urlencode(trim($relative_path, '/')));
                exit;
            } else {
                $errore = "Errore nel salvataggio del file.";
            }
        }
    } else {
        $errore = "Errore nell'upload del file.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>⏫ Carica un file</h2>
<p>Percorso attuale: <b><?= htmlspecialchars($current_dir) ?></b></p>

<?php if ($errore): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Scegli file da caricare:</label><br>
    <input type="file" name="file" required style="margin: 10px 0;"><br>
    <button type="submit" style="padding: 8px 20px; background: #28a745; color: #fff; border: none; border-radius: 4px;">Carica</button>
    <a href="drive.php?path=<?= urlencode(trim($relative_path, '/')) ?>" style="margin-left: 18px;">Annulla</a>
</form>

</main>
</body>
</html>