<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Elenco clienti per sezione pratiche
$stmt = $pdo->query("SELECT id, `Cognome/Ragione sociale` AS nome FROM clienti ORDER BY `Cognome/Ragione sociale` ASC");
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>💬 Chat</h2>
<div style="display: flex; gap: 40px;">
    <!-- Chat di gruppo -->
    <div style="flex: 1; border: 1px solid #ccc; padding: 10px;">
        <h3>Chat di Gruppo</h3>
        <div id="chat-cronologia" style="height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-bottom: 10px;"></div>
        <input type="text" id="chat-input" placeholder="Scrivi un messaggio..." style="width: 80%;">
        <button id="chat-invia">Invia</button>
    </div>

    <!-- Appunti cliente -->
    <div style="flex: 1; border: 1px solid #ccc; padding: 10px;">
        <h3>Appunti Cliente</h3>
        <label>Seleziona Cliente:</label><br>
        <select id="cliente-select" onchange="caricaAppunti()">
            <option value="">-- Seleziona --</option>
            <?php foreach ($clienti as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <div id="appunti-cronologia" style="height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;"></div>
        <input type="text" id="appunto-input" placeholder="Scrivi un appunto..." style="width: 80%;">
        <button id="appunto-invia">Invia</button>
    </div>
</div>

<script src="http://192.168.1.29:3001/socket.io/socket.io.js"></script>
<script>
const socket = io("http://192.168.1.29:3001");
socket.emit("register", <?= $user_id ?>);

// === CHAT DI GRUPPO ===
function aggiornaChat(mittente, messaggio) {
    const area = document.getElementById("chat-cronologia");
    const p = document.createElement("p");
    p.innerHTML = `<strong>${mittente}</strong>: ${messaggio}`;
    area.appendChild(p);
    area.scrollTop = area.scrollHeight;
}

document.getElementById("chat-invia").addEventListener("click", () => {
    const msg = document.getElementById("chat-input").value.trim();
    if (!msg) return;
    socket.emit("chat message", {
        utente_id: <?= $user_id ?>,
        utente_nome: "<?= addslashes($user_name) ?>",
        testo: msg
    });
    document.getElementById("chat-input").value = "";
});

socket.on("chat message", data => {
    aggiornaChat(data.utente_nome, data.testo);
});

// Carica cronologia iniziale
fetch("api/chat_cronologia.php")
.then(res => res.json())
.then(data => {
    data.forEach(m => aggiornaChat(m.nome, m.messaggio));
});

// === CHAT PRATICHE / APPUNTI ===
function aggiornaAppunti(mittente, messaggio) {
    const area = document.getElementById("appunti-cronologia");
    const p = document.createElement("p");
    p.innerHTML = `<strong>${mittente}</strong>: ${messaggio}`;
    area.appendChild(p);
    area.scrollTop = area.scrollHeight;
}

function caricaAppunti() {
    const id = document.getElementById("cliente-select").value;
    if (!id) return;
    fetch("api/chat_pratica_cronologia.php?pratica_id=" + id)
    .then(res => res.json())
    .then(data => {
        const area = document.getElementById("appunti-cronologia");
        area.innerHTML = "";
        data.forEach(m => aggiornaAppunti(m.nome, m.messaggio));
    });
}

document.getElementById("appunto-invia").addEventListener("click", () => {
    const id = document.getElementById("cliente-select").value;
    const msg = document.getElementById("appunto-input").value.trim();
    if (!id || !msg) return;

    socket.emit("nuovo appunto", {
        utente_id: <?= $user_id ?>,
        utente_nome: "<?= addslashes($user_name) ?>",
        pratica_id: id,
        testo: msg
    });

    document.getElementById("appunto-input").value = "";
});

socket.on("appunto aggiunto", data => {
    const selezionato = document.getElementById("cliente-select").value;
    if (selezionato == data.pratica_id) {
        aggiornaAppunti(data.utente_nome, data.testo);
    }
});
</script>
</main>
</body>
</html>
