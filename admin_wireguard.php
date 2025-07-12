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
        <h3>➕ Aggiungi Nuovo PC</h3>
        <form method="post" class="wg-form">
            <input type="hidden" name="add_peer" value="1">
            <div>
                <label>Chiave Pubblica del PC:</label><br>
                <input type="text" name="pubkey" class="wg-input" style="width: 400px;" 
                       placeholder="Incolla qui la chiave pubblica del client" required>
            </div>
            <button type="submit" class="wg-btn wg-btn-primary">Aggiungi PC</button>
        </form>
        
        <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-top: 15px;">
            <h4 style="margin-top: 0;">🎯 Configurazione Automatica</h4>
            <p>Il sistema assegna automaticamente l'IP <strong><?= $next_ip ?></strong> al prossimo PC.</p>
        </div>
    </div>
    
    <!-- Lista Peer -->
    <div class="wg-section">
        <h3>💻 PC Configurati</h3>
        
        <?php if (empty($peers)): ?>
            <p style="text-align: center; color: #6c757d; padding: 40px;">
                Nessun PC configurato. Aggiungi il primo PC usando il form sopra.
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
                                  onsubmit="return confirm('Rimuovere questo PC dalla VPN?');">
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
    
    <!-- Guida Configurazione -->
    <div class="wg-section">
        <h3>📋 Come Configurare un PC Windows</h3>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <h4 style="margin-top: 0;">🔧 Passo 1: Installa WireGuard</h4>
            <p>Scarica e installa WireGuard da: <a href="https://www.wireguard.com/install/" target="_blank">https://www.wireguard.com/install/</a></p>
        </div>
        
        <div style="background: #d4edda; padding: 15px; border-radius: 6px; margin-bottom: 15px;">
            <h4 style="margin-top: 0;">⚙️ Passo 2: Crea Configurazione</h4>
            <p>Apri WireGuard e clicca "Aggiungi tunnel vuoto". Incolla questa configurazione:</p>
            <div class="wg-code">
[Interface]
Address = <?= $next_ip ?>/24
DNS = 8.8.8.8
PrivateKey = [GENERATA_AUTOMATICAMENTE]

[Peer]
PublicKey = <?= htmlspecialchars($publickey ?: 'CONFIGURARE_SERVER') ?>
Endpoint = <?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'IP_DEL_SERVER') ?>:51820
AllowedIPs = 0.0.0.0/0
PersistentKeepalive = 25
            </div>
        </div>
        
        <div style="background: #e3f2fd; padding: 15px; border-radius: 6px;">
            <h4 style="margin-top: 0;">🔑 Passo 3: Copia la Chiave Pubblica</h4>
            <p>Dopo aver salvato la configurazione, copia la <strong>Public Key</strong> che WireGuard ha generato e incollala nel form "Aggiungi Nuovo PC" sopra.</p>
        </div>
    </div>
</div>