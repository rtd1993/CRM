<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Solo admin/dev
if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Non autorizzato.");
}

// Configurazione percorsi
$WG_CONF = '/etc/wireguard/wg0.conf';
$WG_PRIVKEY = '/etc/wireguard/privatekey';
$WG_PUBKEY = '/etc/wireguard/publickey';

// Leggi chiavi server
$privatekey = trim(@file_get_contents($WG_PRIVKEY));
$publickey = trim(@file_get_contents($WG_PUBKEY));

// Funzione per leggere peers dal file
function get_peers($conf) {
    if (!file_exists($conf)) return [];
    
    $peers = [];
    $current = null;
    $lines = file($conf, FILE_IGNORE_NEW_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '[Peer]') {
            if ($current && isset($current['PublicKey'])) {
                $peers[] = $current;
            }
            $current = [];
        } elseif ($current !== null && strpos($line, '=') !== false) {
            list($key, $value) = array_map('trim', explode('=', $line, 2));
            $current[$key] = $value;
        }
    }
    
    if ($current && isset($current['PublicKey'])) {
        $peers[] = $current;
    }
    
    return $peers;
}

// Funzione per calcolare prossimo IP disponibile
function get_next_ip($peers) {
    $used = [1]; // server usa .1
    foreach ($peers as $peer) {
        if (isset($peer['AllowedIPs'])) {
            $ip = explode('/', $peer['AllowedIPs'])[0];
            if (preg_match('/10\.10\.0\.(\d+)/', $ip, $m)) {
                $used[] = (int)$m[1];
            }
        }
    }
    
    for ($i = 2; $i <= 254; $i++) {
        if (!in_array($i, $used)) {
            return "10.10.0.$i";
        }
    }
    return "10.10.0.2";
}

// Funzione per rimuovere peer
function remove_peer($conf, $pubkey_remove) {
    $lines = file($conf, FILE_IGNORE_NEW_LINES);
    $new_lines = [];
    $skip = false;
    $in_peer = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        if ($trimmed === '[Peer]') {
            $in_peer = true;
            $skip = false;
            $peer_block = [$line];
            continue;
        }
        
        if ($in_peer) {
            $peer_block[] = $line;
            
            if (strpos($trimmed, 'PublicKey') === 0) {
                $pubkey = trim(explode('=', $trimmed, 2)[1]);
                if ($pubkey === $pubkey_remove) {
                    $skip = true;
                }
            }
            
            if (empty($trimmed) || $line === end($lines)) {
                if (!$skip) {
                    $new_lines = array_merge($new_lines, $peer_block);
                }
                $in_peer = false;
                unset($peer_block);
            }
        } else {
            $new_lines[] = $line;
        }
    }
    
    file_put_contents($conf, implode("\n", $new_lines));
}

// Funzione per verificare connessioni attive
function get_active_connections() {
    $output = shell_exec('sudo wg show wg0 2>/dev/null');
    if (empty($output)) return [];
    
    $connections = [];
    $lines = explode("\n", $output);
    $current_peer = null;
    
    foreach ($lines as $line) {
        if (strpos($line, 'peer: ') === 0) {
            $current_peer = trim(substr($line, 6));
            $connections[$current_peer] = ['connected' => true, 'transfer' => ''];
        } elseif ($current_peer && strpos($line, 'transfer: ') !== false) {
            $connections[$current_peer]['transfer'] = trim(substr($line, 10));
        }
    }
    
    return $connections;
}

// Gestione POST
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_peer']) && !empty($_POST['pubkey'])) {
        $pubkey = trim($_POST['pubkey']);
        $peers = get_peers($WG_CONF);
        $next_ip = get_next_ip($peers);
        
        $peer_config = "\n[Peer]\nPublicKey = $pubkey\nAllowedIPs = $next_ip/32\n";
        
        // Backup
        @copy($WG_CONF, $WG_CONF . '.bak_' . date('Ymd_His'));
        
        // Aggiungi peer
        file_put_contents($WG_CONF, $peer_config, FILE_APPEND);
        
        // Riavvia WireGuard
        shell_exec('sudo wg-quick down wg0 2>/dev/null && sudo wg-quick up wg0 2>/dev/null');
        
        $msg = "✅ Peer aggiunto con IP $next_ip";
    }
    
    if (isset($_POST['remove_peer'])) {
        $pubkey = $_POST['remove_peer'];
        
        // Backup
        @copy($WG_CONF, $WG_CONF . '.bak_' . date('Ymd_His'));
        
        // Rimuovi peer
        remove_peer($WG_CONF, $pubkey);
        
        // Riavvia WireGuard
        shell_exec('sudo wg-quick down wg0 2>/dev/null && sudo wg-quick up wg0 2>/dev/null');
        
        $msg = "✅ Peer rimosso";
    }
}

$peers = get_peers($WG_CONF);
$next_ip = get_next_ip($peers);
$active_connections = get_active_connections();
?>

<style>
.wg-container { max-width: 1000px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; }
.wg-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.wg-section h3 { margin-top: 0; color: #495057; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
.wg-alert { padding: 15px; margin: 15px 0; border-radius: 5px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.wg-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
.wg-table th, .wg-table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
.wg-table th { background: #007bff; color: white; }
.wg-form { display: flex; gap: 10px; align-items: end; margin: 15px 0; }
.wg-input { padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
.wg-btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
.wg-btn-primary { background: #007bff; color: white; }
.wg-btn-danger { background: #dc3545; color: white; }
.wg-btn:hover { opacity: 0.9; }
.wg-code { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 14px; overflow-x: auto; }
.wg-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.wg-badge-success { background: #d4edda; color: #155724; }
.wg-badge-secondary { background: #f8f9fa; color: #6c757d; }
.wg-status { display: flex; gap: 20px; margin: 15px 0; }
.wg-status div { text-align: center; }
.wg-status .number { font-size: 24px; font-weight: bold; color: #007bff; }
</style>

<div class="wg-container">
    <h2>🔒 WireGuard VPN - Gestione Semplificata</h2>
    
    <?php if ($msg): ?>
        <div class="wg-alert"><?= $msg ?></div>
    <?php endif; ?>
    
    <!-- Stato -->
    <div class="wg-section">
        <h3>📊 Stato VPN</h3>
        <div class="wg-status">
            <div>
                <div class="number"><?= count($peers) ?></div>
                <div>Peer Configurati</div>
            </div>
            <div>
                <div class="number"><?= count($active_connections) ?></div>
                <div>PC Connessi</div>
            </div>
            <div>
                <div class="number"><?= $next_ip ?></div>
                <div>Prossimo IP</div>
            </div>
        </div>
    </div>
    
    <!-- Aggiungi Peer -->
    <div class="wg-section">
        <h3>➕ Gestione Dispositivi</h3>
        <button id="btn-nuovo-dispositivo" class="wg-btn wg-btn-primary" onclick="iniziaWizard()">
            🚀 Aggiungi Nuovo Dispositivo
        </button>
        
        <!-- Wizard di configurazione (nascosto inizialmente) -->
        <div id="wizard-container" style="display: none; margin-top: 20px;">
            <div class="wizard-header">
                <h4>🔧 Configurazione Guidata Dispositivo</h4>
                <div class="wizard-progress">
                    <div class="step active" id="step-indicator-1">1</div>
                    <div class="step" id="step-indicator-2">2</div>
                    <div class="step" id="step-indicator-3">3</div>
                    <div class="step" id="step-indicator-4">4</div>
                </div>
            </div>
            
            <!-- Step 1: Installazione -->
            <div id="wizard-step-1" class="wizard-step">
                <h5>📥 Passo 1: Installa WireGuard</h5>
                <div class="step-content">
                    <p>Prima di tutto, devi installare WireGuard sul dispositivo che vuoi connettere.</p>
                    <div class="platform-buttons">
                        <button class="platform-btn" onclick="selezionaPiattaforma('windows')">
                            🪟 Windows
                        </button>
                        <button class="platform-btn" onclick="selezionaPiattaforma('mac')">
                            🍎 macOS
                        </button>
                        <button class="platform-btn" onclick="selezionaPiattaforma('linux')">
                            🐧 Linux
                        </button>
                        <button class="platform-btn" onclick="selezionaPiattaforma('android')">
                            🤖 Android
                        </button>
                        <button class="platform-btn" onclick="selezionaPiattaforma('ios')">
                            📱 iOS
                        </button>
                    </div>
                    <div id="install-instructions" class="install-box" style="display: none;"></div>
                </div>
            </div>
            
            <!-- Step 2: Generazione configurazione -->
            <div id="wizard-step-2" class="wizard-step" style="display: none;">
                <h5>⚙️ Passo 2: Genera Configurazione</h5>
                <div class="step-content">
                    <p>Ora genereremo la configurazione per il tuo dispositivo.</p>
                    <div class="config-form">
                        <label>Nome del dispositivo (opzionale):</label>
                        <input type="text" id="device-name" placeholder="es. Laptop di Mario, PC Ufficio..." class="wg-input">
                        <p class="info-text">IP assegnato: <strong><?= $next_ip ?></strong></p>
                        <button class="wg-btn wg-btn-primary" onclick="generaConfigurazione()">
                            🔧 Genera Configurazione
                        </button>
                    </div>
                    <div id="generated-config" style="display: none;">
                        <h6>📄 Configurazione Generata:</h6>
                        <div class="config-display">
                            <textarea id="config-text" readonly class="config-textarea"></textarea>
                            <div class="config-actions">
                                <button class="wg-btn wg-btn-primary" onclick="copiaConfigurazione()">
                                    📋 Copia Configurazione
                                </button>
                                <button class="wg-btn wg-btn-primary" onclick="downloadConfig()">
                                    💾 Scarica File .conf
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Importa nel client -->
            <div id="wizard-step-3" class="wizard-step" style="display: none;">
                <h5>📲 Passo 3: Importa Configurazione</h5>
                <div class="step-content">
                    <div id="import-instructions"></div>
                    <div class="verification-box">
                        <h6>🔍 Verifica della Configurazione</h6>
                        <p>Dopo aver importato la configurazione, cerca la <strong>Chiave Pubblica</strong> nel client WireGuard e incollala qui:</p>
                        <input type="text" id="client-pubkey" placeholder="Incolla la chiave pubblica del client..." class="wg-input" style="width: 100%;">
                        <button class="wg-btn wg-btn-primary" onclick="verificaChiave()" style="margin-top: 10px;">
                            ✅ Verifica e Aggiungi
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Step 4: Test connessione -->
            <div id="wizard-step-4" class="wizard-step" style="display: none;">
                <h5>🧪 Passo 4: Test Connessione</h5>
                <div class="step-content">
                    <div class="success-message">
                        <h6>🎉 Dispositivo Aggiunto con Successo!</h6>
                        <p>Il dispositivo è stato configurato correttamente. Ora puoi testare la connessione.</p>
                    </div>
                    <div class="test-instructions">
                        <h6>🔌 Test della Connessione:</h6>
                        <ol>
                            <li>Attiva la VPN nel client WireGuard</li>
                            <li>Prova a visitare: <a href="http://<?= $_SERVER['SERVER_ADDR'] ?? 'server-ip' ?>" target="_blank">http://<?= $_SERVER['SERVER_ADDR'] ?? 'server-ip' ?></a></li>
                            <li>Verifica che il dispositivo appaia come "Connesso" nella lista sottostante</li>
                        </ol>
                    </div>
                    <div class="wizard-actions">
                        <button class="wg-btn wg-btn-primary" onclick="chiudiWizard()">
                            ✅ Completa Configurazione
                        </button>
                        <button class="wg-btn" onclick="aggiungiAltroDispositivo()">
                            ➕ Aggiungi Altro Dispositivo
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Controlli wizard -->
            <div class="wizard-controls">
                <button id="btn-prev" class="wg-btn" onclick="stepPrecedente()" style="display: none;">⬅️ Indietro</button>
                <button id="btn-next" class="wg-btn wg-btn-primary" onclick="stepSuccessivo()" style="display: none;">Avanti ➡️</button>
                <button id="btn-close" class="wg-btn" onclick="chiudiWizard()">❌ Chiudi</button>
            </div>
        </div>
        
        <!-- Form nascosto per aggiungere peer -->
        <form id="add-peer-form" method="post" style="display: none;">
            <input type="hidden" name="add_peer" value="1">
            <input type="hidden" name="pubkey" id="final-pubkey">
            <input type="hidden" name="device_name" id="final-device-name">
        </form>
    </div>
    
    <!-- Lista Peer -->
    <div class="wg-section">
        <h3>💻 Dispositivi Configurati</h3>
        
        <?php if (empty($peers)): ?>
            <p style="text-align: center; color: #6c757d; padding: 40px;">
                Nessun dispositivo configurato. Usa il pulsante "Aggiungi Nuovo Dispositivo" per iniziare.
            </p>
        <?php else: ?>
            <table class="wg-table">
                <thead>
                    <tr>
                        <th>IP Assegnato</th>
                        <th>Chiave Pubblica</th>
                        <th>Stato</th>
                        <th>Traffico</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peers as $peer): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(explode('/', $peer['AllowedIPs'])[0]) ?></strong></td>
                        <td style="font-family: monospace; font-size: 12px; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars(substr($peer['PublicKey'], 0, 20)) ?>...
                        </td>
                        <td>
                            <?php if (isset($active_connections[$peer['PublicKey']])): ?>
                                <span class="wg-badge wg-badge-success">🟢 Connesso</span>
                            <?php else: ?>
                                <span class="wg-badge wg-badge-secondary">⚫ Offline</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px;">
                            <?= isset($active_connections[$peer['PublicKey']]) ? 
                                htmlspecialchars($active_connections[$peer['PublicKey']]['transfer']) : 
                                'N/A' ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;" 
                                  onsubmit="return confirm('Rimuovere questo dispositivo dalla VPN?');">
                                <input type="hidden" name="remove_peer" value="<?= htmlspecialchars($peer['PublicKey']) ?>">
                                <button type="submit" class="wg-btn wg-btn-danger">🗑️ Rimuovi</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    </div>
</div>

<style>
/* Stili Wizard */
.wizard-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #007bff;
}

.wizard-progress {
    display: flex;
    justify-content: center;
    margin-top: 15px;
    gap: 40px;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #6c757d;
    position: relative;
}

.step.active {
    background: #007bff;
    color: white;
}

.step.completed {
    background: #28a745;
    color: white;
}

.step.completed::after {
    content: "✓";
    font-size: 18px;
}

.wizard-step {
    background: white;
    border-radius: 8px;
    padding: 30px;
    margin: 20px 0;
    border: 1px solid #dee2e6;
}

.step-content {
    margin-top: 20px;
}

.platform-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.platform-btn {
    padding: 15px;
    border: 2px solid #007bff;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.3s;
}

.platform-btn:hover, .platform-btn.selected {
    background: #007bff;
    color: white;
}

.install-box {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.config-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.config-display {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 15px 0;
}

.config-textarea {
    width: 100%;
    height: 200px;
    font-family: monospace;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 10px;
    resize: vertical;
}

.config-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.verification-box {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.success-message {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.test-instructions {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.wizard-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.wizard-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.info-text {
    background: #e3f2fd;
    color: #1976d2;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
    font-weight: bold;
}
</style>

<script>
let currentStep = 1;
let selectedPlatform = '';
let generatedPrivateKey = '';
let deviceName = '';

// Configurazioni per piattaforme
const platformConfigs = {
    windows: {
        name: 'Windows',
        downloadUrl: 'https://download.wireguard.com/windows-client/wireguard-installer.exe',
        instructions: `
            <h6>📥 Download e Installazione:</h6>
            <ol>
                <li>Scarica WireGuard: <a href="https://download.wireguard.com/windows-client/wireguard-installer.exe" target="_blank" class="wg-btn wg-btn-primary">⬇️ Download WireGuard</a></li>
                <li>Esegui il file .exe come amministratore</li>
                <li>Segui la procedura di installazione</li>
                <li>Avvia WireGuard dal menu Start</li>
            </ol>
        `
    },
    mac: {
        name: 'macOS',
        downloadUrl: 'https://apps.apple.com/app/wireguard/id1451685025',
        instructions: `
            <h6>📥 Download e Installazione:</h6>
            <ol>
                <li>Apri l'App Store</li>
                <li>Cerca "WireGuard" o usa questo link: <a href="https://apps.apple.com/app/wireguard/id1451685025" target="_blank" class="wg-btn wg-btn-primary">🍎 App Store</a></li>
                <li>Installa l'app gratuitamente</li>
                <li>Apri WireGuard dal Launchpad</li>
            </ol>
        `
    },
    linux: {
        name: 'Linux',
        downloadUrl: 'https://www.wireguard.com/install/',
        instructions: `
            <h6>📥 Installazione via terminale:</h6>
            <div class="wg-code">
# Ubuntu/Debian:
sudo apt update && sudo apt install wireguard

# CentOS/RHEL:
sudo yum install wireguard-tools

# Arch Linux:
sudo pacman -S wireguard-tools
            </div>
        `
    },
    android: {
        name: 'Android',
        downloadUrl: 'https://play.google.com/store/apps/details?id=com.wireguard.android',
        instructions: `
            <h6>📥 Download e Installazione:</h6>
            <ol>
                <li>Apri Google Play Store</li>
                <li>Cerca "WireGuard" o usa questo link: <a href="https://play.google.com/store/apps/details?id=com.wireguard.android" target="_blank" class="wg-btn wg-btn-primary">📱 Play Store</a></li>
                <li>Installa l'app gratuitamente</li>
                <li>Apri l'app WireGuard</li>
            </ol>
        `
    },
    ios: {
        name: 'iOS',
        downloadUrl: 'https://apps.apple.com/app/wireguard/id1441195209',
        instructions: `
            <h6>📥 Download e Installazione:</h6>
            <ol>
                <li>Apri l'App Store</li>
                <li>Cerca "WireGuard" o usa questo link: <a href="https://apps.apple.com/app/wireguard/id1441195209" target="_blank" class="wg-btn wg-btn-primary">📱 App Store</a></li>
                <li>Installa l'app gratuitamente</li>
                <li>Apri l'app WireGuard</li>
            </ol>
        `
    }
};

function iniziaWizard() {
    document.getElementById('btn-nuovo-dispositivo').style.display = 'none';
    document.getElementById('wizard-container').style.display = 'block';
    currentStep = 1;
    aggiornaProgresso();
}

function chiudiWizard() {
    document.getElementById('btn-nuovo-dispositivo').style.display = 'block';
    document.getElementById('wizard-container').style.display = 'none';
    resetWizard();
}

function resetWizard() {
    currentStep = 1;
    selectedPlatform = '';
    generatedPrivateKey = '';
    deviceName = '';
    
    // Reset form
    document.getElementById('device-name').value = '';
    document.getElementById('client-pubkey').value = '';
    
    // Reset display
    document.getElementById('install-instructions').style.display = 'none';
    document.getElementById('generated-config').style.display = 'none';
    
    // Reset platform buttons
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    aggiornaProgresso();
}

function selezionaPiattaforma(platform) {
    selectedPlatform = platform;
    
    // Update button states
    document.querySelectorAll('.platform-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    event.target.classList.add('selected');
    
    // Show instructions
    const config = platformConfigs[platform];
    document.getElementById('install-instructions').innerHTML = config.instructions;
    document.getElementById('install-instructions').style.display = 'block';
    
    // Show next button
    document.getElementById('btn-next').style.display = 'inline-block';
}

function generaChiavePrivata() {
    // Simulazione di generazione chiave (in realtà dovrebbe essere fatto client-side)
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    let result = '';
    for (let i = 0; i < 44; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result + '=';
}

function generaConfigurazione() {
    deviceName = document.getElementById('device-name').value || 'Dispositivo Senza Nome';
    generatedPrivateKey = generaChiavePrivata();
    
    const config = `[Interface]
Address = <?= $next_ip ?>/24
DNS = 8.8.8.8, 1.1.1.1
PrivateKey = ${generatedPrivateKey}

[Peer]
PublicKey = <?= htmlspecialchars($publickey ?: 'SERVER_PUBLIC_KEY_MISSING') ?>
Endpoint = <?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? 'SERVER_IP') ?>:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25`;

    document.getElementById('config-text').value = config;
    document.getElementById('generated-config').style.display = 'block';
    document.getElementById('btn-next').style.display = 'inline-block';
}

function copiaConfigurazione() {
    const textarea = document.getElementById('config-text');
    textarea.select();
    document.execCommand('copy');
    
    // Feedback visivo
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '✅ Copiato!';
    btn.style.background = '#28a745';
    
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.style.background = '#007bff';
    }, 2000);
}

function downloadConfig() {
    const config = document.getElementById('config-text').value;
    const blob = new Blob([config], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${deviceName.replace(/[^a-zA-Z0-9]/g, '_')}_wireguard.conf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

function stepSuccessivo() {
    if (currentStep < 4) {
        // Validazione per ogni step
        if (currentStep === 1 && !selectedPlatform) {
            alert('Seleziona prima una piattaforma!');
            return;
        }
        
        if (currentStep === 2 && !document.getElementById('generated-config').style.display.includes('block')) {
            alert('Genera prima la configurazione!');
            return;
        }
        
        mostraStep(currentStep + 1);
    }
}

function stepPrecedente() {
    if (currentStep > 1) {
        mostraStep(currentStep - 1);
    }
}

function mostraStep(step) {
    // Nasconde step corrente
    document.getElementById(`wizard-step-${currentStep}`).style.display = 'none';
    
    currentStep = step;
    
    // Mostra nuovo step
    document.getElementById(`wizard-step-${currentStep}`).style.display = 'block';
    
    // Aggiorna istruzioni specifiche per piattaforma
    if (currentStep === 3 && selectedPlatform) {
        aggiornaIstruzioniImport();
    }
    
    aggiornaProgresso();
}

function aggiornaIstruzioniImport() {
    const config = platformConfigs[selectedPlatform];
    let instructions = `<h6>📲 Come importare in ${config.name}:</h6>`;
    
    switch (selectedPlatform) {
        case 'windows':
            instructions += `
                <ol>
                    <li>Apri WireGuard sul PC</li>
                    <li>Clicca "Aggiungi Tunnel" → "Aggiungi tunnel vuoto"</li>
                    <li>Sostituisci tutto il contenuto con la configurazione generata sopra</li>
                    <li>Salva con nome "${deviceName}"</li>
                    <li>Copia la "Chiave pubblica" mostrata e incollala sotto</li>
                </ol>
            `;
            break;
        case 'android':
        case 'ios':
            instructions += `
                <ol>
                    <li>Apri l'app WireGuard</li>
                    <li>Tocca il pulsante "+" → "Crea da file o archivio"</li>
                    <li>Seleziona il file .conf scaricato (o copia/incolla la configurazione)</li>
                    <li>Nella configurazione, trova la "Chiave pubblica" e copiala sotto</li>
                </ol>
            `;
            break;
        default:
            instructions += `
                <ol>
                    <li>Salva la configurazione in un file .conf</li>
                    <li>Importa nel client WireGuard</li>
                    <li>Trova la chiave pubblica generata e incollala sotto</li>
                </ol>
            `;
    }
    
    document.getElementById('import-instructions').innerHTML = instructions;
}

function verificaChiave() {
    const pubkey = document.getElementById('client-pubkey').value.trim();
    
    if (!pubkey) {
        alert('Inserisci la chiave pubblica del client!');
        return;
    }
    
    // Validazione base della chiave
    if (pubkey.length < 40 || !pubkey.match(/^[A-Za-z0-9+/]+=*$/)) {
        alert('La chiave pubblica non sembra valida. Controlla di aver copiato correttamente.');
        return;
    }
    
    // Aggiungi il peer
    document.getElementById('final-pubkey').value = pubkey;
    document.getElementById('final-device-name').value = deviceName;
    document.getElementById('add-peer-form').submit();
}

function aggiornaProgresso() {
    // Update step indicators
    for (let i = 1; i <= 4; i++) {
        const indicator = document.getElementById(`step-indicator-${i}`);
        indicator.classList.remove('active', 'completed');
        
        if (i < currentStep) {
            indicator.classList.add('completed');
        } else if (i === currentStep) {
            indicator.classList.add('active');
        }
    }
    
    // Update step visibility
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById(`wizard-step-${i}`);
        step.style.display = i === currentStep ? 'block' : 'none';
    }
    
    // Update navigation buttons
    document.getElementById('btn-prev').style.display = currentStep > 1 ? 'inline-block' : 'none';
    document.getElementById('btn-next').style.display = currentStep < 4 ? 'inline-block' : 'none';
}

function aggiungiAltroDispositivo() {
    resetWizard();
    mostraStep(1);
}
</script>