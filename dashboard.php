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
    </div>
</div>




<script src="http://192.168.1.29:3001/socket.io/socket.io.js">
    const socket = io('http://192.168.1.29:3001');


    function inviaMessaggio(e) {
        e.preventDefault();
        const testo = document.getElementById("messaggio").value.trim();
        if (testo) {
            socket.emit('chat message', {
                utente_id: <?= $_SESSION['user_id'] ?>,
                utente_nome: <?= json_encode($_SESSION['user_name']) ?>,
                testo: testo
            });
            document.getElementById("messaggio").value = '';
        }
    }

    socket.on('chat message', function (data) {
        const div = document.createElement("div");
        div.innerHTML = `<strong>${data.utente_nome}:</strong> ${data.testo} <small>(${data.orario})</small>`;
        document.getElementById("chat-messages").appendChild(div);
        document.getElementById("chat-messages").scrollTop = document.getElementById("chat-messages").scrollHeight;
    });

    window.onload = () => {
        fetch('api/chat_cronologia.php')
            .then(res => res.json())
            .then(data => {
                data.forEach(msg => {
                    const div = document.createElement("div");
                    div.innerHTML = `<strong>${msg.nome}:</strong> ${msg.messaggio} <small>(${msg.timestamp})</small>`;
                    document.getElementById("chat-messages").appendChild(div);
                });
                document.getElementById("chat-messages").scrollTop = document.getElementById("chat-messages").scrollHeight;
            });
    };
</script>

</main>
</body>
</html>
