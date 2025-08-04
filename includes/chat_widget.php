<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<link rel="stylesheet" href="/assets/css/chat_widgets.css?v=<?= time() ?>">

<div class="crm-chat-widget" id="chat-widget" data-tooltip="Chat Globale">
    <div class="crm-chat-header" onclick="toggleChatWidget('chat-widget')">
        <span class="chat-icon">💬</span>
        <span class="chat-text">Chat Globale</span>
        <span class="chat-notification-badge" id="chatNotificationBadge" style="display: none;">0</span>
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
// Assicuriamoci che i widget partano chiusi
document.addEventListener('DOMContentLoaded', function() {
    const widgets = document.querySelectorAll('.crm-chat-widget');
    widgets.forEach(widget => {
        widget.classList.remove('open');
        console.log('Widget ' + widget.id + ' impostato come chiuso');
    });
});

function toggleChatWidget(id) {
    const widget = document.getElementById(id);
    widget.classList.toggle('open');
    
    // Rimuovi notifica e resetta contatore quando aperto
    if (widget.classList.contains('open')) {
        widget.classList.remove('has-notification');
        resetUnreadCount(id);
        scrollToLastRead(id);
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

let globalSocket = null;
let unreadCount = 0;
let lastReadTimestamp = null;
let isWidgetOpen = false;

// Definisci inviaMsg immediatamente (placeholder)
window.inviaMsg = function() {
    console.log('Socket.IO non ancora pronto - funzione placeholder');
};

// Debug: controlla se Socket.IO è già disponibile
console.log('Tipo di io:', typeof io);
console.log('IO oggetto:', window.io);

// Funzioni per gestire messaggi non letti
function updateUnreadCount(count) {
    unreadCount = count;
    const badge = document.getElementById('chatNotificationBadge');
    const widget = document.getElementById('chat-widget');
    
    if (count > 0 && !isWidgetOpen) {
        badge.textContent = count;
        badge.style.display = 'inline-block';
        widget.classList.add('has-notification');
    } else {
        badge.style.display = 'none';
        widget.classList.remove('has-notification');
    }
}

function resetUnreadCount(widgetId) {
    if (widgetId === 'chat-widget') {
        updateUnreadCount(0);
        updateLastReadTimestamp();
        isWidgetOpen = true;
    }
}

function scrollToLastRead(widgetId) {
    if (widgetId === 'chat-widget') {
        setTimeout(() => {
            const chatMessages = document.getElementById('chat-messages');
            if (chatMessages) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }, 100);
    }
}

function updateLastReadTimestamp() {
    lastReadTimestamp = new Date().toISOString();
    // Invia al server per salvare nel database
    fetch('/api/update_read_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: user_id,
            chat_type: 'globale',
            timestamp: lastReadTimestamp
        })
    }).catch(err => console.error('Errore aggiornamento stato lettura:', err));
}

function playIncomingMessageSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Suono più distintivo per messaggi in arrivo
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(600, audioContext.currentTime + 0.15);
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.15);
    } catch (e) {
        console.log('Audio context not available');
    }
}

// Aspetta che Socket.IO sia caricato
function initializeSocketIO() {
    console.log('Controllo Socket.IO... typeof io:', typeof io);
    if (typeof io === 'undefined') {
        console.log('Socket.IO non ancora caricato, riprovo in 500ms...');
        setTimeout(initializeSocketIO, 500);
        return;
    }
    
    console.log('✅ Socket.IO caricato, inizializzo connessione...');
    const socketUrl = '<?= getSocketIOUrl() ?>';
    console.log('🔗 URL Socket.IO:', socketUrl);
    
    try {
        globalSocket = io(socketUrl);
        console.log('Socket creato:', globalSocket);

        globalSocket.emit("register", user_id);
        console.log('Registrazione utente inviata:', user_id);

        // Sovrascrivi inviaMsg con la versione funzionale
        window.inviaMsg = function() {
            console.log('inviaMsg chiamata con Socket.IO pronto');
            const input = document.getElementById("chat-input");
            const testo = input.value.trim();
            if (!testo) return;
            console.log('Invio messaggio:', testo);
            globalSocket.emit("chat message", {
                utente_id: user_id,
                utente_nome: user_name,
                testo: testo
            });
            input.value = "";
        };

        globalSocket.on("chat message", data => {
            console.log('Messaggio ricevuto:', data);
            const div = document.createElement("div");
            div.innerHTML = `<strong>${data.utente_nome}</strong>: ${data.testo}`;
            document.getElementById("chat-messages").appendChild(div);
            document.getElementById("chat-messages").scrollTop = 9999;
            
            // Se il messaggio non è del current user e widget è chiuso, incrementa contatore
            if (data.utente_id !== user_id && !isWidgetOpen) {
                unreadCount++;
                updateUnreadCount(unreadCount);
                playIncomingMessageSound();
            }
        });
        
        // Carica stato iniziale messaggi non letti
        loadUnreadCount();
        
        console.log('✅ Chat inizializzata correttamente');
    } catch (error) {
        console.error('❌ Errore inizializzazione Socket.IO:', error);
    }
}

function loadUnreadCount() {
    fetch('/api/get_unread_count.php?chat_type=globale')
        .then(r => r.json())
        .then(data => {
            if (data.unread_count) {
                updateUnreadCount(data.unread_count);
            }
        })
        .catch(err => console.error('Errore caricamento messaggi non letti:', err));
}

// Aggiorna la funzione toggleChatWidget esistente
document.addEventListener('DOMContentLoaded', function() {
    const widgets = document.querySelectorAll('.crm-chat-widget');
    widgets.forEach(widget => {
        widget.classList.remove('open');
        console.log('Widget ' + widget.id + ' impostato come chiuso');
        
        // Aggiungi listener per tracciare quando widget è chiuso
        const originalToggle = window.toggleChatWidget;
        window.toggleChatWidget = function(id) {
            const widget = document.getElementById(id);
            const wasOpen = widget.classList.contains('open');
            
            originalToggle(id);
            
            if (id === 'chat-widget') {
                isWidgetOpen = widget.classList.contains('open');
                if (!isWidgetOpen && wasOpen) {
                    // Widget appena chiuso, non più in lettura
                    updateLastReadTimestamp();
                }
            }
        };
    });
    
    initializeSocketIO();
});

// Avvia inizializzazione quando il DOM è pronto
function toggleChat() {
    const body = document.getElementById("chat-body");
    body.style.display = body.style.display === "none" ? "block" : "none";
}

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
})
.catch(err => console.error('Errore caricamento cronologia:', err));

</script>

</script>
<?php endif; ?>
