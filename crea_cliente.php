<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Rimuovi "Link cartella" dal form
$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori', 'Titolare'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'Scadenza PEC', 'Rinnovo Pec', 'User Aruba', 'Password'],
    'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d’identità', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l’espatrio'],
    'Fiscali' => ['Codice ditta', 'Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Dipendenti', 'Codice inps', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile', 'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'SDI'],
    'Altro' => ['Colore', 'Inserito gestionale', 'Inizio rapporto', 'Fine rapporto']
];

// Gestione POST e creazione cartella
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campi = [];
    $valori = [];
    foreach ($gruppi as $fields) {
        foreach ($fields as $campo) {
            $campi[] = $campo;
            $valori[] = $_POST[$campo] ?? null;
        }
    }
    $segnaposti = implode(', ', array_fill(0, count($campi), '?'));
    $campi_sql = '`' . implode('`, `', $campi) . '`';

    // Prepara il codice fiscale (usato per la cartella)
    $idx_cf = array_search('Codice fiscale', $campi);
    $codice_fiscale = ($idx_cf !== false) ? trim(strtoupper($valori[$idx_cf])) : '';
    $errore_cf = empty($codice_fiscale);

    // Salva nel database solo se c'è codice fiscale
    if (!$errore_cf) {
        $stmt = $pdo->prepare("INSERT INTO clienti ($campi_sql) VALUES ($segnaposti)");
        if ($stmt->execute($valori)) {
            // Crea cartella con nome = codice fiscale
            $cartella = '/var/www/CRM/local_drive/' . $codice_fiscale;
            if (!is_dir($cartella)) {
                mkdir($cartella, 0775, true);
            }
            header("Location: clienti.php");
            exit;
        } else {
            $errore = "Errore durante l'inserimento.";
        }
    } else {
        $errore = "Il campo Codice fiscale è obbligatorio e serve per creare la cartella.";
    }
}

function campo_input($nome, $type = 'text') {
    return "<div style=\"flex: 1 1 22%; margin-bottom: 15px;\"><label style=\"font-weight:bold;\">$nome:<br><input type=\"$type\" name=\"$nome\" style=\"width:100%; padding: 5px; border: 1px solid #ccc; border-radius: 4px;\"></label></div>";
}
?>

<h2>➕ Crea Nuovo Cliente</h2>

<?php if (!empty($errore)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px;">
        <?= htmlspecialchars($errore) ?>
    </div>
<?php endif; ?>

<form method="post" autocomplete="off">
<?php foreach ($gruppi as $titolo => $campi): ?>
    <fieldset style="margin-bottom: 30px; border: 2px solid #007BFF; padding: 15px; border-radius: 8px;">
        <legend style="font-size: 1.1em; font-weight: bold; color: #007BFF; padding: 0 10px;"><?= htmlspecialchars($titolo) ?></legend>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach ($campi as $campo): ?>
                <?= campo_input($campo) ?>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php endforeach; ?>
    <button type="submit" style="padding: 10px 20px; font-size: 1em; background-color: #28a745; color: white; border: none; border-radius: 5px;">💾 Salva Cliente</button>
</form>

<p><a href="clienti.php">⬅️ Torna alla lista clienti</a></p>

</main>
</body>
</html>