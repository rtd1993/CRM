<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Ele    <!-- Chat Pratiche -->
    <div class="chat-section">
        <h3>Chat Pratiche</h3>
        
        <!-- Form selezione cliente sempre visibile -->
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1rem    // Invia appunto via API
    fetch("api/salva_appunto_cliente.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            cliente_id: id,
            appunto: msg
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Aggiungi l'appunto alla visualizzazione
            aggiornaAppunti("<?= addslashes($user_name) ?>", msg);
            input.value = "";
            input.focus();
        } else {
            alert("❌ Errore nel salvare l'appunto: " + (data.error || 'Errore sconosciuto'));
        }
    })
    .catch(err => {
        console.error('Errore invio appunto:', err);
        alert("❌ Errore di connessione");
    })
    .finally(() => {
        // Riattiva il pulsante
        button.disabled = false;
        button.innerHTML = '📝 Appunto';
    });olid #e1e5e9;">
            <label class="select-label">👤 Seleziona Cliente per gli Appunti:</label>
            <select id="cliente-select" class="cliente-select" onchange="caricaPraticaChat()">
                <option value="">-- Seleziona un cliente --</option>
                <?php foreach ($clienti as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Area messaggi che si carica immediatamente -->
        <div id="appunti-cronologia" class="chat-cronologia">
            <p style="text-align: center; color: #6c757d; font-style: italic;">
                📝 Seleziona un cliente per visualizzare gli appunti specifici
            </p>
        </div>
        
        <!-- Input sempre attivo -->
        <div class="chat-input-group">
            <input type="text" id="appunto-input" class="chat-input" placeholder="Seleziona un cliente per scrivere appunti..." disabled>
            <button id="appunto-invia" class="chat-button" disabled>📝 Appunto</button>r sezione pratiche
$stmt = $pdo->query("SELECT id, `Cognome_Ragione_sociale` AS nome FROM clienti ORDER BY `Cognome_Ragione_sociale` ASC");
$clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.chat-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.chat-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.chat-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.chat-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    position: relative;
    overflow: hidden;
}

.chat-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.chat-section h3 {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chat-section h3::before {
    font-size: 1.2rem;
}

.chat-section:first-child h3::before {
    content: '💬';
}

.chat-section:last-child h3::before {
    content: '📝';
}

.chat-cronologia {
    height: 350px;
    overflow-y: auto;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chat-cronologia p {
    margin: 0.5rem 0;
    padding: 0.5rem;
    background: white;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 3px solid #667eea;
}

.chat-cronologia p strong {
    color: #667eea;
    font-weight: 600;
}

.chat-input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.chat-input {
    flex: 1;
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.chat-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.chat-button {
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.chat-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.chat-button:active {
    transform: translateY(0);
}

.cliente-select {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    margin-bottom: 1rem;
    background: white;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.cliente-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.select-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.status-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    margin-right: 0.5rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

@media (max-width: 768px) {
    .chat-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .chat-header h2 {
        font-size: 2rem;
    }
    
    .chat-section {
        padding: 1rem;
    }
    
    .chat-cronologia {
        height: 250px;
    }
    
    .chat-input-group {
        flex-direction: column;
    }
    
    .chat-button {
        width: 100%;
    }
}
</style>

<div class="chat-container">
    <!-- Chat di gruppo -->
    <div class="chat-section">
        <h3>Chat di Gruppo <span class="status-indicator"></span></h3>
        <div id="chat-cronologia" class="chat-cronologia"></div>
        <div class="chat-input-group">
            <input type="text" id="chat-input" class="chat-input" placeholder="Scrivi un messaggio...">
            <button id="chat-invia" class="chat-button">📤 Invia</button>
        </div>
    </div>

    <!-- Chat Pratiche -->
    <div class="chat-section">
        <h3>Chat Pratiche</h3>
        <label class="select-label">👤 Seleziona Cliente/Pratica:</label>
        <select id="cliente-select" class="cliente-select" onchange="caricaPraticaChat()">
            <option value="">-- Seleziona una pratica --</option>
            <?php foreach ($clienti as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <div id="appunti-cronologia" class="chat-cronologia"></div>
        <div class="chat-input-group">
            <input type="text" id="appunto-input" class="chat-input" placeholder="Scrivi un messaggio sulla pratica...">
            <button id="appunto-invia" class="chat-button">� Invia</button>
        </div>
    </div>
</div>

<script src="<?= getSocketIOUrl() ?>/socket.io/socket.io.js"></script>
<script>
const socket = io("<?= getSocketIOUrl() ?>");
socket.emit("register", <?= $user_id ?>);

// === CHAT DI GRUPPO ===
function aggiornaChat(mittente, messaggio) {
    const area = document.getElementById("chat-cronologia");
    const p = document.createElement("p");
    
    // Aggiungi timestamp
    const now = new Date();
    const timestamp = now.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    
    p.innerHTML = `<strong>${mittente}</strong> <small style="color: #6c757d;">${timestamp}</small><br>${messaggio}`;
    area.appendChild(p);
    area.scrollTop = area.scrollHeight;
    
    // Animazione di entrata
    p.style.opacity = '0';
    p.style.transform = 'translateY(20px)';
    setTimeout(() => {
        p.style.transition = 'all 0.3s ease';
        p.style.opacity = '1';
        p.style.transform = 'translateY(0)';
    }, 10);
}

// Invia messaggio con Enter
document.getElementById("chat-input").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        inviaMessaggio();
    }
});

document.getElementById("chat-invia").addEventListener("click", inviaMessaggio);

function inviaMessaggio() {
    const input = document.getElementById("chat-input");
    const msg = input.value.trim();
    if (!msg) return;
    
    // Disabilita temporaneamente il pulsante
    const button = document.getElementById("chat-invia");
    button.disabled = true;
    button.innerHTML = '⏳ Invio...';
    
    socket.emit("chat message", {
        utente_id: <?= $user_id ?>,
        utente_nome: "<?= addslashes($user_name) ?>",
        testo: msg
    });
    
    input.value = "";
    input.focus();
    
    // Riattiva il pulsante
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = '� Appunto';
    }, 1000);
}

socket.on("chat message", data => {
    aggiornaChat(data.utente_nome, data.testo);
});

// Carica cronologia iniziale
fetch("api/chat_cronologia.php")
.then(res => {
    if (!res.ok) {
        throw new Error('Errore HTTP: ' + res.status);
    }
    return res.json();
})
.then(data => {
    const area = document.getElementById("chat-cronologia");
    if (data.length === 0) {
        area.innerHTML = '<p style="text-align: center; color: #6c757d; font-style: italic;">Nessun messaggio nella chat globale</p>';
    } else {
        data.forEach(m => aggiornaChat(m.nome, m.messaggio));
    }
})
.catch(err => {
    console.error('Errore caricamento cronologia:', err);
    document.getElementById("chat-cronologia").innerHTML = 
        '<p style="text-align: center; color: #dc3545;">❌ Errore nel caricamento dei messaggi: ' + err.message + '</p>';
});

// === CHAT PRATICHE / APPUNTI ===
function aggiornaAppunti(mittente, messaggio, timestamp = null) {
    const area = document.getElementById("appunti-cronologia");
    const p = document.createElement("p");
    
    // Usa timestamp fornito o crea nuovo
    let timeStr;
    if (timestamp) {
        const date = new Date(timestamp);
        timeStr = date.toLocaleString('it-IT', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit', 
            minute: '2-digit' 
        });
    } else {
        const now = new Date();
        timeStr = now.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    }
    
    p.innerHTML = `<strong>${mittente}</strong> <small style="color: #6c757d;">${timeStr}</small><br>${messaggio}`;
    area.appendChild(p);
    area.scrollTop = area.scrollHeight;
    
    // Animazione di entrata
    p.style.opacity = '0';
    p.style.transform = 'translateY(20px)';
    setTimeout(() => {
        p.style.transition = 'all 0.3s ease';
        p.style.opacity = '1';
        p.style.transform = 'translateY(0)';
    }, 10);
}

function caricaPraticaChat() {
    const select = document.getElementById("cliente-select");
    const id = select.value;
    const input = document.getElementById("appunto-input");
    const button = document.getElementById("appunto-invia");
    
    if (!id) {
        // Nessun cliente selezionato - disabilita input
        document.getElementById("appunti-cronologia").innerHTML = 
            '<p style="text-align: center; color: #6c757d; font-style: italic;">📝 Seleziona un cliente per visualizzare gli appunti specifici</p>';
        input.disabled = true;
        input.placeholder = "Seleziona un cliente per scrivere appunti...";
        button.disabled = true;
        return;
    }
    
    // Cliente selezionato - abilita input
    input.disabled = false;
    input.placeholder = "Scrivi un appunto per questo cliente...";
    button.disabled = false;
    input.focus();
    
    // Mostra loading
    document.getElementById("appunti-cronologia").innerHTML = 
        '<p style="text-align: center; color: #6c757d;"><span class="status-indicator"></span> Caricamento appunti cliente...</p>';
    
    // Carica appunti del cliente selezionato
    fetch("api/appunti_cliente.php?cliente_id=" + id)
    .then(res => {
        if (!res.ok) {
            throw new Error('Errore HTTP: ' + res.status);
        }
        return res.json();
    })
    .then(data => {
        const area = document.getElementById("appunti-cronologia");
        area.innerHTML = "";
        
        if (data.length === 0) {
            area.innerHTML = '<p style="text-align: center; color: #6c757d; font-style: italic;">📝 Nessun appunto per questo cliente. Inizia a scrivere!</p>';
        } else {
            data.forEach(m => {
                aggiornaAppunti(m.nome || m.utente_nome, m.messaggio || m.appunto, m.timestamp);
            });
        }
    })
    .catch(err => {
        console.error('Errore caricamento appunti cliente:', err);
        document.getElementById("appunti-cronologia").innerHTML = 
            '<p style="text-align: center; color: #dc3545;">❌ Errore caricamento appunti: ' + err.message + '</p>';
    });
}

// Invia appunto con Enter
document.getElementById("appunto-input").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        inviaAppunto();
    }
});

document.getElementById("appunto-invia").addEventListener("click", inviaAppunto);

function inviaAppunto() {
    const id = document.getElementById("cliente-select").value;
    const input = document.getElementById("appunto-input");
    const msg = input.value.trim();
    
    if (!id) {
        alert("⚠️ Seleziona prima un cliente");
        return;
    }
    
    if (!msg) {
        alert("⚠️ Scrivi un appunto");
        return;
    }
    
    // Disabilita temporaneamente il pulsante
    const button = document.getElementById("appunto-invia");
    button.disabled = true;
    button.innerHTML = '⏳ Salvo...';

    socket.emit("messaggio pratica", {
        utente_id: <?= $user_id ?>,
        utente_nome: "<?= addslashes($user_name) ?>",
        pratica_id: id,
        testo: msg
    });

    input.value = "";
    input.focus();
    
    // Riattiva il pulsante
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = '� Invia';
    }, 1000);
}

// Focus automatico sul campo messaggio globale
document.getElementById("chat-input").focus();
</script>
</main>
</body>
</html>
