<?php
// Widget Chat Semplice e Funzionante
// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    return;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utente';
?>

<!-- Chat Widget HTML -->
<div id="simpleChatWidget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <!-- Toggle Button -->
    <button id="simpleChatToggle" type="button" style="
        width: 60px; 
        height: 60px; 
        border-radius: 50%; 
        background: #007bff; 
        border: none; 
        color: white; 
        font-size: 24px;
        box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        cursor: pointer;
        transition: all 0.3s ease;
    ">
        <i class="fas fa-comments"></i>
        <span id="simpleChatBadge" style="
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            line-height: 20px;
            text-align: center;
            display: none;
        ">0</span>
    </button>

    <!-- Chat Panel -->
    <div id="simpleChatPanel" style="
        position: absolute;
        bottom: 70px;
        right: 0;
        width: 350px;
        height: 400px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
    ">
        <!-- Header -->
        <div style="
            background: #007bff;
            color: white;
            padding: 15px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        ">
            <span>Chat Globale</span>
            <button id="simpleChatClose" type="button" style="
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
            ">×</button>
        </div>

        <!-- Messages Area -->
        <div id="simpleChatMessages" style="
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
        ">
            <div style="text-align: center; color: #6c757d; padding: 20px;">
                Caricamento messaggi...
            </div>
        </div>

        <!-- Input Area -->
        <div style="
            padding: 15px;
            border-top: 1px solid #dee2e6;
            background: white;
        ">
            <div style="display: flex; gap: 10px;">
                <input id="simpleChatInput" type="text" placeholder="Scrivi un messaggio..." style="
                    flex: 1;
                    border: 1px solid #dee2e6;
                    border-radius: 20px;
                    padding: 8px 15px;
                    font-size: 14px;
                    outline: none;
                ">
                <button id="simpleChatSend" type="button" style="
                    background: #007bff;
                    border: none;
                    color: white;
                    border-radius: 50%;
                    width: 36px;
                    height: 36px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
console.log('🚀 Inizializzazione Simple Chat Widget');

// Configurazione
window.simpleChatConfig = {
    userId: <?= $user_id ?>,
    userName: '<?= addslashes($user_name) ?>',
    apiBase: '/api/simple-chat/',
    pollInterval: 5000
};

// Classe Simple Chat
class SimpleChatWidget {
    constructor() {
        this.isOpen = false;
        this.pollTimer = null;
        this.lastMessageId = 0;
        
        this.elements = {
            widget: document.getElementById('simpleChatWidget'),
            toggle: document.getElementById('simpleChatToggle'),
            panel: document.getElementById('simpleChatPanel'),
            close: document.getElementById('simpleChatClose'),
            messages: document.getElementById('simpleChatMessages'),
            input: document.getElementById('simpleChatInput'),
            send: document.getElementById('simpleChatSend'),
            badge: document.getElementById('simpleChatBadge')
        };
        
        console.log('📋 Elementi trovati:', {
            widget: !!this.elements.widget,
            toggle: !!this.elements.toggle,
            panel: !!this.elements.panel
        });
        
        this.bindEvents();
        this.loadMessages();
        this.startPolling();
        
        console.log('✅ Simple Chat Widget inizializzato');
    }
    
    bindEvents() {
        // Toggle chat
        this.elements.toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            console.log('🎯 Toggle clicked');
            this.toggle();
        });
        
        // Close chat
        this.elements.close.addEventListener('click', (e) => {
            e.stopPropagation();
            this.close();
        });
        
        // Send message
        this.elements.send.addEventListener('click', () => {
            this.sendMessage();
        });
        
        // Enter to send
        this.elements.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.elements.widget.contains(e.target)) {
                this.close();
            }
        });
        
        // Prevent closing when clicking inside panel
        this.elements.panel.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        console.log('🔓 Apertura chat');
        this.elements.panel.style.display = 'flex';
        this.isOpen = true;
        this.elements.input.focus();
        this.loadMessages();
    }
    
    close() {
        console.log('🔒 Chiusura chat');
        this.elements.panel.style.display = 'none';
        this.isOpen = false;
    }
    
    async loadMessages() {
        try {
            console.log('📥 Caricamento messaggi...');
            
            const response = await fetch('/api/simple-chat/get_messages.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    limit: 50
                })
            });
            
            const data = await response.json();
            console.log('📩 Risposta API:', data);
            
            if (data.success) {
                this.renderMessages(data.messages || []);
            } else {
                this.showError('Errore caricamento messaggi: ' + (data.error || 'Unknown'));
            }
            
        } catch (error) {
            console.error('❌ Errore caricamento:', error);
            this.showError('Errore di connessione');
        }
    }
    
    renderMessages(messages) {
        console.log('🎨 Rendering messaggi:', messages.length);
        
        if (!messages.length) {
            this.elements.messages.innerHTML = `
                <div style="text-align: center; color: #6c757d; padding: 20px;">
                    <i class="fas fa-comments fa-2x mb-3"></i>
                    <p>Nessun messaggio ancora.<br>Inizia la conversazione!</p>
                </div>
            `;
            return;
        }
        
        this.elements.messages.innerHTML = '';
        
        messages.forEach(msg => {
            this.addMessage(msg);
            if (msg.id > this.lastMessageId) {
                this.lastMessageId = msg.id;
            }
        });
        
        this.scrollToBottom();
    }
    
    addMessage(message) {
        const isOwn = message.user_id == window.simpleChatConfig.userId;
        const time = new Date(message.created_at).toLocaleTimeString('it-IT', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            align-items: ${isOwn ? 'flex-end' : 'flex-start'};
        `;
        
        messageDiv.innerHTML = `
            ${!isOwn ? `<div style="font-size: 12px; color: #6c757d; margin-bottom: 4px;">${this.escapeHtml(message.user_name)}</div>` : ''}
            <div style="
                background: ${isOwn ? '#007bff' : '#e9ecef'};
                color: ${isOwn ? 'white' : '#333'};
                padding: 8px 12px;
                border-radius: 18px;
                max-width: 70%;
                word-wrap: break-word;
                font-size: 14px;
            ">
                ${this.escapeHtml(message.message)}
            </div>
            <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">
                ${time}
            </div>
        `;
        
        this.elements.messages.appendChild(messageDiv);
    }
    
    async sendMessage() {
        const message = this.elements.input.value.trim();
        if (!message) return;
        
        console.log('📤 Invio messaggio:', message);
        
        // Disabilita input
        this.elements.input.disabled = true;
        this.elements.send.disabled = true;
        
        try {
            const response = await fetch('/api/simple-chat/send_message.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    message: message
                })
            });
            
            const data = await response.json();
            console.log('📨 Risposta invio:', data);
            
            if (data.success) {
                // Pulisci input
                this.elements.input.value = '';
                
                // Aggiungi messaggio alla UI
                this.addMessage({
                    id: data.message_id || Date.now(),
                    user_id: window.simpleChatConfig.userId,
                    user_name: window.simpleChatConfig.userName,
                    message: message,
                    created_at: new Date().toISOString()
                });
                
                this.scrollToBottom();
                
            } else {
                this.showError('Errore invio: ' + (data.error || 'Unknown'));
            }
            
        } catch (error) {
            console.error('❌ Errore invio:', error);
            this.showError('Errore di connessione');
        } finally {
            // Riabilita input
            this.elements.input.disabled = false;
            this.elements.send.disabled = false;
            this.elements.input.focus();
        }
    }
    
    startPolling() {
        this.pollTimer = setInterval(() => {
            this.checkNewMessages();
        }, window.simpleChatConfig.pollInterval);
        
        console.log('⏰ Polling avviato ogni', window.simpleChatConfig.pollInterval, 'ms');
    }
    
    async checkNewMessages() {
        if (!this.isOpen) return;
        
        try {
            const response = await fetch('/api/simple-chat/get_messages.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    since: this.lastMessageId
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                console.log('📨 Nuovi messaggi:', data.messages.length);
                data.messages.forEach(msg => {
                    this.addMessage(msg);
                    if (msg.id > this.lastMessageId) {
                        this.lastMessageId = msg.id;
                    }
                });
                this.scrollToBottom();
            }
            
        } catch (error) {
            console.error('❌ Errore polling:', error);
        }
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.elements.messages.scrollTop = this.elements.messages.scrollHeight;
        }, 100);
    }
    
    showError(message) {
        console.error('❌', message);
        this.elements.messages.innerHTML = `
            <div style="text-align: center; color: #dc3545; padding: 20px;">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${this.escapeHtml(message)}</p>
                <button onclick="window.simpleChat.loadMessages()" style="
                    background: #dc3545;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin-top: 10px;
                ">Riprova</button>
            </div>
        `;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔄 DOM loaded, inizializzo Simple Chat...');
    window.simpleChat = new SimpleChatWidget();
});

// Cleanup
window.addEventListener('beforeunload', () => {
    if (window.simpleChat) {
        window.simpleChat.destroy();
    }
});
</script>

<style>
/* Hover effects */
#simpleChatToggle:hover {
    transform: scale(1.1);
    background: #0056b3;
}

#simpleChatSend:hover {
    background: #0056b3;
}

#simpleChatInput:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

/* Scrollbar styling */
#simpleChatMessages::-webkit-scrollbar {
    width: 6px;
}

#simpleChatMessages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#simpleChatMessages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#simpleChatMessages::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
