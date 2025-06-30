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
                header("Location: drive.php?path=" . urlencode(trim($relative_path, '/')));
                exit;
            } else {
                $errore = "Errore nella creazione della cartella.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h2>➕ Crea Nuova Cartella</h2>

<p>Percorso attuale: <b><?= htmlspecialchars($current_dir) ?></b></p>

<?php if ($errore): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" autocomplete="off">
    <label>Nome nuova cartella:</label><br>
    <input type="text" name="nome_cartella" required style="padding: 8px; width: 260px; margin-bottom: 14px;" maxlength="64"><br>
    <button type="submit" style="padding: 8px 20px; background: #007bff; color: #fff; border: none; border-radius: 4px;">Crea Cartella</button>
    <a href="drive.php?path=<?= urlencode(trim($relative_path, '/')) ?>" style="margin-left: 18px;">Annulla</a>
</form>

</main>
</body>
</html>