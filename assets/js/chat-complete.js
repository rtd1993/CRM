/**
 * FOOTER CHAT SYSTEM - WHATSAPP LIKE
 * Sistema chat completo secondo README_CHAT_SYSTEM.md
 * Supporta: Chat Globale, Chat Pratiche, Chat Private
 */

console.log('🚀 Inizializzazione Complete Chat System');

// Configurazione
window.completeChatConfig = {
    userId: null, // Sarà impostato dal PHP
    userName: null, // Sarà impostato dal PHP
    apiBase: '/api/chat/',
    pollingInterval: 3000,
    maxMessageLength: 1000,
    debug: true
};

class CompleteChatSystem {
    constructor() {
        this.isInitialized = false;
        this.currentChat = null;
        this.isVisible = false;
        this.pollingTimer = null;
        this.lastMessageIds = {};
        this.unreadCounts = {};
        this.privateChats = new Map();
        this.onlineUsers = new Map();
        
        // Elementi DOM
        this.elements = {};
        
        // Configurazione
        this.config = window.completeChatConfig || {};
        this.apiBase = this.config.apiBase || '/api/chat/';
        this.pollingInterval = this.config.pollingInterval || 3000;
        
        this.init();
    }
    
    /**
     * Inizializzazione sistema
     */
    async init() {
        try {
            this.log('🔧 Inizio inizializzazione Complete Chat System...');
            
            this.log('📌 Binding elementi DOM...');
            this.bindElements();
            
            this.log('🎪 Binding eventi...');
            this.bindEvents();
            
            this.log('💾 Caricamento dati iniziali...');
            await this.loadInitialData();
            
            this.log('⏱️ Avvio polling...');
            this.startPolling();
            
            this.isInitialized = true;
            this.log('✅ Complete Chat System inizializzato con successo');

        } catch (error) {
            this.log('❌ Errore inizializzazione chat:', error);
            console.error('Stack trace:', error.stack);
        }
    }
    
    /**
     * Collega elementi DOM
     */
    bindElements() {
        this.elements = {
            // Elementi principali
            widget: document.getElementById('chat-footer-widget'),
            toggleBtn: document.getElementById('chat-toggle-btn'),
            panel: document.getElementById('chat-panel'),
            minimizeBtn: document.getElementById('chat-minimize-btn'),
            
            // Badge e contatori
            totalBadge: document.getElementById('total-unread-badge'),
            globalBadge: document.getElementById('global-chat-badge'),
            practiceBadge: document.getElementById('practice-chat-badge'),
            
            // Chat list
            listContainer: document.getElementById('chat-list-container'),
            globalItem: document.querySelector('[data-type="globale"]'),
            practiceItem: document.querySelector('[data-type="pratiche"]'),
            clientSelector: document.getElementById('client-selector'),
            privatesList: document.getElementById('private-chats-list'),
            newPrivateBtn: document.getElementById('new-private-chat-btn'),
            
            // Chat window
            chatWindow: document.getElementById('chat-window'),
            backBtn: document.getElementById('back-to-list-btn'),
            windowTitle: document.getElementById('chat-window-title'),
            windowStatus: document.getElementById('chat-window-status'),
            messagesArea: document.getElementById('chat-messages-area'),
            messageInput: document.getElementById('chat-message-input'),
            sendBtn: document.getElementById('send-message-btn'),
            
            // Modal
            userModal: document.getElementById('userSelectionModal'),
            usersList: document.getElementById('users-list')
        };
        
        // Verifica elementi critici
        const required = ['widget', 'toggleBtn', 'panel', 'chatWindow'];
        for (const el of required) {
            if (!this.elements[el]) {
                throw new Error(`Elemento richiesto non trovato: ${el}`);
            }
        }
        
        this.log('📋 Elementi DOM collegati:', {
            widget: !!this.elements.widget,
            toggleBtn: !!this.elements.toggleBtn,
            panel: !!this.elements.panel,
            chatWindow: !!this.elements.chatWindow
        });
    }
    
    /**
     * Collega eventi DOM
     */
    bindEvents() {
        // Toggle panel
        this.elements.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.log('🎯 Toggle panel clicked');
            this.togglePanel();
        });
        
        // Minimize panel
        this.elements.minimizeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.hidePanel();
        });
        
        // Chat globale
        if (this.elements.globalItem) {
            this.elements.globalItem.addEventListener('click', () => {
                this.log('📞 Apertura chat globale');
                this.openChat('globale', 1, 'Chat Generale');
            });
        }
        
        // Selezione cliente per pratiche
        if (this.elements.clientSelector) {
            this.elements.clientSelector.addEventListener('change', (e) => {
                const clientId = e.target.value;
                if (clientId) {
                    const clientName = e.target.options[e.target.selectedIndex].text;
                    this.log('📝 Apertura chat pratica per cliente:', clientName);
                    this.openChat('pratica', clientId, `Pratica: ${clientName}`);
                }
            });
        }
        
        // Nuova chat privata
        if (this.elements.newPrivateBtn) {
            this.elements.newPrivateBtn.addEventListener('click', () => {
                this.log('👥 Richiesta nuova chat privata');
                this.showUserSelectionModal();
            });
        }
        
        // Back to list
        if (this.elements.backBtn) {
            this.elements.backBtn.addEventListener('click', () => {
                this.showChatList();
            });
        }
        
        // Invio messaggio
        if (this.elements.sendBtn) {
            this.elements.sendBtn.addEventListener('click', () => {
                this.sendMessage();
            });
        }
        
        // Enter per inviare
        if (this.elements.messageInput) {
            this.elements.messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
        
        // Chiudi panel cliccando fuori
        document.addEventListener('click', (e) => {
            if (this.isVisible && !this.elements.widget.contains(e.target)) {
                this.hidePanel();
            }
        });
        
        // Prevent panel close quando si clicca dentro
        this.elements.panel.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    /**
     * Carica dati iniziali
     */
    async loadInitialData() {
        try {
            this.log('💾 Caricamento dati iniziali...');
            
            // Carica chat private esistenti
            await this.loadPrivateChats();
            
            // Carica utenti online
            await this.loadOnlineUsers();
            
            // Carica contatori non letti
            await this.updateUnreadCounts();
            
            this.log('✅ Dati iniziali caricati con successo');
            
        } catch (error) {
            this.log('❌ Errore caricamento dati iniziali:', error);
            
            // Se è un errore di autenticazione, nascondi il widget
            if (error.message && error.message.includes('401')) {
                this.log('🚫 Utente non autenticato, nascondo widget');
                if (this.elements.widget) {
                    this.elements.widget.style.display = 'none';
                }
                return;
            }
            
            // Per altri errori, continua comunque
            this.showError('Errore caricamento chat. Riprovo tra poco...');
        }
    }
    
    /**
     * Toggle visibility panel
     */
    togglePanel() {
        if (this.isVisible) {
            this.hidePanel();
        } else {
            this.showPanel();
        }
    }
    
    /**
     * Mostra panel
     */
    showPanel() {
        this.log('🔓 Apertura panel chat');
        this.log('📋 Panel element:', this.elements.panel);
        this.log('📋 Panel classes before:', this.elements.panel ? this.elements.panel.className : 'ELEMENT NOT FOUND');
        
        if (this.elements.panel) {
            this.elements.panel.classList.remove('hidden');
            this.log('📋 Panel classes after:', this.elements.panel.className);
        } else {
            this.log('❌ Panel element not found!');
        }
        
        this.elements.toggleBtn.classList.add('active');
        this.isVisible = true;
        
        // Aggiorna dati
        this.updateUnreadCounts();
        this.loadPrivateChats();
    }
    
    /**
     * Nascondi panel
     */
    hidePanel() {
        this.log('🔒 Chiusura panel chat');
        this.elements.panel.classList.add('hidden');
        this.elements.toggleBtn.classList.remove('active');
        this.isVisible = false;
        
        // Torna alla lista se in chat
        if (!this.elements.chatWindow.classList.contains('hidden')) {
            this.showChatList();
        }
    }
    
    /**
     * Apri una chat specifica
     */
    async openChat(type, id, title) {
        try {
            this.log('💬 Apertura chat:', {type, id, title});
            
            this.currentChat = { type, id, title };
            
            // Aggiorna header
            this.elements.windowTitle.textContent = title;
            this.updateChatStatus(type, id);
            
            // Abilita input
            this.elements.messageInput.disabled = false;
            this.elements.sendBtn.disabled = false;
            this.elements.messageInput.placeholder = 'Scrivi un messaggio...';
            
            // Carica cronologia
            await this.loadChatHistory(type, id);
            
            // Mostra chat window
            this.showChatWindow();
            
            // Segna come letti
            await this.markAsRead(type, id);
            
            // Focus input
            setTimeout(() => {
                this.elements.messageInput.focus();
            }, 300);
            
        } catch (error) {
            this.log('❌ Errore apertura chat:', error);
            this.showError('Errore nell\'apertura della chat');
        }
    }
    
    /**
     * Mostra chat window
     */
    showChatWindow() {
        this.elements.listContainer.classList.add('hidden');
        this.elements.chatWindow.classList.remove('hidden');
    }
    
    /**
     * Mostra lista chat
     */
    showChatList() {
        this.elements.chatWindow.classList.add('hidden');
        this.elements.listContainer.classList.remove('hidden');
        
        this.currentChat = null;
        
        // Disabilita input
        this.elements.messageInput.disabled = true;
        this.elements.sendBtn.disabled = true;
        this.elements.messageInput.placeholder = 'Seleziona una chat...';
        this.elements.messageInput.value = '';
    }
    
    /**
     * Aggiorna status chat
     */
    updateChatStatus(type, id) {
        let status = '';
        
        switch (type) {
            case 'globale':
                const onlineCount = Array.from(this.onlineUsers.values()).filter(u => u.is_online).length;
                status = `${onlineCount} utenti online`;
                break;
                
            case 'pratica':
                status = 'Chat pratica cliente';
                break;
                
            case 'privata':
                const user = this.onlineUsers.get(parseInt(id));
                status = user ? (user.is_online ? 'Online' : 'Offline') : 'Sconosciuto';
                break;
        }
        
        this.elements.windowStatus.textContent = status;
    }
    
    /**
     * Carica cronologia chat
     */
    async loadChatHistory(type, id) {
        try {
            this.showLoading();
            
            // Determina conversation_id in base al tipo
            let conversationId = null;
            
            if (type === 'globale') {
                conversationId = 1; // Chat globale sempre ID 1
            } else if (type === 'pratica') {
                // Trova o crea conversazione pratica per il cliente
                conversationId = await this.getOrCreatePracticeConversation(id);
            } else if (type === 'privata') {
                // Trova o crea conversazione privata
                conversationId = await this.getOrCreatePrivateConversation(id);
            }
            
            if (!conversationId) {
                throw new Error('Impossibile determinare conversation_id');
            }
            
            // Carica messaggi usando l'API semplificata
            const response = await fetch('/api/simple-chat/get_messages.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    conversation_id: conversationId,
                    limit: 50
                })
            });
            
            const data = await response.json();
            this.log('📩 Risposta cronologia:', data);
            
            if (data.success) {
                this.renderMessages(data.messages || []);
                this.scrollToBottom();
            } else {
                throw new Error(data.error || 'Errore caricamento cronologia');
            }
            
        } catch (error) {
            this.log('❌ Errore caricamento cronologia:', error);
            this.showError('Errore nel caricamento dei messaggi');
        } finally {
            this.hideLoading();
        }
    }
    
    /**
     * Ottieni o crea conversazione pratica
     */
    async getOrCreatePracticeConversation(clientId) {
        try {
            const response = await fetch(this.apiBase + 'conversations/get_practice.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    client_id: parseInt(clientId)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.conversation_id;
            } else {
                throw new Error(data.error || 'Errore creazione conversazione pratica');
            }
            
        } catch (error) {
            this.log('❌ Errore conversazione pratica:', error);
            return null;
        }
    }
    
    /**
     * Ottieni o crea conversazione privata
     */
    async getOrCreatePrivateConversation(otherUserId) {
        try {
            const response = await fetch(this.apiBase + 'conversations/create_private.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    other_user_id: parseInt(otherUserId)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                return data.conversation_id;
            } else {
                throw new Error(data.error || 'Errore creazione conversazione privata');
            }
            
        } catch (error) {
            this.log('❌ Errore conversazione privata:', error);
            return null;
        }
    }
    
    /**
     * Invia messaggio
     */
    async sendMessage() {
        const message = this.elements.messageInput.value.trim();
        
        if (!message || !this.currentChat) {
            return;
        }
        
        if (message.length > this.config.maxMessageLength) {
            this.showError(`Messaggio troppo lungo (max ${this.config.maxMessageLength} caratteri)`);
            return;
        }
        
        try {
            this.log('📤 Invio messaggio:', message);
            
            // Disabilita input temporaneamente
            this.elements.messageInput.disabled = true;
            this.elements.sendBtn.disabled = true;
            
            // Determina conversation_id
            let conversationId = null;
            
            if (this.currentChat.type === 'globale') {
                conversationId = 1;
            } else if (this.currentChat.type === 'pratica') {
                conversationId = await this.getOrCreatePracticeConversation(this.currentChat.id);
            } else if (this.currentChat.type === 'privata') {
                conversationId = await this.getOrCreatePrivateConversation(this.currentChat.id);
            }
            
            if (!conversationId) {
                throw new Error('Impossibile determinare conversation_id per invio');
            }
            
            // Invia messaggio usando API semplificata
            const response = await fetch('/api/simple-chat/send_message.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    conversation_id: conversationId,
                    message: message
                })
            });
            
            const data = await response.json();
            this.log('📨 Risposta invio:', data);
            
            if (data.success) {
                // Pulisci input
                this.elements.messageInput.value = '';
                
                // Aggiungi messaggio alla UI
                this.addMessageToUI({
                    id: data.message_id || Date.now(),
                    user_id: this.config.userId,
                    user_name: this.config.userName,
                    message: message,
                    created_at: new Date().toISOString(),
                    is_own: true
                });
                
                this.scrollToBottom();
                
            } else {
                throw new Error(data.error || 'Errore invio messaggio');
            }
            
        } catch (error) {
            this.log('❌ Errore invio messaggio:', error);
            this.showError('Errore nell\'invio del messaggio');
        } finally {
            // Riabilita input
            this.elements.messageInput.disabled = false;
            this.elements.sendBtn.disabled = false;
            this.elements.messageInput.focus();
        }
    }
    
    /**
     * Renderizza messaggi
     */
    renderMessages(messages) {
        this.elements.messagesArea.innerHTML = '';
        
        if (!messages || messages.length === 0) {
            this.elements.messagesArea.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="fas fa-comments fa-2x mb-3"></i>
                    <p>Nessun messaggio ancora.<br>Inizia la conversazione!</p>
                </div>
            `;
            return;
        }
        
        messages.forEach(msg => this.addMessageToUI(msg));
    }
    
    /**
     * Aggiungi messaggio alla UI
     */
    addMessageToUI(message) {
        const isOwn = message.user_id == this.config.userId;
        const messageDate = new Date(message.created_at);
        const timeString = messageDate.toLocaleTimeString('it-IT', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        const messageHTML = `
            <div class="message-bubble ${isOwn ? 'own' : ''}" data-message-id="${message.id}">
                ${!isOwn ? `<div class="message-sender">${this.escapeHtml(message.user_name)}</div>` : ''}
                <div class="message-content">
                    ${this.escapeHtml(message.message)}
                </div>
                <div class="message-meta">
                    <span class="message-time">${timeString}</span>
                    ${isOwn ? '<i class="fas fa-check message-status"></i>' : ''}
                </div>
            </div>
        `;
        
        this.elements.messagesArea.insertAdjacentHTML('beforeend', messageHTML);
    }
    
    /**
     * Carica chat private
     */
    async loadPrivateChats() {
        try {
            this.log('👥 Caricamento chat private...');
            // TODO: Implementare API per chat private
            this.renderPrivateChats([]);
            
        } catch (error) {
            this.log('❌ Errore caricamento chat private:', error);
        }
    }
    
    /**
     * Renderizza chat private
     */
    renderPrivateChats(chats) {
        this.elements.privatesList.innerHTML = '';
        
        if (!chats || chats.length === 0) {
            this.elements.privatesList.innerHTML = `
                <div class="no-private-chats" style="padding: 15px; text-align: center; color: #6c757d; font-size: 13px;">
                    Nessuna chat privata.<br>
                    Clicca + per iniziare.
                </div>
            `;
            return;
        }
        
        chats.forEach(chat => {
            const isOnline = this.onlineUsers.get(chat.other_user_id)?.is_online || false;
            const unreadCount = chat.unread_count || 0;
            
            const chatHTML = `
                <div class="chat-item" data-type="privata" data-id="${chat.other_user_id}" data-chat-id="${chat.id}">
                    <div class="chat-avatar user" style="position: relative;">
                        ${chat.other_user_name.charAt(0).toUpperCase()}
                        <div class="online-indicator ${isOnline ? '' : 'offline'}"></div>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name">${this.escapeHtml(chat.other_user_name)}</div>
                        <div class="chat-last-message">${chat.last_message ? this.escapeHtml(chat.last_message) : 'Nessun messaggio'}</div>
                    </div>
                    <div class="chat-meta">
                        ${chat.last_message_time ? `<span class="chat-time">${this.formatTime(chat.last_message_time)}</span>` : ''}
                        ${unreadCount > 0 ? `<span class="chat-unread-badge">${unreadCount}</span>` : ''}
                    </div>
                </div>
            `;
            
            this.elements.privatesList.insertAdjacentHTML('beforeend', chatHTML);
        });
        
        // Aggiungi event listeners
        this.elements.privatesList.querySelectorAll('.chat-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.id;
                const userName = item.querySelector('.chat-name').textContent;
                this.openChat('privata', userId, userName);
            });
        });
    }
    
    /**
     * Mostra modal selezione utente
     */
    async showUserSelectionModal() {
        try {
            await this.loadOnlineUsers();
            this.renderUsersList();
            
            // Mostra modal (Bootstrap)
            if (window.bootstrap && this.elements.userModal) {
                const modal = new bootstrap.Modal(this.elements.userModal);
                modal.show();
            }
            
        } catch (error) {
            this.log('❌ Errore caricamento utenti:', error);
            this.showError('Errore nel caricamento degli utenti');
        }
    }
    
    /**
     * Carica utenti online
     */
    async loadOnlineUsers() {
        try {
            // TODO: Implementare API per utenti online
            // Per ora usa dati mock
            this.onlineUsers.clear();
            this.onlineUsers.set(1, {id: 1, name: 'Admin', is_online: true});
            this.onlineUsers.set(2, {id: 2, name: 'Roberto', is_online: true});
            
        } catch (error) {
            this.log('❌ Errore caricamento utenti:', error);
        }
    }
    
    /**
     * Renderizza lista utenti
     */
    renderUsersList() {
        if (!this.elements.usersList) return;
        
        this.elements.usersList.innerHTML = '';
        
        Array.from(this.onlineUsers.values()).forEach(user => {
            // Non mostrare se stesso
            if (user.id == this.config.userId) return;
            
            const userHTML = `
                <button class="list-group-item list-group-item-action d-flex align-items-center" 
                        data-user-id="${user.id}" data-user-name="${this.escapeHtml(user.name)}">
                    <div class="chat-avatar user me-3" style="width: 40px; height: 40px; font-size: 14px; position: relative;">
                        ${user.name.charAt(0).toUpperCase()}
                        <div class="online-indicator ${user.is_online ? '' : 'offline'}"></div>
                    </div>
                    <div>
                        <div class="fw-semibold">${this.escapeHtml(user.name)}</div>
                        <small class="text-muted">${user.is_online ? 'Online' : 'Offline'}</small>
                    </div>
                </button>
            `;
            
            this.elements.usersList.insertAdjacentHTML('beforeend', userHTML);
        });
        
        // Event listeners
        this.elements.usersList.querySelectorAll('.list-group-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.dataset.userId;
                const userName = item.dataset.userName;
                
                // Chiudi modal
                if (window.bootstrap && this.elements.userModal) {
                    const modal = bootstrap.Modal.getInstance(this.elements.userModal);
                    if (modal) modal.hide();
                }
                
                // Apri chat
                this.openChat('privata', userId, userName);
            });
        });
    }
    
    /**
     * Aggiorna contatori non letti
     */
    async updateUnreadCounts() {
        try {
            // TODO: Implementare API per contatori non letti
            // Per ora usa valori mock
            const counts = {
                globale: 0,
                pratiche: 0,
                private: 0
            };
            
            // Aggiorna badge
            this.updateBadge(this.elements.globalBadge, counts.globale);
            this.updateBadge(this.elements.practiceBadge, counts.pratiche);
            
            // Badge totale
            const total = Object.values(counts).reduce((sum, count) => sum + count, 0);
            this.updateBadge(this.elements.totalBadge, total);
            
        } catch (error) {
            this.log('❌ Errore aggiornamento contatori:', error);
        }
    }
    
    /**
     * Aggiorna badge
     */
    updateBadge(element, count) {
        if (!element) return;
        
        if (count > 0) {
            element.textContent = count > 99 ? '99+' : count;
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    }
    
    /**
     * Segna come letti
     */
    async markAsRead(type, id) {
        try {
            // TODO: Implementare API mark as read
            this.log('📖 Messaggi segnati come letti per:', type, id);
            
            // Aggiorna contatori
            setTimeout(() => {
                this.updateUnreadCounts();
            }, 500);
            
        } catch (error) {
            this.log('❌ Errore mark as read:', error);
        }
    }
    
    /**
     * Avvia polling
     */
    startPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }
        
        this.pollingTimer = setInterval(() => {
            this.pollUpdates();
        }, this.pollingInterval);
        
        this.log('⏰ Polling avviato ogni', this.pollingInterval, 'ms');
    }
    
    /**
     * Polling aggiornamenti
     */
    async pollUpdates() {
        try {
            // Aggiorna contatori
            await this.updateUnreadCounts();
            
            // Se in una chat, controlla nuovi messaggi
            if (this.currentChat && this.isVisible) {
                await this.checkNewMessages();
            }
            
            // Aggiorna utenti online
            await this.loadOnlineUsers();
            
        } catch (error) {
            this.log('❌ Errore polling:', error);
        }
    }
    
    /**
     * Controlla nuovi messaggi
     */
    async checkNewMessages() {
        if (!this.currentChat) return;
        
        try {
            // TODO: Implementare controllo nuovi messaggi per chat corrente
            this.log('🔍 Controllo nuovi messaggi per:', this.currentChat);
            
        } catch (error) {
            this.log('❌ Errore controllo nuovi messaggi:', error);
        }
    }
    
    /**
     * Scroll al bottom
     */
    scrollToBottom() {
        setTimeout(() => {
            this.elements.messagesArea.scrollTop = this.elements.messagesArea.scrollHeight;
        }, 100);
    }
    
    /**
     * Mostra loading
     */
    showLoading() {
        this.elements.messagesArea.innerHTML = `
            <div class="loading-messages">
                <i class="fas fa-spinner fa-spin"></i> Caricamento messaggi...
            </div>
        `;
    }
    
    /**
     * Nascondi loading
     */
    hideLoading() {
        const loading = this.elements.messagesArea.querySelector('.loading-messages');
        if (loading) {
            loading.remove();
        }
    }
    
    /**
     * Mostra errore
     */
    showError(message) {
        this.log('ERROR:', message);
        
        this.elements.messagesArea.innerHTML = `
            <div style="text-align: center; color: #dc3545; padding: 40px 20px;">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p>${this.escapeHtml(message)}</p>
                <button onclick="window.completeChatSystem.loadChatHistory('${this.currentChat?.type}', '${this.currentChat?.id}')" 
                        style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                    Riprova
                </button>
            </div>
        `;
    }
    
    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Formatta tempo
     */
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'ora';
        if (diffMins < 60) return `${diffMins}m`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h`;
        
        return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
    }
    
    /**
     * Logging
     */
    log(...args) {
        if (this.config.debug) {
            console.log('[CompleteChat]', ...args);
        }
    }
    
    /**
     * Cleanup
     */
    destroy() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }
        
        this.isInitialized = false;
        this.log('Sistema chat distrutto');
    }
}

// Inizializzazione automatica
document.addEventListener('DOMContentLoaded', function() {
    // Verifica che sia presente la configurazione
    if (typeof window.completeChatConfig !== 'undefined') {
        // Inizializza sistema chat
        window.completeChatSystem = new CompleteChatSystem();
        
        // Cleanup su unload
        window.addEventListener('beforeunload', () => {
            if (window.completeChatSystem) {
                window.completeChatSystem.destroy();
            }
        });
    }
});

// Export per testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CompleteChatSystem;
}
