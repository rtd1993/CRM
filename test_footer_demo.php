<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Chat Footer - Senza Autenticazione</title>
    <!-- Bootstrap CDN for modern style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chat Footer System CSS -->
    <link rel="stylesheet" href="/assets/css/chat-footer.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-vial"></i>
                            Test Chat Footer System - Demo
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Test Visuale Footer Chat</h5>
                            <p class="mb-0">Questa pagina mostra il footer chat senza autenticazione per testare la visualizzazione. 
                            Il widget dovrebbe apparire in basso a destra con stili inline per garantire la visibilità.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>📊 Test Visivo</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">✅ Footer posizionato in basso a destra</li>
                                    <li class="list-group-item">✅ Bottone toggle chat visibile</li>
                                    <li class="list-group-item">✅ Stili inline applicati</li>
                                    <li class="list-group-item">✅ Design WhatsApp-like</li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>🎨 Caratteristiche Footer</h5>
                                <ul class="list-group">
                                    <li class="list-group-item">🔵 Bottone circolare blu/viola</li>
                                    <li class="list-group-item">💬 Icona chat FontAwesome</li>
                                    <li class="list-group-item">🎯 Z-index 9999 (sempre in primo piano)</li>
                                    <li class="list-group-item">📱 Responsive per mobile</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>🔧 Test Funzionalità JavaScript</h5>
                            <div class="d-grid gap-2 d-md-flex">
                                <button class="btn btn-outline-primary" onclick="testToggleChat()">
                                    <i class="fas fa-toggle-on"></i> Test Toggle Chat
                                </button>
                                <button class="btn btn-outline-success" onclick="testChatVisibility()">
                                    <i class="fas fa-eye"></i> Test Visibilità
                                </button>
                                <button class="btn btn-outline-info" onclick="testStyles()">
                                    <i class="fas fa-palette"></i> Test Stili
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>📝 Log Test</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <pre id="testLog" style="font-size: 12px; max-height: 200px; overflow-y: auto; margin: 0;">Caricamento test...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SIMULAZIONE SESSIONE PER TEST -->
    <?php
    // Simuliamo dati sessione per il test
    $user_id = 1;
    $user_name = "Test User";
    $clienti_chat = [
        ['id' => 1, 'nome' => 'Cliente Test 1'],
        ['id' => 2, 'nome' => 'Cliente Test 2'],
        ['id' => 3, 'nome' => 'Azienda Demo SRL']
    ];
    ?>

    <!-- FOOTER CHAT WIDGET -->
    <div id="chatFooterWidget" class="chat-footer-widget" style="position: fixed !important; bottom: 20px !important; right: 20px !important; z-index: 9999 !important; font-family: 'Segoe UI', Arial, sans-serif !important;">
        <!-- Toggle Button -->
        <button id="chatToggleBtn" class="chat-toggle-button" title="Apri Chat" style="
            width: 64px !important; 
            height: 64px !important; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
            border-radius: 50% !important; 
            display: flex !important; 
            align-items: center !important; 
            justify-content: center !important; 
            color: white !important; 
            cursor: pointer !important; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.15) !important; 
            transition: all 0.3s ease !important; 
            position: relative !important; 
            border: none !important; 
            outline: none !important;
        ">
            <i class="fas fa-comments" style="font-size: 24px !important; transition: transform 0.3s ease !important;"></i>
            <span id="chatTotalBadge" class="chat-total-badge" style="
                position: absolute !important; 
                top: -5px !important; 
                right: -5px !important; 
                background: #dc3545 !important; 
                color: white !important; 
                border-radius: 50% !important; 
                width: 20px !important; 
                height: 20px !important; 
                font-size: 11px !important; 
                font-weight: bold !important; 
                display: flex !important; 
                align-items: center !important; 
                justify-content: center !important; 
                border: 2px solid white !important;
            ">3</span>
        </button>
        
        <!-- Chat Panel (Demo) -->
        <div id="chatPanel" class="chat-panel" style="
            position: absolute !important; 
            bottom: 80px !important; 
            right: 0 !important; 
            width: 380px !important; 
            height: 500px !important; 
            background: white !important; 
            border-radius: 16px !important; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.2) !important; 
            display: none !important; 
            flex-direction: column !important; 
            overflow: hidden !important; 
            border: 1px solid #e1e8ed !important;
        ">
            <div style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
                color: white !important; 
                padding: 16px 20px !important; 
                display: flex !important; 
                justify-content: space-between !important; 
                align-items: center !important;
            ">
                <h5 style="margin: 0 !important; font-size: 16px !important; font-weight: 600 !important;">
                    <i class="fas fa-comments" style="margin-right: 8px !important;"></i>Chat Demo
                </h5>
                <button onclick="toggleDemo()" style="
                    background: none !important; 
                    border: none !important; 
                    color: white !important; 
                    font-size: 18px !important; 
                    cursor: pointer !important;
                ">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 20px !important; text-align: center !important; color: #657786 !important;">
                <i class="fas fa-comments" style="font-size: 48px !important; opacity: 0.3 !important; margin-bottom: 16px !important;"></i>
                <h6>Chat Footer Widget</h6>
                <p style="font-size: 14px !important; margin: 0 !important;">Sistema di chat integrato nel footer con design moderno e responsive.</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    function log(message) {
        const logEl = document.getElementById('testLog');
        const timestamp = new Date().toLocaleTimeString();
        logEl.textContent += `[${timestamp}] ${message}\n`;
        logEl.scrollTop = logEl.scrollHeight;
    }

    function testToggleChat() {
        const panel = document.getElementById('chatPanel');
        const isVisible = panel.style.display === 'flex';
        panel.style.display = isVisible ? 'none' : 'flex';
        log(`Toggle chat: ${isVisible ? 'Chiuso' : 'Aperto'}`);
    }

    function testChatVisibility() {
        const widget = document.getElementById('chatFooterWidget');
        const computedStyle = window.getComputedStyle(widget);
        log(`Posizione: ${computedStyle.position}`);
        log(`Bottom: ${computedStyle.bottom}`);
        log(`Right: ${computedStyle.right}`);
        log(`Z-index: ${computedStyle.zIndex}`);
        log(`Visibilità: ${computedStyle.display}`);
    }

    function testStyles() {
        const button = document.getElementById('chatToggleBtn');
        const computedStyle = window.getComputedStyle(button);
        log(`Bottone - Width: ${computedStyle.width}`);
        log(`Bottone - Height: ${computedStyle.height}`);
        log(`Bottone - Background: ${computedStyle.background}`);
        log(`Bottone - Border-radius: ${computedStyle.borderRadius}`);
    }

    function toggleDemo() {
        testToggleChat();
    }

    // Hover effects
    document.getElementById('chatToggleBtn').addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1) translateY(-2px)';
        this.style.boxShadow = '0 12px 40px rgba(102, 126, 234, 0.3)';
    });

    document.getElementById('chatToggleBtn').addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = '0 8px 32px rgba(0,0,0,0.15)';
    });

    document.getElementById('chatToggleBtn').addEventListener('click', testToggleChat);

    // Log iniziale
    window.addEventListener('load', function() {
        log('=== TEST CHAT FOOTER WIDGET ===');
        log('Pagina caricata completamente');
        
        setTimeout(() => {
            testChatVisibility();
            log('Test completato - Il footer dovrebbe essere visibile in basso a destra');
        }, 1000);
    });
    </script>
</body>
</html>
