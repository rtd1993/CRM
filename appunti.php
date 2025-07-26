<?php
// File: appunti.php

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$utente_id = $_SESSION['user_id'];
$utente_nome = 'Utente #' . $utente_id;
$pratica_id = isset($_GET['pratica_id']) ? intval($_GET['pratica_id']) : 0;
if ($pratica_id <= 0) {
    die('ID pratica non valido.');
}

$stmt = $pdo->prepare("SELECT a.testo, a.data_inserimento, u.nome FROM appunti a JOIN utenti u ON a.utente_id = u.id WHERE pratica_id = ? ORDER BY a.data_inserimento ASC");
$stmt->execute([$pratica_id]);
$appunti = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<h2>Appunti Condivisi - Pratica #<?= $pratica_id ?></h2>

<ul id="note-list" style="list-style:none; padding:0; max-height:500px; overflow-y:auto; border:1px solid #ccc; background:white;">
    <?php foreach ($appunti as $note): ?>
        <li><strong><?= htmlspecialchars($note['nome']) ?>:</strong> <?= htmlspecialchars($note['testo']) ?> <small>(<?= $note['data_inserimento'] ?>)</small></li>
    <?php endforeach; ?>
</ul>

<form id="noteForm" onsubmit="inviaAppunto(event)">
    <textarea id="testo" placeholder="Scrivi un appunto..." required style="width: 100%; height: 100px;"></textarea><br>
    <button type="submit">Salva Appunto</button>
</form>

<script src="https://cdn.socket.io/4.6.1/socket.io.min.js"></script>
<script>
   const socket = io('<?= getSocketIOUrl() ?>');


    function inviaAppunto(e) {
        e.preventDefault();
        const testo = document.getElementById("testo").value.trim();
        if (testo) {
            socket.emit('nuovo appunto', {
                utente_id: <?= $utente_id ?>,
                utente_nome: <?= json_encode($utente_nome) ?>,
                pratica_id: <?= $pratica_id ?>,
                testo: testo
            });
            document.getElementById("testo").value = '';
        }
    }

    socket.on('appunto aggiunto', function(data) {
        if (data.pratica_id == <?= $pratica_id ?>) {
            const li = document.createElement("li");
            li.innerHTML = `<strong>${data.utente_nome}:</strong> ${data.testo} <small>(${data.data_inserimento})</small>`;
            document.getElementById("note-list").appendChild(li);
        }
    });

    socket.on('notifica appunto', function(msg) {
        console.log('[NOTIFICA]', msg);
    });
</script>

</main>
</body>
</html>
