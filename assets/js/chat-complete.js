/**
 * FOOTER CHAT SYSTEM - WHATSAPP LIKE
 * Sistema chat completo secondo README_CHAT_SYSTEM.md
 * Supporta: Chat Globale, Chat Pratiche, Chat Private
 */

// Configurazione di default (solo se non già impostata dal PHP)
if (typeof window.completeChatConfig === 'undefined') {
    window.completeChatConfig = {
        userId: null, // Sarà impostato dal PHP
        userName: null, // Sarà impostato dal PHP
        apiBase: '/api/chat/',
        pollingInterval: 3000,
        maxMessageLength: 1000,
        debug: true
    };
}

class CompleteChatSystem {
    constructor() {
        this.isInitialized = false;
        this.currentChat = null;
        this.isVisible = false;
        this.pollingTimer = null;
        this.lastMessageIds = {}; // Tiene traccia dell'ultimo message_id per ogni chat
        this.unreadCounts = {};
        this.privateChats = new Map();
        this.onlineUsers = new Map();
        this.currentConversationId = null; // ID della conversazione attualmente aperta
        
        // Socket.IO connection
        this.socket = null;
        this.socketConnected = false;
        
        // Elementi DOM
        this.elements = {};
        
        // Configurazione
        this.config = window.completeChatConfig || {};
        this.apiBase = this.config.apiBase || '/api/chat/';
        this.pollingInterval = this.config.pollingInterval || 3000;
        
        // Verifica userId - fondamentale per il funzionamento
        if (!this.config.userId && window.completeChatConfig) {
            this.config = { ...window.completeChatConfig };
        }
        
        // Controlla se l'utente è autenticato
        if (window.completeChatConfig && window.completeChatConfig.authenticated === false) {
            return; // Non avviare la chat per utenti non autenticati
        }
        
        // Converti userId in numero se è una stringa
        if (typeof this.config.userId === 'string') {
            this.config.userId = parseInt(this.config.userId);
        }
        
        if (!this.config.userId || this.config.userId === null || isNaN(this.config.userId) || this.config.userId <= 0) {
            
            // Mostra messaggio user-friendly
            if (document.getElementById('chat-footer-widget')) {
                document.getElementById('chat-footer-widget').innerHTML = `
                    <div style="position: fixed; bottom: 20px; right: 20px; 
                                background: #dc3545; color: white; padding: 15px; 
                                border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                                font-family: Arial, sans-serif; z-index: 1000;">
                        <strong>Errore Sessione</strong><br>
                        <small>Rifare login per accedere alla chat</small>
                        <button onclick="window.location.href='logout.php'" 
                                style="margin-left: 10px; padding: 5px 10px; 
                                       background: white; color: #dc3545; border: none; 
                                       border-radius: 5px; cursor: pointer;">
                            Logout
                        </button>
                    </div>
                `;
            }
            return; // Non avviare la chat se non c'è un userId valido
        }
        
        this.init();
    }
    
    /**
     * Inizializzazione sistema
     */
    async init() {
        try {
            
            this.bindElements();
            
            this.bindEvents();
            
            await this.initSocketIO();
            
            await this.loadInitialData();
            
            this.requestNotificationPermission();
            
            this.startPolling();
            
            // Carica badge immediatamente
            this.updateChatBadges();
            
            this.isInitialized = true;

        } catch (error) {
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
            // practiceBadge rimosso - solo notifica in chat globale
            
            // Chat list
            listContainer: document.getElementById('chat-list-container'),
            globalItem: document.querySelector('[data-type="globale"]'),
            practiceItem: document.querySelector('[data-type="pratiche"]'),
            clientSelector: document.getElementById('client-selector'),
            openPracticeChatBtn: document.getElementById('open-practice-chat-btn'),
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
            chatWindow: !!this.elements.chatWindow,
            globalItem: !!this.elements.globalItem,
            practiceItem: !!this.elements.practiceItem,
            clientSelector: !!this.elements.clientSelector,
            newPrivateBtn: !!this.elements.newPrivateBtn
        });
        
        // Debug elementi critici
        if (!this.elements.globalItem) {
            this.log('❌ GlobalItem non trovato! Elemento nel DOM:', document.querySelector('[data-type="globale"]'));
        }
        if (!this.elements.practiceItem) {
            this.log('❌ PracticeItem non trovato! Elemento nel DOM:', document.querySelector('[data-type="pratiche"]'));
        }
    }
    
    /**
     * Inizializza connessione Socket.IO
     */
    async initSocketIO() {
        try {
            // Connettiti al server Socket.IO (usa il protocollo corretto)
            let socketUrl;
            if (window.location.hostname === 'localhost') {
                socketUrl = 'http://localhost:3001';
            } else if (window.location.protocol === 'https:') {
                // Per HTTPS, disabilita Socket.IO temporaneamente per evitare errori mixed content
                this.log('⚠️ Socket.IO disabilitato per HTTPS (mixed content policy)');
                return;
            } else {
                socketUrl = `http://${window.location.hostname}:3001`;
            }
            this.socket = io(socketUrl);
            
            this.socket.on('connect', () => {
                this.socketConnected = true;
                this.log('🔌 Socket.IO connesso!');
                
                // Registra utente per tracciamento online
                this.socket.emit('register', this.config.userId);
                this.log('👤 Utente registrato per tracking online:', this.config.userId);
            });
            
            this.socket.on('disconnect', () => {
                this.socketConnected = false;
                this.log('🔌 Socket.IO disconnesso!');
            });
            
            // Gestione messaggi in tempo reale
            this.socket.on('new_message', (data) => {
                this.handleNewMessage(data);
            });
            
            // Gestione utenti online/offline
            this.socket.on('user_online', (data) => {
                this.updateUserStatus(data.user_id, true);
            });
            
            this.socket.on('user_offline', (data) => {
                this.updateUserStatus(data.user_id, false);
            });
            
            // Gestione "sta scrivendo"
            this.socket.on('user_typing', (data) => {
                this.showTypingIndicator(data.chat_id, data.user_name);
            });
            
            this.socket.on('user_stop_typing', (data) => {
                this.hideTypingIndicator(data.chat_id, data.user_name);
            });
            
        } catch (error) {
            this.log('❌ Errore connessione Socket.IO:', error);
        }
    }
    
    /**
     * Richiede permesso per notifiche browser
     */
    requestNotificationPermission() {
        if ('Notification' in window) {
            if (Notification.permission === 'default') {
                Notification.requestPermission().then(permission => {
                });
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
        
        // Chat globale
        if (this.elements.globalItem) {
            this.elements.globalItem.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita chiusura panel
                this.openChat('globale', 1, 'Chat Generale');
            });
        } else {
            this.log('❌ Elemento globalItem non trovato!');
        }
        
        // Selezione cliente per pratiche - ora attiva/disattiva solo il pulsante
        if (this.elements.clientSelector) {
            this.elements.clientSelector.addEventListener('change', (e) => {
                e.stopPropagation(); // Evita chiusura panel
                const clientId = e.target.value;
                const openBtn = this.elements.openPracticeChatBtn;
                
                if (clientId && openBtn) {
                    openBtn.disabled = false;
                    openBtn.setAttribute('data-client-id', clientId);
                    openBtn.setAttribute('data-client-name', e.target.options[e.target.selectedIndex].text);
                } else if (openBtn) {
                    openBtn.disabled = true;
                    openBtn.removeAttribute('data-client-id');
                    openBtn.removeAttribute('data-client-name');
                }
            });
        }
        
        // Pulsante "Apri" chat pratica
        if (this.elements.openPracticeChatBtn) {
            this.elements.openPracticeChatBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita chiusura panel
                const clientId = e.target.getAttribute('data-client-id');
                const clientName = e.target.getAttribute('data-client-name');
                
                if (clientId && clientName) {
                    this.log('📝 Apertura chat pratica per cliente:', clientName);
                    this.openChat('pratica', clientId, `Pratica: ${clientName}`);
                }
            });
        }
        
        // Rimuoviamo il pulsante nuova chat privata
        // if (this.elements.newPrivateBtn) {
        //     this.elements.newPrivateBtn.addEventListener('click', (e) => {
        //         e.stopPropagation();
        //         this.log('👥 CLICK RICEVUTO su nuova chat privata!');
        //         this.log('👥 Richiesta nuova chat privata');
        //         this.showUserSelectionModal();
        //     });
        // } else {
        //     this.log('❌ Elemento newPrivateBtn non trovato!');
        // }
        
        // Back to list
        if (this.elements.backBtn) {
            this.elements.backBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                this.log('🔙 Click su bottone back');
                this.showChatList();
            });
        }
        
        // Invio messaggio
        if (this.elements.sendBtn) {
            this.elements.sendBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Evita chiusura panel
                this.sendMessage();
            });
        }
        
        // Enter per inviare
        if (this.elements.messageInput) {
            this.elements.messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation(); // Evita chiusura panel
                    this.sendMessage();
                }
            });
        }
        
        // Chiudi panel cliccando fuori
        document.addEventListener('click', (e) => {
            // Non chiudere se si clicca sul bottone back o all'interno del widget
            if (this.isVisible && !this.elements.widget.contains(e.target) && 
                !e.target.closest('#back-to-list-btn') && 
                !e.target.closest('.btn-back')) {
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
            
            // Avvia heartbeat per mantenere sessione attiva
            this.startSessionHeartbeat();
            
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
        
        if (this.elements.panel) {
            this.elements.panel.classList.remove('hidden');
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
            
            // Determina conversation ID prima del caricamento
            let conversationId = id;
            if (type === 'globale') {
                conversationId = 1; // Chat globale ha sempre ID 1
            } else if (type === 'pratica') {
                // Per le pratiche, ottieni il conversation_id dal sistema
                this.log('🔧 Creando conversazione pratica per cliente:', id);
                conversationId = await this.getOrCreatePracticeConversation(id);
                this.log('✅ Conversation ID pratica ottenuto:', conversationId);
            } else if (type === 'privata') {
                // Per le chat private, ottieni il conversation_id dal sistema
                this.log('🔧 Creando conversazione privata per utente:', id);
                conversationId = await this.getOrCreatePrivateConversation(id);
                this.log('✅ Conversation ID privata ottenuto:', conversationId);
            }

            // Salva il conversation ID corrente
            this.currentConversationId = conversationId;

            // Carica cronologia
            await this.loadChatHistory(type, id);

            // Mostra chat window
            this.showChatWindow();            // Lascia room precedente se necessario
            if (this.currentConversationId && this.socketConnected) {
                this.socket.emit('leave_conversation', this.currentConversationId);
            }
            
            // Unisciti alla nuova room
            this.currentConversationId = conversationId;
            if (this.socketConnected) {
                this.socket.emit('join_conversation', conversationId);
                this.log('🏠 Unito alla room conversazione:', conversationId);
            }
            
            // Segna come letti
            this.markAsRead(conversationId);
            
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
        // Forza un reflow per assicurare che le transizioni funzionino correttamente
        this.elements.panel.offsetHeight;
        
        // Aggiungi classe per nascondere header principale
        this.elements.panel.classList.add('chat-open');
        
        // Attendi un frame prima di mostrare la chat window per una transizione fluida
        requestAnimationFrame(() => {
            this.elements.listContainer.classList.add('hidden');
            this.elements.chatWindow.classList.remove('hidden');
            
            // Forza il posizionamento corretto
            this.elements.chatWindow.style.transform = 'translateX(0)';
            this.elements.chatWindow.style.opacity = '1';
        });
    }
    
    /**
     * Mostra lista chat
     */
    showChatList() {
        // Forza un reflow per assicurare che le transizioni funzionino correttamente
        this.elements.panel.offsetHeight;
        
        // Inizia la transizione di uscita
        this.elements.chatWindow.style.transform = 'translateX(100%)';
        this.elements.chatWindow.style.opacity = '0';
        
        // Attendi che la transizione sia completata prima di nascondere
        setTimeout(() => {
            this.elements.chatWindow.classList.add('hidden');
            this.elements.listContainer.classList.remove('hidden');
            
            // Rimuovi classe per mostrare header principale
            this.elements.panel.classList.remove('chat-open');
            
            this.currentChat = null;
            
            // Disabilita input
            this.elements.messageInput.disabled = true;
            this.elements.sendBtn.disabled = true;
        }, 300); // Durata della transizione CSS
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
                const userId = parseInt(id);
                const user = this.onlineUsers.get(userId);
                status = user ? (user.is_online ? 'Online' : 'Offline') : `Sconosciuto (ID: ${userId})`;
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
            
            // Carica messaggi usando la nuova API
            const response = await fetch(`./api/chat_messages.php?conversation_id=${conversationId}`, {
                method: 'GET',
                headers: {'Content-Type': 'application/json'}
            });
            
            const data = await response.json();
            this.log('📩 Risposta cronologia:', data);
            
            if (data.success) {
                // Se non ci sono messaggi, mostra il messaggio vuoto invece di errore
                const messages = data.messages || [];
                this.renderMessages(messages);
                this.scrollToBottom();
                this.log(`💬 Caricati ${messages.length} messaggi per ${type}:${id}`);
                return; // Esce qui se tutto va bene
            } else {
                throw new Error(data.error || 'Errore caricamento cronologia');
            }
            
        } catch (error) {
            this.log('❌ Errore caricamento cronologia:', error);
            
            // FALLBACK: Usa messaggi locali se disponibili per conversation ID >= 1000
            let fallbackConversationId = null;
            if (type === 'pratica') {
                fallbackConversationId = 1000 + parseInt(id);
            } else if (type === 'privata') {
                const userId1 = Math.min(this.config.userId, parseInt(id));
                const userId2 = Math.max(this.config.userId, parseInt(id));
                fallbackConversationId = 2000 + userId1 * 100 + userId2;
            }
            
            if (fallbackConversationId && fallbackConversationId >= 1000) {
                this.log('🔧 Caricamento messaggi locali per conversation_id fallback:', fallbackConversationId);
                const localMessages = this.getLocalMessages(fallbackConversationId);
                if (localMessages.length > 0) {
                    this.log('📱 Messaggi locali trovati:', localMessages.length);
                    this.renderMessages(localMessages);
                    this.scrollToBottom();
                    return; // Non mostrare errore se abbiamo messaggi locali
                } else {
                    // Nessun messaggio locale trovato - mostra stato vuoto invece di errore
                    this.log('💭 Nessun messaggio per questa chat, mostra stato vuoto');
                    this.renderMessages([]);
                    return;
                }
            }
            
            // Solo per altri tipi di errore o chat non supportate, mostra errore
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
            this.log('🔗 Chiamando API pratica per cliente:', clientId);
            const response = await fetch('./api/get_or_create_practice.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    client_id: parseInt(clientId)
                })
            });
            
            this.log('📡 Risposta API pratica:', response.status);
            const data = await response.json();
            this.log('📄 Dati API pratica:', data);
            
            if (data.success) {
                return data.conversation_id;
            } else {
                throw new Error(data.error || 'Errore creazione conversazione pratica');
            }
            
        } catch (error) {
            this.log('❌ Errore conversazione pratica:', error);
            
            // FALLBACK: Usa un ID conversazione basato sul client_id
            // Questo permette di testare il sistema anche se l'API non funziona
            this.log('🔧 Usando fallback conversation_id per cliente:', clientId);
            return 1000 + parseInt(clientId); // es. cliente 2 -> conversation_id 1002
        }
    }
    
    /**
     * Ottieni o crea conversazione privata
     */
    async getOrCreatePrivateConversation(otherUserId) {
        try {
            this.log('🔗 Chiamando API privata per utente:', otherUserId);
            const response = await fetch('./api/get_or_create_private.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    other_user_id: parseInt(otherUserId)
                })
            });
            
            this.log('📡 Risposta API privata:', response.status);
            const data = await response.json();
            this.log('📄 Dati API privata:', data);
            
            if (data.success) {
                return data.conversation_id;
            } else {
                throw new Error(data.error || 'Errore creazione conversazione privata');
            }
            
        } catch (error) {
            this.log('❌ Errore conversazione privata:', error);
            
            // FALLBACK: Usa un ID conversazione basato sugli user ID
            // Questo permette di testare il sistema anche se l'API non funziona
            this.log('🔧 Usando fallback conversation_id per utenti:', this.config.userId, 'e', otherUserId);
            const userId1 = Math.min(this.config.userId, parseInt(otherUserId));
            const userId2 = Math.max(this.config.userId, parseInt(otherUserId));
            return 2000 + userId1 * 100 + userId2; // es. utenti 1,2 -> conversation_id 2102
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
            
            // Prova prima Socket.IO, poi fallback ad API
            let messageSent = false;
            
            if (this.sendMessageViaSocket(conversationId, message)) {
                this.log('📡 Messaggio inviato via Socket.IO');
                messageSent = true;
                
                // Pulisci input
                this.elements.messageInput.value = '';
                
                // Aggiungi messaggio alla UI (Socket.IO non rimanderà il proprio messaggio)
                const messageEl = this.createMessageElement({
                    id: Date.now(),
                    user_id: this.config.userId,
                    user_name: this.config.userName,
                    message: message,
                    created_at: new Date().toISOString(),
                    is_own: true
                });

                const messagesContainer = document.getElementById('messages');
                if (messagesContainer && messageEl) {
                    messagesContainer.appendChild(messageEl);
                    
                    // Aggiorna l'ultimo ID per questa conversazione
                    this.lastMessageIds[conversationId] = Date.now();
                    
                    // Scroll intelligente
                    this.smartScroll();
                }            } else {
                // Fallback ad API REST
                this.log('📡 Fallback ad API REST');
                
                const response = await fetch('./api/send_message.php', {
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
                    messageSent = true;

                    // Pulisci input
                    this.elements.messageInput.value = '';

                    // Aggiungi messaggio alla UI con il nuovo sistema incrementale
                    const messageEl = this.createMessageElement({
                        id: data.message_id || Date.now(),
                        user_id: this.config.userId,
                        user_name: this.config.userName,
                        message: message,
                        created_at: new Date().toISOString(),
                        is_own: true
                    });

                    const messagesContainer = document.getElementById('messages');
                    if (messagesContainer && messageEl) {
                        messagesContainer.appendChild(messageEl);
                        
                        // Aggiorna l'ultimo ID per questa conversazione
                        this.lastMessageIds[conversationId] = data.message_id || Date.now();
                        
                        // Scroll intelligente
                        this.smartScroll();
                    }                } else {
                    throw new Error(data.error || 'Errore invio messaggio');
                }
            }
            
        } catch (error) {
            this.log('❌ Errore invio messaggio:', error);
            
            // FALLBACK: Simula invio messaggio locale (per testing)
            // Ottieni conversationId per il fallback
            let fallbackConversationId = null;
            if (this.currentChat.type === 'globale') {
                fallbackConversationId = 1;
            } else if (this.currentChat.type === 'pratica') {
                fallbackConversationId = 1000 + parseInt(this.currentChat.id);
            } else if (this.currentChat.type === 'privata') {
                const userId1 = Math.min(this.config.userId, parseInt(this.currentChat.id));
                const userId2 = Math.max(this.config.userId, parseInt(this.currentChat.id));
                fallbackConversationId = 2000 + userId1 * 100 + userId2;
            }
            
            if (fallbackConversationId && fallbackConversationId >= 1000) { // Se è un ID fallback
                this.log('🔧 Simulando invio messaggio locale per conversation_id fallback:', fallbackConversationId);
                
                // Crea oggetto messaggio
                const messageObj = {
                    id: Date.now(),
                    user_id: this.config.userId,
                    user_name: this.config.userName,
                    message: message,
                    created_at: new Date().toISOString(),
                    is_own: true
                };
                
                // Salva messaggio localmente
                this.saveMessageLocally(fallbackConversationId, messageObj);

                // Aggiungi messaggio alla UI con il nuovo sistema incrementale
                const messageEl = this.createMessageElement(messageObj);
                const messagesContainer = document.getElementById('messages');
                if (messagesContainer && messageEl) {
                    messagesContainer.appendChild(messageEl);
                    
                    // Aggiorna l'ultimo ID per questa conversazione
                    this.lastMessageIds[fallbackConversationId] = messageObj.id;
                    
                    // Scroll intelligente
                    this.smartScroll();
                }

                // Pulisci input
                this.elements.messageInput.value = '';                this.log('✅ Messaggio simulato aggiunto alla UI');
                return; // Non mostrare errore
            }
            
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
            // Personalizza il messaggio in base al tipo di chat
            let emptyMessage = 'Nessun messaggio ancora.<br>Inizia la conversazione!';
            let icon = 'fas fa-comments';
            
            if (this.currentChat) {
                if (this.currentChat.type === 'private' || this.currentChat.type === 'privata') {
                    emptyMessage = 'Nessun messaggio ancora in questa chat privata.<br>Scrivi il primo messaggio!';
                    icon = 'fas fa-user-friends';
                } else if (this.currentChat.type === 'pratica') {
                    emptyMessage = 'Nessun messaggio ancora per questa pratica.<br>Inizia a discutere!';
                    icon = 'fas fa-folder-open';
                } else if (this.currentChat.type === 'globale') {
                    emptyMessage = 'Nessun messaggio ancora nella chat generale.<br>Saluta tutti!';
                    icon = 'fas fa-globe';
                }
            }
            
            this.elements.messagesArea.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #6c757d;">
                    <i class="${icon} fa-2x" style="margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="margin: 0; line-height: 1.4;">${emptyMessage}</p>
                </div>
            `;
            return;
        }
        
        messages.forEach(msg => {
            const messageElement = this.createMessageElement(msg);
            this.elements.messagesArea.appendChild(messageElement);
        });

        // Salva l'ID dell'ultimo messaggio per questa conversazione
        if (messages.length > 0 && this.currentConversationId) {
            const lastMessage = messages[messages.length - 1];
            this.lastMessageIds[this.currentConversationId] = lastMessage.id;
            this.log(`📝 Ultimo messaggio salvato per conversazione ${this.currentConversationId}: ${lastMessage.id}`);
        }
    }
    
    /**
     * Carica chat private
     */
    async loadPrivateChats() {
        try {
            this.log('👥 Caricamento chat private...');
            
            // Carica lista utenti dal database
            const response = await fetch('/api/online_users.php');
            const data = await response.json();
            
            if (data.success && data.users) {
                // Filtra l'utente corrente dalla lista
                const otherUsers = data.users.filter(user => user.id != this.config.userId);
                this.renderPrivateChats(otherUsers);
            } else {
                this.renderPrivateChats([]);
            }

        } catch (error) {
            this.log('❌ Errore caricamento chat private:', error);
            this.renderPrivateChats([]);
        }
    }    /**
     * Renderizza chat private
     */
    renderPrivateChats(chats) {
        this.elements.privatesList.innerHTML = '';
        
        // Carica tutti gli utenti disponibili invece delle chat esistenti
        this.loadAndRenderAllUsers();
    }
    
    /**
     * Carica e renderizza tutti gli utenti per le chat private
     */
    async loadAndRenderAllUsers() {
        try {
            this.log('🔄 Caricamento utenti per chat private...');
            
            // Svuota la lista utenti
            this.elements.privatesList.innerHTML = '';
            
            // Carica utenti dal database
            const response = await fetch(`api/utenti_for_chat.php?user_id=${this.config.userId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Errore caricamento utenti');
            }
            
            const users = data.users || [];
            this.log('✅ Utenti caricati:', users.length);
            
            if (users.length === 0) {
                this.elements.privatesList.innerHTML = `
                    <div style="padding: 15px; text-align: center; color: #6c757d; font-size: 13px;">
                        Nessun altro utente disponibile.
                    </div>
                `;
                return;
            }
            
            // Renderizza ogni utente
            users.forEach(user => {
                const userName = user.name || user.nome || `Utente ${user.id}`;
                const userInitial = userName.charAt(0).toUpperCase();
                
                const userHTML = `
                    <div class="chat-item" data-type="privata" data-id="${user.id}">
                        <div class="chat-avatar user" style="position: relative;">
                            ${userInitial}
                            <div class="online-indicator ${user.is_online ? '' : 'offline'}"></div>
                        </div>
                        <div class="chat-info">
                            <div class="chat-name">${this.escapeHtml(userName)}</div>
                            <div class="chat-last-message">
                                ${user.ruolo ? `${user.ruolo} • ` : ''}${user.username}
                            </div>
                        </div>
                        <div class="chat-meta">
                            <span id="private-chat-badge-${user.id}" class="chat-unread-badge hidden">0</span>
                        </div>
                    </div>
                `;
                
                this.elements.privatesList.insertAdjacentHTML('beforeend', userHTML);
            });
            
            // Aggiungi event listeners
            this.elements.privatesList.querySelectorAll('.chat-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const userId = item.dataset.id;
                    const userName = item.querySelector('.chat-name').textContent;
                    this.log('👥 Click su utente per chat privata:', userName);
                    this.openChat('privata', userId, userName);
                });
            });
            
        } catch (error) {
            this.log('❌ Errore caricamento utenti:', error);
            
            // Mostra messaggio di errore dettagliato
            this.elements.privatesList.innerHTML = `
                <div style="padding: 15px; text-align: center; color: #dc3545; font-size: 13px;">
                    <i class="fas fa-exclamation-triangle mb-2"></i><br>
                    Errore caricamento utenti: ${error.message}<br>
                    <small style="color: #6c757d; font-size: 11px;">
                        User ID: ${this.config.userId || 'Non definito'}
                    </small><br>
                    <button onclick="window.completeChatSystem.loadAndRenderAllUsers()" 
                            style="background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-top: 8px; font-size: 11px;">
                        Riprova
                    </button>
                </div>
            `;
        }
    }
    
    /**
     * Mostra modal selezione utente (RIMOSSA - ora gli utenti sono mostrati direttamente)
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
            const response = await fetch('./api/online_users.php');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Errore sconosciuto');
            }
            
            this.onlineUsers.clear();
            data.users.forEach(user => {
                this.onlineUsers.set(user.id, user);
            });
            
            this.log('👥 Utenti caricati:', data.users.length);
            
        } catch (error) {
            this.log('❌ Errore caricamento utenti:', error);
            // Fallback dati mock in caso di errore
            this.onlineUsers.clear();
            this.onlineUsers.set(1, {id: 1, name: 'Admin', is_online: true});
            this.onlineUsers.set(2, {id: 2, name: 'Roberto', is_online: true});
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
            
            const userName = user.name || user.nome || `Utente ${user.id}`;
            const userInitial = userName.charAt(0).toUpperCase();
            
            const userHTML = `
                <button class="list-group-item list-group-item-action d-flex align-items-center" 
                        data-user-id="${user.id}" data-user-name="${this.escapeHtml(userName)}">
                    <div class="chat-avatar user me-3" style="width: 40px; height: 40px; font-size: 14px; position: relative;">
                        ${userInitial}
                        <div class="online-indicator ${user.is_online ? '' : 'offline'}"></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold d-flex align-items-center justify-content-between">
                            ${this.escapeHtml(userName)}
                            ${user.is_online ? '<span class="badge bg-success-subtle text-success ms-2">●</span>' : ''}
                        </div>
                        <small class="text-muted ${user.is_online ? 'text-success' : ''}">${user.is_online ? 'Online' : 'Offline'}</small>
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
            this.log('🔔 Aggiornamento contatori non letti...');
            
            const response = await fetch('./api/chat_notifications.php?action=unread_counts', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Errore sconosciuto');
            }
            
            this.log('📊 Contatori ricevuti:', data);
            
            // Utilizza il metodo updateChatBadges che abbiamo creato
            this.processBadgeData(data);
            
            // Salva contatori in cache per confronti futuri
            this.unreadCounts = data.unread_counts;
            
        } catch (error) {
            this.log('❌ Errore aggiornamento contatori:', error);
            
            // In caso di errore, nascondi tutti i badge invece di mostrare cache
            ['#total-unread-badge', '#global-chat-badge', '#practice-chat-badge'].forEach(selector => {
                const badge = document.querySelector(selector);
                if (badge) {
                    badge.classList.add('hidden');
                }
            });
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
            const response = await fetch('api/mark_as_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    id: id
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.log('📖 Messaggi segnati come letti:', data.messages_marked, 'per', type, id);
                
                // Aggiorna immediatamente i contatori
                this.updateUnreadCounts();
            } else {
                throw new Error(data.error || 'Errore sconosciuto');
            }
            
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
     * Avvia heartbeat sessione per mantenere utente online
     */
    startSessionHeartbeat() {
        // Heartbeat ogni 2 minuti
        setInterval(() => {
            this.sessionHeartbeat();
        }, 120000); // 2 minuti
        
        // Prima chiamata immediata
        this.sessionHeartbeat();
        
        // Gestisci chiusura finestra/tab
        window.addEventListener('beforeunload', () => {
            // Usa navigator.sendBeacon per request affidabile durante chiusura
            navigator.sendBeacon('api/session_end.php', JSON.stringify({
                user_id: this.config.userId
            }));
        });
    }
    
    /**
     * Heartbeat sessione
     */
    async sessionHeartbeat() {
        try {
            const response = await fetch('api/session_heartbeat.php');
            const data = await response.json();
            
            if (data.success) {
                this.log('💓 Heartbeat sessione OK');
            } else {
                this.log('💓 Heartbeat sessione fallito:', data.error);
            }
        } catch (error) {
            this.log('❌ Errore heartbeat sessione:', error);
        }
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
     * Controlla nuovi messaggi (VERSIONE INCREMENTALE)
     */
    async checkNewMessages() {
        if (!this.currentChat || !this.currentConversationId) return;

        try {
            this.log('🔍 Controllo incrementale nuovi messaggi per conversation:', this.currentConversationId);

            // Ottieni l'ID dell'ultimo messaggio caricato
            const lastMessageId = this.lastMessageIds[this.currentConversationId] || 0;
            
            if (lastMessageId === 0) {
                // Prima volta - usa caricamento completo
                await this.loadChatHistory(this.currentChat.type, this.currentChat.id);
                return;
            }

            // Caricamento incrementale - solo nuovi messaggi
            const response = await fetch(`./api/chat_messages.php?conversation_id=${this.currentConversationId}&since_id=${lastMessageId}`, {
                method: 'GET',
                headers: {'Content-Type': 'application/json'}
            });

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.messages && data.messages.length > 0) {
                this.log(`📩 ${data.messages.length} nuovi messaggi ricevuti`);
                
                // Aggiungi solo i nuovi messaggi senza ricaricare tutto
                this.appendNewMessages(data.messages);
                
                // Aggiorna l'ultimo message ID
                const lastMessage = data.messages[data.messages.length - 1];
                this.lastMessageIds[this.currentConversationId] = lastMessage.id;
                
                // Scroll solo se l'utente è già in fondo
                this.smartScroll();
            }

            // Aggiorna anche i badge
            this.updateChatBadges();

        } catch (error) {
            this.log('❌ Errore controllo nuovi messaggi:', error);
        }
    }    /**
     * Scroll al bottom
     */
    scrollToBottom() {
        setTimeout(() => {
            this.elements.messagesArea.scrollTop = this.elements.messagesArea.scrollHeight;
        }, 100);
    }

    /**
     * Smart scroll - scorri solo se l'utente è già vicino al fondo
     */
    smartScroll() {
        const messagesArea = this.elements.messagesArea;
        const isNearBottom = messagesArea.scrollTop >= messagesArea.scrollHeight - messagesArea.clientHeight - 100;
        
        if (isNearBottom) {
            this.scrollToBottom();
        }
    }

    /**
     * Aggiungi nuovi messaggi senza ricaricare tutto
     */
    appendNewMessages(newMessages) {
        if (!newMessages || newMessages.length === 0) return;

        newMessages.forEach(message => {
            const messageElement = this.createMessageElement(message);
            this.elements.messagesArea.appendChild(messageElement);
        });
    }

    /**
     * Crea elemento messaggio singolo
     */
    createMessageElement(message) {
        const isOwn = message.user_id == this.config.userId;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-bubble ${isOwn ? 'own' : ''}`;
        
        // Messaggio di sistema (notifiche chat pratiche)
        if (message.message_type === 'system') {
            messageDiv.innerHTML = `
                <div class="system-message" style="text-align: center; padding: 8px; margin: 8px 0; background: #f8f9fa; border-radius: 8px; font-style: italic; color: #6c757d; border-left: 3px solid #007bff;">
                    <i class="fas fa-info-circle me-2"></i>${this.escapeHtml(message.message)}
                    <div style="font-size: 0.7em; margin-top: 4px; opacity: 0.8;">
                        ${this.formatTime(message.timestamp || message.created_at)}
                    </div>
                </div>
            `;
        } else {
            // Messaggio normale
            const senderName = message.sender_name || message.user_name || 'User';
            const senderInitial = senderName.charAt(0).toUpperCase();
            
            messageDiv.innerHTML = `
                ${!isOwn ? `<div class="message-sender" data-initial="${senderInitial}">${this.escapeHtml(senderName)}</div>` : ''}
                <div class="message-content">
                    ${this.escapeHtml(message.message)}
                </div>
                <div class="message-meta">
                    <span class="message-time">${this.formatTime(message.timestamp || message.created_at)}</span>
                </div>
            `;
        }
        
        return messageDiv;
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
     * Salva messaggio localmente (localStorage)
     */
    saveMessageLocally(conversationId, messageObj) {
        try {
            const key = `chat_messages_${conversationId}`;
            let messages = JSON.parse(localStorage.getItem(key) || '[]');
            messages.push(messageObj);
            
            // Mantieni solo gli ultimi 100 messaggi
            if (messages.length > 100) {
                messages = messages.slice(-100);
            }
            
            localStorage.setItem(key, JSON.stringify(messages));
            this.log('💾 Messaggio salvato localmente:', messageObj.message);
        } catch (error) {
            this.log('❌ Errore salvataggio locale:', error);
        }
    }
    
    /**
     * Ottieni messaggi locali (localStorage)
     */
    getLocalMessages(conversationId) {
        try {
            const key = `chat_messages_${conversationId}`;
            const messages = JSON.parse(localStorage.getItem(key) || '[]');
            this.log('📱 Messaggi locali caricati:', messages.length);
            return messages;
        } catch (error) {
            this.log('❌ Errore caricamento messaggi locali:', error);
            return [];
        }
    }
    
    /**
     * Gestione persistenza locale dei messaggi (fallback)
     */
    getLocalStorageKey(conversationId) {
        return `chat_messages_${this.config.userId}_${conversationId}`;
    }
    
    saveMessageLocally(conversationId, message) {
        try {
            const key = this.getLocalStorageKey(conversationId);
            const messages = this.getLocalMessages(conversationId);
            messages.push({
                ...message,
                is_local: true,
                local_timestamp: Date.now()
            });
            
            // Mantieni solo gli ultimi 100 messaggi per conversazione
            if (messages.length > 100) {
                messages.splice(0, messages.length - 100);
            }
            
            localStorage.setItem(key, JSON.stringify(messages));
            this.log('💾 Messaggio salvato localmente:', conversationId, message.message);
        } catch (error) {
            this.log('❌ Errore salvataggio locale:', error);
        }
    }
    
    getLocalMessages(conversationId) {
        try {
            const key = this.getLocalStorageKey(conversationId);
            const stored = localStorage.getItem(key);
            return stored ? JSON.parse(stored) : [];
        } catch (error) {
            this.log('❌ Errore caricamento messaggi locali:', error);
            return [];
        }
    }
    
    clearLocalMessages(conversationId) {
        try {
            const key = this.getLocalStorageKey(conversationId);
            localStorage.removeItem(key);
            this.log('🗑️ Messaggi locali rimossi per:', conversationId);
        } catch (error) {
            this.log('❌ Errore pulizia messaggi locali:', error);
        }
    }

    /**
     * Logging - Metodo vuoto per evitare errori
     */
    log(...args) {
        // Debug disabilitato - metodo vuoto
    }
    
    
    /**
     * Gestisce nuovo messaggio ricevuto via Socket.IO
     */
    handleNewMessage(data) {
        try {
            // Se siamo nella chat corrente, aggiungi solo il nuovo messaggio
            if (this.currentChat && this.currentChat.id === data.conversation_id) {
                // Usa il nuovo sistema incrementale
                const messageEl = this.createMessageElement(data);
                const messagesContainer = document.getElementById('messages');
                if (messagesContainer && messageEl) {
                    messagesContainer.appendChild(messageEl);
                    
                    // Aggiorna l'ultimo ID per questa conversazione
                    this.lastMessageIds[data.conversation_id] = data.id;
                    
                    // Scroll intelligente
                    this.smartScroll();
                }

                // Marca come letto se stiamo visualizzando la chat
                if (data.user_id !== this.config.userId) {
                    this.markAsRead(data.conversation_id);
                }
            } else {
                // Aggiorna badge per messaggi in altre chat
                this.updateChatBadges();
            }

            // Mostra notifica se la chat non è aperta o se non è il nostro messaggio
            if (data.user_id !== this.config.userId &&
                (!this.isVisible || !this.currentChat || this.currentChat.id !== data.conversation_id)) {
                this.showNotification(data);
            }

        } catch (error) {
            this.log('❌ Errore gestione nuovo messaggio:', error);
        }
    }    /**
     * Aggiorna status online/offline utente
     */
    updateUserStatus(userId, isOnline) {
        // Aggiorna lo stato nell'oggetto onlineUsers
        if (this.onlineUsers.has(parseInt(userId))) {
            const user = this.onlineUsers.get(parseInt(userId));
            user.is_online = isOnline;
            this.onlineUsers.set(parseInt(userId), user);
        }
        
        // Aggiorna indicatori online nella lista utenti (modal)
        const userElement = document.querySelector(`[data-user-id="${userId}"]`);
        if (userElement) {
            const onlineIndicator = userElement.querySelector('.online-indicator');
            const statusText = userElement.querySelector('small');
            
            if (onlineIndicator) {
                onlineIndicator.className = `online-indicator ${isOnline ? '' : 'offline'}`;
            }
            
            if (statusText) {
                statusText.textContent = isOnline ? 'Online' : 'Offline';
                statusText.className = `text-muted ${isOnline ? 'text-success' : ''}`;
            }
        }
        
        // Aggiorna indicatori nella lista chat private
        const privateUserElement = document.querySelector(`[data-id="${userId}"]`);
        if (privateUserElement) {
            const privateOnlineIndicator = privateUserElement.querySelector('.online-indicator');
            if (privateOnlineIndicator) {
                privateOnlineIndicator.className = `online-indicator ${isOnline ? '' : 'offline'}`;
            }
        }
        
        // Log per debug
        this.log(`🔄 Stato utente ${userId} aggiornato: ${isOnline ? 'online' : 'offline'}`);
    }
    
    /**
     * Mostra indicatore "sta scrivendo"
     */
    showTypingIndicator(chatId, userName) {
        if (this.currentChat && this.currentChat.id === chatId) {
            const container = this.elements.messagesContainer;
            let indicator = container.querySelector('.typing-indicator');
            
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.className = 'typing-indicator';
                container.appendChild(indicator);
            }
            
            indicator.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${userName} sta scrivendo...`;
        }
    }
    
    /**
     * Nasconde indicatore "sta scrivendo"  
     */
    hideTypingIndicator(chatId, userName) {
        if (this.currentChat && this.currentChat.id === chatId) {
            const indicator = this.elements.messagesContainer.querySelector('.typing-indicator');
            if (indicator) {
                indicator.remove();
            }
        }
    }
    
    /**
     * Mostra notifica browser
     */
    showNotification(data) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(`Nuovo messaggio da ${data.user_name}`, {
                body: data.message,
                icon: '/assets/images/chat-icon.png',
                badge: '/assets/images/badge-icon.png'
            });
        }
    }
    
    /**
     * Incrementa contatore messaggi non letti
     */
    incrementUnreadCount(chatId) {
        if (!this.unreadCounts[chatId]) {
            this.unreadCounts[chatId] = 0;
        }
        this.unreadCounts[chatId]++;
        
        // Aggiorna badge specifico della chat
        this.updateChatBadge(chatId);
    }
    
    /**
     * Aggiorna badge chat specifica
     */
    updateChatBadge(chatId) {
        const count = this.unreadCounts[chatId] || 0;
        const badge = document.querySelector(`[data-chat-id="${chatId}"] .chat-badge`);
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
    
    /**
     * Aggiorna badge totale
     */
    updateTotalBadge() {
        const total = Object.values(this.unreadCounts).reduce((sum, count) => sum + count, 0);
        
        
        if (this.elements.totalBadge) {
            if (total > 0) {
                this.elements.totalBadge.textContent = total;
                this.elements.totalBadge.classList.remove('hidden');
            } else {
                this.elements.totalBadge.classList.add('hidden');
            }
        }
    }
    
    /**
     * Invia messaggio via Socket.IO
     */
    sendMessageViaSocket(conversationId, message) {
        if (this.socketConnected && this.socket) {
            this.socket.emit('send_message', {
                conversation_id: conversationId,
                message: message,
                user_id: this.config.userId,
                user_name: this.config.userName,
                chat_type: this.currentChat?.type || 'private'
            });
            return true;
        }
        return false;
    }
    
    /**
     * Cleanup
     */
    destroy() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }
        
        if (this.socket) {
            this.socket.disconnect();
        }
        
        this.isInitialized = false;
        this.log('Sistema chat distrutto');
    }
    
    // Aggiorna i badge dei messaggi non letti
    updateChatBadges() {
        fetch('./api/chat_notifications.php?action=unread_counts')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const counts = data.unread_counts;
                    
                    // Definisci badge elements all'inizio
                    const globalBadge = document.querySelector('#global-chat-badge');
                    
                    if (counts.global && counts.global > 0) {
                        if (globalBadge) {
                            globalBadge.textContent = counts.global;
                            globalBadge.classList.remove('hidden');
                        }
                    } else if (globalBadge) {
                        globalBadge.classList.add('hidden');
                    }
                    
                    // Gestisci solo badge chat private (pratiche rimosse)
                    Object.keys(counts).forEach(key => {
                        if (key.startsWith('private_user_')) {
                            const userId = key.replace('private_user_', '');
                            const badge = document.querySelector(`#private-chat-badge-${userId}`);
                            if (badge) {
                                badge.textContent = counts[key];
                                badge.classList.remove('hidden');
                            }
                        }
                    });
                    
                    // Badge totale sul toggle button del widget
                    const totalBadge = document.querySelector('#total-unread-badge');
                    if (data.total > 0) {
                        if (totalBadge) {
                            totalBadge.textContent = data.total;
                            totalBadge.classList.remove('hidden');
                        }
                    } else if (totalBadge) {
                        totalBadge.classList.add('hidden');
                    }
                } else {
                    // Se l'API restituisce un errore (es. non autenticato), nascondi tutti i badge
                    ['#total-unread-badge', '#global-chat-badge', '#practice-chat-badge'].forEach(selector => {
                        const badge = document.querySelector(selector);
                        if (badge) {
                            badge.classList.add('hidden');
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Errore aggiornamento badge:', error);
            });
    }
    
    // Marca messaggi come letti per una conversazione
    markAsRead(conversationId) {
        fetch('./api/chat_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: conversationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Aggiorna badge dopo aver marcato come letto
                this.updateChatBadges();
            }
        })
        .catch(error => {
            console.error('Errore marca come letto:', error);
        });
    }
    
    // Processa i dati dei badge dal polling
    processBadgeData(data) {
        
        if (data.success && data.unread_counts !== undefined) {
            const counts = data.unread_counts;
            
            // Badge chat globale
            const globalBadge = document.querySelector('#global-chat-badge');
            if (counts.global && counts.global > 0) {
                if (globalBadge) {
                    globalBadge.textContent = counts.global;
                    globalBadge.classList.remove('hidden');
                }
            } else if (globalBadge) {
                globalBadge.classList.add('hidden');
            }
            
            // Badge totale - USA SOLO data.total dall'API
            const totalBadge = document.querySelector('#total-unread-badge');
            
            
            if (data.total > 0) {
                if (totalBadge) {
                    totalBadge.textContent = data.total;
                    totalBadge.classList.remove('hidden');
                }
            } else if (totalBadge) {
                totalBadge.classList.add('hidden');
            }
            
            // Gestisci solo badge chat private (pratiche rimosse)
            Object.keys(counts).forEach(key => {
                if (key.startsWith('private_user_')) {
                    const userId = key.replace('private_user_', '');
                    const badge = document.querySelector(`#private-chat-badge-${userId}`);
                    if (badge) {
                        badge.textContent = counts[key];
                        badge.classList.remove('hidden');
                    }
                }
            });
        }
    }
}

// Inizializzazione automatica
document.addEventListener('DOMContentLoaded', function() {
    // Verifica che sia presente la configurazione
    if (typeof window.completeChatConfig !== 'undefined') {
        // Inizializza sistema chat
        window.completeChatSystem = new CompleteChatSystem();
        
        // BADGE DEBUG: MutationObserver per tracciare modifiche al badge
        const totalBadge = document.querySelector('#total-unread-badge');
        if (totalBadge) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || (mutation.type === 'attributes' && mutation.attributeName === 'class')) {
                    }
                });
            });
            
            observer.observe(totalBadge, {
                childList: true,
                attributes: true,
                attributeFilter: ['class'],
                subtree: true
            });
            
        }
        
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
