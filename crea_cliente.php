<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal form
    $cognome = trim($_POST['cognome'] ?? '');
    $codice_fiscale = strtoupper(trim($_POST['codice_fiscale'] ?? ''));
    $codice_ditta = trim($_POST['codice_ditta'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $pec = trim($_POST['pec'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    // ... altri campi se vuoi aggiungerli

    // Validazione base
    if ($cognome === '') $errors[] = 'Il campo Cognome/Ragione sociale è obbligatorio.';
    if ($codice_fiscale === '') $errors[] = 'Il campo Codice Fiscale è obbligatorio.';

    if (empty($errors)) {
        // Salva nel database
        $stmt = $pdo->prepare("INSERT INTO clienti (`Cognome/Ragione sociale`, `Codice fiscale`, `Codice ditta`, Mail, PEC, Telefono)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$cognome, $codice_fiscale, $codice_ditta, $mail, $pec, $telefono]);

        // Crea la cartella in /var/www/CRM/local_drive/NOME_CARTELLA
        $cartella_base = '/var/www/CRM/local_drive/';
        $path_cartella = $cartella_base . $codice_fiscale;

        if (!is_dir($path_cartella)) {
            mkdir($path_cartella, 0775, true);
            // Puoi aggiungere qui eventuali permessi/gruppi se necessario
        }

        $success = true;
    }
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<h2>Nuovo Cliente</h2>

<?php if ($success): ?>
    <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
        Cliente creato con successo! <a href="clienti.php" style="color: #155724; text-decoration: underline;">Torna all’elenco clienti</a>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
        <ul style="margin:0; padding-left: 22px;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$success): ?>
<form method="post" autocomplete="off">
    <div style="margin-bottom: 12px;">
        <label>Cognome / Ragione sociale*</label><br>
        <input type="text" name="cognome" value="<?= isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : '' ?>" required style="width: 320px;">
    </div>
    <div style="margin-bottom: 12px;">
        <label>Codice Fiscale*</label><br>
        <input type="text" name="codice_fiscale" value="<?= isset($_POST['codice_fiscale']) ? htmlspecialchars($_POST['codice_fiscale']) : '' ?>" required style="width: 220px;">
    </div>
    <div style="margin-bottom: 12px;">
        <label>Codice Ditta</label><br>
        <input type="text" name="codice_ditta" value="<?= isset($_POST['codice_ditta']) ? htmlspecialchars($_POST['codice_ditta']) : '' ?>" style="width: 220px;">
    </div>
    <div style="margin-bottom: 12px;">
        <label>Email</label><br>
        <input type="email" name="mail" value="<?= isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : '' ?>" style="width: 320px;">
    </div>
    <div style="margin-bottom: 12px;">
        <label>PEC</label><br>
        <input type="text" name="pec" value="<?= isset($_POST['pec']) ? htmlspecialchars($_POST['pec']) : '' ?>" style="width: 320px;">
    </div>
    <div style="margin-bottom: 12px;">
        <label>Telefono</label><br>
        <input type="text" name="telefono" value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" style="width: 220px;">
    </div>
    <!-- altri campi se necessario -->

    <button type="submit" style="padding: 9px 26px; background: #007bff; color: #fff; border: none; border-radius: 4px;">Salva Cliente</button>
</form>
<?php endif; ?>

</main>
</body>
</html>