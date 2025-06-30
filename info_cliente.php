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

function format_label($label) {
    $label = str_replace('_', ' ', $label);
    $label = str_replace('/', ' / ', $label);
    return ucwords($label);
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

<h2>👤 Informazioni Cliente</h2>

<?php foreach ($gruppi as $titolo => $campi): ?>
    <fieldset style="margin-bottom: 30px; border: 2px solid #007BFF; padding: 15px; border-radius: 8px;">
        <legend style="font-size: 1.1em; font-weight: bold; color: #007BFF; padding: 0 10px;"><?= htmlspecialchars($titolo) ?></legend>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach ($campi as $campo): ?>
                <?php if (isset($cliente[$campo])): ?>
                    <div style="flex: 1 1 22%; margin-bottom: 15px;">
                        <strong><?= format_label($campo) ?>:</strong><br>
                        <div style="padding: 5px; border: 1px solid #ccc; border-radius: 4px; background: #f9f9f9;">
                            <?= htmlspecialchars($cliente[$campo]) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </fieldset>
<?php endforeach; ?>

<p>
    <a href="modifica_cliente.php?id=<?= $cliente['id'] ?>">
        <button style="padding: 10px 20px; background: #007BFF; color: white; border: none; border-radius: 5px;">✏️ Modifica Cliente</button>
    </a>
    <button onclick="window.print()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px;">🖨️ Stampa PDF</button>
</p>

<p><a href="clienti.php">⬅️ Torna alla lista clienti</a></p>

</main>
</body>
</html>
