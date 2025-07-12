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
$WG_PRIVKEY = '/var/www/CRM/privatekey';
$WG_PUBKEY = '/var/www/CRM/publickey';

// Leggi chiavi server
$privatekey = trim(@file_get_contents($WG_PRIVKEY));
$publickey = trim(@file_get_contents($WG_PUBKEY));

// Funzione per leggere peers dal file di configurazione WireGuard
function get_peers($conf) {
    if (!file_exists($conf)) {
        return [];
    }
    
    $content = '';
    if (is_readable($conf)) {
        $content = file_get_contents($conf);
    }
    
    // Se PHP non riesce a leggere, prova con sudo
    if ($content === false || empty($content)) {
        $content = shell_exec("sudo cat " . escapeshellarg($conf) . " 2>/dev/null");
    }
    
    if (empty($content)) {
        return [];
    }
    
    $peers = [];
    $current_peer = null;
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Ignora commenti e linee vuote
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Inizia una nuova sezione [Peer]
        if (strtolower($line) === '[peer]') {
            // Salva il peer precedente se esiste
            if ($current_peer !== null && isset($current_peer['PublicKey'])) {
                $peers[] = $current_peer;
            }
            $current_peer = [];
            continue;
        }
        
        // Ignora sezione [Interface]
        if (strtolower($line) === '[interface]') {
            $current_peer = null;
            continue;
        }
        
        // Processa le righe chiave = valore solo se siamo in una sezione [Peer]
        if ($current_peer !== null && strpos($line, '=') !== false) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $current_peer[$key] = $value;
            }
        }
    }
    
    // Aggiungi l'ultimo peer se esiste
    if ($current_peer !== null && isset($current_peer['PublicKey'])) {
        $peers[] = $current_peer;
    }
    
    return $peers;
}

// Funzione per riscrivere il wg0.conf senza un peer specifico
function remove_peer($conf, $pubkey_to_remove) {
    if (!file_exists($conf)) {
        return false;
    }
    
    $content = '';
    if (is_readable($conf)) {
        $content = file_get_contents($conf);
    }
    
    // Se PHP non riesce a leggere, prova con sudo
    if ($content === false || empty($content)) {
        $content = shell_exec("sudo cat " . escapeshellarg($conf) . " 2>/dev/null");
    }
    
    if (empty($content)) {
        return false;
    }
    
    $lines = explode("\n", $content);
    $new_lines = [];
    $current_section = null;
    $skip_peer = false;
    $peer_buffer = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Identifica sezioni
        if (preg_match('/^\[(\w+)\]$/', $trimmed, $matches)) {
            $section = strtolower($matches[1]);
            
            // Se stavamo processando un peer, decidere se salvarlo
            if ($current_section === 'peer' && !empty($peer_buffer)) {
                if (!$skip_peer) {
                    $new_lines = array_merge($new_lines, $peer_buffer);
                }
                $peer_buffer = [];
                $skip_peer = false;
            }
            
            $current_section = $section;
            
            if ($section === 'interface') {
                $new_lines[] = $line;
            } elseif ($section === 'peer') {
                $peer_buffer = [$line];
            }
            continue;
        }
        
        // Processa contenuto delle sezioni
        if ($current_section === 'interface') {
            $new_lines[] = $line;
        } elseif ($current_section === 'peer') {
            $peer_buffer[] = $line;
            
            // Controlla se questo peer deve essere rimosso
            if (strpos($trimmed, 'PublicKey') === 0) {
                $pubkey_value = trim(explode('=', $trimmed, 2)[1]);
                if ($pubkey_value === $pubkey_to_remove) {
                    $skip_peer = true;
                }
            }
        }
    }
    
    // Gestisci l'ultimo peer se necessario
    if ($current_section === 'peer' && !empty($peer_buffer) && !$skip_peer) {
        $new_lines = array_merge($new_lines, $peer_buffer);
    }
    
    // Scrivi il file aggiornato
    $content = implode("\n", $new_lines);
    if (!empty($content) && substr($content, -1) !== "\n") {
        $content .= "\n";
    }
    
    // Usa file temporaneo e sudo per scrivere
    $temp_file = tempnam(sys_get_temp_dir(), 'wg_remove_');
    if ($temp_file !== false && file_put_contents($temp_file, $content) !== false) {
        $copy_result = shell_exec("sudo cp " . escapeshellarg($temp_file) . " " . escapeshellarg($conf) . " 2>&1");
        unlink($temp_file);
        
        // Ripristina permessi
        shell_exec("sudo chown root:www-data " . escapeshellarg($conf));
        shell_exec("sudo chmod 777 " . escapeshellarg($conf));
        
        return file_exists($conf);
    }
    
    return false;
}

// Inizializza i peer e l'IP successivo
$peers = file_exists($WG_CONF) ? get_peers($WG_CONF) : [];
$next_ip = get_next_available_ip($peers);

// Aggiungi peer
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inizializza configurazione WireGuard se non esiste
    if (isset($_POST['init_config'])) {
        if (create_initial_config($WG_CONF, $privatekey)) {
            $msg = "File di configurazione WireGuard creato con successo.";
            // Ricarica i peer dopo la creazione
            $peers = get_peers($WG_CONF);
            $next_ip = get_next_available_ip($peers);
        } else {
            $debug_info = get_debug_info($WG_CONF);
            $msg = "Errore durante la creazione del file di configurazione. Debug: WG installato=" . ($debug_info['wg_installed'] ? 'Sì' : 'No') . ", Utente web=" . $debug_info['web_user'];
        }
    }
    
    if (isset($_POST['add_peer']) && preg_match('/^[A-Za-z0-9+\/=]{43,44}$/', $_POST['pubkey'])) {
        // Verifica che il file di configurazione esista
        if (!file_exists($WG_CONF)) {
            if (create_initial_config($WG_CONF, $privatekey)) {
                $msg = "File di configurazione creato automaticamente. ";
            } else {
                $msg = "Errore: impossibile creare il file di configurazione.";
                goto end_processing;
            }
        }
        
        $pubkey = trim($_POST['pubkey']);
        $allowedip = trim($_POST['allowedip'] ?? '10.10.0.2/32');
        $peer_conf = "\n[Peer]\nPublicKey = $pubkey\nAllowedIPs = $allowedip\n";
        
        // Crea backup
        $backup_file = $WG_CONF . '.bak_' . date('Ymd_His');
        shell_exec("sudo cp " . escapeshellarg($WG_CONF) . " " . escapeshellarg($backup_file));
        
        // Legge il contenuto attuale e aggiunge il peer
        $current_content = '';
        if (file_exists($WG_CONF) && is_readable($WG_CONF)) {
            $current_content = file_get_contents($WG_CONF);
            if ($current_content === false) {
                // Se non riesce a leggere con PHP, prova con sudo
                $current_content = shell_exec("sudo cat " . escapeshellarg($WG_CONF));
            }
        }
        
        // Assicurati che il contenuto non sia vuoto per un file esistente
        if (empty($current_content) && file_exists($WG_CONF)) {
            $current_content = shell_exec("sudo cat " . escapeshellarg($WG_CONF));
        }
        
        $new_content = $current_content . $peer_conf;
        
        // Crea un file temporaneo e poi lo sposta con sudo
        $temp_file = tempnam(sys_get_temp_dir(), 'wg_peer_');
        if ($temp_file !== false && file_put_contents($temp_file, $new_content) !== false) {
            $copy_result = shell_exec("sudo cp " . escapeshellarg($temp_file) . " " . escapeshellarg($WG_CONF) . " 2>&1");
            unlink($temp_file);
            
            // Ripristina permessi
            shell_exec("sudo chown root:www-data " . escapeshellarg($WG_CONF));
            shell_exec("sudo chmod 777 " . escapeshellarg($WG_CONF));
            
            shell_exec('sudo wg-quick down wg0 2>/dev/null && sudo wg-quick up wg0 2>/dev/null');
            $msg .= "Peer aggiunto e servizio WireGuard riavviato.";
            // Ricarica i peer per aggiornare l'IP successivo
            $peers = get_peers($WG_CONF);
            $next_ip = get_next_available_ip($peers);
            
            // Debug: mostra il contenuto del file
            $debug_content = shell_exec("sudo cat " . escapeshellarg($WG_CONF) . " 2>/dev/null");
            if (!empty($debug_content)) {
                $msg .= " [Debug: File contiene " . substr_count($debug_content, '[Peer]') . " peer(s)]";
            }
        } else {
            $msg = "Errore durante l'aggiunta del peer.";
        }
    }
    
    if (isset($_POST['remove_peer'])) {
        $pubkey = $_POST['remove_peer'];
        
        // Crea backup
        $backup_file = $WG_CONF . '.bak_' . date('Ymd_His');
        shell_exec("sudo cp " . escapeshellarg($WG_CONF) . " " . escapeshellarg($backup_file));
        
        if (remove_peer($WG_CONF, $pubkey)) {
            shell_exec('sudo wg-quick down wg0 2>/dev/null && sudo wg-quick up wg0 2>/dev/null');
            $msg = "Peer rimosso e servizio WireGuard riavviato.";
            // Ricarica i peer per aggiornare l'IP successivo
            $peers = get_peers($WG_CONF);
            $next_ip = get_next_available_ip($peers);
        } else {
            $msg = "Errore durante la rimozione del peer.";
        }
    }
    
    end_processing:
}

// Verifica e correggi permessi se necessario
check_and_fix_permissions($WG_CONF);

$peers = file_exists($WG_CONF) ? get_peers($WG_CONF) : [];

// Funzione per verificare lo stato di WireGuard
function check_wireguard_status() {
    $output = shell_exec('sudo wg show 2>/dev/null');
    return !empty($output);
}

// Funzione per verificare se WireGuard è installato
function check_wireguard_installed() {
    $output = shell_exec('which wg 2>/dev/null');
    return !empty($output);
}

// Funzione per ottenere informazioni di debug
function get_debug_info($conf_file) {
    $info = [];
    $info['wg_installed'] = check_wireguard_installed();
    $info['conf_exists'] = file_exists($conf_file);
    $info['conf_readable'] = is_readable($conf_file);
    $info['conf_writable'] = is_writable($conf_file);
    $info['conf_permissions'] = file_exists($conf_file) ? substr(sprintf('%o', fileperms($conf_file)), -4) : 'N/A';
    $info['conf_owner'] = file_exists($conf_file) ? posix_getpwuid(fileowner($conf_file))['name'] : 'N/A';
    $info['web_user'] = posix_getpwuid(posix_geteuid())['name'];
    return $info;
}

// Funzione per creare il file di configurazione iniziale
function create_initial_config($conf_file, $private_key) {
    $initial_config = "[Interface]\n";
    $initial_config .= "Address = 10.10.0.1/24\n";
    $initial_config .= "ListenPort = 51820\n";
    if ($private_key) {
        $initial_config .= "PrivateKey = $private_key\n";
    }
    $initial_config .= "\n";
    
    // Crea directory se non esiste
    $dir = dirname($conf_file);
    if (!is_dir($dir)) {
        shell_exec("sudo mkdir -p " . escapeshellarg($dir));
    }
    
    // Crea un file temporaneo e poi lo sposta con sudo
    $temp_file = tempnam(sys_get_temp_dir(), 'wg_config_');
    if ($temp_file === false) {
        return false;
    }
    
    // Scrive il contenuto nel file temporaneo
    $result = file_put_contents($temp_file, $initial_config);
    if ($result === false) {
        unlink($temp_file);
        return false;
    }
    
    // Sposta il file temporaneo nella posizione finale con sudo
    $copy_result = shell_exec("sudo cp " . escapeshellarg($temp_file) . " " . escapeshellarg($conf_file) . " 2>&1");
    unlink($temp_file);
    
    // Verifica che il file sia stato creato
    if (!file_exists($conf_file)) {
        return false;
    }
    
    // Imposta permessi corretti
    shell_exec("sudo chown root:www-data " . escapeshellarg($conf_file));
    shell_exec("sudo chmod 777 " . escapeshellarg($conf_file));
    
    return true;
}

// Funzione per verificare e correggere i permessi
function check_and_fix_permissions($conf_file) {
    if (!file_exists($conf_file)) {
        return false;
    }
    
    // Verifica se possiamo leggere il file
    if (!is_readable($conf_file)) {
        // Prova a correggere i permessi per permettere la lettura
        shell_exec("sudo chmod 777 " . escapeshellarg($conf_file));
        shell_exec("sudo chown root:www-data " . escapeshellarg($conf_file));
    }
    
    return is_readable($conf_file);
}

// Funzione per calcolare prossimo IP disponibile
function get_next_available_ip($peers) {
    $used_ips = [];
    foreach ($peers as $peer) {
        if (isset($peer['AllowedIPs'])) {
            $ip = explode('/', $peer['AllowedIPs'])[0];
            if (preg_match('/^10\.10\.0\.(\d+)$/', $ip, $matches)) {
                $used_ips[] = intval($matches[1]);
            }
        }
    }
    
    // Inizia da 10.10.0.2 (il server usa 10.10.0.1)
    for ($i = 2; $i <= 254; $i++) {
        if (!in_array($i, $used_ips)) {
            return "10.10.0.$i";
        }
    }
    
    return "10.10.0.2"; // fallback
}

$wg_running = check_wireguard_status();

// Reinizializza i peer e l'IP successivo se non già fatto nel POST
if (!isset($peers)) {
    $peers = file_exists($WG_CONF) ? get_peers($WG_CONF) : [];
    $next_ip = get_next_available_ip($peers);
}
?>

<style>
.wireguard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.alert {
    padding: 15px 20px;
    margin: 15px 0;
    border-radius: 8px;
    border: 1px solid transparent;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.section {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.config-box {
    background: #1e1e1e;
    color: #f8f8f2;
    padding: 15px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    border: 1px solid #444;
    overflow-x: auto;
}

.peers-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.peers-table th {
    background: #007bff;
    color: white;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
}

.peers-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
}

.peers-table tr:hover {
    background-color: #f8f9fa;
}

.peers-table tr:last-child td {
    border-bottom: none;
}

.pubkey-cell {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-width: 200px;
    word-break: break-all;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    font-size: 12px;
    padding: 6px 12px;
}

.btn-danger:hover {
    background-color: #c82333;
}

.add-peer-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.commands-box {
    background: #e9ecef;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #007bff;
    margin-top: 15px;
}

.commands-box h4 {
    margin-top: 0;
    color: #495057;
}

.commands-box pre {
    background: #1e1e1e;
    color: #f8f8f2;
    padding: 10px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    margin: 0;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
    
    .pubkey-cell {
        max-width: 150px;
    }
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state .icon {
    font-size: 48px;
    margin-bottom: 15px;
}
</style>

<div class="wireguard-container">
    <h2>🔒 Admin WireGuard - Gestione VPN</h2>
    
    <?php if ($msg): ?>
        <div class="alert alert-success">
            <strong>✅ Successo:</strong> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="grid-2">
        <!-- Configurazione Server -->
        <div class="section">
            <h3>🖥️ Configurazione Server</h3>
            <div class="config-box">
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <?= htmlspecialchars($privatekey ?: '--- NON CONFIGURATO ---') ?>

PublicKey = <?= htmlspecialchars($publickey ?: '--- NON CONFIGURATO ---') ?>
            </div>
            <?php if (!$privatekey || !$publickey): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; border-color: #ffeaa7; margin-top: 10px;">
                    <strong>⚠️ Attenzione:</strong> 
                    <?php if (!$privatekey): ?>Private key non trovata in /etc/wireguard/privatekey. <?php endif; ?>
                    <?php if (!$publickey): ?>Public key non trovata in /var/www/CRM/publickey. <?php endif; ?>
                    Configurare WireGuard prima di procedere.
                </div>
            <?php endif; ?>
        </div>

        <!-- Stato WireGuard -->
        <div class="section">
            <h3>📊 Stato WireGuard</h3>
            <div style="margin-bottom: 15px;">
                <strong>Servizio:</strong> 
                <span class="status-badge <?= $wg_running ? 'status-active' : 'status-inactive' ?>">
                    <?= $wg_running ? 'Attivo' : 'Inattivo' ?>
                </span>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Peers configurati:</strong> 
                <span style="font-weight: bold; color: #007bff;"><?= count($peers) ?></span>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Porta:</strong> 51820 (UDP)
            </div>
            <div style="margin-bottom: 15px;">
                <strong>WireGuard installato:</strong> 
                <span style="font-size: 12px; color: #6c757d;"><?= check_wireguard_installed() ? '✅ Sì' : '❌ No' ?></span>
            </div>
            <div>
                <strong>File configurazione:</strong> 
                <span style="font-size: 12px; color: #6c757d;"><?= file_exists($WG_CONF) ? '✅ Presente' : '❌ Non trovato' ?></span>
                <?php if (!file_exists($WG_CONF) && $privatekey): ?>
                    <form method="post" style="display: inline-block; margin-left: 10px;">
                        <input type="hidden" name="init_config" value="1">
                        <button type="submit" class="btn btn-primary" style="padding: 4px 8px; font-size: 12px;">
                            🔧 Crea File Config
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabella Peers -->
    <div class="section">
        <h3>👥 Peers Configurati</h3>
        
        <?php if (empty($peers)): ?>
            <div class="empty-state">
                <div class="icon">🔌</div>
                <h4>Nessun peer configurato</h4>
                <p>Aggiungi il primo peer usando il form sottostante.</p>
            </div>
        <?php else: ?>
            <table class="peers-table">
                <thead>
                    <tr>
                        <th>🔑 Public Key</th>
                        <th>🌐 Allowed IPs</th>
                        <th>📍 Endpoint</th>
                        <th>⚙️ Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peers as $peer): ?>
                    <tr>
                        <td class="pubkey-cell"><?= htmlspecialchars($peer['PublicKey'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($peer['AllowedIPs'] ?? '-') ?></td>
                        <td style="font-size: 12px;"><?= htmlspecialchars($peer['Endpoint'] ?? 'N/A') ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('⚠️ Rimuovere questo peer?\n\nQuesta azione rimuoverà il peer dalla configurazione e riavvierà WireGuard.');">
                                <input type="hidden" name="remove_peer" value="<?= htmlspecialchars($peer['PublicKey']) ?>">
                                <button type="submit" class="btn btn-danger">🗑️ Rimuovi</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Form Aggiungi Peer -->
    <div class="section">
        <h3>➕ Aggiungi Nuovo Peer</h3>
        
        <?php if (!file_exists($WG_CONF)): ?>
            <div class="alert" style="background: #fff3cd; color: #856404; border-color: #ffeaa7; margin-bottom: 20px;">
                <strong>⚠️ Attenzione:</strong> 
                Il file di configurazione WireGuard non esiste. Verrà creato automaticamente quando aggiungi il primo peer.
                <?php if ($privatekey): ?>
                    <br>Oppure puoi crearlo manualmente usando il bottone "Crea File Config" nella sezione Stato WireGuard.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="add-peer-form">
            <form method="post">
                <input type="hidden" name="add_peer" value="1">
                
                <div class="form-group">
                    <label for="pubkey">🔑 Public Key del Client:</label>
                    <input type="text" 
                           id="pubkey"
                           name="pubkey" 
                           class="form-control" 
                           placeholder="Es: ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890+/="
                           required 
                           pattern="[A-Za-z0-9+/=]{43,44}"
                           title="La chiave pubblica deve essere di 43-44 caratteri (base64)">
                </div>
                
                <div class="form-group">
                    <label for="allowedip">🌐 Allowed IPs:</label>
                    <input type="text" 
                           id="allowedip"
                           name="allowedip" 
                           class="form-control" 
                           value="<?= $next_ip ?>/32" 
                           placeholder="Es: 10.10.0.2/32"
                           required>
                    <small style="color: #6c757d; margin-top: 5px; display: block;">
                        💡 IP automatico assegnato: <?= $next_ip ?> (DHCP-like)
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    ➕ Aggiungi Peer e Riavvia WireGuard
                </button>
            </form>
        </div>
    </div>

    <!-- Guida Configurazione Windows -->
    <div class="section">
        <h3>📋 Guida: Collegare PC Windows a WireGuard</h3>
        
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h4 style="margin-top: 0; color: #1976d2;">🎯 Configurazione Automatica</h4>
            <p>Il sistema assegna automaticamente gli IP ai nuovi peer come un DHCP. Non devi specificare manualmente l'indirizzo IP.</p>
            <p><strong>Prossimo IP disponibile:</strong> <code><?= $next_ip ?></code></p>
        </div>

        <div class="commands-box">
            <h4>� Passo 1: Installare WireGuard su Windows</h4>
            <ol>
                <li>Scarica WireGuard per Windows: <a href="https://www.wireguard.com/install/" target="_blank">https://www.wireguard.com/install/</a></li>
                <li>Installa e avvia WireGuard</li>
                <li>Clicca su "Aggiungi tunnel vuoto" o premi <kbd>Ctrl+N</kbd></li>
            </ol>
        </div>

        <div class="commands-box">
            <h4>⚙️ Passo 2: Configurare il Client</h4>
            <p>Copia e incolla questa configurazione nel client WireGuard:</p>
            <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; margin: 10px 0;">
[Interface]
# IP automatico assegnato (DHCP-like)
Address = <?= $next_ip ?>/24
# Chiave privata generata automaticamente dal client
PrivateKey = &lt;GENERATA_AUTOMATICAMENTE&gt;
# DNS del server
DNS = 8.8.8.8, 1.1.1.1

[Peer]
# Chiave pubblica del server
PublicKey = <?= htmlspecialchars($publickey ?: 'CONFIGURARE_SERVER_PRIMA') ?>

# Indirizzo del server (sostituire con IP pubblico)
Endpoint = <?= htmlspecialchars($_SERVER['SERVER_ADDR'] ?? 'IP_PUBBLICO_SERVER') ?>:51820

# Tutto il traffico attraverso VPN
AllowedIPs = 0.0.0.0/0

# Mantieni connessione attiva
PersistentKeepalive = 25</pre>
        </div>

        <div class="commands-box">
            <h4>🔑 Passo 3: Ottenere la Public Key del Client</h4>
            <ol>
                <li>Dopo aver incollato la configurazione, WireGuard genererà automaticamente una <strong>PrivateKey</strong></li>
                <li>Nella finestra di configurazione, troverai la <strong>PublicKey</strong> corrispondente</li>
                <li>Copia la <strong>PublicKey</strong> del client (lunga 44 caratteri)</li>
                <li>Incollala nel form "Aggiungi Nuovo Peer" qui sopra</li>
            </ol>
        </div>

        <div class="commands-box">
            <h4>🚀 Passo 4: Attivare la Connessione</h4>
            <ol>
                <li>Salva la configurazione con un nome (es: "CRM Server")</li>
                <li>Dopo aver aggiunto il peer qui sopra, clicca "Attiva" in WireGuard</li>
                <li>Verifica la connessione: dovresti vedere traffico in entrata/uscita</li>
                <li>Testa l'accesso: <code>ping 10.10.0.1</code> dal prompt di Windows</li>
            </ol>
        </div>

        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
            <h4 style="margin-top: 0; color: #856404;">⚠️ Informazioni Importanti</h4>
            <ul style="margin-bottom: 0;">
                <li><strong>IP Server:</strong> Sostituisci <code>IP_PUBBLICO_SERVER</code> con l'IP pubblico reale del server</li>
                <li><strong>Porta:</strong> Assicurati che la porta 51820 UDP sia aperta nel firewall</li>
                <li><strong>DNS:</strong> Puoi cambiare i DNS con quelli del tuo provider</li>
                <li><strong>AllowedIPs:</strong> <code>0.0.0.0/0</code> instrada tutto il traffico tramite VPN</li>
            </ul>
        </div>

        <div style="background: #d4edda; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; margin-top: 15px;">
            <h4 style="margin-top: 0; color: #155724;">✅ Configurazione Alternativa (Solo Rete Locale)</h4>
            <p>Se vuoi accedere solo alla rete locale del server, cambia:</p>
            <pre style="background: #1e1e1e; color: #f8f8f2; padding: 10px; border-radius: 4px; margin: 10px 0;">
# Invece di: AllowedIPs = 0.0.0.0/0
# Usa: AllowedIPs = 10.10.0.0/24, 192.168.1.0/24</pre>
            <p>Questo instraderà solo il traffico verso la rete VPN (10.10.0.x) e la rete locale del server (192.168.1.x).</p>
        </div>
    </div>

    <!-- Sezione Debug/Troubleshooting -->
    <?php if (!check_wireguard_installed() || !file_exists($WG_CONF) || !$privatekey): ?>
    <div class="section">
        <h3>🔧 Troubleshooting</h3>
        
        <?php 
        $debug_info = get_debug_info($WG_CONF);
        ?>
        
        <div class="commands-box">
            <h4>📋 Informazioni di Sistema</h4>
            <ul>
                <li><strong>WireGuard installato:</strong> <?= $debug_info['wg_installed'] ? '✅ Sì' : '❌ No' ?></li>
                <li><strong>File configurazione esiste:</strong> <?= $debug_info['conf_exists'] ? '✅ Sì' : '❌ No' ?></li>
                <li><strong>File configurazione leggibile:</strong> <?= $debug_info['conf_readable'] ? '✅ Sì' : '❌ No' ?></li>
                <li><strong>Permessi file:</strong> <?= $debug_info['conf_permissions'] ?></li>
                <li><strong>Proprietario file:</strong> <?= $debug_info['conf_owner'] ?></li>
                <li><strong>Utente web server:</strong> <?= $debug_info['web_user'] ?></li>
                <li><strong>Chiave privata presente:</strong> <?= $privatekey ? '✅ Sì' : '❌ No' ?></li>
                <li><strong>Chiave pubblica presente:</strong> <?= $publickey ? '✅ Sì' : '❌ No' ?></li>
            </ul>
        </div>
        
        <?php if (file_exists($WG_CONF)): ?>
        <div class="commands-box">
            <h4>📄 Contenuto File di Configurazione</h4>
            <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; max-height: 300px; overflow-y: auto;"><?= htmlspecialchars(shell_exec("sudo cat " . escapeshellarg($WG_CONF) . " 2>/dev/null") ?: 'Impossibile leggere il file') ?></pre>
        </div>
        <?php endif; ?>
        
        <?php if (!$debug_info['wg_installed']): ?>
        <div class="commands-box">
            <h4>🚨 WireGuard non installato</h4>
            <p>WireGuard non è installato sul sistema. Installa WireGuard con:</p>
            <pre>sudo apt update && sudo apt install wireguard</pre>
        </div>
        <?php endif; ?>
        
        <?php if (!$privatekey): ?>
        <div class="commands-box">
            <h4>🔑 Chiavi Server Mancanti</h4>
            <p>Le chiavi del server WireGuard non sono state trovate. Genera le chiavi con:</p>
            <pre>sudo wg genkey | tee /var/www/CRM/privatekey | wg pubkey | tee /var/www/CRM/publickey</pre>
        </div>
        <?php endif; ?>
        
        <?php if ($debug_info['wg_installed'] && $privatekey && !$debug_info['conf_exists']): ?>
        <div class="commands-box">
            <h4>📄 File di Configurazione Mancante</h4>
            <p>Il file di configurazione WireGuard non esiste. Usa il bottone "Crea File Config" sopra o crealo manualmente.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Sezione Debug sempre visibile per troubleshooting -->
    <?php if (file_exists($WG_CONF)): ?>
    <div class="section">
        <h3>🔍 Debug - Contenuto File WireGuard</h3>
        <div class="commands-box">
            <h4>📄 Contenuto Attuale di <?= htmlspecialchars($WG_CONF) ?></h4>
            <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; max-height: 400px; overflow-y: auto;"><?= htmlspecialchars(shell_exec("sudo cat " . escapeshellarg($WG_CONF) . " 2>/dev/null") ?: 'Impossibile leggere il file') ?></pre>
        </div>
    </div>
    <?php endif; ?>
</div>