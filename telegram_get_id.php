<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Configurazione del bot (token configurato automaticamente)
$bot_token = '7235317891:AAGpr8mOFVVksFV9LbF5Fe8RPWsLqdcOAd4';

$chat_ids = [];
$error = null;

if ($bot_token && $bot_token !== 'INSERISCI_IL_TUO_BOT_TOKEN_QUI') {
    try {
        $updates = file_get_contents("https://api.telegram.org/bot$bot_token/getUpdates");
        $updates = json_decode($updates, true);
        
        if (isset($updates['result'])) {
            foreach ($updates['result'] as $update) {
                if (isset($update['message'])) {
                    $chat_id = $update['message']['chat']['id'];
                    $nome = $update['message']['chat']['first_name'] ?? '';
                    $cognome = $update['message']['chat']['last_name'] ?? '';
                    $username = $update['message']['chat']['username'] ?? '';
                    $testo = $update['message']['text'] ?? '';
                    
                    $chat_ids[] = [
                        'id' => $chat_id,
                        'nome' => trim($nome . ' ' . $cognome),
                        'username' => $username,
                        'ultimo_messaggio' => $testo,
                        'data' => date('d/m/Y H:i', $update['message']['date'])
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $error = "Errore nel recupero dei dati: " . $e->getMessage();
    }
}
?>

<style>
.telegram-id-header {
    background: linear-gradient(135deg, #0088cc 0%, #229ED9 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.telegram-id-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.telegram-id-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.setup-instructions {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.instruction-step {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding: 1rem;
    border-radius: 10px;
    background: #f8f9fa;
    border-left: 4px solid #0088cc;
}

.step-number {
    background: #0088cc;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 1rem;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.step-content p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.bot-token-config {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.chat-ids-list {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.chat-id-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-radius: 10px;
    background: #f8f9fa;
    margin-bottom: 1rem;
    border: 1px solid #dee2e6;
}

.chat-id-info {
    flex: 1;
}

.chat-id-name {
    font-weight: bold;
    color: #333;
    margin-bottom: 0.25rem;
}

.chat-id-details {
    font-size: 0.9rem;
    color: #666;
}

.chat-id-number {
    background: #0088cc;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-family: monospace;
    font-weight: bold;
    cursor: pointer;
}

.copy-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 0.5rem;
    font-size: 0.9rem;
}

.copy-btn:hover {
    background: #218838;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .chat-id-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .telegram-id-header h2 {
        font-size: 2rem;
    }
}
</style>


<div class="setup-instructions">
    <h3>📋 Istruzioni per ottenere il Chat ID</h3>
    
    <div class="instruction-step">
        <div class="step-number">1</div>
        <div class="step-content">
            <h4>Configura il Bot Token</h4>
            <p>Modifica il file <code>telegram_get_id.php</code> inserendo il token del bot fornito da BotFather</p>
        </div>
    </div>
    
    <div class="instruction-step">
        <div class="step-number">2</div>
        <div class="step-content">
            <h4>Avvia il Bot</h4>
            <p>Cerca il tuo bot su Telegram e premi <strong>Start</strong> o invia il comando <code>/start</code></p>
        </div>
    </div>
    
    <div class="instruction-step">
        <div class="step-number">3</div>
        <div class="step-content">
            <h4>Invia un Messaggio</h4>
            <p>Scrivi qualsiasi messaggio al bot (es. "Ciao" o "Test")</p>
        </div>
    </div>
    
    <div class="instruction-step">
        <div class="step-number">4</div>
        <div class="step-content">
            <h4>Aggiorna questa Pagina</h4>
            <p>Ricarica la pagina per vedere il tuo Chat ID nella lista sottostante</p>
        </div>
    </div>
</div>

<?php if ($bot_token === 'INSERISCI_IL_TUO_BOT_TOKEN_QUI'): ?>
    <div class="bot-token-config">
        <h4>⚠️ Configurazione Richiesta</h4>
        <p>Il token del bot non è stato configurato. Modifica il file <code>telegram_get_id.php</code> alla riga 7 inserendo il token fornito da BotFather.</p>
        <p><strong>Esempio:</strong> <code>$bot_token = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';</code></p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-message">
        ❌ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="chat-ids-list">
    <h3>💬 Chat ID Trovati</h3>
    
    <?php if (empty($chat_ids)): ?>
        <div class="no-data">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🤖</div>
            <p>Nessun Chat ID trovato.</p>
            <p>Assicurati di aver inviato un messaggio al bot e ricarica la pagina.</p>
        </div>
    <?php else: ?>
        <?php foreach ($chat_ids as $chat): ?>
            <div class="chat-id-item">
                <div class="chat-id-info">
                    <div class="chat-id-name">
                        <?= htmlspecialchars($chat['nome']) ?: 'Utente senza nome' ?>
                        <?php if ($chat['username']): ?>
                            <span style="color: #666;">(@<?= htmlspecialchars($chat['username']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-id-details">
                        Ultimo messaggio: "<?= htmlspecialchars($chat['ultimo_messaggio']) ?>" - <?= $chat['data'] ?>
                    </div>
                </div>
                <div style="display: flex; align-items: center;">
                    <span class="chat-id-number" onclick="copyToClipboard('<?= $chat['id'] ?>', this)">
                        <?= $chat['id'] ?>
                    </span>
                    <button class="copy-btn" onclick="copyToClipboard('<?= $chat['id'] ?>', this)">
                        📋 Copia
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalText = button.textContent;
        button.textContent = '✅ Copiato!';
        button.style.background = '#28a745';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
    }).catch(function(err) {
        console.error('Errore nella copia: ', err);
        // Fallback per browser più vecchi
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        button.textContent = '✅ Copiato!';
        setTimeout(() => {
            button.textContent = '📋 Copia';
        }, 2000);
    });
}

// Auto-refresh ogni 10 secondi
setTimeout(() => {
    location.reload();
}, 10000);
</script>

</main>
</body>
</html>