<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// Funzione per recuperare eventi Google Calendar
function getCalendarEvents($timeMin, $timeMax) {
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        
        $calendarId = 'gestione.ascontabilmente@gmail.com';
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google-calendar.json');
        
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $service = new Google_Service_Calendar($client);
        
        $params = [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ];
        
        $events = $service->events->listEvents($calendarId, $params);
        $output = [];
        
        global $pdo;
        foreach ($events->getItems() as $event) {
            $eventId = $event->getId();
            
            // Ottieni i metadati dell'evento dal database locale
            $stmt = $pdo->prepare("SELECT event_color, assigned_to_user_id, created_by_user_id FROM calendar_events_meta WHERE google_event_id = ?");
            $stmt->execute([$eventId]);
            $meta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $eventData = [
                'id' => $eventId,
                'title' => $event->getSummary(),
                'start' => $event->start->dateTime ?: $event->start->date,
                'end' => $event->end->dateTime ?: $event->end->date,
                'color' => $meta['event_color'] ?? '#007BFF'
            ];
            
            $output[] = $eventData;
        }
        
        return $output;
    } catch (Exception $e) {
        error_log("Errore recupero eventi calendario: " . $e->getMessage());
        return [];
    }
}

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

.appointment-item {
    transition: all 0.3s ease;
}

.appointment-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
}

.appointments-today::-webkit-scrollbar,
.appointments-week::-webkit-scrollbar {
    width: 6px;
}

.appointments-today::-webkit-scrollbar-track,
.appointments-week::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.appointments-today::-webkit-scrollbar-thumb,
.appointments-week::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

.appointments-today::-webkit-scrollbar-thumb:hover,
.appointments-week::-webkit-scrollbar-thumb:hover {
    background: #5a6fd8;
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
    
    .appointments-today,
    .appointments-week {
        max-height: 250px !important;
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
    <!-- Appuntamenti Oggi e Settimana -->
    <div class="calendar-section">
        <h3 class="section-title">📅 Appuntamenti</h3>
        
        <div class="row">
            <!-- Appuntamenti di Oggi -->
            <div class="col-md-6">
                <h4 class="mb-3" style="color: var(--primary-color); font-weight: 600;">
                    <i class="fas fa-calendar-day me-2"></i>Oggi (<?= date('d/m/Y') ?>)
                </h4>
                <div class="appointments-today" style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $oggi = date('Y-m-d');
                    $oggiStart = $oggi . 'T00:00:00+02:00';
                    $oggiEnd = $oggi . 'T23:59:59+02:00';
                    $eventsToday = getCalendarEvents($oggiStart, $oggiEnd);
                    
                    if (empty($eventsToday)):
                    ?>
                        <div class="empty-state text-center py-3">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; color: #ccc; margin-bottom: 0.5rem;"></i>
                            <p style="color: #999; margin: 0;">Nessun appuntamento oggi</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($eventsToday as $event): 
                            $startTime = new DateTime($event['start']);
                            $endTime = new DateTime($event['end']);
                        ?>
                            <div class="appointment-item mb-3 p-3" style="background: rgba(255,255,255,0.9); border-left: 4px solid <?= $event['color'] ?>; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1" style="color: #333; font-weight: 600;"><?= htmlspecialchars($event['title']) ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= $startTime->format('H:i') ?> - <?= $endTime->format('H:i') ?>
                                        </small>
                                    </div>
                                    <div class="appointment-color" style="width: 12px; height: 12px; background: <?= $event['color'] ?>; border-radius: 50%; margin-top: 4px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Appuntamenti della Settimana -->
            <div class="col-md-6">
                <h4 class="mb-3" style="color: var(--primary-color); font-weight: 600;">
                    <i class="fas fa-calendar-week me-2"></i>Questa Settimana
                </h4>
                <div class="appointments-week" style="max-height: 300px; overflow-y: auto;">
                    <?php
                    $inizioSettimana = date('Y-m-d', strtotime('monday this week')) . 'T00:00:00+02:00';
                    $fineSettimana = date('Y-m-d', strtotime('sunday this week')) . 'T23:59:59+02:00';
                    $eventsWeek = getCalendarEvents($inizioSettimana, $fineSettimana);
                    
                    if (empty($eventsWeek)):
                    ?>
                        <div class="empty-state text-center py-3">
                            <i class="fas fa-calendar-times" style="font-size: 2rem; color: #ccc; margin-bottom: 0.5rem;"></i>
                            <p style="color: #999; margin: 0;">Nessun appuntamento questa settimana</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($eventsWeek as $event): 
                            $startTime = new DateTime($event['start']);
                            $endTime = new DateTime($event['end']);
                            $isToday = $startTime->format('Y-m-d') === $oggi;
                        ?>
                            <div class="appointment-item mb-2 p-2 <?= $isToday ? 'opacity-50' : '' ?>" style="background: rgba(255,255,255,0.9); border-left: 3px solid <?= $event['color'] ?>; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1);">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1" style="color: #333; font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($event['title']) ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?= $startTime->format('d/m') ?> alle <?= $startTime->format('H:i') ?>
                                            <?= $isToday ? '(oggi)' : '' ?>
                                        </small>
                                    </div>
                                    <div class="appointment-color" style="width: 10px; height: 10px; background: <?= $event['color'] ?>; border-radius: 50%; margin-top: 3px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="/calendario.php" class="btn btn-primary" style="background: var(--primary-gradient); border: none; border-radius: 10px; padding: 0.6rem 1.5rem; font-weight: 600;">
                <i class="fas fa-calendar me-2"></i>Apri Calendario Completo
            </a>
        </div>
    </div>

    <!-- Sezione Unificata Scadenze -->
    <div class="data-section scadenze-section">
        <h3 class="section-title">⏰ Scadenze (30 giorni)</h3>
        <div class="scadenze-container">
            
            <!-- Sottosezione Task -->
            <div class="scadenze-subsection">
                <h4 class="subsection-title">
                    <span class="subsection-icon">📋</span>
                    Task Generali
                </h4>
                <div class="scroll-content">
                    <?php
                    $oggi = date('Y-m-d');
                    $entro30 = date('Y-m-d', strtotime('+30 days'));
                    $da30giorni = date('Y-m-d', strtotime('-30 days'));

                    // Determina i permessi dell'utente per i task
                    $user_role = $_SESSION['user_role'] ?? 'employee';
                    $user_id = $_SESSION['user_id'] ?? 0;
                    $can_see_all = in_array($user_role, ['admin', 'developer']);

                    // Query per task con filtro permessi
                    $sql_task = "
                        SELECT t.id, t.descrizione, t.scadenza, t.ricorrenza, u.nome as nome_assegnato
                        FROM task t
                        LEFT JOIN utenti u ON t.assegnato_a = u.id
                        WHERE t.scadenza BETWEEN ? AND ?";
                    
                    $params_task = [$da30giorni, $entro30];
                    
                    // Filtro per ruolo utente
                    if (!$can_see_all) {
                        $sql_task .= " AND (t.assegnato_a IS NULL OR t.assegnato_a = ?)";
                        $params_task[] = $user_id;
                    }
                    
                    $sql_task .= " ORDER BY t.scadenza ASC";
                    
                    $stmt = $pdo->prepare($sql_task);
                    $stmt->execute($params_task);
                    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (empty($tasks)): ?>
                        <div class="empty-state-mini">
                            <div class="icon-mini">📋</div>
                            <p>Nessun task da gestire</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table compact">
                            <thead>
                                <tr>
                                    <th>Descrizione</th>
                                    <th>Scadenza</th>
                                    <th>Assegnato a</th>
                                    <th>Stato</th>
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
                                            <?php if ($is_recurring): ?>
                                                <span class="badge-recurring">🔄</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($t['scadenza'])) ?></td>
                                    <td>
                                        <?php if ($t['nome_assegnato']): ?>
                                            <span class="assigned-user"><?= htmlspecialchars($t['nome_assegnato']) ?></span>
                                        <?php else: ?>
                                            <span class="general-task">Generale</span>
                                        <?php endif; ?>
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

            <!-- Sottosezione Task Clienti -->
            <div class="scadenze-subsection">
                <h4 class="subsection-title">
                    <span class="subsection-icon">👥</span>
                    Task Clienti
                </h4>
                <div class="scroll-content">
                    <?php
                    // Query per task clienti con filtro permessi
                    $sql_task_clienti = "
                        SELECT tc.id, tc.descrizione, tc.scadenza, tc.ricorrenza,
                               c.`Cognome_Ragione_sociale` as cliente_nome, c.id as cliente_id,
                               u.nome as nome_assegnato
                        FROM task_clienti tc
                        LEFT JOIN clienti c ON tc.cliente_id = c.id
                        LEFT JOIN utenti u ON tc.assegnato_a = u.id
                        WHERE tc.scadenza BETWEEN ? AND ?";
                    
                    $params_tc = [$da30giorni, $entro30];
                    
                    // Filtro per ruolo utente
                    if (!$can_see_all) {
                        $sql_task_clienti .= " AND (tc.assegnato_a IS NULL OR tc.assegnato_a = ?)";
                        $params_tc[] = $user_id;
                    }
                    
                    $sql_task_clienti .= " ORDER BY tc.scadenza ASC";
                    
                    $stmt = $pdo->prepare($sql_task_clienti);
                    $stmt->execute($params_tc);
                    $task_clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (empty($task_clienti)): ?>
                        <div class="empty-state-mini">
                            <div class="icon-mini">👥</div>
                            <p>Nessun task cliente da gestire</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table compact">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Descrizione</th>
                                    <th>Scadenza</th>
                                    <th>Assegnato a</th>
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
                                        <?php if ($tc['nome_assegnato']): ?>
                                            <span class="assigned-user"><?= htmlspecialchars($tc['nome_assegnato']) ?></span>
                                        <?php else: ?>
                                            <span class="general-task">Generale</span>
                                        <?php endif; ?>
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

            <!-- Sottosezione Documenti -->
            <div class="scadenze-subsection">
                <h4 class="subsection-title">
                    <span class="subsection-icon">📄</span>
                    Documenti
                </h4>
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
                            `Rinnovo_Pec` AS pec_rinnovo
                        FROM clienti
                        WHERE 
                            (`Data_di_scadenza` IS NOT NULL AND `Data_di_scadenza` BETWEEN ? AND ?)
                            OR
                            (`Scadenza_PEC` IS NOT NULL AND `Scadenza_PEC` BETWEEN ? AND ?)
                            OR
                            (`Rinnovo_Pec` IS NOT NULL AND `Rinnovo_Pec` BETWEEN ? AND ?)
                        ORDER BY 
                            LEAST(
                                IFNULL(`Data_di_scadenza`, '9999-12-31'), 
                                IFNULL(`Scadenza_PEC`, '9999-12-31'),
                                IFNULL(`Rinnovo_Pec`, '9999-12-31')
                            ) ASC
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $da30giorni, $entro30, // Carta d'identità
                        $da30giorni, $entro30, // PEC
                        $da30giorni, $entro30  // Rinnovo PEC
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
                        // Rinnovo PEC
                        if (!empty($row['PEC']) && !empty($row['pec_rinnovo'])) {
                            $documenti[] = [
                                'id' => $row['id'],
                                'cognome' => $row['cognome'],
                                'tipo' => "Rinnovo PEC",
                                'numero' => $row['PEC'],
                                'scadenza' => $row['pec_rinnovo'],
                            ];
                        }
                    }

                    // Ordiniamo i documenti per scadenza
                    usort($documenti, function($a, $b) {
                        return strtotime($a['scadenza']) - strtotime($b['scadenza']);
                    });
                    ?>

                    <?php if (empty($documenti)): ?>
                        <div class="empty-state-mini">
                            <div class="icon-mini">📄</div>
                            <p>Nessun documento da rinnovare</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table compact">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
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
                                            <a href="info_cliente.php?id=<?= urlencode($doc['id']) ?>">
                                                <?= htmlspecialchars($doc['cognome']) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($doc['tipo']) ?></td>
                                    <td><?= htmlspecialchars($doc['numero']) ?></td>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
/* Stili per la sezione Scadenze unificata */
.scadenze-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 15px;
    padding: 0;
    margin-bottom: 2rem;
    overflow: hidden;
}

.scadenze-section .section-title {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    margin: 0;
    padding: 1.5rem 2rem;
    font-size: 1.5rem;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.scadenze-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
}

.scadenze-subsection {
    background: white;
    border-bottom: 1px solid #dee2e6;
}

.scadenze-subsection:last-child {
    border-bottom: none;
    border-radius: 0 0 15px 15px;
}

.subsection-title {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    margin: 0;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.subsection-icon {
    font-size: 1.2rem;
}

.scroll-content {
    max-height: 400px;
    overflow-y: auto;
    padding: 0;
}

.data-table.compact {
    margin: 0;
    font-size: 0.9rem;
}

.data-table.compact th,
.data-table.compact td {
    padding: 0.75rem 1rem;
}

.empty-state-mini {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.empty-state-mini .icon-mini {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state-mini p {
    margin: 0;
    font-size: 0.9rem;
}

.badge-recurring {
    background: #17a2b8;
    color: white;
    padding: 0.2rem 0.4rem;
    border-radius: 10px;
    font-size: 0.7rem;
    margin-left: 0.5rem;
}

.assigned-user {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

.general-task {
    background: #f3e5f5;
    color: #7b1fa2;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .scadenze-container {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .scadenze-section .section-title {
        padding: 1rem 1.5rem;
        font-size: 1.3rem;
    }
    
    .subsection-title {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
    
    .data-table.compact {
        font-size: 0.8rem;
    }
    
    .data-table.compact th,
    .data-table.compact td {
        padding: 0.5rem 0.75rem;
    }
    
    .scroll-content {
        max-height: 300px;
    }
}

/* Animazioni */
.scadenze-subsection {
    transition: all 0.3s ease;
}

.scadenze-subsection:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Miglioramenti per le tabelle nelle sottosezioni */
.scadenze-subsection .data-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateX(2px);
    transition: all 0.2s ease;
}

.scadenze-subsection .status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<script>
// Auto-refresh della dashboard ogni 15 minuti
setTimeout(() => {
    location.reload();
}, 900000);
</script>

</main>
</body>
</html>
