<?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'chat.php'])): ?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_login();
// Rimuovi il caricamento immediato di tutti i clienti - usa lazy loading invece
?>
<div class="crm-chat-widget" id="chat-pratiche-widget" data-tooltip="Chat Pratiche">
    <div class="crm-chat-header" onclick="toggleChatWidget('chat-pratiche-widget')">
        <span class="chat-icon">📁</span>
        <span class="chat-text">Chat Pratiche</span>
    </div>
    <div class="crm-chat-body">
        <form id="praticaChatForm" autocomplete="off">
            <select class="form-select crm-chat-select" id="clienteSelect" required>
                <option value="" selected disabled>Caricamento clienti...</option>
            </select>
            <div class="crm-chat-messages" id="praticaChatBox">
                <small class="text-muted">Seleziona una pratica per caricare la chat...</small>
            </div>
            <div class="crm-chat-input-group">
                <input type="text" id="praticaMsg" class="form-control crm-chat-pratiche-input" placeholder="Scrivi un messaggio..." autocomplete="off" disabled>
                <button class="crm-chat-send-btn btn btn-success" type="submit" id="sendPraticaBtn" disabled>Invia</button>
            </div>
        </form>
    </div>
</div>
<script>
function toggleChatWidget(id) {
    document.getElementById(id).classList.toggle('open');
    
    // Lazy load clienti solo quando il widget viene aperto per la prima volta
    if (id === 'chat-pratiche-widget' && document.getElementById(id).classList.contains('open')) {
        loadClientiList();
    }
}
</script>

<script>
let clientiLoaded = false;

function togglePraticaChat() {
    document.getElementById('pratica-chat-widget').classList.toggle('open');
}

// Lazy loading dei clienti
function loadClientiList() {
    if (clientiLoaded) return; // Evita caricamenti multipli
    
    const select = document.getElementById('clienteSelect');
    select.innerHTML = '<option value="" disabled>Caricamento...</option>';
    
    const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
    fetch(baseUrl + '/ajax/clienti_list.php')
        .then(r => r.json())
        .then(clienti => {
            select.innerHTML = '<option value="" selected disabled>Seleziona pratica (Cognome/Rag. Sociale)...</option>';
            clienti.forEach(cli => {
                const option = document.createElement('option');
                option.value = cli.id;
                option.textContent = `${cli.cognome_ragione_sociale} ${cli.nome}`;
                select.appendChild(option);
            });
            clientiLoaded = true;
        })
        .catch(error => {
            console.error('Errore caricamento clienti:', error);
            select.innerHTML = '<option value="" disabled>Errore caricamento clienti</option>';
        });
}

document.getElementById('clienteSelect').addEventListener('change', function() {
    let clienteId = this.value;
    document.getElementById('praticaMsg').disabled = !clienteId;
    document.getElementById('sendPraticaBtn').disabled = !clienteId;
    if (clienteId) {
        loadPraticaChat(clienteId);
    }
});

// Cache per i messaggi per evitare richieste ripetute
let chatCache = {};

function loadPraticaChat(clienteId) {
    const chatBox = document.getElementById('praticaChatBox');
    
    // Usa cache se disponibile
    if (chatCache[clienteId]) {
        renderChatMessages(chatCache[clienteId], chatBox);
        return;
    }
    
    chatBox.innerHTML = '<span class="text-secondary">Caricamento...</span>';
    const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
    
    fetch(baseUrl + '/ajax/pratica_chat_fetch.php?cliente_id=' + clienteId)
      .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP ${r.status}: ${r.statusText}`);
        }
        return r.json();
      })
      .then(messages => {
        chatCache[clienteId] = messages; // Salva in cache
        renderChatMessages(messages, chatBox);
      })
      .catch(error => {
        console.error('Errore caricamento chat:', error);
        chatBox.innerHTML = '<span class="text-danger">Errore: ' + error.message + '</span>';
      });
}

// Rendering ottimizzato dei messaggi
function renderChatMessages(messages, chatBox) {
    if (!messages.length) {
        chatBox.innerHTML = '<small class="text-muted">Nessun messaggio per questa pratica.</small>';
        return;
    }
    
    // Usa DocumentFragment per performance migliori
    const fragment = document.createDocumentFragment();
    
    messages.forEach(m => {
        const msgDiv = document.createElement('div');
        msgDiv.innerHTML = `<strong>${m.utente}</strong> <small class="text-muted">${m.data}</small><br>${m.testo}`;
        
        const hr = document.createElement('hr');
        hr.style.margin = '0.3em 0';
        
        fragment.appendChild(msgDiv);
        fragment.appendChild(hr);
    });
    
    chatBox.innerHTML = '';
    chatBox.appendChild(fragment);
    chatBox.scrollTop = chatBox.scrollHeight;
}

document.getElementById('praticaChatForm').addEventListener('submit', function(e) {
    e.preventDefault();
    let clienteId = document.getElementById('clienteSelect').value;
    let msg = document.getElementById('praticaMsg').value.trim();
    if (!clienteId || !msg) return;
    
    const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/');
    fetch(baseUrl + '/ajax/pratica_chat_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'cliente_id=' + encodeURIComponent(clienteId) + '&msg=' + encodeURIComponent(msg)
    })
    .then(r => {
        if (!r.ok) {
            throw new Error(`HTTP ${r.status}: ${r.statusText}`);
        }
        return r.json();
    })
    .then(resp => {
        if (resp.ok) {
            // Invalida la cache per questo cliente
            delete chatCache[clienteId];
            loadPraticaChat(clienteId);
            document.getElementById('praticaMsg').value = '';
        } else {
            console.error('Errore invio messaggio:', resp.error);
            alert('Errore nell\'invio del messaggio: ' + (resp.error || 'Errore sconosciuto'));
        }
    })
    .catch(error => {
        console.error('Errore invio messaggio:', error);
        alert('Errore nell\'invio del messaggio: ' + error.message);
    });
});
</script>
<?php endif; ?>