
<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descrizione = $_POST['descrizione'] ?? '';
    $scadenza = $_POST['scadenza'] ?? '';
    $ricorrenza = isset($_POST['ricorrenza']) && $_POST['ricorrenza'] !== '' ? intval($_POST['ricorrenza']) : null;

    if (!empty($descrizione) && !empty($scadenza)) {
        $stmt = $pdo->prepare("INSERT INTO task (descrizione, scadenza, ricorrenza) VALUES (?, ?, ?)");
        $stmt->bindValue(1, $descrizione);
        $stmt->bindValue(2, $scadenza);
        $stmt->bindValue(3, $ricorrenza, is_null($ricorrenza) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        header("Location: task.php");
        exit;
    } else {
        $errore = "Inserisci almeno descrizione e scadenza.";
    }
}
?>

<h2>➕ Crea Nuovo Task</h2>

<?php if (!empty($errore)): ?>
    <p style="color:red;"><?= htmlspecialchars($errore) ?></p>
<?php endif; ?>

<form method="post">
    <label>Descrizione:</label><br>
    <input type="text" name="descrizione" required style="width: 300px;"><br><br>

    <label>Scadenza:</label><br>
    <input type="date" name="scadenza" required><br><br>

    <label>Ricorrenza (giorni):</label><br>
    <input type="number" name="ricorrenza" placeholder="Lascia vuoto se non ricorrente"><br><br>

    <button type="submit">💾 Salva Task</button>
</form>

</main>
</body>
</html>