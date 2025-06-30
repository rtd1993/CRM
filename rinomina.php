<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_path = realpath($base_dir . $relative_path);

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
                header("Location: drive.php?path=" . urlencode($relative_parent));
                exit;
            } else {
                $errore = "Errore durante la rinomina.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>✏️ Rinomina</h2>
<p>Percorso attuale: <b><?= htmlspecialchars($current_path) ?></b></p>

<?php if ($errore): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <label>Nuovo nome:</label><br>
    <input type="text" name="nuovo_nome" required style="padding: 8px; width: 260px; margin-bottom: 14px;" maxlength="128" value="<?= htmlspecialchars($current_name) ?>"><br>
    <button type="submit" style="padding: 8px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px;">Rinomina</button>
    <a href="drive.php?path=<?= urlencode($relative_parent) ?>" style="margin-left: 18px;">Annulla</a>
</form>

</main>
</body>
</html>