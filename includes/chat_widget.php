<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<link rel="stylesheet" href="/assets/css/chat_widgets.css">
<script src="http://192.168.1.29:3001/socket.io/socket.io.js"></script>

<div class="crm-chat-widget" id="chat-widget">
    <div class="crm-chat-header" onclick="toggleChatWidget('chat-widget')">
        💬 Chat Globale
    </div>
    <div class="crm-chat-body">
        <div class="crm-chat-messages" id="chat-messages"></div>
        <div class="crm-chat-input-group">
            <input type="text" class="crm-chat-input" id="chat-input" placeholder="Scrivi...">
            <button class="crm-chat-send-btn btn btn-primary" onclick="inviaMsg()">Invia</button>
        </div>
    </div>
</div>
<script>
function toggleChatWidget(id) {
    document.getElementById(id).classList.toggle('open');
}
</script>


<script>
const user_id = <?= json_encode($_SESSION['user_id']) ?>;
const user_name = <?= json_encode($_SESSION['user_name']) ?>;
const socket = io('http://192.168.1.29:3001');

socket.emit("register", user_id);

function toggleChat() {
    const body = document.getElementById("chat-body");
    body.style.display = body.style.display === "none" ? "block" : "none";
}

function inviaMsg() {
    const input = document.getElementById("chat-input");
    const testo = input.value.trim();
    if (!testo) return;
    socket.emit("chat message", {
        utente_id: user_id,
        utente_nome: user_name,
        testo: testo
    });
    input.value = "";
}

socket.on("chat message", data => {
    const div = document.createElement("div");
    div.innerHTML = `<strong>${data.utente_nome}</strong>: ${data.testo}`;
    document.getElementById("chat-messages").appendChild(div);
    document.getElementById("chat-messages").scrollTop = 9999;
});
// Cronologia iniziale
fetch("/api/chat_cronologia.php")
.then(r => r.json())
.then(data => {
    const div = document.getElementById("chat-messages");
    data.forEach(msg => {
        const p = document.createElement("p");
        p.innerHTML = `<strong>${msg.nome}</strong>: ${msg.messaggio}`;
        div.appendChild(p);
    });
    div.scrollTop = div.scrollHeight;
});

</script>
<?php endif; ?>
