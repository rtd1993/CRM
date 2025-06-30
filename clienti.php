<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';

// Ricerca rapida
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query base
$sql = "SELECT id, `Cognome/Ragione sociale` AS cognome, `Codice ditta`, Mail, PEC, Telefono, `Data di scadenza`, `Scadenza PEC` FROM clienti";
$params = [];

// Ricerca
if ($search !== '') {
    $sql .= " WHERE 
        `Cognome/Ragione sociale` LIKE ? OR 
        `Codice ditta` LIKE ? OR
        Mail LIKE ? OR
        PEC LIKE ? OR
        Telefono LIKE ?";
    $wild = "%$search%";
    $params = [$wild, $wild, $wild, $wild, $wild];
}

$sql .= " ORDER BY `Cognome/Ragione sociale` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Per evidenziare documenti in scadenza entro 30 giorni
$oggi = date('Y-m-d');
$entro30 = date('Y-m-d', strtotime('+30 days'));

function has_doc_alert($row, $oggi, $entro30) {
    // Carta d'identità
    if (!empty($row['Data di scadenza']) && $row['Data di scadenza'] <= $entro30) return true;
    // PEC
    if (!empty($row['Scadenza PEC']) && $row['Scadenza PEC'] <= $entro30) return true;
    return false;
}
?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Elenco Clienti</h2>
    <a href="clienti_aggiungi.php" style="padding: 8px 18px; background: #007bff; color: #fff; border-radius: 5px; text-decoration: none; font-weight: bold;">+ Nuovo Cliente</a>
</div>

<form method="get" style="margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Cerca cliente, mail, telefono..." value="<?= htmlspecialchars($search) ?>" style="padding: 8px; border: 1px solid #bbb; border-radius: 4px; width: 260px;">
    <button type="submit" style="padding: 8px 14px; background: #28a745; color: #fff; border: none; border-radius: 4px;">Cerca</button>
    <?php if ($search): ?>
        <a href="clienti.php" style="margin-left: 10px; color: #007bff;">Mostra tutti</a>
    <?php endif; ?>
</form>

<div style="overflow-x:auto;">
<table style="width: 100%; border-collapse: collapse; background:#fff; box-shadow:0 2px 8px #eee;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 22%;">Cognome/Ragione sociale</th>
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 15%;">Codice ditta</th>
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 20%;">Mail</th>
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 20%;">PEC</th>
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 13%;">Telefono</th>
            <th style="padding: 10px; border-bottom:2px solid #dee2e6; text-align:left; width: 10%;">Dettagli</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($clienti as $c):
        $alert = has_doc_alert($c, $oggi, $entro30);
    ?>
        <tr style="<?= $alert ? 'border-left: 5px solid #dc3545; background: #fff6f6;' : '' ?>">
            <td style="padding:10px; text-align:left;">
                <a href="info_cliente.php?id=<?= urlencode($c['id']) ?>" style="color:#007bff; font-weight:500; text-decoration:underline;">
                    <?= htmlspecialchars($c['cognome']) ?>
                </a>
                <?php if ($alert): ?><span title="Documenti in scadenza" style="color:#dc3545; margin-left:4px;">&#9888;</span><?php endif; ?>
            </td>
            <td style="padding:10px; text-align:left;"><?= htmlspecialchars($c['Codice ditta']) ?></td>
            <td style="padding:10px; text-align:left;"><?= htmlspecialchars($c['Mail']) ?></td>
            <td style="padding:10px; text-align:left;"><?= htmlspecialchars($c['PEC']) ?></td>
            <td style="padding:10px; text-align:left;"><?= htmlspecialchars($c['Telefono']) ?></td>
            <td style="padding:10px; text-align:left;">
                <a href="info_cliente.php?id=<?= urlencode($c['id']) ?>" style="background:#f5f5f5; border-radius:4px; padding:6px 10px; text-decoration:none; color:#333; font-weight:bold;">Dettagli</a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($clienti)): ?>
        <tr>
            <td colspan="6" style="text-align:left; padding: 20px;">Nessun cliente trovato.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

</main>
</body>
</html>