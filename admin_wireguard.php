<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Solo admin/dev
if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Non autorizzato.");
}

// Configurazione ZeroTier
$ZEROTIER_NETWORK_ID = '363C67C55A6F1A40';
$ZEROTIER_ADMIN_EMAIL = 'gestione.ascontabilmente@gmail.com';
$ZEROTIER_ADMIN_PASSWORD = 'AnnaSabina01!';
$ZEROTIER_CENTRAL_URL = 'https://my.zerotier.com/';

// Funzione per ottenere dispositivi ZeroTier (simulata - richiede API key)
function get_zerotier_devices($network_id) {
    // In un'implementazione reale, qui useresti l'API di ZeroTier
    // Per ora restituiamo dati di esempio
    return [
        [
            'id' => 'abcd1234efgh',
            'name' => 'Laptop Ufficio',
            'ip' => '192.168.192.10',
            'online' => true,
            'authorized' => true,
            'lastSeen' => time() - 300
        ],
        [
            'id' => 'ijkl5678mnop',
            'name' => 'PC Casa',
            'ip' => '192.168.192.15',
            'online' => false,
            'authorized' => true,
            'lastSeen' => time() - 7200
        ]
    ];
}

$devices = get_zerotier_devices($ZEROTIER_NETWORK_ID);

// Gestione POST
$msg = null;
?>

<style>
.zt-container { max-width: 1000px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif; }
.zt-section { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.zt-section h3 { margin-top: 0; color: #495057; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
.zt-alert { padding: 15px; margin: 15px 0; border-radius: 5px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.zt-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
.zt-table th, .zt-table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
.zt-table th { background: #007bff; color: white; }
.zt-btn { padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; margin: 5px; transition: all 0.3s; }
.zt-btn-primary { background: #007bff; color: white; }
.zt-btn-success { background: #28a745; color: white; }
.zt-btn-warning { background: #ffc107; color: #212529; }
.zt-btn-danger { background: #dc3545; color: white; }
.zt-btn:hover { opacity: 0.9; transform: translateY(-1px); }
.zt-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.zt-badge-success { background: #d4edda; color: #155724; }
.zt-badge-danger { background: #f8d7da; color: #721c24; }
.zt-badge-secondary { background: #f8f9fa; color: #6c757d; }
.zt-status { display: flex; gap: 20px; margin: 15px 0; flex-wrap: wrap; }
.zt-status div { text-align: center; flex: 1; min-width: 120px; }
.zt-status .number { font-size: 24px; font-weight: bold; color: #007bff; }
.network-info { background: #e3f2fd; border: 1px solid #2196f3; border-radius: 8px; padding: 20px; margin: 20px 0; }
.network-id { font-family: monospace; font-size: 18px; font-weight: bold; color: #1976d2; background: white; padding: 10px; border-radius: 4px; display: inline-block; }
.credentials-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 15px 0; }
.download-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
.download-card { background: white; border: 2px solid #007bff; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; }
.download-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,123,255,0.15); }
.download-icon { font-size: 48px; margin-bottom: 15px; }
.step-number { background: #007bff; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
.instruction-step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #007bff; border-radius: 0 8px 8px 0; }
</style>

<div class="zt-container">
    <h2>🌐 ZeroTier VPN - Gestione Rete Privata</h2>
    
    <?php if ($msg): ?>
        <div class="zt-alert"><?= $msg ?></div>
    <?php endif; ?>
    
    <!-- Informazioni Rete -->
    <div class="zt-section">
        <h3>🔧 Informazioni Rete</h3>
        <div class="network-info">
            <h4>📡 Network ID della tua rete ZeroTier:</h4>
            <div class="network-id"><?= $ZEROTIER_NETWORK_ID ?></div>
            <p style="margin-top: 15px;"><strong>Importante:</strong> Dovrai inserire questo ID nel software ZeroTier per connetterti alla rete aziendale.</p>
        </div>
        
        <div class="credentials-box">
            <h5>🔐 Credenziali di Amministrazione:</h5>
            <p><strong>Email:</strong> <?= $ZEROTIER_ADMIN_EMAIL ?></p>
            <p><strong>Password:</strong> <?= $ZEROTIER_ADMIN_PASSWORD ?></p>
            <p><strong>URL:</strong> <a href="<?= $ZEROTIER_CENTRAL_URL ?>" target="_blank"><?= $ZEROTIER_CENTRAL_URL ?></a></p>
        </div>
    </div>
    
    <!-- Stato -->
    <div class="zt-section">
        <h3>📊 Stato Rete VPN</h3>
        <div class="zt-status">
            <div>
                <div class="number"><?= count($devices) ?></div>
                <div>Dispositivi Totali</div>
            </div>
            <div>
                <div class="number"><?= count(array_filter($devices, function($d) { return $d['online']; })) ?></div>
                <div>Dispositivi Online</div>
            </div>
            <div>
                <div class="number"><?= count(array_filter($devices, function($d) { return $d['authorized']; })) ?></div>
                <div>Dispositivi Autorizzati</div>
            </div>
        </div>
    </div>
    
    <!-- Configurazione Guidata -->
    <div class="zt-section">
        <h3>🚀 Configurazione Nuovo Dispositivo</h3>
        
        <!-- Step 1: Download Software -->
        <div class="instruction-step">
            <h4><span class="step-number">1</span>Scarica ZeroTier per il tuo dispositivo</h4>
            <div class="download-grid">
                <div class="download-card">
                    <div class="download-icon">🪟</div>
                    <h5>Windows</h5>
                    <a href="https://download.zerotier.com/dist/ZeroTier%20One.msi" class="zt-btn zt-btn-primary" target="_blank">
                        📥 Download Windows
                    </a>
                    <small style="display: block; margin-top: 10px; color: #6c757d;">
                        Per Windows 7/8/10/11
                    </small>
                </div>
                
                <div class="download-card">
                    <div class="download-icon">🍎</div>
                    <h5>macOS</h5>
                    <a href="https://download.zerotier.com/dist/ZeroTier%20One.pkg" class="zt-btn zt-btn-primary" target="_blank">
                        📥 Download macOS
                    </a>
                    <small style="display: block; margin-top: 10px; color: #6c757d;">
                        Per macOS 10.13+
                    </small>
                </div>
                
                <div class="download-card">
                    <div class="download-icon">🤖</div>
                    <h5>Android</h5>
                    <a href="https://play.google.com/store/apps/details?id=com.zerotier.one" class="zt-btn zt-btn-primary" target="_blank">
                        📥 Google Play
                    </a>
                    <small style="display: block; margin-top: 10px; color: #6c757d;">
                        Dal Google Play Store
                    </small>
                </div>
                
                <div class="download-card">
                    <div class="download-icon">📱</div>
                    <h5>iOS</h5>
                    <a href="https://apps.apple.com/app/zerotier-one/id1084101492" class="zt-btn zt-btn-primary" target="_blank">
                        📥 App Store
                    </a>
                    <small style="display: block; margin-top: 10px; color: #6c757d;">
                        Dall'App Store iOS
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Step 2: Installazione -->
        <div class="instruction-step">
            <h4><span class="step-number">2</span>Installa e avvia ZeroTier</h4>
            <ul>
                <li><strong>Windows/Mac:</strong> Esegui il file scaricato e segui la procedura di installazione</li>
                <li><strong>Android/iOS:</strong> Installa l'app dal store</li>
                <li>Avvia ZeroTier (su desktop potrebbe apparire nella system tray)</li>
            </ul>
        </div>
        
        <!-- Step 3: Unisciti alla rete -->
        <div class="instruction-step">
            <h4><span class="step-number">3</span>Unisciti alla rete aziendale</h4>
            <ol>
                <li>Apri ZeroTier (su desktop: click destro sull'icona nella system tray)</li>
                <li>Clicca su "Join Network" o "Unisciti alla rete"</li>
                <li><strong>Inserisci questo Network ID:</strong> 
                    <div class="network-id" style="margin: 10px 0;"><?= $ZEROTIER_NETWORK_ID ?></div>
                </li>
                <li>Clicca "Join" o "Unisciti"</li>
            </ol>
        </div>
        
        <!-- Step 4: Autorizzazione -->
        <div class="instruction-step">
            <h4><span class="step-number">4</span>Autorizza il dispositivo (solo per amministratori)</h4>
            <p>Il dispositivo apparirà come "Pending" finché non viene autorizzato. Per autorizzarlo:</p>
            <ol>
                <li>Vai su <a href="<?= $ZEROTIER_CENTRAL_URL ?>" target="_blank" class="zt-btn zt-btn-warning"><?= $ZEROTIER_CENTRAL_URL ?></a></li>
                <li><strong>Accedi con:</strong>
                    <ul style="margin: 10px 0;">
                        <li>Email: <code><?= $ZEROTIER_ADMIN_EMAIL ?></code></li>
                        <li>Password: <code><?= $ZEROTIER_ADMIN_PASSWORD ?></code></li>
                        <li>Oppure: <a href="<?= $ZEROTIER_CENTRAL_URL ?>" target="_blank" class="zt-btn zt-btn-primary">🔑 Accedi con Google</a></li>
                    </ul>
                </li>
                <li>Clicca sulla rete <code><?= $ZEROTIER_NETWORK_ID ?></code></li>
                <li>Trova il nuovo dispositivo nella lista "Members"</li>
                <li>Spunta la casella "Authorized" accanto al dispositivo</li>
                <li>Opzionale: assegna un nome descrittivo al dispositivo</li>
            </ol>
        </div>
        
        <!-- Step 5: Test -->
        <div class="instruction-step">
            <h4><span class="step-number">5</span>Testa la connessione</h4>
            <p>Una volta autorizzato, il dispositivo dovrebbe connettersi automaticamente. Per testare:</p>
            <ul>
                <li>Il dispositivo dovrebbe mostrare stato "Connected" in ZeroTier</li>
                <li>Dovresti riuscire ad accedere al CRM dalla rete aziendale</li>
                <li>Il dispositivo apparirà nella lista sottostante come "Online"</li>
            </ul>
        </div>
    </div>
    
    <!-- Lista Dispositivi ZeroTier -->
    <div class="zt-section">
        <h3>💻 Dispositivi Connessi alla Rete</h3>
        
        <?php if (empty($devices)): ?>
            <p style="text-align: center; color: #6c757d; padding: 40px;">
                Nessun dispositivo connesso alla rete ZeroTier. Segui le istruzioni sopra per aggiungere dispositivi.
            </p>
        <?php else: ?>
            <table class="zt-table">
                <thead>
                    <tr>
                        <th>Nome Dispositivo</th>
                        <th>ID Dispositivo</th>
                        <th>IP Assegnato</th>
                        <th>Stato</th>
                        <th>Ultima Connessione</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($device['name']) ?></strong></td>
                        <td style="font-family: monospace; font-size: 12px;">
                            <?= htmlspecialchars($device['id']) ?>
                        </td>
                        <td><strong><?= htmlspecialchars($device['ip']) ?></strong></td>
                        <td>
                            <?php if ($device['online'] && $device['authorized']): ?>
                                <span class="zt-badge zt-badge-success">🟢 Online</span>
                            <?php elseif ($device['authorized']): ?>
                                <span class="zt-badge zt-badge-secondary">⚫ Offline</span>
                            <?php else: ?>
                                <span class="zt-badge zt-badge-danger">⚠️ Non Autorizzato</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px;">
                            <?= date('d/m/Y H:i', $device['lastSeen']) ?>
                        </td>
                        <td>
                            <a href="<?= $ZEROTIER_CENTRAL_URL ?>" target="_blank" class="zt-btn zt-btn-warning">
                                ⚙️ Gestisci
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="<?= $ZEROTIER_CENTRAL_URL ?>" target="_blank" class="zt-btn zt-btn-primary">
                🌐 Apri ZeroTier Central
            </a>
            <p style="margin-top: 10px; color: #6c757d; font-size: 14px;">
                Per gestire tutti i dispositivi, autorizzazioni e impostazioni avanzate
            </p>
        </div>
    </div>
    
    <!-- Informazioni Aggiuntive -->
    <div class="zt-section">
        <h3>📖 Informazioni Aggiuntive</h3>
        
        <div class="instruction-step">
            <h4>🔧 Risoluzione Problemi</h4>
            <ul>
                <li><strong>Dispositivo non si connette:</strong> Verifica che sia autorizzato su ZeroTier Central</li>
                <li><strong>Non riesci ad accedere al CRM:</strong> Controlla che il firewall locale non blocchi ZeroTier</li>
                <li><strong>IP non assegnato:</strong> Il dispositivo potrebbe non essere completamente connesso alla rete</li>
            </ul>
        </div>
        
        <div class="instruction-step">
            <h4>⚡ Vantaggi di ZeroTier</h4>
            <ul>
                <li><strong>Configurazione semplice:</strong> Nessuna configurazione router necessaria</li>
                <li><strong>Cross-platform:</strong> Funziona su tutti i dispositivi</li>
                <li><strong>Sicurezza:</strong> Traffico crittografato end-to-end</li>
                <li><strong>Gestione centralizzata:</strong> Controllo completo da ZeroTier Central</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Funzione per copiare Network ID negli appunti
function copyNetworkId() {
    const networkId = '<?= $ZEROTIER_NETWORK_ID ?>';
    navigator.clipboard.writeText(networkId).then(function() {
        alert('Network ID copiato negli appunti!');
    }, function(err) {
        // Fallback per browser più vecchi
        const textArea = document.createElement('textarea');
        textArea.value = networkId;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('Network ID copiato negli appunti!');
    });
}

// Aggiungi evento click per copiare Network ID
document.addEventListener('DOMContentLoaded', function() {
    const networkIdElements = document.querySelectorAll('.network-id');
    networkIdElements.forEach(element => {
        element.style.cursor = 'pointer';
        element.title = 'Clicca per copiare';
        element.addEventListener('click', copyNetworkId);
    });
});
</script>