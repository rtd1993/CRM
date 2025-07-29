<?php
// File: index.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tunnel_bypass.php';
require_once __DIR__ . '/includes/config.php';

// Controllo bypass più intelligente per LocalTunnel
if (isLocalTunnel()) {
    $headers = getallheaders();
    $isFromLocaltunnelPage = isset($_GET['bypass']) || 
                            isset($_POST['bypass']) || 
                            (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'loca.lt') !== false);
    
    $hasHeaderBypass = isset($headers['bypass-tunnel-reminder']) || 
                      isset($headers['Bypass-Tunnel-Reminder']);
    
    $hasUserAgentBypass = isset($headers['User-Agent']) && 
                         (strpos($headers['User-Agent'], 'CRM-Access-Bot') !== false ||
                          strpos($headers['User-Agent'], 'curl') !== false ||
                          !preg_match('/Mozilla|Chrome|Safari|Firefox|Edge/', $headers['User-Agent']));
    
    // Solo mostra bypass se:
    // 1. Non abbiamo header di bypass
    // 2. Non abbiamo User-Agent di bypass  
    // 3. Non arriviamo da una pagina LocalTunnel (evita loop)
    // 4. Non abbiamo parametri di bypass
    if (!$hasHeaderBypass && !$hasUserAgentBypass && !$isFromLocaltunnelPage) {
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
                .password-info {
                    background: #d1ecf1;
                    color: #0c5460;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border: 1px solid #bee5eb;
                }
                .btn {
                    background: #007bff;
                    color: white;
                    border: none;
                    padding: 15px 30px;
                    border-radius: 8px;
                    cursor: pointer;
                    font-size: 16px;
                    margin: 10px;
                    text-decoration: none;
                    display: inline-block;
                    transition: background 0.3s;
                }
                .btn:hover {
                    background: #0056b3;
                }
                .btn.success {
                    background: #28a745;
                }
                .helper-links {
                    margin-top: 30px;
                    font-size: 14px;
                }
                .helper-links a {
                    color: #007bff;
                    text-decoration: none;
                    margin: 0 10px;
                }
                .helper-links a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="bypass-container">
                <div class="logo">🚀</div>
                <h1>Accesso CRM ASContabilmente</h1>
                
                <div class="password-info">
                    <h3>🔑 LocalTunnel Password</h3>
                    <p><strong>Password fissa:</strong> <code>AnnaSabina01!</code></p>
                    <p>Usa questa password per accedere al tunnel.</p>
                </div>
                
                <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;">
                    <strong>🔐 Accesso Richiesto</strong><br>
                    LocalTunnel richiede autenticazione per sicurezza.
                </div>
                
                <div style="margin: 20px 0;">
                    <p><strong>Metodo 1 (Consigliato):</strong> Usa la password fissa sopra</p>
                    <p><strong>Metodo 2:</strong> Bypass automatico (clicca sotto)</p>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="https://ascontabilmente.loca.lt" target="_blank" class="btn success">
                        🚀 Vai al CRM (Password)
                    </a>
                    <button onclick="attemptBypass()" class="btn">
                        🔧 Bypass Automatico
                    </button>
                </div>
                
                <div class="helper-links">
                    <a href="/auto_login.html">🔐 Auto Login Helper</a> |
                    <a href="/tunnel_wrapper.html">🚀 Wrapper Automatico</a>
                </div>
                
                <div style="margin-top: 20px; font-size: 14px; color: #6c757d;">
                    <p><strong>URL:</strong> https://ascontabilmente.loca.lt</p>
                    <p><strong>Password:</strong> AnnaSabina01!</p>
                </div>
            </div>
            
            <script>
                function attemptBypass() {
                    console.log('🚀 Tentativo bypass automatico...');
                    
                    // Metodo User-Agent personalizzato
                    fetch(window.location.href + '?bypass=1', {
                        headers: {
                            'User-Agent': 'CRM-Access-Bot',
                            'Cache-Control': 'no-cache'
                        }
                    }).then(response => {
                        if (response.ok) {
                            console.log('✅ Bypass completato, reindirizzamento...');
                            window.location.href = window.location.href + '?bypass=1';
                        } else {
                            throw new Error('Bypass fallito');
                        }
                    }).catch(error => {
                        console.log('❌ Bypass automatico fallito:', error);
                        alert('Bypass automatico fallito. Usa la password: AnnaSabina01!');
                    });
                }
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
?>