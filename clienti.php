<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

// Mappa campi visualizzabili
$campi_mappa = [
    'Cognome/Ragione sociale' => 'Cognome/Ragione sociale',
    'Nome' => 'Nome',
    'Codice fiscale' => 'Codice fiscale',
    'Partita IVA' => 'Partita IVA',
    'Mail' => 'Mail'
];

// Ricerca
$criteri = [];
$params = [];

if (!empty($_GET['cognome'])) {
    $criteri[] = "`Cognome/Ragione sociale` LIKE ?";
    $params[] = '%' . $_GET['cognome'] . '%';
}
if (!empty($_GET['nome'])) {
    $criteri[] = "`Nome` LIKE ?";
    $params[] = '%' . $_GET['nome'] . '%';
}
if (!empty($_GET['codice_fiscale'])) {
    $criteri[] = "`Codice fiscale` LIKE ?";
    $params[] = '%' . $_GET['codice_fiscale'] . '%';
}
if (!empty($_GET['partita_iva'])) {
    $criteri[] = "`Partita IVA` LIKE ?";
    $params[] = '%' . $_GET['partita_iva'] . '%';
}
if (!empty($_GET['mail'])) {
    $criteri[] = "`Mail` LIKE ?";
    $params[] = '%' . $_GET['mail'] . '%';
	
}

$where = $criteri ? ('WHERE ' . implode(' AND ', $criteri)) : '';
$campi_sql = '`id`, ' . implode(', ', array_map(fn($c) => '`' . $campi_mappa[$c] . '`', array_keys($campi_mappa)));
$stmt = $pdo->prepare("SELECT $campi_sql FROM clienti $where ORDER BY `Cognome/Ragione sociale`");
$stmt->execute($params);
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>📁 Ricerca Clienti</h2>
<form method="get">
    <input type="text" name="cognome" placeholder="Cognome/Ragione Sociale" value="<?= htmlspecialchars($_GET['cognome'] ?? '') ?>">
    <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
    <input type="text" name="codice_fiscale" placeholder="Codice Fiscale" value="<?= htmlspecialchars($_GET['codice_fiscale'] ?? '') ?>">
    <input type="text" name="partita_iva" placeholder="Partita IVA" value="<?= htmlspecialchars($_GET['partita_iva'] ?? '') ?>">
    <input type="text" name="mail" placeholder="Email" value="<?= htmlspecialchars($_GET['mail'] ?? '') ?>">
    <button type="submit">🔍 Cerca</button>
</form>

<p><a href="crea_cliente.php"><button>➕ Crea nuovo cliente</button></a></p>

<?php if (count($clienti) === 0): ?>
    <p>Nessun cliente trovato.</p>
<?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <?php foreach ($campi_mappa as $label => $campo): ?>
                    <th><?= htmlspecialchars($label) ?></th>
                <?php endforeach; ?>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clienti as $c): ?>
            <tr>
                <?php foreach ($campi_mappa as $campo): ?>
                    <td><?= htmlspecialchars($c[$campo]) ?></td>
                <?php endforeach; ?>
                <td>
                    <a href="info_cliente.php?id=<?= $c['id'] ?>">👁️ Visualizza</a> |
                    <a href="modifica_cliente.php?id=<?= $c['id'] ?>">✏️ Modifica</a> |
                    <a href="elimina_cliente.php?id=<?= $c['id'] ?>" onclick="return confirm('Confermi l\'eliminazione di questo cliente?')">🗑️ Elimina</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</main>
</body>
</html>