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

// Leggi privatekey server
$privatekey = trim(@file_get_contents($WG_PRIVKEY));

// Funzione per leggere peers già presenti
function get_peers($conf) {
    $peers = [];
    $current = [];
    $index = 0;
    foreach (file($conf) as $line) {
        $line = trim($line);
        if ($line === '[Peer]') {
            if ($current) {
                $current['_index'] = $index++;
                $peers[] = $current;
            }
            $current = ['raw' => '[Peer]'];
        } elseif ($line && strpos($line, '=') !== false) {
            list($k, $v) = array_map('trim', explode('=', $line, 2));
            $current[$k] = $v;
            $current['raw'] .= "\n$k = $v";
        }
    }
    if ($current && isset($current['PublicKey'])) {
        $current['_index'] = $index++;
        $peers[] = $current;
    }
    return $peers;
}

// Funzione per riscrivere il wg0.conf senza un peer specifico
function remove_peer($conf, $pubkey) {
    $lines = file($conf);
    $new_lines = [];
    $skip = false;
    foreach ($lines as $line) {
        if (trim($line) === '[Peer]') {
            $skip = false;
            $buffer = [$line];
            continue;
        }
        if (isset($buffer)) {
            $buffer[] = $line;
            if (stripos($line, 'PublicKey') !== false && trim(explode('=', $line)[1]) === $pubkey) {
                $skip = true;
            }
            if (trim($line) === '' || $line === end($lines)) {
                if (!$skip) {
                    $new_lines = array_merge($new_lines, $buffer);
                }
                unset($buffer);
            }
        } else {
            $new_lines[] = $line;
        }
    }
    file_put_contents($conf, implode('', $new_lines));
}

// Aggiungi peer
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_peer']) && preg_match('/^[A-Za-z0-9+\/=]{43,44}$/', $_POST['pubkey'])) {
        $pubkey = trim($_POST['pubkey']);
        $allowedip = trim($_POST['allowedip'] ?? '10.10.0.2/32');
        $peer_conf = "\n[Peer]\nPublicKey = $pubkey\nAllowedIPs = $allowedip\n";
        @copy($WG_CONF, $WG_CONF . '.bak_' . date('Ymd_His'));
        file_put_contents($WG_CONF, $peer_conf, FILE_APPEND);
        shell_exec('sudo wg-quick down wg0 && sudo wg-quick up wg0');
        $msg = "Peer aggiunto e servizio WireGuard riavviato.";
    }
    if (isset($_POST['remove_peer'])) {
        $pubkey = $_POST['remove_peer'];
        @copy($WG_CONF, $WG_CONF . '.bak_' . date('Ymd_His'));
        remove_peer($WG_CONF, $pubkey);
        shell_exec('sudo wg-quick down wg0 && sudo wg-quick up wg0');
        $msg = "Peer rimosso e servizio WireGuard riavviato.";
    }
}

$peers = file_exists($WG_CONF) ? get_peers($WG_CONF) : [];
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
            </div>
            <?php if (!$privatekey): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; border-color: #ffeaa7; margin-top: 10px;">
                    <strong>⚠️ Attenzione:</strong> Private key non trovata. Configurare WireGuard prima di procedere.
                </div>
            <?php endif; ?>
        </div>

        <!-- Stato WireGuard -->
        <div class="section">
            <h3>📊 Stato WireGuard</h3>
            <div style="margin-bottom: 15px;">
                <strong>Servizio:</strong> 
                <span class="status-badge status-active">Attivo</span>
            </div>
            <div style="margin-bottom: 15px;">
                <strong>Peers configurati:</strong> 
                <span style="font-weight: bold; color: #007bff;"><?= count($peers) ?></span>
            </div>
            <div>
                <strong>Porta:</strong> 51820 (UDP)
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
                        <th>⚙️ Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peers as $peer): ?>
                    <tr>
                        <td class="pubkey-cell"><?= htmlspecialchars($peer['PublicKey'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($peer['AllowedIPs'] ?? '-') ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('⚠️ Rimuovere questo peer?\n\nQuesta azione non può essere annullata.');">
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
                           value="10.10.0.2/32" 
                           placeholder="Es: 10.10.0.2/32"
                           required>
                    <small style="color: #6c757d; margin-top: 5px; display: block;">
                        💡 Usa /32 per un singolo IP, /24 per una subnet
                    </small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    ➕ Aggiungi Peer e Riavvia WireGuard
                </button>
            </form>
        </div>
    </div>

    <!-- Comandi Utili -->
    <div class="section">
        <div class="commands-box">
            <h4>🛠️ Comandi Utili</h4>
            <p><strong>Mostra stato WireGuard:</strong></p>
            <pre>sudo wg show</pre>
            
            <p><strong>Riavvia WireGuard:</strong></p>
            <pre>sudo wg-quick down wg0 && sudo wg-quick up wg0</pre>
            
            <p><strong>Visualizza configurazione:</strong></p>
            <pre>sudo cat /etc/wireguard/wg0.conf</pre>
        </div>
    </div>
</div>