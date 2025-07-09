<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Solo admin/dev
if (!in_array($_SESSION['ruolo'], ['admin', 'developer'])) {
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

<h2>🔒 Admin WireGuard: Gestione VPN</h2>
<?php if ($msg): ?>
    <div style="background:#d4edda;color:#155724;padding:10px;margin:10px 0;border-radius:5px;"><?= $msg ?></div>
<?php endif; ?>

<h3>1. Configurazione lato server</h3>
<pre>
[Interface]
Address = 10.10.0.1/24
ListenPort = 51820
PrivateKey = <?= htmlspecialchars($privatekey ?: '---') ?>
</pre>

<h3>2. Peer attualmente configurati</h3>
<table style="border-collapse:collapse;">
    <tr>
        <th style="padding:6px;border-bottom:1px solid #ccc;">PublicKey</th>
        <th style="padding:6px;border-bottom:1px solid #ccc;">AllowedIPs</th>
        <th style="padding:6px;border-bottom:1px solid #ccc;">Azione</th>
    </tr>
    <?php foreach ($peers as $peer): ?>
    <tr>
        <td style="padding:6px;"><?= htmlspecialchars($peer['PublicKey'] ?? '-') ?></td>
        <td style="padding:6px;"><?= htmlspecialchars($peer['AllowedIPs'] ?? '-') ?></td>
        <td style="padding:6px;">
            <form method="post" style="display:inline;" onsubmit="return confirm('Rimuovere questo peer?');">
                <input type="hidden" name="remove_peer" value="<?= htmlspecialchars($peer['PublicKey']) ?>">
                <button type="submit" style="background:#dc3545;color:#fff;border:none;padding:5px 10px;border-radius:3px;">Rimuovi</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>3. Aggiungi nuovo Peer (PC Client)</h3>
<form method="post" style="margin-bottom:20px;">
    <input type="hidden" name="add_peer" value="1">
    <label>
        PublicKey del client:<br>
        <input type="text" name="pubkey" style="width:400px;" required pattern="[A-Za-z0-9+/=]{43,44}">
    </label><br>
    <label>
        AllowedIPs (es: 10.10.0.2/32):<br>
        <input type="text" name="allowedip" style="width:200px;" value="10.10.0.2/32" required>
    </label><br>
    <button type="submit" style="margin-top:8px;">➕ Aggiungi Peer</button>
</form>

<hr>
<h4>Comandi utili:</h4>
<pre>
sudo wg show
</pre>