<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<link rel="stylesheet" href="/assets/css/chat_widgets.css">
<script src="http://192.168.1.29:3001/socket.io/socket.io.js"></script>

<div class="crm-chat-widget" id="chat-widget" data-tooltip="Chat Globale">
    <div class="crm-chat-header" onclick="toggleChatWidget('chat-widget')">
        <span class="chat-icon">💬</span>
        <span class="chat-text">Chat Globale</span>
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
    const widget = document.getElementById(id);
    widget.classList.toggle('open');
    
    // Rimuovi notifica quando aperto
    if (widget.classList.contains('open')) {
        widget.classList.remove('has-notification');
    }
    
    // Effetto sonoro (opzionale)
    if (widget.classList.contains('open')) {
        playNotificationSound();
    }
}

function playNotificationSound() {
    // Crea un suono sottile di apertura
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(1000, audioContext.currentTime + 0.1);
    
    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

// Simula notifiche periodiche per demo
function simulateNotifications() {
    const widgets = document.querySelectorAll('.crm-chat-widget:not(.open)');
    widgets.forEach(widget => {
        if (Math.random() > 0.7) { // 30% di probabilità
            widget.classList.add('has-notification');
            
            // Rimuovi notifica dopo 10 secondi
            setTimeout(() => {
                widget.classList.remove('has-notification');
            }, 10000);
        }
    });
}

// Avvia simulazione notifiche ogni 30 secondi
setInterval(simulateNotifications, 30000);

// Gestisci l'animazione quando si passa il mouse
document.addEventListener('DOMContentLoaded', function() {
    const widgets = document.querySelectorAll('.crm-chat-widget');
    
    widgets.forEach(widget => {
        widget.addEventListener('mouseenter', function() {
            if (!this.classList.contains('open')) {
                this.style.animation = 'none';
            }
        });
        
        widget.addEventListener('mouseleave', function() {
            if (!this.classList.contains('open')) {
                this.style.animation = 'pulse 2s infinite';
            }
        });
    });
});
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
