// Chat Footer System - WhatsApp-like Internal Chat
class ChatSystem {
    constructor() {
        this.socket = null;
        this.currentUser = null;
        this.openChats = new Map(); // chat_id => window element
        this.notifications = new Map(); // chat_id => count
        this.chatSessions = new Set(); // chat IDs attivi nella sessione
        this.typingTimeouts = new Map();
        
        this.init();
    }
    
    async init() {
        try {
            // Carica informazioni utente corrente
            await this.loadCurrentUser();
            
            // Inizializza Socket.IO
            await this.initSocket();
            
            // Crea footer chat
            this.createChatFooter();
            
            // Carica sessioni chat salvate
            await this.loadChatSessions();
            
            // Inizializza contatori notifiche
            await this.loadNotifications();
            
            // Se Socket.IO non è disponibile, usa polling per notifiche
            if (!this.socket) {
                this.startNotificationPolling();
            }
            
            console.log('✅ Chat System inizializzato');
        } catch (error) {
            console.error('❌ Errore inizializzazione Chat System:', error);
        }
    }
    
    startNotificationPolling() {
        // Polling ogni 30 secondi per le notifiche
        setInterval(async () => {
            try {
                await this.loadNotifications();
            } catch (error) {
                console.error('Errore polling notifiche:', error);
            }
        }, 30000);
    }
    
    async loadCurrentUser() {
        try {
            const response = await fetch('api/current_user.php');
            this.currentUser = await response.json();
        } catch (error) {
            console.error('Errore caricamento utente:', error);
        }
    }
    
    async initSocket() {
        return new Promise((resolve) => {
            try {
                // Prova a connettersi a Socket.IO
                this.socket = io();
                
                this.socket.on('connect', () => {
                    console.log('🔌 Socket.IO connesso');
                    this.socket.emit('join_user', this.currentUser.id);
                    resolve();
                });
                
                this.socket.on('new_message', (data) => {
                    this.handleNewMessage(data);
                });
                
                this.socket.on('user_typing', (data) => {
                    this.showTypingIndicator(data.windowId, data.user_name);
                });
                
                this.socket.on('user_online', (data) => {
                    this.updateUserStatus(data.user_id, true);
                });
                
                this.socket.on('user_offline', (data) => {
                    this.updateUserStatus(data.user_id, false);
                });
                
                this.socket.on('connect_error', (error) => {
                    console.warn('⚠️ Socket.IO non raggiungibile, usando polling:', error.message);
                    this.socket = null;
                    resolve(); // Non bloccare l'inizializzazione
                });
                
                // Timeout di connessione
                setTimeout(() => {
                    if (!this.socket || !this.socket.connected) {
                        console.warn('⚠️ Timeout connessione Socket.IO, usando polling');
                        this.socket = null;
                        resolve();
                    }
                }, 5000);
                
            } catch (error) {
                console.warn('⚠️ Errore Socket.IO, usando polling:', error.message);
                this.socket = null;
                resolve(); // Non bloccare l'inizializzazione
            }
        });
    }
    
    createChatFooter() {
        const footer = document.createElement('div');
        footer.className = 'chat-footer';
        footer.innerHTML = `
            <button class="chat-footer-btn globale" onclick="chatSystem.toggleChat('globale')">
                <i class="fas fa-comments"></i>
                Chat Globale
                <span class="chat-notification" id="notif-globale" style="display: none;">0</span>
            </button>
            <button class="chat-footer-btn pratiche" onclick="chatSystem.showPraticheModal()">
                <i class="fas fa-clipboard-list"></i>
                Chat Pratiche
                <span class="chat-notification" id="notif-pratiche" style="display: none;">0</span>
            </button>
            <button class="chat-footer-btn nuovo" onclick="chatSystem.showNewChatModal()">
                <i class="fas fa-plus"></i>
                Nuova Chat
            </button>
        `;
        
        document.body.appendChild(footer);
    }
    
    async toggleChat(chatType, chatId = null, praticaId = null) {
        const windowId = chatId || (chatType === 'globale' ? 'globale' : `pratica-${praticaId}`);
        
        if (this.openChats.has(windowId)) {
            const chatWindow = this.openChats.get(windowId);
            if (chatWindow.classList.contains('show')) {
                chatWindow.classList.remove('show');
            } else {
                chatWindow.classList.add('show');
                if (chatWindow.classList.contains('minimized')) {
                    this.maximizeChat(windowId);
                }
            }
        } else {
            await this.openChat(chatType, chatId, praticaId);
        }
    }
    
    async openChat(chatType, chatId = null, praticaId = null) {
        const windowId = chatId || (chatType === 'globale' ? 'globale' : `pratica-${praticaId}`);
        
        // Se già aperta, la porta in primo piano
        if (this.openChats.has(windowId)) {
            const chatWindow = this.openChats.get(windowId);
            chatWindow.classList.add('show');
            chatWindow.classList.remove('minimized');
            return;
        }
        
        // Determina i dati della chat
        let chatData;
        if (chatType === 'globale') {
            chatData = { id: 1, name: 'Chat Globale', type: 'globale' };
        } else if (chatType === 'pratica') {
            chatData = { id: `pratica-${praticaId}`, name: `Pratica ${praticaId}`, type: 'pratica' };
        } else {
            chatData = { id: chatId, name: 'Chat Privata', type: 'privata' };
        }
        
        this.createChatWindow(chatData, windowId);
        await this.loadChatMessages(chatData.id, windowId);
    }
    
    createChatWindow(chatData, windowId) {
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chat-window show';
        chatWindow.id = `chat-window-${windowId}`;
        
        // Calcola posizione per evitare sovrapposizioni
        const existingChats = document.querySelectorAll('.chat-window').length;
        const rightOffset = 20 + (existingChats * 370);
        
        chatWindow.style.right = rightOffset + 'px';
        
        chatWindow.innerHTML = `
            <div class="chat-header ${chatData.type}" onclick="chatSystem.toggleMinimize('${windowId}')">
                <div class="chat-header-title">
                    <i class="fas ${this.getChatIcon(chatData.type)}"></i>
                    ${chatData.name}
                </div>
                <div class="chat-header-controls">
                    <button class="chat-header-btn" onclick="event.stopPropagation(); chatSystem.minimizeChat('${windowId}')" title="Minimizza">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button class="chat-header-btn" onclick="event.stopPropagation(); chatSystem.closeChat('${windowId}')" title="Chiudi">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="chat-body" id="chat-body-${windowId}">
                <div class="chat-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Caricamento messaggi...
                </div>
            </div>
            <div class="chat-input-container">
                <div class="chat-typing" id="typing-${windowId}" style="display: none;">
                    <span class="typing-text"></span>
                </div>
                <div class="chat-input-wrapper">
                    <input type="text" 
                           class="chat-input" 
                           id="input-${windowId}"
                           placeholder="Scrivi un messaggio..."
                           onkeypress="if(event.key==='Enter') chatSystem.sendMessage('${windowId}', '${chatData.id}')">
                    <button class="chat-send-btn" onclick="chatSystem.sendMessage('${windowId}', '${chatData.id}')">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(chatWindow);
        this.openChats.set(windowId, chatWindow);
        this.chatSessions.add(windowId);
        this.saveChatSessions();
    }
    
    getChatIcon(type) {
        switch (type) {
            case 'globale': return 'fa-comments';
            case 'pratica': return 'fa-clipboard-list';
            case 'privata': return 'fa-user';
            default: return 'fa-comment';
        }
    }
    
    getPraticaSelect() {
        return `
            <option value="">Seleziona pratica...</option>
            <!-- Qui andrebbero caricate le pratiche dal database -->
        `;
    }
    
    async loadChatMessages(chatId, windowId) {
        try {
            const response = await fetch(`api/chat_messages.php?chat_id=${chatId}&limit=50`);
            const messages = await response.json();
            
            const chatBody = document.getElementById(`chat-body-${windowId}`);
            chatBody.innerHTML = '';
            
            messages.forEach(message => {
                this.appendMessage(windowId, message, false);
            });
            
            // Scorri alla fine
            chatBody.scrollTop = chatBody.scrollHeight;
            
        } catch (error) {
            console.error('Errore caricamento messaggi:', error);
            const chatBody = document.getElementById(`chat-body-${windowId}`);
            chatBody.innerHTML = '<div class="chat-error">Errore nel caricamento dei messaggi</div>';
        }
    }
    
    appendMessage(windowId, message, isNew = true) {
        const chatBody = document.getElementById(`chat-body-${windowId}`);
        if (!chatBody) return;
        
        const messageEl = document.createElement('div');
        messageEl.className = `chat-message ${message.user_id == this.currentUser.id ? 'own' : 'other'}`;
        
        const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageEl.innerHTML = `
            <div class="message-content">
                <div class="message-text">${message.message}</div>
                <div class="message-meta">
                    <span class="message-author">${message.user_name}</span>
                    <span class="message-time">${time}</span>
                </div>
            </div>
        `;
        
        chatBody.appendChild(messageEl);
        
        if (isNew) {
            // Scorri alla fine solo per nuovi messaggi
            chatBody.scrollTop = chatBody.scrollHeight;
            
            // Effetto di evidenziazione
            messageEl.style.animation = 'messageAppear 0.3s ease-out';
        }
    }
    
    async sendMessage(windowId, chatId) {
        const input = document.getElementById(`input-${windowId}`);
        const message = input.value.trim();
        
        if (!message) return;
        
        try {
            const response = await fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    chat_id: chatId,
                    message: message,
                    user_id: this.currentUser.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                input.value = '';
                
                // Se Socket.IO è disponibile, il messaggio arriverà via socket
                // Altrimenti lo aggiungiamo direttamente
                if (!this.socket) {
                    const newMessage = {
                        id: result.message_id,
                        message: message,
                        user_id: this.currentUser.id,
                        user_name: this.currentUser.nome,
                        created_at: new Date().toISOString()
                    };
                    this.appendMessage(windowId, newMessage, true);
                }
            }
            
        } catch (error) {
            console.error('Errore invio messaggio:', error);
            alert('Errore nell\'invio del messaggio');
        }
    }
    
    handleNewMessage(data) {
        // Gestisce i nuovi messaggi ricevuti via Socket.IO
        const windowId = data.windowId || data.chat_id;
        
        if (this.openChats.has(windowId)) {
            this.appendMessage(windowId, data, true);
        }
        
        // Aggiorna contatori
        this.updateNotificationCount(data.chat_id, 1);
        
        // Mostra notifica se chat non è visibile
        if (!this.openChats.has(windowId) || !this.openChats.get(windowId).classList.contains('show')) {
            this.showDesktopNotification(data.user_name, data.message);
        }
    }
    
    showTypingIndicator(windowId, userName) {
        const typingEl = document.getElementById(`typing-${windowId}`);
        if (typingEl) {
            typingEl.querySelector('.typing-text').textContent = `${userName} sta scrivendo...`;
            typingEl.style.display = 'block';
            
            // Rimuovi dopo 3 secondi
            clearTimeout(this.typingTimeouts.get(windowId));
            this.typingTimeouts.set(windowId, setTimeout(() => {
                typingEl.style.display = 'none';
            }, 3000));
        }
    }
    
    updateUserStatus(userId, isOnline) {
        // Aggiorna lo stato online/offline degli utenti
        console.log(`Utente ${userId} è ${isOnline ? 'online' : 'offline'}`);
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('api/chat_notifications.php');
            const data = await response.json();
            
            data.forEach(notif => {
                this.updateNotificationCount(notif.chat_id, notif.unread_count);
            });
            
        } catch (error) {
            console.error('Errore caricamento notifiche:', error);
        }
    }
    
    updateNotificationCount(chatId, count) {
        let notifId;
        if (chatId === 1 || chatId === 'globale') {
            notifId = 'notif-globale';
        } else if (chatId.toString().startsWith('pratica-')) {
            notifId = 'notif-pratiche';
        }
        
        if (notifId) {
            const notifEl = document.getElementById(notifId);
            if (notifEl) {
                if (count > 0) {
                    notifEl.textContent = count > 99 ? '99+' : count;
                    notifEl.style.display = 'flex';
                } else {
                    notifEl.style.display = 'none';
                }
            }
        }
    }
    
    showDesktopNotification(userName, message) {
        if (Notification.permission === 'granted') {
            new Notification(`${userName} - Chat CRM`, {
                body: message,
                icon: '/assets/img/chat-icon.png'
            });
        }
    }
    
    minimizeChat(windowId) {
        const chatWindow = this.openChats.get(windowId);
        if (chatWindow) {
            chatWindow.classList.add('minimized');
        }
    }
    
    maximizeChat(windowId) {
        const chatWindow = this.openChats.get(windowId);
        if (chatWindow) {
            chatWindow.classList.remove('minimized');
            
            // Segna come letti quando si apre
            const chatBody = chatWindow.querySelector('.chat-body');
            if (chatBody) {
                // Implementa logica per segnare come letti
            }
        }
    }
    
    closeChat(windowId) {
        const chatWindow = this.openChats.get(windowId);
        if (chatWindow) {
            chatWindow.remove();
            this.openChats.delete(windowId);
            this.chatSessions.delete(windowId);
            this.saveChatSessions();
        }
    }
    
    toggleMinimize(windowId) {
        const chatWindow = this.openChats.get(windowId);
        if (chatWindow) {
            if (chatWindow.classList.contains('minimized')) {
                this.maximizeChat(windowId);
            } else {
                this.minimizeChat(windowId);
            }
        }
    }
    
    async showNewChatModal() {
        // Mostra modal per selezionare utenti
        const modal = document.createElement('div');
        modal.className = 'chat-list-modal show';
        modal.innerHTML = `
            <div class="chat-list-content">
                <div class="chat-list-header">
                    <h3>Nuova Chat</h3>
                    <button class="chat-list-close" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-list-body">
                    <div class="chat-loading">Caricamento utenti...</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        try {
            const response = await fetch('api/users_list.php');
            const users = await response.json();
            
            const body = modal.querySelector('.chat-list-body');
            body.innerHTML = '';
            
            users.forEach(user => {
                if (user.id !== this.currentUser.id) { // Escludi se stesso
                    const userEl = document.createElement('div');
                    userEl.className = 'chat-user-item';
                    userEl.innerHTML = `
                        <div class="user-info">
                            <div class="user-name">${user.nome}</div>
                            <div class="user-status ${user.is_online ? 'online' : 'offline'}">
                                ${user.is_online ? 'Online' : 'Offline'}
                            </div>
                        </div>
                        <button class="chat-start-btn" onclick="chatSystem.startPrivateChat(${user.id}, '${user.nome}')">
                            <i class="fas fa-comment"></i>
                        </button>
                    `;
                    body.appendChild(userEl);
                }
            });
            
        } catch (error) {
            console.error('Errore caricamento utenti:', error);
        }
    }
    
    async startPrivateChat(userId, userName) {
        try {
            const response = await fetch('api/create_private_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    participant_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Chiudi modal
                document.querySelector('.chat-list-modal')?.remove();
                
                // Apri chat
                await this.openChat('privata', result.chat_id);
            }
            
        } catch (error) {
            console.error('Errore creazione chat privata:', error);
        }
    }
    
    async showPraticheModal() {
        // Modal per selezionare pratiche/clienti
        const modal = document.createElement('div');
        modal.className = 'chat-list-modal show';
        modal.innerHTML = `
            <div class="chat-list-content">
                <div class="chat-list-header">
                    <h3>Chat Pratiche</h3>
                    <button class="chat-list-close" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="chat-list-body">
                    <div class="pratica-input">
                        <input type="number" id="pratica-id-input" placeholder="ID Pratica/Cliente" min="1">
                        <button onclick="chatSystem.openPraticaChat()">Apri Chat</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        try {
            const response = await fetch(`api/get_pratica_chat.php?pratica_id=${praticaId || ''}`);
            const pratiche = await response.json();
            
            // Qui potresti mostrare una lista di pratiche esistenti
            
        } catch (error) {
            console.error('Errore caricamento pratiche:', error);
        }
    }
    
    async openPraticaChat() {
        const praticaId = document.getElementById('pratica-id-input').value;
        if (!praticaId) return;
        
        try {
            const response = await fetch('api/create_private_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    pratica_id: praticaId,
                    chat_type: 'pratica'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.querySelector('.chat-list-modal')?.remove();
                await this.openChat('pratica', result.chat_id, praticaId);
            }
            
        } catch (error) {
            console.error('Errore apertura chat pratica:', error);
        }
    }
    
    async loadChatSessions() {
        // Carica sessioni salvate dal localStorage
        const saved = localStorage.getItem('chatSessions');
        if (saved) {
            this.chatSessions = new Set(JSON.parse(saved));
            
            // Riapri le chat salvate
            for (const windowId of this.chatSessions) {
                if (windowId === 'globale') {
                    await this.openChat('globale');
                    const chatWindow = this.openChats.get('globale');
                    if (chatWindow) {
                        this.minimizeChat('globale');
                    }
                }
                // Implementa per altri tipi di chat se necessario
            }
        }
    }
    
    saveChatSessions() {
        localStorage.setItem('chatSessions', JSON.stringify([...this.chatSessions]));
    }
}

// Inizializza il sistema chat quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    // Richiedi permesso notifiche
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Inizializza sistema chat
    window.chatSystem = new ChatSystem();
});
