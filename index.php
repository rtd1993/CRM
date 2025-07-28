<?php
// File: index.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tunnel_bypass.php';

<?php
// File: index.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/tunnel_bypass.php';
require_once __DIR__ . '/includes/config.php';

// Se siamo su LocalTunnel e non abbiamo l'header di bypass, mostra pagina di bypass
if (isLocalTunnel()) {
    $headers = getallheaders();
    if (!isset($headers['bypass-tunnel-reminder']) && !isset($headers['Bypass-Tunnel-Reminder'])) {
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
            </style>
            <?= getTunnelBypassScript() ?>
        </head>
        <body>
            <div class="bypass-container">
                <div class="logo">🚀</div>
                <h1>Accesso CRM ASContabilmente</h1>
                <div class="status">
                    ⏳ Bypass LocalTunnel in corso...
                </div>
                <div class="loader"></div>
                <p>Verrai reindirizzato automaticamente al CRM.</p>
            </div>
            
            <script>
                // Bypass automatico immediato
                (function() {
                    console.log('Eseguendo bypass LocalTunnel...');
                    
                    fetch(window.location.href, {
                        headers: {
                            'bypass-tunnel-reminder': 'crm-access',
                            'Cache-Control': 'no-cache'
                        }
                    }).then(response => {
                        if (response.ok) {
                            console.log('Bypass completato, reindirizzamento...');
                            window.location.reload();
                        } else {
                            console.log('Tentativo alternativo...');
                            // Fallback: aggiungi parametro e ricarica
                            const url = new URL(window.location);
                            url.searchParams.set('bypass', '1');
                            window.location.href = url.toString();
                        }
                    }).catch(error => {
                        console.log('Errore bypass, tentativo manuale:', error);
                        // Ultimo tentativo: semplice ricarica dopo 3 secondi
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    });
                })();
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
