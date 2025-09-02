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
        
        console.log('🔄 Polling notifiche attivato (30s)');
    }
    
    async loadCurrentUser() {
        try {
            const response = await fetch('/api/current_user.php');
            this.currentUser = await response.json();
        } catch (error) {
            console.error('Errore caricamento utente:', error);
        }
    }
    
    async initSocket() {
        return new Promise((resolve, reject) => {
            try {
                // Verifica che Socket.IO sia disponibile
                if (typeof io === 'undefined') {
                    console.warn('⚠️ Socket.IO non disponibile, usando polling');
                    this.socket = null;
                    resolve();
                    return;
                }
                
                this.socket = io('http://localhost:3000'); // Aggiorna con il tuo URL
                
                this.socket.on('connect', () => {
                    console.log('🔗 Socket.IO connesso');
                    this.socket.emit('register', this.currentUser.id);
                    resolve();
                });
                
                this.socket.on('disconnect', () => {
                    console.log('🔌 Socket.IO disconnesso');
                });
                
                this.socket.on('new_message', (data) => {
                    this.handleNewMessage(data);
                });
                
                this.socket.on('user_typing', (data) => {
                    this.handleUserTyping(data);
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
            // Chat già aperta, toggle minimize/maximize
            const chatWindow = this.openChats.get(windowId);
            if (chatWindow.classList.contains('minimized')) {
                this.maximizeChat(windowId);
            } else {
                this.minimizeChat(windowId);
            }
        } else {
            // Apri nuova chat
            await this.openChat(chatType, chatId, praticaId);
        }
    }
    
    async openChat(chatType, chatId = null, praticaId = null) {
        let chatData;
        
        if (chatType === 'globale') {
            chatData = { id: 1, type: 'globale', name: 'Chat Globale' };
        } else if (chatType === 'pratica') {
            chatData = await this.getOrCreatePraticaChat(praticaId);
        } else if (chatType === 'privata') {
            chatData = await this.getOrCreatePrivateChat(chatId);
        }
        
        const windowId = chatId || (chatType === 'globale' ? 'globale' : `pratica-${praticaId}`);
        
        // Crea finestra chat
        const chatWindow = this.createChatWindow(chatData, windowId);
        this.openChats.set(windowId, chatWindow);
        
        // Carica messaggi
        await this.loadChatMessages(chatData.id, windowId);
        
        // Aggiungi alla sessione
        this.chatSessions.add(windowId);
        this.saveChatSessions();
        
        // Reset notifiche
        this.resetNotifications(windowId);
    }
    
    createChatWindow(chatData, windowId) {
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chat-window show';
        chatWindow.id = `chat-${windowId}`;
        
        // Calcola posizione (ogni chat si sposta a sinistra)
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
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Caricamento messaggi...
                </div>
            </div>
            <div class="typing-indicator" id="typing-${windowId}">
                <i class="fas fa-ellipsis-h"></i> Qualcuno sta scrivendo...
            </div>
            <div class="chat-input-area">
                ${chatData.type === 'pratica' ? this.getPraticaSelect() : ''}
                <textarea class="chat-input" id="chat-input-${windowId}" 
                         placeholder="Scrivi un messaggio..." 
                         onkeydown="chatSystem.handleKeyPress(event, '${windowId}', '${chatData.id}')"
                         oninput="chatSystem.handleTyping('${chatData.id}')"></textarea>
                <button class="chat-send-btn" onclick="chatSystem.sendMessage('${windowId}', '${chatData.id}')">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(chatWindow);
        return chatWindow;
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
        // Se è una chat pratiche, aggiungi il select per scegliere il cliente
        return `
            <select class="pratica-select" id="pratica-select" onchange="chatSystem.changePratica()">
                <option value="">Seleziona una pratica...</option>
                <!-- Popolato dinamicamente -->
            </select>
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
            
            // Marca messaggi come letti
            await this.markAsRead(chatId);
            
        } catch (error) {
            console.error('Errore caricamento messaggi:', error);
        }
    }
    
    appendMessage(windowId, message, isNew = true) {
        const chatBody = document.getElementById(`chat-body-${windowId}`);
        const isOwn = message.user_id == this.currentUser.id;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${isOwn ? 'own' : 'other'}`;
        
        const time = new Date(message.created_at).toLocaleTimeString('it-IT', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        messageDiv.innerHTML = `
            <div class="chat-message-content">${this.escapeHtml(message.message)}</div>
            <div class="chat-message-meta">
                ${!isOwn ? message.user_name + ' • ' : ''}${time}
                ${isOwn ? '<span class="message-status message-sent"><i class="fas fa-check"></i></span>' : ''}
            </div>
        `;
        
        chatBody.appendChild(messageDiv);
        
        if (isNew) {
            // Animazione entrata
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(20px)';
            setTimeout(() => {
                messageDiv.style.transition = 'all 0.3s ease';
                messageDiv.style.opacity = '1';
                messageDiv.style.transform = 'translateY(0)';
            }, 10);
            
            // Scorri alla fine
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }
    
    async sendMessage(windowId, chatId) {
        const input = document.getElementById(`chat-input-${windowId}`);
        const message = input.value.trim();
        
        if (!message) return;
        
        try {
            const response = await fetch('/api/send_message.php', {
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
                // Il messaggio verrà aggiunto via Socket.IO se disponibile, altrimenti ricarica
                input.value = '';
                input.style.height = 'auto';
                
                if (this.socket && this.socket.connected) {
                    // Emetti via socket per real-time
                    this.socket.emit('send_message', {
                        chat_id: chatId,
                        message: message,
                        user_id: this.currentUser.id,
                        user_name: this.currentUser.name
                    });
                } else {
                    // Fallback: aggiungi messaggio direttamente e ricarica dopo poco
                    this.appendMessage(windowId, {
                        message: message,
                        user_id: this.currentUser.id,
                        user_name: this.currentUser.name,
                        created_at: new Date().toISOString()
                    }, true);
                    
                    // Ricarica messaggi dopo 1 secondo per sincronizzazione
                    setTimeout(() => {
                        this.loadChatMessages(chatId, windowId);
                    }, 1000);
                }
            } else {
                throw new Error(result.error);
            }
            
        } catch (error) {
            console.error('Errore invio messaggio:', error);
            alert('Errore nell\'invio del messaggio');
        }
    }
    
    handleKeyPress(event, windowId, chatId) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage(windowId, chatId);
        }
        
        // Auto-resize textarea
        const input = event.target;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 60) + 'px';
    }
    
    handleTyping(chatId) {
        // Implementa indicatore "sta scrivendo"
        this.socket.emit('typing', {
            chat_id: chatId,
            user_id: this.currentUser.id,
            user_name: this.currentUser.name
        });
    }
    
    handleNewMessage(data) {
        const windowId = this.getChatWindowId(data.chat_id, data.chat_type);
        
        if (this.openChats.has(windowId)) {
            // Chat aperta, aggiungi messaggio
            this.appendMessage(windowId, data, true);
            
            // Marca come letto se la finestra è visibile
            if (!this.openChats.get(windowId).classList.contains('minimized')) {
                this.markAsRead(data.chat_id);
            }
        } else {
            // Chat chiusa, incrementa notifiche
            this.incrementNotification(windowId);
        }
        
        // Riproduci suono notifica se non è il mittente
        if (data.user_id != this.currentUser.id) {
            this.playNotificationSound();
        }
    }
    
    handleUserTyping(data) {
        if (data.user_id === this.currentUser.id) return;
        
        const windowId = this.getChatWindowId(data.chat_id);
        const typingIndicator = document.getElementById(`typing-${windowId}`);
        
        if (typingIndicator) {
            typingIndicator.textContent = `${data.user_name} sta scrivendo...`;
            typingIndicator.classList.add('show');
            
            // Nascondi dopo 3 secondi
            clearTimeout(this.typingTimeouts.get(windowId));
            this.typingTimeouts.set(windowId, setTimeout(() => {
                typingIndicator.classList.remove('show');
            }, 3000));
        }
    }
    
    getChatWindowId(chatId, chatType = null) {
        if (chatId === 1 || chatType === 'globale') {
            return 'globale';
        }
        // Per chat pratiche e private, usa l'ID della chat
        return `chat-${chatId}`;
    }
    
    incrementNotification(windowId) {
        const currentCount = this.notifications.get(windowId) || 0;
        const newCount = currentCount + 1;
        this.notifications.set(windowId, newCount);
        
        // Aggiorna UI
        this.updateNotificationDisplay(windowId, newCount);
    }
    
    resetNotifications(windowId) {
        this.notifications.set(windowId, 0);
        this.updateNotificationDisplay(windowId, 0);
    }
    
    updateNotificationDisplay(windowId, count) {
        // Aggiorna badge nel footer
        const footerTypes = ['globale', 'pratiche'];
        footerTypes.forEach(type => {
            if (windowId.includes(type) || windowId === type) {
                const badge = document.getElementById(`notif-${type}`);
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        });
    }
    
    async loadNotifications() {
        try {
            const response = await fetch('/api/chat_notifications.php');
            const notifications = await response.json();
            
            notifications.forEach(notif => {
                const windowId = this.getChatWindowId(notif.chat_id, notif.chat_type);
                this.notifications.set(windowId, notif.unread_count);
                this.updateNotificationDisplay(windowId, notif.unread_count);
            });
        } catch (error) {
            console.error('Errore caricamento notifiche:', error);
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
                // Estrai chat_id dall'elemento
                // Implementa logica per ottenere chat_id
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
                    <h5>Inizia una nuova chat</h5>
                    <button class="chat-list-close" onclick="this.closest('.chat-list-modal').remove()">×</button>
                </div>
                <div class="chat-list-body" id="user-list">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Caricamento utenti...
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Carica lista utenti
        try {
            const response = await fetch('/api/users_list.php');
            const users = await response.json();
            
            const userList = document.getElementById('user-list');
            userList.innerHTML = '';
            
            users.forEach(user => {
                if (user.id !== this.currentUser.id) {
                    const userDiv = document.createElement('div');
                    userDiv.className = 'user-list-item';
                    userDiv.onclick = () => {
                        this.startPrivateChat(user.id, user.name);
                        modal.remove();
                    };
                    
                    userDiv.innerHTML = `
                        <div class="user-avatar">${user.name.charAt(0).toUpperCase()}</div>
                        <div class="user-info">
                            <div class="user-name">${user.name}</div>
                            <div class="user-status ${user.online ? 'user-online' : ''}">
                                ${user.online ? 'Online' : 'Offline'}
                            </div>
                        </div>
                    `;
                    
                    userList.appendChild(userDiv);
                }
            });
        } catch (error) {
            console.error('Errore caricamento utenti:', error);
        }
    }
    
    async startPrivateChat(userId, userName) {
        try {
            // Crea o recupera chat privata
            const response = await fetch('/api/create_private_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    current_user_id: this.currentUser.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await this.openChat('privata', result.chat_id);
            }
        } catch (error) {
            console.error('Errore creazione chat privata:', error);
        }
    }
    
    async showPraticheModal() {
        // Mostra modal per selezionare pratica o apri chat pratiche generale
        await this.toggleChat('pratica', null, 'generale');
    }
    
    async markAsRead(chatId) {
        try {
            await fetch('/api/mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    chat_id: chatId,
                    user_id: this.currentUser.id
                })
            });
        } catch (error) {
            console.error('Errore aggiornamento lettura:', error);
        }
    }
    
    saveChatSessions() {
        localStorage.setItem('chat_sessions', JSON.stringify([...this.chatSessions]));
    }
    
    async loadChatSessions() {
        try {
            const sessions = JSON.parse(localStorage.getItem('chat_sessions') || '[]');
            
            for (const windowId of sessions) {
                // Ripristina chat della sessione precedente (minimizzate)
                if (windowId === 'globale') {
                    await this.openChat('globale');
                    this.minimizeChat('globale');
                }
                // Aggiungi logica per altri tipi di chat
            }
        } catch (error) {
            console.error('Errore caricamento sessioni:', error);
        }
    }
    
    playNotificationSound() {
        // Suono notifica discreto
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBS2A1vLFdicHKYS+8daFOgwUbrt662ALEM');
            audio.volume = 0.1;
            audio.play().catch(() => {}); // Ignora errori
        } catch (error) {
            // Ignora errori audio
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    updateUserStatus(userId, online) {
        // Aggiorna indicatori di stato utente online/offline
        document.querySelectorAll(`.user-status[data-user="${userId}"]`).forEach(el => {
            el.textContent = online ? 'Online' : 'Offline';
            el.className = `user-status ${online ? 'user-online' : ''}`;
        });
    }
    
    async getOrCreatePraticaChat(praticaId) {
        try {
            const response = await fetch(`/api/get_pratica_chat.php?pratica_id=${praticaId || ''}`);
            const result = await response.json();
            
            if (result.success) {
                return {
                    id: result.chat_id,
                    type: result.type,
                    name: result.name,
                    pratica_id: result.pratica_id || null
                };
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Errore creazione chat pratica:', error);
            throw error;
        }
    }
    
    async getOrCreatePrivateChat(userId) {
        try {
            const response = await fetch('/api/create_private_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: userId,
                    current_user_id: this.currentUser.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Ottieni nome dell'altro utente
                const userResponse = await fetch('/api/users_list.php');
                const users = await userResponse.json();
                const otherUser = users.find(u => u.id == userId);
                
                return {
                    id: result.chat_id,
                    type: 'privata',
                    name: `Chat con ${otherUser ? otherUser.name : 'Utente'}`
                };
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Errore creazione chat privata:', error);
            throw error;
        }
    }
}

// Inizializza il sistema chat quando il DOM è pronto
document.addEventListener('DOMContentLoaded', () => {
    window.chatSystem = new ChatSystem();
});
