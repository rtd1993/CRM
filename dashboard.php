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

.dashboard-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto auto;
    gap: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.calendar-section {
    grid-column: 1 / -1;
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
}

.data-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
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

.calendar-embed {
    width: 100%;
    height: 500px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
}

.scroll-content {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    background: #f8f9fa;
}

.scroll-content::-webkit-scrollbar {
    width: 8px;
}

.scroll-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.scroll-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.scroll-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e1e5e9;
    position: sticky;
    top: 0;
    z-index: 10;
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
    min-width: 70px;
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
    padding: 3rem;
    color: #6c757d;
    font-style: italic;
}

.empty-state .icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.task-description {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.client-name {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .dashboard-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .calendar-embed {
        height: 350px;
    }
    
    .scroll-content {
        max-height: 300px;
    }
    
    .data-table {
        font-size: 0.9rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.6rem 0.8rem;
    }
    
    .dashboard-header h2 {
        font-size: 2rem;
    }
}
</style>

<div class="dashboard-container">
    <!-- Calendario Google -->
    <div class="calendar-section">
        <h3 class="section-title">📅 Calendario Appuntamenti</h3>
        <iframe src="https://calendar.google.com/calendar/embed?src=gestione.ascontabilmente%40gmail.com&ctz=Europe%2FRome"
                class="calendar-embed" frameborder="0" scrolling="no"></iframe>
    </div>

    <!-- Task in Scadenza -->
    <div class="data-section">
        <h3 class="section-title">📋 Task in Scadenza (30 giorni)</h3>
        <div class="scroll-content">
            <?php
            $oggi = date('Y-m-d');
            $entro30 = date('Y-m-d', strtotime('+30 days'));
            $da30giorni = date('Y-m-d', strtotime('-30 days'));

            $stmt = $pdo->prepare("
                SELECT id, descrizione, scadenza, ricorrenza
                FROM task
                WHERE scadenza BETWEEN ? AND ?
                ORDER BY scadenza ASC
            ");
            $stmt->execute([$da30giorni, $entro30]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if (empty($tasks)): ?>
                <div class="empty-state">
                    <div class="icon">📋</div>
                    <p>Nessun task da gestire nei prossimi 30 giorni</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Descrizione Task</th>
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
                            $status_text = 'Scaduto';
                        } elseif ($diff < 7) {
                            $status_class = 'status-urgent';
                            $status_text = 'Urgente';
                        } else {
                            $status_class = 'status-normal';
                            $status_text = 'Normale';
                        }
                        
                        $is_recurring = !empty($t['ricorrenza']) && $t['ricorrenza'] > 0;
                    ?>
                        <tr>
                            <td>
                                <div class="task-description">
                                    <a href="task.php"><?= htmlspecialchars($t['descrizione']) ?></a>
                                </div>
                            </td>
                            <td><?= date('d/m/Y', strtotime($t['scadenza'])) ?></td>
                            <td>
                                <span class="status-badge <?= $status_class ?>">
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_recurring): ?>
                                    <span style="color: #0c5460;">🔄 Ricorrente</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Una tantum</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Task Clienti in Scadenza -->
    <div class="data-section">
        <h3 class="section-title">👥 Task Clienti Scaduti e in Scadenza (30 giorni)</h3>
        <div class="scroll-content">
            <?php
            // Prendiamo tutti i task: scaduti negli ultimi 30 giorni E in scadenza nei prossimi 30 giorni
            $stmt = $pdo->prepare("
                SELECT tc.id, tc.descrizione, tc.scadenza, tc.ricorrenza,
                       c.`Cognome_Ragione_sociale` as cliente_nome, c.id as cliente_id
                FROM task_clienti tc
                LEFT JOIN clienti c ON tc.cliente_id = c.id
                WHERE tc.scadenza BETWEEN ? AND ?
                ORDER BY tc.scadenza ASC
            ");
            $stmt->execute([$da30giorni, $entro30]);
            $task_clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php if (empty($task_clienti)): ?>
                <div class="empty-state">
                    <div class="icon">👥</div>
                    <p>Nessun task cliente scaduto o in scadenza nei 30 giorni</p>
                    <!-- Debug info -->
                    <small style="color: #999; font-size: 0.8rem;">
                        Periodo ricerca: <?= $da30giorni ?> - <?= $entro30 ?>
                    </small>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome Cliente</th>
                            <th>Nome Task</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($task_clienti as $tc): 
                        $diff = (strtotime($tc['scadenza']) - strtotime($oggi)) / 86400;
                        
                        if ($diff < 0) {
                            $status_class = 'status-overdue';
                            $status_text = 'Scaduto';
                        } elseif ($diff < 7) {
                            $status_class = 'status-urgent';
                            $status_text = 'Urgente';
                        } else {
                            $status_class = 'status-normal';
                            $status_text = 'Normale';
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="client-name">
                                    <a href="info_cliente.php?id=<?= urlencode($tc['cliente_id']) ?>">
                                        <?= htmlspecialchars($tc['cliente_nome'] ?? 'Cliente Sconosciuto') ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="task-description">
                                    <a href="task_clienti.php"><?= htmlspecialchars($tc['descrizione']) ?></a>
                                </div>
                            </td>
                            <td><?= date('d/m/Y', strtotime($tc['scadenza'])) ?></td>
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

    <!-- Documenti in Scadenza -->
    <div class="data-section">
        <h3 class="section-title">📄 Documenti Scaduti e in Scadenza (30 giorni)</h3>
        <div class="scroll-content">
            <?php
            $sql = "
                SELECT 
                    id,
                    `Cognome_Ragione_sociale` AS cognome,
                    `Numero_carta_d_identità` AS carta,
                    `Data_di_scadenza` AS carta_scad,
                    PEC,
                    `Scadenza_PEC` AS pec_scad,
                    `Numero patente` AS patente,
                    `Scadenza patente` AS patente_scad,
                    `Numero passaporto` AS passaporto,
                    `Scadenza passaporto` AS passaporto_scad,
                    `Certificato firma digitale` AS firma_digitale,
                    `Scadenza firma digitale` AS firma_digitale_scad
                FROM clienti
                WHERE 
                    (`Data_di_scadenza` IS NOT NULL AND `Data_di_scadenza` BETWEEN ? AND ?)
                    OR
                    (`Scadenza_PEC` IS NOT NULL AND `Scadenza_PEC` BETWEEN ? AND ?)
                    OR
                    (`Scadenza patente` IS NOT NULL AND `Scadenza patente` BETWEEN ? AND ?)
                    OR
                    (`Scadenza passaporto` IS NOT NULL AND `Scadenza passaporto` BETWEEN ? AND ?)
                    OR
                    (`Scadenza firma digitale` IS NOT NULL AND `Scadenza firma digitale` BETWEEN ? AND ?)
                ORDER BY 
                    LEAST(
                        IFNULL(`Data_di_scadenza`, '9999-12-31'), 
                        IFNULL(`Scadenza_PEC`, '9999-12-31'),
                        IFNULL(`Scadenza patente`, '9999-12-31'),
                        IFNULL(`Scadenza passaporto`, '9999-12-31'),
                        IFNULL(`Scadenza firma digitale`, '9999-12-31')
                    ) ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $da30giorni, $entro30, // Carta d'identità
                $da30giorni, $entro30, // PEC
                $da30giorni, $entro30, // Patente
                $da30giorni, $entro30, // Passaporto
                $da30giorni, $entro30  // Firma digitale
            ]);
            $clienti_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Costruiamo array documenti
            $documenti = [];
            foreach ($clienti_docs as $row) {
                // Carta d'identità
                if (!empty($row['carta']) && !empty($row['carta_scad'])) {
                    $documenti[] = [
                        'id' => $row['id'],
                        'cognome' => $row['cognome'],
                        'tipo' => "Carta d'Identità",
                        'numero' => $row['carta'],
                        'scadenza' => $row['carta_scad'],
                    ];
                }
                // PEC
                if (!empty($row['PEC']) && !empty($row['pec_scad'])) {
                    $documenti[] = [
                        'id' => $row['id'],
                        'cognome' => $row['cognome'],
                        'tipo' => "PEC",
                        'numero' => $row['PEC'],
                        'scadenza' => $row['pec_scad'],
                    ];
                }
                // Patente
                if (!empty($row['patente']) && !empty($row['patente_scad'])) {
                    $documenti[] = [
                        'id' => $row['id'],
                        'cognome' => $row['cognome'],
                        'tipo' => "Patente",
                        'numero' => $row['patente'],
                        'scadenza' => $row['patente_scad'],
                    ];
                }
                // Passaporto
                if (!empty($row['passaporto']) && !empty($row['passaporto_scad'])) {
                    $documenti[] = [
                        'id' => $row['id'],
                        'cognome' => $row['cognome'],
                        'tipo' => "Passaporto",
                        'numero' => $row['passaporto'],
                        'scadenza' => $row['passaporto_scad'],
                    ];
                }
                // Firma digitale
                if (!empty($row['firma_digitale']) && !empty($row['firma_digitale_scad'])) {
                    $documenti[] = [
                        'id' => $row['id'],
                        'cognome' => $row['cognome'],
                        'tipo' => "Firma Digitale",
                        'numero' => $row['firma_digitale'],
                        'scadenza' => $row['firma_digitale_scad'],
                    ];
                }
            }

            // Ordiniamo i documenti per scadenza
            usort($documenti, function($a, $b) {
                return strtotime($a['scadenza']) - strtotime($b['scadenza']);
            });
        
            ?>

            <?php if (empty($documenti)): ?>
                <div class="empty-state">
                    <div class="icon">📄</div>
                    <p>Nessun documento scaduto o in scadenza nei 30 giorni</p>
                    <!-- Debug info -->
                    <small style="color: #999; font-size: 0.8rem;">
                        Periodo ricerca: <?= $da30giorni ?> - <?= $entro30 ?>
                    </small>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome Cliente</th>
                            <th>Tipo Documento</th>
                            <th>Numero/Codice</th>
                            <th>Scadenza</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documenti as $doc): 
                        $diff = (strtotime($doc['scadenza']) - strtotime($oggi)) / 86400;
                        
                        if ($diff < 0) {
                            $status_class = 'status-overdue';
                            $status_text = 'Scaduto';
                            $giorni_info = abs(floor($diff)) . ' giorni fa';
                        } elseif ($diff < 7) {
                            $status_class = 'status-urgent';
                            $status_text = 'Urgente';
                            $giorni_info = floor($diff) . ' giorni';
                        } elseif ($diff < 30) {
                            $status_class = 'status-urgent';
                            $status_text = 'In Scadenza';
                            $giorni_info = floor($diff) . ' giorni';
                        } else {
                            $status_class = 'status-normal';
                            $status_text = 'Normale';
                            $giorni_info = floor($diff) . ' giorni';
                        }
                    ?>
                        <tr>
                            <td>
                                <div class="client-name">
                                    <a href="info_cliente.php?id=<?= urlencode($doc['id']) ?>">
                                        <?= htmlspecialchars($doc['cognome']) ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <strong style="color: #495057;"><?= htmlspecialchars($doc['tipo']) ?></strong>
                            </td>
                            <td style="font-family: monospace; font-size: 0.9rem;">
                                <?= htmlspecialchars(substr($doc['numero'], 0, 20)) ?>
                                <?php if (strlen($doc['numero']) > 20): ?>...<?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                                    <span><?= date('d/m/Y', strtotime($doc['scadenza'])) ?></span>
                                    <small style="color: #6c757d; font-size: 0.7rem;"><?= $giorni_info ?></small>
                                </div>
                            </td>
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
</div>

<script>
// Auto-refresh della dashboard ogni 15 minuti
setTimeout(() => {
    location.reload();
}, 900000);
</script>

</main>
</body>
</html>
