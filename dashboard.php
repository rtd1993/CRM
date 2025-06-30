<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';
?>

<h2>Dashboard</h2>

<div style="display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
    <!-- Colonna sinistra: Pulsanti -->
    <div style="flex: 1; min-width: 250px;">
        <button onclick="location.href='drive.php'" style="width: 100%; padding: 15px; margin-bottom: 10px;">📁 Accedi al Drive</button>
        <button onclick="location.href='clienti.php'" style="width: 100%; padding: 15px; margin-bottom: 10px;">📋 Database Clienti</button>
        <button onclick="location.href='task.php'" style="width: 100%; padding: 15px; margin-bottom: 10px;">✅ Task Mensili</button>
        <button onclick="location.href='info.php'" style="width: 100%; padding: 15px;">ℹ️ Informazioni Utili</button>
    </div>

    <!-- Colonna destra: Calendario e Task -->
    <div style="flex: 2; min-width: 400px;">
        <h3>Calendario Google</h3>
        <iframe src="https://calendar.google.com/calendar/embed?src=gestione.ascontabilmente%40gmail.com&ctz=Europe%2FRome"
                style="width: 100%; height: 250px; border: 1px solid #ccc;" frameborder="0" scrolling="no"></iframe>

        <h3 style="margin-top: 20px;">Task in Scadenza</h3>
        <?php
        $oggi = date('Y-m-d');
        $entro30 = date('Y-m-d', strtotime('+30 days'));

        $stmt = $pdo->prepare("
            SELECT descrizione, scadenza
            FROM task
            WHERE scadenza BETWEEN ? AND ?
            ORDER BY scadenza ASC
        ");
        $stmt->execute([$oggi, $entro30]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <ul style="list-style: none; padding: 0; max-height: 200px; overflow-y: auto;">
            <?php foreach ($tasks as $t):
                $scadenza = $t['scadenza'];
                $descrizione = htmlspecialchars($t['descrizione']);
                
                $diff = (strtotime($scadenza) - strtotime($oggi)) / 86400;
                $style = '';
                if ($diff < 0) {
                    $style = 'color:red;';
                } elseif ($diff < 5) {
                    $descrizione = "⚠️ $descrizione";
                }
            ?>
                <li style="<?= $style ?>">
                    <strong><?= $descrizione ?></strong> (Scadenza: <?= $scadenza ?>)
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Documenti da aggiornare -->
        <h3 style="margin-top: 30px;">Documenti da aggiornare</h3>
        <?php
        // ATTENZIONE: Modifica i nomi di tabella/colonne se diversi!
        // Si presuppone: tabella "documenti" con colonne: id, cliente_id, tipo_documento, data_scadenza
        // e tabella "clienti" con colonna "Cognome/Ragione sociale"
        $query = "
            SELECT d.id, d.tipo_documento, d.data_scadenza,
                   c.`Cognome/Ragione sociale` AS cognome
            FROM documenti d
            JOIN clienti c ON d.cliente_id = c.id
            WHERE (d.data_scadenza BETWEEN ? AND ?) OR (d.data_scadenza < ?)
            ORDER BY d.data_scadenza ASC
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$oggi, $entro30, $oggi]);
        $documenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($documenti) === 0): ?>
            <p>Nessun documento in scadenza entro 30 giorni.</p>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="border: 1px solid #ccc; padding: 6px;">Cognome/Ragione sociale</th>
                        <th style="border: 1px solid #ccc; padding: 6px;">Tipo Documento</th>
                        <th style="border: 1px solid #ccc; padding: 6px;">Data Scadenza</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($documenti as $doc): 
                    $scaduto = strtotime($doc['data_scadenza']) < strtotime($oggi) ? 'background: #ffcccc;' : '';
                ?>
                    <tr style="<?= $scaduto ?>">
                        <td style="border: 1px solid #ccc; padding: 6px;"><?= htmlspecialchars($doc['cognome']) ?></td>
                        <td style="border: 1px solid #ccc; padding: 6px;"><?= htmlspecialchars($doc['tipo_documento']) ?></td>
                        <td style="border: 1px solid #ccc; padding: 6px;"><?= htmlspecialchars($doc['data_scadenza']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>