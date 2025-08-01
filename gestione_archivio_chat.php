<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';

// Solo admin e developer possono accedere
if (!in_array($_SESSION['ruolo'], ['admin', 'developer'])) {
    die('<div class="alert alert-danger">Accesso negato: solo admin e developer possono gestire l\'archivio chat</div>');
}

// Se è stata richiesta l'esecuzione manuale
if (isset($_POST['esegui_archivio'])) {
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-info">⏳ Esecuzione archiviazione in corso...</div>';
    echo '<div class="card"><div class="card-body">';
    echo '<h5 class="card-title">Output dell\'archiviazione:</h5>';
    echo '<pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">';
    
    // Esegue lo script e mostra l'output
    $output = shell_exec('/var/www/CRM/archivio_chat_globale.sh 2>&1');
    echo htmlspecialchars($output);
    
    echo '</pre>';
    echo '</div></div>';
    echo '<div class="mt-3"><a href="gestione_archivio_chat.php" class="btn btn-primary">Torna alla gestione</a></div>';
    echo '</div>';
    exit;
}
?>

<style>
.archive-header {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.stats-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border-left: 4px solid #6f42c1;
}

.file-list {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
</style>

<div class="container mt-4">
    <div class="archive-header">
        <h2>🗄️ Gestione Archivio Chat Globale</h2>
        <p>Archiviazione automatica dei messaggi più vecchi di 60 giorni</p>
    </div>

    <?php
    // Statistiche database
    require_once __DIR__ . '/includes/db.php';
    
    // Conta messaggi totali
    $stmt = $pdo->query("SELECT COUNT(*) FROM chat_messaggi");
    $messaggi_totali = $stmt->fetchColumn();
    
    // Conta messaggi vecchi (più di 60 giorni)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messaggi WHERE timestamp < ?");
    $data_limite = date('Y-m-d H:i:s', strtotime('-60 days'));
    $stmt->execute([$data_limite]);
    $messaggi_vecchi = $stmt->fetchColumn();
    
    // Ultimo messaggio
    $stmt = $pdo->query("SELECT timestamp FROM chat_messaggi ORDER BY timestamp DESC LIMIT 1");
    $ultimo_messaggio = $stmt->fetchColumn();
    
    // File di archivio esistenti
    $archivio_dir = '/var/www/CRM/local_drive/ASContabilmente/Archivio_chat';
    $files_archivio = [];
    if (is_dir($archivio_dir)) {
        $files = scandir($archivio_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'txt') {
                $file_path = $archivio_dir . '/' . $file;
                $files_archivio[] = [
                    'nome' => $file,
                    'dimensione' => filesize($file_path),
                    'modificato' => filemtime($file_path)
                ];
            }
        }
    }
    ?>

    <div class="row">
        <div class="col-md-3">
            <div class="stats-card">
                <h5>📊 Messaggi Totali</h5>
                <h3 class="text-primary"><?= number_format($messaggi_totali) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h5>🗑️ Da Archiviare</h5>
                <h3 class="text-warning"><?= number_format($messaggi_vecchi) ?></h3>
                <small class="text-muted">Più vecchi di 60 giorni</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h5>💾 Attivi</h5>
                <h3 class="text-success"><?= number_format($messaggi_totali - $messaggi_vecchi) ?></h3>
                <small class="text-muted">Ultimi 60 giorni</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h5>⏰ Ultimo Messaggio</h5>
                <p class="mb-0"><?= $ultimo_messaggio ? date('d/m/Y H:i', strtotime($ultimo_messaggio)) : 'Nessuno' ?></p>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="file-list">
                <h5>🗂️ Controllo Manuale</h5>
                <p class="text-muted">Esegui l'archiviazione manualmente (normalmente viene eseguita automaticamente ogni 1° del mese alle 02:30)</p>
                
                <?php if ($messaggi_vecchi > 0): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Attenzione:</strong> Ci sono <?= number_format($messaggi_vecchi) ?> messaggi pronti per l'archiviazione.
                    </div>
                    <form method="post" onsubmit="return confirm('Sei sicuro di voler procedere con l\'archiviazione? I messaggi più vecchi di 60 giorni verranno spostati nell\'archivio e rimossi dal database.')">
                        <button type="submit" name="esegui_archivio" class="btn btn-warning btn-lg">
                            🗄️ Esegui Archiviazione Ora
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>✅ Tutto pulito:</strong> Non ci sono messaggi da archiviare al momento.
                    </div>
                    <button class="btn btn-secondary btn-lg" disabled>
                        🗄️ Nessuna Archiviazione Necessaria
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="file-list">
                <h5>📁 File di Archivio (<?= count($files_archivio) ?>)</h5>
                
                <?php if (empty($files_archivio)): ?>
                    <p class="text-muted">Nessun file di archivio trovato.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Dimensione</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files_archivio as $file): ?>
                                <tr>
                                    <td>
                                        <a href="download.php?file=<?= urlencode('local_drive/ASContabilmente/Archivio_chat/' . $file['nome']) ?>" class="text-decoration-none">
                                            📄 <?= htmlspecialchars($file['nome']) ?>
                                        </a>
                                    </td>
                                    <td><?= number_format($file['dimensione'] / 1024, 1) ?> KB</td>
                                    <td><?= date('d/m/Y H:i', $file['modificato']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="file-list">
                <h5>ℹ️ Informazioni</h5>
                <ul class="list-unstyled">
                    <li><strong>🕐 Frequenza:</strong> Automatica ogni 1° del mese alle 02:30</li>
                    <li><strong>📅 Criteri:</strong> Messaggi più vecchi di 60 giorni</li>
                    <li><strong>📁 Destinazione:</strong> <code>/local_drive/ASContabilmente/Archivio_chat/</code></li>
                    <li><strong>📝 Formato:</strong> <code>chat_MM_YYYY.txt</code> (es: chat_06_2025.txt)</li>
                    <li><strong>🔄 Tabelle:</strong> Solo <code>chat_messaggi</code> (chat globale), non le chat delle pratiche</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Aggiorna la pagina ogni 5 minuti per statistiche aggiornate
setTimeout(function() {
    location.reload();
}, 300000);
</script>
