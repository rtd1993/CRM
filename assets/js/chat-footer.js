/**
 * FOOTER CHAT SYSTEM - WHATSAPP LIKE
 * Sistema chat completo con gestione globale, pratiche e private
 * Data: 2025-09-02
 */


class ChatFooterSystem {
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
        this.config = window.chatConfig || {};
        this.apiBase = this.config.apiBase || 'api/chat/';
        this.pollingInterval = this.config.pollingInterval || 3000;
        
        this.init();
    }
    
    /**
     * Inizializzazione sistema
     */
    async init() {
        try {
            this.bindElements();
            this.bindEvents();
            await this.loadInitialData();
            this.startPolling();
            this.isInitialized = true;
            
            this.log('Sistema chat inizializzato con successo');
            
        } catch (error) {
            this.log('Errore inizializzazione chat:', error);
        }
    }
    
    /**
     * Collega elementi DOM
     */
    bindElements() {
        this.elements = {
            // Elementi principali
            widget: document.getElementById('chatFooterWidget'),
            toggleBtn: document.getElementById('chatToggleBtn'),
            panel: document.getElementById('chatPanel'),
            minimizeBtn: document.getElementById('chatMinimizeBtn'),
            
            // Badge e contatori
            totalBadge: document.getElementById('chatTotalBadge'),
            globalBadge: document.getElementById('globalChatBadge'),
            practiceBadge: document.getElementById('practiceChatBadge'),
            
            // Chat list
            listContainer: document.getElementById('chatListContainer'),
            globalItem: document.getElementById('globalChatItem'),
            practiceItem: document.getElementById('practiceChatItem'),
            clientSelector: document.getElementById('clientSelector'),
            privatesList: document.getElementById('privateChatsList'),
            newPrivateBtn: document.getElementById('newPrivateChatBtn'),
            
            // Chat window
            chatWindow: document.getElementById('chatWindow'),
            backBtn: document.getElementById('backToListBtn'),
            windowTitle: document.getElementById('chatWindowTitle'),
            windowStatus: document.getElementById('chatWindowStatus'),
            messagesArea: document.getElementById('chatMessagesArea'),
            messageInput: document.getElementById('chatMessageInput'),
            sendBtn: document.getElementById('sendMessageBtn'),
            
            // Modal
            userModal: document.getElementById('userSelectionModal'),
            usersList: document.getElementById('usersList'),
            
            // Loading
            loadingIndicator: document.getElementById('loadingIndicator')
        };
        
        // Verifica elementi critici
        const required = ['widget', 'toggleBtn', 'panel', 'chatWindow'];
        for (const el of required) {
            if (!this.elements[el]) {
                throw new Error(`Elemento richiesto non trovato: ${el}`);
            }
        }
    }
    
    /**
     * Collega eventi DOM
     */
    bindEvents() {
        // Toggle panel
        this.elements.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.togglePanel();
        });
        
        // Minimize panel
        this.elements.minimizeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.hidePanel();
        });
        
        // Chat items
        this.elements.globalItem.addEventListener('click', () => {
            this.openChat('globale', 1, 'Chat Generale');
        });
        
        // Selezione cliente per pratiche
        this.elements.clientSelector.addEventListener('change', (e) => {
            const clientId = e.target.value;
            if (clientId) {
                const clientName = e.target.options[e.target.selectedIndex].text;
                this.openChat('pratica', clientId, `Pratica: ${clientName}`);
            }
        });
        
        // Nuova chat privata
        this.elements.newPrivateBtn.addEventListener('click', () => {
            this.showUserSelectionModal();
        });
        
        // Back to list
        this.elements.backBtn.addEventListener('click', () => {
            this.showChatList();
        });
        
        // Invio messaggio
        this.elements.sendBtn.addEventListener('click', () => {
            this.sendMessage();
        });
        
        // Enter per inviare
        this.elements.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
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
            this.log('Caricamento dati iniziali...');
            
            // Carica chat private esistenti
            await this.loadPrivateChats();
            
            // Carica utenti online
            await this.loadOnlineUsers();
            
            // Carica contatori non letti
            await this.updateUnreadCounts();
            
            this.log('Dati iniziali caricati con successo');
            
        } catch (error) {
            this.log('Errore caricamento dati iniziali:', error);
            
            // Se è un errore di autenticazione, nascondi il widget
            if (error.message && error.message.includes('401')) {
                this.log('Utente non autenticato, nascondo widget');
                if (this.elements.widget) {
                    this.elements.widget.style.display = 'none';
                }
                return;
            }
            
            // Per altri errori, mostra un messaggio ma continua
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
        this.elements.panel.classList.add('visible');
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
        this.elements.panel.classList.remove('visible');
        this.elements.toggleBtn.classList.remove('active');
        this.isVisible = false;
        
        // Torna alla lista se in chat
        if (this.elements.chatWindow.classList.contains('visible')) {
            this.showChatList();
        }
    }
    
    /**
     * Apri una chat specifica
     */
    async openChat(type, id, title) {
        try {
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
            this.log('Errore apertura chat:', error);
            this.showError('Errore nell\'apertura della chat');
        }
    }
    
    /**
     * Mostra chat window
     */
    showChatWindow() {
        this.elements.listContainer.style.display = 'none';
        this.elements.chatWindow.classList.add('visible');
    }
    
    /**
     * Mostra lista chat
     */
    showChatList() {
        this.elements.chatWindow.classList.remove('visible');
        setTimeout(() => {
            this.elements.listContainer.style.display = 'block';
        }, 300);
        
        this.currentChat = null;
        
        // Disabilita input
        this.elements.messageInput.disabled = true;
        this.elements.sendBtn.disabled = true;
        this.elements.messageInput.placeholder = 'Seleziona una chat...';
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
            
            const response = await this.apiCall('messages/get_history.php', {
                type: type,
                id: id,
                limit: 50
            });
            
            if (response.success) {
                this.renderMessages(response.messages);
                this.scrollToBottom();
            } else {
                throw new Error(response.error || 'Errore caricamento cronologia');
            }
            
        } catch (error) {
            this.log('Errore caricamento cronologia:', error);
            this.showError('Errore nel caricamento dei messaggi');
        } finally {
            this.hideLoading();
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
            // Disabilita input temporaneamente
            this.elements.messageInput.disabled = true;
            this.elements.sendBtn.disabled = true;
            
            const response = await this.apiCall('messages/send.php', {
                type: this.currentChat.type,
                id: this.currentChat.id,
                message: message
            });
            
            if (response.success) {
                // Pulisci input
                this.elements.messageInput.value = '';
                
                // Aggiungi messaggio alla UI
                this.addMessageToUI({
                    id: response.message_id,
                    user_id: this.config.currentUserId,
                    user_name: this.config.currentUserName,
                    message: message,
                    created_at: new Date().toISOString(),
                    is_own: true
                });
                
                this.scrollToBottom();
                
            } else {
                throw new Error(response.error || 'Errore invio messaggio');
            }
            
        } catch (error) {
            this.log('Errore invio messaggio:', error);
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
        const isOwn = message.user_id == this.config.currentUserId;
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
            const response = await this.apiCall('conversations/get_private.php');
            
            if (response.success) {
                this.renderPrivateChats(response.chats);
            }
            
        } catch (error) {
            this.log('Errore caricamento chat private:', error);
        }
    }
    
    /**
     * Renderizza chat private
     */
    renderPrivateChats(chats) {
        this.elements.privatesList.innerHTML = '';
        
        if (!chats || chats.length === 0) {
            this.elements.privatesList.innerHTML = `
                <div style="padding: 20px; text-align: center; color: #6c757d; font-size: 13px;">
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
                    <div class="chat-avatar user">
                        ${chat.other_user_name.charAt(0).toUpperCase()}
                        ${isOnline ? '<div class="online-indicator"></div>' : '<div class="online-indicator offline"></div>'}
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
            if (window.bootstrap) {
                const modal = new bootstrap.Modal(this.elements.userModal);
                modal.show();
            }
            
        } catch (error) {
            this.log('Errore caricamento utenti:', error);
            this.showError('Errore nel caricamento degli utenti');
        }
    }
    
    /**
     * Carica utenti online
     */
    async loadOnlineUsers() {
        try {
            const response = await this.apiCall('users/get_list.php');
            
            if (response.success) {
                this.onlineUsers.clear();
                response.users.forEach(user => {
                    this.onlineUsers.set(user.id, user);
                });
            }
            
        } catch (error) {
            this.log('Errore caricamento utenti:', error);
        }
    }
    
    /**
     * Renderizza lista utenti
     */
    renderUsersList() {
        this.elements.usersList.innerHTML = '';
        
        Array.from(this.onlineUsers.values()).forEach(user => {
            const userHTML = `
                <button class="list-group-item list-group-item-action d-flex align-items-center" 
                        data-user-id="${user.id}" data-user-name="${this.escapeHtml(user.name)}">
                    <div class="chat-avatar user me-3" style="width: 40px; height: 40px; font-size: 14px;">
                        ${user.name.charAt(0).toUpperCase()}
                        ${user.is_online ? '<div class="online-indicator"></div>' : '<div class="online-indicator offline"></div>'}
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
                if (window.bootstrap) {
                    const modal = bootstrap.Modal.getInstance(this.elements.userModal);
                    modal.hide();
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
            const response = await this.apiCall('notifications/get_unread.php');
            
            if (response.success) {
                const counts = response.counts;
                
                // Aggiorna badge
                this.updateBadge(this.elements.globalBadge, counts.globale);
                this.updateBadge(this.elements.practiceBadge, counts.pratiche);
                
                // Badge totale
                const total = Object.values(counts).reduce((sum, count) => sum + count, 0);
                this.updateBadge(this.elements.totalBadge, total);
            }
            
        } catch (error) {
            this.log('Errore aggiornamento contatori:', error);
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
            await this.apiCall('notifications/mark_read.php', {
                type: type,
                id: id
            });
            
            // Aggiorna contatori
            this.updateUnreadCounts();
            
        } catch (error) {
            this.log('Errore mark as read:', error);
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
        
        this.log('Polling avviato ogni', this.pollingInterval, 'ms');
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
            this.log('Errore polling:', error);
        }
    }
    
    /**
     * Controlla nuovi messaggi
     */
    async checkNewMessages() {
        if (!this.currentChat) return;
        
        try {
            const response = await this.apiCall('messages/get_new.php', {
                type: this.currentChat.type,
                id: this.currentChat.id,
                since: this.getLastMessageTime()
            });
            
            if (response.success && response.messages.length > 0) {
                response.messages.forEach(msg => {
                    this.addMessageToUI(msg);
                });
                
                this.scrollToBottom();
                await this.markAsRead(this.currentChat.type, this.currentChat.id);
            }
            
        } catch (error) {
            this.log('Errore controllo nuovi messaggi:', error);
        }
    }
    
    /**
     * Ottieni timestamp ultimo messaggio
     */
    getLastMessageTime() {
        const messages = this.elements.messagesArea.querySelectorAll('.message-bubble');
        if (messages.length === 0) return null;
        
        const lastMessage = messages[messages.length - 1];
        return lastMessage.dataset.messageId || null;
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
            <div class="loading-indicator">
                <div class="spinner"></div>
                Caricamento messaggi...
            </div>
        `;
    }
    
    /**
     * Nascondi loading
     */
    hideLoading() {
        const loading = this.elements.messagesArea.querySelector('.loading-indicator');
        if (loading) {
            loading.remove();
        }
    }
    
    /**
     * Mostra errore
     */
    showError(message) {
        // Puoi implementare un sistema di notifiche più sofisticato
        if (window.toastr) {
            toastr.error(message);
        } else {
            alert(message);
        }
    }
    
    /**
     * Chiamata API
     */
    async apiCall(endpoint, data = {}) {
        const url = this.apiBase + endpoint;
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        };
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
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
            console.log('[ChatFooter]', ...args);
        }
    }
    
    /**
     * Mostra messaggio di errore
     */
    showError(message) {
        this.log('ERROR:', message);
        
        // Trova o crea un contenitore per gli errori
        let errorContainer = document.getElementById('chatErrorContainer');
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = 'chatErrorContainer';
            errorContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                font-size: 14px;
                max-width: 300px;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(errorContainer);
        }
        
        errorContainer.textContent = message;
        errorContainer.style.opacity = '1';
        
        // Nascondi automaticamente dopo 5 secondi
        setTimeout(() => {
            if (errorContainer) {
                errorContainer.style.opacity = '0';
                setTimeout(() => {
                    if (errorContainer && errorContainer.parentNode) {
                        errorContainer.parentNode.removeChild(errorContainer);
                    }
                }, 300);
            }
        }, 5000);
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
    if (typeof window.chatConfig !== 'undefined') {
        // Inizializza sistema chat
        window.chatFooterSystem = new ChatFooterSystem();
        
        // Cleanup su unload
        window.addEventListener('beforeunload', () => {
            if (window.chatFooterSystem) {
                window.chatFooterSystem.destroy();
            }
        });
    }
});

// Export per testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChatFooterSystem;
}
