<?php
// File: index.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tunnel_bypass.php';
require_once __DIR__ . '/includes/config.php';


// Se siamo su LocalTunnel e non abbiamo l'header di bypass, mostra pagina di bypass
if (isLocalTunnel()) {
    $headers = getallheaders();
    $hasHeaderBypass = isset($headers['bypass-tunnel-reminder']) || isset($headers['Bypass-Tunnel-Reminder']);
    $hasUserAgentBypass = isset($headers['User-Agent']) && 
                         (strpos($headers['User-Agent'], 'CRM-Access-Bot') !== false ||
                          !preg_match('/Mozilla|Chrome|Safari|Firefox|Edge/', $headers['User-Agent']));
    
    if (!$hasHeaderBypass && !$hasUserAgentBypass) {
        // Mostra pagina di bypass invece del redirect
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <title>Accesso CRM - <?= SITE_NAME ?></title>
            <?= getTunnelBypassMeta() ?>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    margin: 0;
                    padding: 20px;
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }
                .bypass-container {
                    background: white;
                    border-radius: 15px;
                    padding: 40px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    max-width: 500px;
                    text-align: center;
                }
                .logo {
                    font-size: 48px;
                    color: #003366;
                    margin-bottom: 20px;
                }
                .status {
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-weight: bold;
                    background: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeaa7;
                }
                .loader {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                    margin: 20px auto;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .progress {
                    background: #e9ecef;
                    border-radius: 10px;
                    height: 20px;
                    margin: 20px 0;
                    overflow: hidden;
                }
                .progress-bar {
                    background: linear-gradient(90deg, #007bff, #0056b3);
                    height: 100%;
                    width: 0%;
                    transition: width 0.5s ease;
                    border-radius: 10px;
                }
            </style>
            <?= getTunnelBypassScript() ?>
        </head>
        <body>
            <div class="bypass-container">
                <div class="logo">🚀</div>
                <h1>Accesso CRM ASContabilmente</h1>
                <div class="status" id="status">
                    ⏳ Bypass LocalTunnel in corso...
                </div>
                <div class="progress">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="loader" id="loader"></div>
                <p id="message">Tentativo bypass automatico...</p>
            </div>
            
            <script>
                let progressValue = 0;
                let attempts = 0;
                const maxAttempts = 3;
                
                function updateProgress(percent, message) {
                    document.getElementById('progressBar').style.width = percent + '%';
                    document.getElementById('message').textContent = message;
                }
                
                function updateStatus(text, type = 'info') {
                    const status = document.getElementById('status');
                    status.textContent = text;
                    status.className = 'status';
                    if (type === 'success') status.style.background = '#d4edda';
                    if (type === 'error') status.style.background = '#f8d7da';
                }
                
                // Bypass automatico con metodi multipli
                function attemptBypass() {
                    attempts++;
                    updateProgress(20, `Tentativo ${attempts}/${maxAttempts}...`);
                    
                    console.log(`🚀 Tentativo bypass ${attempts}...`);
                    
                    // Metodo 1: User-Agent personalizzato (più affidabile)
                    fetch(window.location.href, {
                        headers: {
                            'User-Agent': 'CRM-Access-Bot',
                            'Cache-Control': 'no-cache'
                        }
                    }).then(response => {
                        updateProgress(60, 'Verifica User-Agent bypass...');
                        if (response.ok) {
                            updateProgress(100, 'Bypass User-Agent completato!');
                            updateStatus('✅ Bypass completato! Reindirizzamento...', 'success');
                            setTimeout(() => window.location.reload(), 1000);
                            return true;
                        }
                        throw new Error('User-Agent bypass fallito');
                    }).catch(error => {
                        console.log('User-Agent bypass fallito, provo header bypass...');
                        
                        // Metodo 2: Header bypass (fallback)
                        return fetch(window.location.href, {
                            headers: {
                                'bypass-tunnel-reminder': 'crm-access',
                                'Cache-Control': 'no-cache'
                            }
                        });
                    }).then(response => {
                        if (response && response.ok) {
                            updateProgress(100, 'Bypass Header completato!');
                            updateStatus('✅ Bypass completato! Reindirizzamento...', 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            throw new Error('Entrambi i metodi falliti');
                        }
                    }).catch(error => {
                        console.log(`Tentativo ${attempts} fallito:`, error);
                        
                        if (attempts < maxAttempts) {
                            updateProgress(attempts * 30, `Ritento tra 2 secondi...`);
                            setTimeout(attemptBypass, 2000);
                        } else {
                            updateStatus('⚠️ Bypass automatico fallito', 'error');
                            updateProgress(100, 'Prova il bypass manuale...');
                            document.getElementById('loader').style.display = 'none';
                            
                            // Mostra istruzioni manuali
                            document.getElementById('message').innerHTML = `
                                <div style="margin-top: 20px; text-align: left; font-size: 14px;">
                                    <strong>💡 Bypass Manuale:</strong><br>
                                    1. Apri Console (F12)<br>
                                    2. Incolla: <code style="background: #f8f9fa; padding: 2px;">fetch(window.location.href, {headers: {'User-Agent': 'CRM-Access-Bot'}}).then(() => window.location.reload());</code><br>
                                    3. Premi Enter
                                </div>
                            `;
                        }
                    });
                }
                
                // Avvia bypass automatico dopo un breve delay
                setTimeout(() => {
                    updateProgress(10, 'Inizializzazione bypass...');
                    attemptBypass();
                }, 1000);
            </script>
        </body>
        </html>
        <?php
        exit();
    }
}

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit();
} else {
    header('Location: login.php');
    exit();
}
