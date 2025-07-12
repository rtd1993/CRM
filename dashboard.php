<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';
?>

<style>
.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.dashboard-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.dashboard-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-top: 2rem;
}

.dashboard-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    position: relative;
    overflow: hidden;
}

.dashboard-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.section-title {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: white;
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
    cursor: pointer;
    font-size: 1rem;
}

.action-btn:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.action-btn .icon {
    font-size: 1.5rem;
    width: 2rem;
    text-align: center;
}

.calendar-embed {
    width: 100%;
    height: 300px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.data-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e1e5e9;
}

.data-table td {
    padding: 0.8rem 1rem;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.data-table a {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.data-table a:hover {
    text-decoration: underline;
}

.status-badge {
    padding: 0.25rem 0.8rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    text-align: center;
    display: inline-block;
    min-width: 80px;
}

.status-overdue {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.status-urgent {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.status-normal {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
    font-style: italic;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e1e5e9;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-content {
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .dashboard-header h2 {
        font-size: 2rem;
    }
    
    .dashboard-section {
        padding: 1rem;
    }
    
    .action-btn {
        padding: 0.8rem 1rem;
        gap: 0.8rem;
    }
    
    .calendar-embed {
        height: 250px;
    }
    
    .data-table {
        font-size: 0.9rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.6rem 0.8rem;
    }
}
</style>

<div class="dashboard-header">
    <h2>🏠 Dashboard CRM</h2>
    <p>Panoramica generale e accesso rapido alle funzionalità</p>
</div>

<div class="dashboard-grid">
    <!-- Colonna sinistra: Azioni rapide -->
    <div class="dashboard-section">
        <h3 class="section-title">🚀 Azioni Rapide</h3>
        <div class="quick-actions">
            <a href="drive.php" class="action-btn">
                <span class="icon">📁</span>
                <span>Accedi al Drive</span>
            </a>
            <a href="clienti.php" class="action-btn">
                <span class="icon">📋</span>
                <span>Database Clienti</span>
            </a>
            <a href="task.php" class="action-btn">
                <span class="icon">✅</span>
                <span>Task Mensili</span>
            </a>
            <a href="calendario.php" class="action-btn">
                <span class="icon">📅</span>
                <span>Calendario Eventi</span>
            </a>
            <a href="chat.php" class="action-btn">
                <span class="icon">💬</span>
                <span>Chat & Appunti</span>
            </a>
            <a href="info.php" class="action-btn">
                <span class="icon">ℹ️</span>
                <span>Informazioni Utili</span>
            </a>
        </div>
    </div>

    <!-- Colonna destra: Calendario e dati -->
    <div class="dashboard-section">
        <h3 class="section-title">📅 Calendario Google</h3>
        <iframe src="https://calendar.google.com/calendar/embed?src=gestione.ascontabilmente%40gmail.com&ctz=Europe%2FRome"
                class="calendar-embed" frameborder="0" scrolling="no"></iframe>

        <!-- Task in Scadenza e Scaduti -->
        <h3 class="section-title">📋 Task da Gestire</h3>
        <?php
        $oggi = date('Y-m-d');
        $entro30 = date('Y-m-d', strtotime('+30 days'));
        $da30giorni = date('Y-m-d', strtotime('-30 days'));

        // Prendiamo task scaduti (ultimi 30 giorni) e in scadenza (prossimi 30 giorni)
        $stmt = $pdo->prepare("
            SELECT id, descrizione, scadenza, ricorrenza
            FROM task
            WHERE scadenza BETWEEN ? AND ?
            ORDER BY scadenza ASC
        ");
        $stmt->execute([$da30giorni, $entro30]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcola statistiche
        $task_scaduti = 0;
        $task_urgenti = 0;
        $task_normali = 0;
        $task_ricorrenti = 0;

        foreach ($tasks as $t) {
            $diff = (strtotime($t['scadenza']) - strtotime($oggi)) / 86400;
            if ($diff < 0) $task_scaduti++;
            elseif ($diff < 5) $task_urgenti++;
            else $task_normali++;
            
            if (!empty($t['ricorrenza']) && $t['ricorrenza'] > 0) $task_ricorrenti++;
        }
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?= $task_scaduti ?></div>
                <div class="stat-label">Scaduti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #fd7e14;"><?= $task_urgenti ?></div>
                <div class="stat-label">Urgenti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?= $task_normali ?></div>
                <div class="stat-label">Normali</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #17a2b8;"><?= $task_ricorrenti ?></div>
                <div class="stat-label">Ricorrenti</div>
            </div>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <p>📋 Nessun task da gestire nei prossimi 30 giorni.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Descrizione</th>
                        <th>Scadenza</th>
                        <th>Stato</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tasks as $t): 
                    $scadenza = $t['scadenza'];
                    $diff = (strtotime($scadenza) - strtotime($oggi)) / 86400;
                    
                    if ($diff < 0) {
                        $status_class = 'status-overdue';
                        $status_text = 'Scaduto ' . abs(floor($diff)) . ' giorni fa';
                    } elseif ($diff < 5) {
                        $status_class = 'status-urgent';
                        $status_text = 'Scade tra ' . floor($diff) . ' giorni';
                    } else {
                        $status_class = 'status-normal';
                        $status_text = 'Scade tra ' . floor($diff) . ' giorni';
                    }
                    
                    $is_recurring = !empty($t['ricorrenza']) && $t['ricorrenza'] > 0;
                ?>
                    <tr>
                        <td>
                            <a href="task.php">
                                <?= htmlspecialchars($t['descrizione']) ?>
                            </a>
                        </td>
                        <td><?= date('d/m/Y', strtotime($t['scadenza'])) ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($is_recurring): ?>
                                <span class="status-badge" style="background: #cce7ff; color: #0c5460;">
                                    🔄 Ricorrente
                                </span>
                            <?php else: ?>
                                <span style="color: #6c757d;">Una tantum</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Documenti da aggiornare -->
        <h3 class="section-title">📄 Documenti da Aggiornare</h3>
        <?php
        // Cerchiamo documenti scaduti (ultimi 30 giorni) e in scadenza (prossimi 30 giorni)
        $da30giorni = date('Y-m-d', strtotime('-30 days'));
        $entro30 = date('Y-m-d', strtotime('+30 days'));

        $sql = "
            SELECT 
                id,
                `Cognome/Ragione sociale` AS cognome,
                `Numero carta d'identità` AS carta,
                `Data di scadenza` AS carta_scad,
                PEC,
                `Scadenza PEC` AS pec_scad
            FROM clienti
            WHERE 
                (`Data di scadenza` IS NOT NULL AND `Data di scadenza` BETWEEN ? AND ?)
                OR
                (`Scadenza PEC` IS NOT NULL AND `Scadenza PEC` BETWEEN ? AND ?)
            ORDER BY 
                LEAST(
                    IFNULL(`Data di scadenza`, '9999-12-31'), 
                    IFNULL(`Scadenza PEC`, '9999-12-31')
                ) ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$da30giorni, $entro30, $da30giorni, $entro30]);
        $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Costruiamo una tabella documenti da aggiornare
        $documenti = [];
        foreach ($clienti as $row) {
            // Carta d'identità
            if (!empty($row['carta']) && !empty($row['carta_scad'])) {
                $documenti[] = [
                    'id' => $row['id'],
                    'cognome' => $row['cognome'],
                    'tipo' => "Carta d'identità",
                    'dettaglio' => $row['carta'],
                    'scadenza' => $row['carta_scad'],
                ];
            }
            // PEC
            if (!empty($row['PEC']) && !empty($row['pec_scad'])) {
                $documenti[] = [
                    'id' => $row['id'],
                    'cognome' => $row['cognome'],
                    'tipo' => "PEC",
                    'dettaglio' => $row['PEC'],
                    'scadenza' => $row['pec_scad'],
                ];
            }
        }

        // Calcola statistiche documenti
        $doc_scaduti = 0;
        $doc_urgenti = 0;
        $doc_normali = 0;

        foreach ($documenti as $doc) {
            $diff = (strtotime($doc['scadenza']) - strtotime($oggi)) / 86400;
            if ($diff < 0) $doc_scaduti++;
            elseif ($diff < 30) $doc_urgenti++;
            else $doc_normali++;
        }
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?= $doc_scaduti ?></div>
                <div class="stat-label">Scaduti</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #fd7e14;"><?= $doc_urgenti ?></div>
                <div class="stat-label">In Scadenza</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($documenti) ?></div>
                <div class="stat-label">Totali</div>
            </div>
        </div>

        <?php if (empty($documenti)): ?>
            <div class="empty-state">
                <p>📄 Nessun documento da aggiornare nei prossimi 30 giorni.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo Documento</th>
                        <th>Dettaglio</th>
                        <th>Scadenza</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($documenti as $doc): 
                    $diff = (strtotime($doc['scadenza']) - strtotime($oggi)) / 86400;
                    
                    if ($diff < 0) {
                        $status_class = 'status-overdue';
                        $status_text = 'Scaduto ' . abs(floor($diff)) . ' giorni fa';
                    } elseif ($diff < 30) {
                        $status_class = 'status-urgent';
                        $status_text = 'Scade tra ' . floor($diff) . ' giorni';
                    } else {
                        $status_class = 'status-normal';
                        $status_text = 'Scade tra ' . floor($diff) . ' giorni';
                    }
                ?>
                    <tr>
                        <td>
                            <a href="clienti_scheda.php?id=<?= urlencode($doc['id']) ?>">
                                <?= htmlspecialchars($doc['cognome']) ?>
                            </a>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($doc['tipo']) ?></strong>
                        </td>
                        <td>
                            <small style="color: #6c757d;"><?= htmlspecialchars($doc['dettaglio']) ?></small>
                        </td>
                        <td><?= date('d/m/Y', strtotime($doc['scadenza'])) ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh della dashboard ogni 10 minuti
setTimeout(() => {
    location.reload();
}, 600000);

// Mostra avviso se ci sono elementi scaduti
<?php if (($task_scaduti + $doc_scaduti) > 0): ?>
document.addEventListener('DOMContentLoaded', function() {
    const totalScaduti = <?= $task_scaduti + $doc_scaduti ?>;
    if (totalScaduti > 0) {
        console.log(`⚠️ Attenzione: ${totalScaduti} elementi scaduti richiedono attenzione`);
    }
});
<?php endif; ?>
</script>

</main>
</body>
</html>