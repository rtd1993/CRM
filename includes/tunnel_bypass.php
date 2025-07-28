<?php
/**
 * Tunnel Bypass Handler
 * Gestisce automaticamente il bypass per localtunnel
 */

// Verifica se siamo su localtunnel
function isLocalTunnel() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return (
        strpos($host, 'loca.lt') !== false || 
        strpos($host, 'localtunnel.me') !== false ||
        strpos($host, 'ascontabilemente.loca.lt') !== false
    );
}

// Aggiunge automaticamente l'header di bypass se necessario
function setupTunnelBypass() {
    if (isLocalTunnel()) {
        // Se non abbiamo l'header di bypass, redirect con l'header
        $headers = getallheaders();
        
        if (!isset($headers['bypass-tunnel-reminder']) && 
            !isset($headers['Bypass-Tunnel-Reminder'])) {
            
            // JavaScript per aggiungere l'header e ricaricare
            echo '<script>
                // Controlla se siamo nella pagina di avviso localtunnel
                if (document.body && document.body.innerText.includes("bypass-tunnel-reminder")) {
                    // Reindirizza con parametro speciale
                    const url = new URL(window.location);
                    url.searchParams.set("bypass", "1");
                    
                    // Usa fetch con header personalizzato
                    fetch(url.toString(), {
                        method: "GET",
                        headers: {
                            "bypass-tunnel-reminder": "crm-access",
                            "Cache-Control": "no-cache"
                        }
                    }).then(response => {
                        if (response.ok) {
                            window.location.href = url.toString();
                        }
                    });
                }
            </script>';
        }
    }
}

// Meta tag per bypass automatico
function getTunnelBypassMeta() {
    if (isLocalTunnel()) {
        return '<meta http-equiv="Set-Cookie" content="bypass-tunnel-reminder=crm-access; Path=/">';
    }
    return '';
}

// JavaScript per gestire il bypass
function getTunnelBypassScript() {
    if (isLocalTunnel()) {
        return '
        <script>
        // Auto-bypass per localtunnel con doppio metodo
        (function() {
            // Imposta cookie per future richieste
            document.cookie = "bypass-tunnel-reminder=crm-access; path=/";
            
            // Intercetta tutti i fetch e xhr per aggiungere header
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = options.headers || {};
                options.headers["bypass-tunnel-reminder"] = "crm-access";
                options.headers["User-Agent"] = "CRM-Access-Bot";
                return originalFetch(url, options);
            };
            
            // Override XMLHttpRequest
            const originalOpen = XMLHttpRequest.prototype.open;
            XMLHttpRequest.prototype.open = function() {
                originalOpen.apply(this, arguments);
                this.setRequestHeader("bypass-tunnel-reminder", "crm-access");
                this.setRequestHeader("User-Agent", "CRM-Access-Bot");
            };
            
            // Auto-bypass se rileva pagina LocalTunnel
            if (document.body && (document.body.innerText.includes("bypass-tunnel-reminder") || 
                document.body.innerText.includes("Set a bypass-tunnel-reminder"))) {
                console.log("🚀 Rilevata pagina LocalTunnel, eseguo bypass automatico...");
                
                // Metodo 1: Header bypass
                fetch(window.location.href, {
                    headers: {
                        "bypass-tunnel-reminder": "crm-access",
                        "Cache-Control": "no-cache"
                    }
                }).then(response => {
                    if (response.ok) {
                        console.log("✅ Bypass Header completato");
                        window.location.reload();
                    } else {
                        // Metodo 2: User-Agent bypass
                        return fetch(window.location.href, {
                            headers: {
                                "User-Agent": "CRM-Access-Bot",
                                "Cache-Control": "no-cache"
                            }
                        });
                    }
                }).then(response => {
                    if (response && response.ok) {
                        console.log("✅ Bypass User-Agent completato");
                        window.location.reload();
                    }
                }).catch(error => {
                    console.log("⚠️ Auto-bypass fallito:", error);
                });
            }
        })();
        </script>';
    }
    return '';
}
?>
