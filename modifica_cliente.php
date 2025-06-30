<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID cliente non valido.</p></main></body></html>";
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<p>Cliente non trovato.</p></main></body></html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campi = array_keys($_POST);
    $valori = array_map(fn($c) => $_POST[$c] ?? null, $campi);
    $update_sql = implode(', ', array_map(fn($c) => "`$c` = ?", $campi));

    $stmt = $pdo->prepare("UPDATE clienti SET $update_sql WHERE id = ?");
    if ($stmt->execute([...$valori, $id])) {
        header("Location: info_cliente.php?id=$id");
        exit;
    } else {
        echo "<p style='color:red;'>Errore durante l'aggiornamento.</p>";
    }
}

function campo_input($nome, $valore, $type = 'text') {
    return "<div style=\"flex: 1 1 22%; margin-bottom: 15px;\"><label style=\"font-weight:bold;\">$nome:<br><input type=\"$type\" name=\"$nome\" value=\"" . htmlspecialchars($valore) . "\" style=\"width:100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;\"></label></div>";
}

$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori', 'Titolare'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'Scadenza PEC', 'Rinnovo Pec', 'User Aruba', 'Password'],
    'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d’identità', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l’espatrio'],
    'Fiscali' => ['Codice ditta', 'Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Dipendenti', 'Codice inps', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile', 'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'SDI'],
    'Altro' => ['Colore', 'Inserito gestionale', 'Inizio rapporto', 'Fine rapporto', 'Link cartella']
];
?>

<h2>✏️ Modifica Cliente</h2>

<form method="post">
<?php foreach ($gruppi as $titolo => $campi): ?>
    <fieldset style="margin-bottom: 30px; border: 2px solid #007BFF; padding: 15px; border-radius: 8px;">
        <legend style="font-size: 1.1em; font-weight: bold; color: #007BFF; padding: 0 10px;"><?= htmlspecialchars($titolo) ?></legend>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach ($campi as $campo): ?>
                <?= campo_input($campo, $cliente[$campo] ?? '') ?>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php endforeach; ?>
    <button type="submit" style="padding: 10px 20px; font-size: 1em; background-color: #007BFF; color: white; border: none; border-radius: 5px;">💾 Salva Modifiche</button>
</form>

<p><a href="info_cliente.php?id=<?= $id ?>">⬅️ Torna ai dettagli cliente</a></p>

</main>
</body>
</html>