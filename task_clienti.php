<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// **NUOVO**: Gestione richieste AJAX
if (isset($_GET['action']) && $_GET['action'] === 'get_client_tasks') {
    header('Content-Type: application/json');
    
    try {
        $client_id = intval($_GET['client_id'] ?? 0);
        
        if ($client_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID cliente non valido']);
            exit;
        }
        
        // Query per ottenere i task del cliente ordinati per scadenza
        $stmt = $pdo->prepare("
            SELECT tc.*, 
                   CONCAT(c.`Cognome_Ragione_sociale`, ' ', COALESCE(c.Nome, '')) as nome_cliente
            FROM task_clienti tc
            LEFT JOIN clienti c ON tc.cliente_id = c.id
            WHERE tc.cliente_id = ?
            ORDER BY tc.scadenza ASC
        ");
        
        $stmt->execute([$client_id]);
        $tasks = $stmt->fetchAll();
        
        // Formatta i task per la risposta JSON
        $formatted_tasks = [];
        foreach ($tasks as $task) {
            $is_ricorrente = !empty($task['ricorrenza']);
            $is_scaduto = strtotime($task['scadenza']) < strtotime('today');
            $scadenza_formatted = date('d/m/Y', strtotime($task['scadenza']));
            
            $ricorrenza_text = '';
            if ($is_ricorrente) {
                $giorni = $task['ricorrenza'];
                if ($giorni % 365 == 0) {
                    $ricorrenza_text = ($giorni / 365) . ' anni';
                } elseif ($giorni % 30 == 0) {
                    $ricorrenza_text = ($giorni / 30) . ' mesi';
                } elseif ($giorni % 7 == 0) {
                    $ricorrenza_text = ($giorni / 7) . ' settimane';
                } else {
                    $ricorrenza_text = $giorni . ' giorni';
                }
            }
            
            $formatted_tasks[] = [
                'id' => $task['id'],
                'descrizione' => $task['descrizione'],
                'scadenza' => $task['scadenza'],
                'scadenza_formatted' => $scadenza_formatted,
                'ricorrenza' => $task['ricorrenza'],
                'ricorrenza_text' => $ricorrenza_text,
                'ricorrente' => $is_ricorrente,
                'scaduto' => $is_scaduto,
                'nome_cliente' => $task['nome_cliente'],
                'cliente_id' => $task['cliente_id']
            ];
        }
        
        echo json_encode(['success' => true, 'tasks' => $formatted_tasks]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

include __DIR__ . '/includes/header.php';

$messaggio = "";

// Gestione messaggi di successo dal redirect
if (isset($_GET['success'])) {
    $messaggio = "Operazione completata con successo!";
}

// Gestione completamento task
if (isset($_GET['completa']) && is_numeric($_GET['completa'])) {
    try {
        $task_id = intval($_GET['completa']);
        
        // Recupera i dati del task prima di eliminarlo
        $stmt = $pdo->prepare("
            SELECT tc.*, c.`Cognome_Ragione_sociale`, c.Nome, c.`Codice_fiscale`
            FROM task_clienti tc
            LEFT JOIN clienti c ON tc.cliente_id = c.id 
            WHERE tc.id = ?
        ");
        $stmt->execute([$task_id]);
        $task_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task_data) {
            $nome_cliente = trim(($task_data['Nome'] ?? '') . ' ' . ($task_data['Cognome_Ragione_sociale'] ?? ''));
            if (empty($nome_cliente)) {
                $nome_cliente = "Cliente ID " . $task_data['cliente_id'];
            }
            
            // Salva il log del task completato nella cartella del cliente
            if (!empty($task_data['Codice_fiscale']) && isset($_SESSION['user_name'])) {
                $codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $task_data['Codice_fiscale']);
                $cartella_cliente = __DIR__ . '/local_drive/' . $codice_fiscale_clean;
                
                // Assicurati che la cartella esista (con controllo ottimizzato)
                if (!is_dir($cartella_cliente)) {
                    mkdir($cartella_cliente, 0755, true);
                }
                
                $log_file = $cartella_cliente . '/task_completati.txt';
                $data_completamento = date('d/m/Y H:i:s');
                $utente_completamento = $_SESSION['user_name'];
                
                $log_entry = sprintf(
                    "[%s] TASK CLIENTE COMPLETATO: %s | Cliente: %s | Utente: %s | Scadenza: %s | Ricorrente: %s\n",
                    $data_completamento,
                    $task_data['descrizione'],
                    $nome_cliente,
                    $utente_completamento,
                    date('d/m/Y', strtotime($task_data['scadenza'])),
                    (!empty($task_data['ricorrenza']) && $task_data['ricorrenza'] > 0) ? "Sì (ogni {$task_data['ricorrenza']} giorni)" : "No"
                );
                
                // Scrivi il log in modo ottimizzato (evita lock prolungati)
                $handle = fopen($log_file, 'a');
                if ($handle) {
                    fwrite($handle, $log_entry);
                    fclose($handle);
                }
            }
            
            // Invia notifica nella chat se l'utente è loggato
            if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
                $msg_notifica = $_SESSION['user_name'] . " ha completato il task: " . $task_data['descrizione'] . 
                               ($nome_cliente ? " (Cliente: $nome_cliente)" : "");
                $stmt_chat = $pdo->prepare("INSERT INTO chat_messaggi (utente_id, messaggio, timestamp) VALUES (?, ?, NOW())");
                $stmt_chat->execute([$_SESSION['user_id'], $msg_notifica]);
            }
            
            if ($task_data['ricorrenza'] && $task_data['ricorrenza'] > 0) {
                // Task ricorrente: calcola nuova scadenza
                $nuova_scadenza = date('Y-m-d', strtotime($task_data['scadenza'] . ' + ' . $task_data['ricorrenza'] . ' days'));
                
                // Aggiorna il task esistente con nuova scadenza
                $stmt_aggiorna = $pdo->prepare("UPDATE task_clienti SET scadenza = ? WHERE id = ?");
                $stmt_aggiorna->execute([$nuova_scadenza, $task_id]);
                
                $messaggio = "Task ricorrente completato! È stato aggiornato con scadenza: " . date('d/m/Y', strtotime($nuova_scadenza));
            } else {
                // Task one-shot: elimina definitivamente
                $pdo->prepare("DELETE FROM task_clienti WHERE id = ?")->execute([$task_id]);
                $messaggio = "Task completato ed eliminato!";
            }
        } else {
            $messaggio = "Errore: Task non trovato";
        }
    } catch (Exception $e) {
        $messaggio = "Errore nel completamento: " . $e->getMessage();
    }
}

// Gestione eliminazione task
if (isset($_GET['elimina']) && is_numeric($_GET['elimina'])) {
    try {
        $task_id = intval($_GET['elimina']);
        $stmt = $pdo->prepare("DELETE FROM task_clienti WHERE id = ?");
        if ($stmt->execute([$task_id])) {
            $messaggio = "Task eliminato con successo!";
        }
    } catch (Exception $e) {
        $messaggio = "Errore nell'eliminazione: " . $e->getMessage();
    }
}

// Gestione filtri (PRIMA di tutto)
$filtro_cliente = $_GET['cliente'] ?? '';
$filtro_scadenza = $_GET['scadenza'] ?? '';
$filtro_ricorrenza = $_GET['ricorrenza'] ?? '';
$search = $_GET['search'] ?? '';

// Carica lista clienti (ottimizzata - solo se necessario)
$clienti = [];
// Carica sempre i clienti per i filtri
$clienti = $pdo->query("SELECT id, `Cognome_Ragione_sociale`, Nome, `Codice_fiscale` FROM clienti ORDER BY `Cognome_Ragione_sociale`, Nome")->fetchAll();

// Carica lista task con clienti associati (query ottimizzata)
$where_conditions = ["1=1"]; // Base condition per semplificare la logica
$params = [];

// Query ottimizzata con calcolo statistiche integrate
$sql = "SELECT tc.*, 
        CONCAT(c.`Cognome_Ragione_sociale`, ' ', COALESCE(c.Nome, '')) as nome_cliente,
        c.`Codice_fiscale` as codice_fiscale,
        CASE 
            WHEN tc.scadenza < CURDATE() THEN 'scaduto'
            WHEN tc.scadenza = CURDATE() THEN 'oggi'
            WHEN tc.scadenza <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'settimana'
            WHEN tc.scadenza <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 'quindici_giorni'
            ELSE 'futuro'
        END as categoria_scadenza
        FROM task_clienti tc 
        LEFT JOIN clienti c ON tc.cliente_id = c.id";

// I task sono già associati ai clienti nella tabella task_clienti

if (!empty($search)) {
    $where_conditions[] = "(tc.descrizione LIKE ? OR c.`Cognome_Ragione_sociale` LIKE ? OR c.Nome LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filtro_cliente)) {
    $where_conditions[] = "tc.cliente_id = ?";
    $params[] = intval($filtro_cliente);
}

if (!empty($filtro_scadenza)) {
    switch ($filtro_scadenza) {
        case 'scaduti':
            $where_conditions[] = "tc.scadenza < CURDATE()";
            break;
        case 'oggi':
            $where_conditions[] = "tc.scadenza = CURDATE()";
            break;
        case 'settimana':
            $where_conditions[] = "tc.scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            break;
        case '15giorni':
            $where_conditions[] = "tc.scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)";
            break;
        case 'mese':
            $where_conditions[] = "tc.scadenza BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

if (!empty($filtro_ricorrenza)) {
    if ($filtro_ricorrenza === 'ricorrenti') {
        $where_conditions[] = "tc.ricorrenza IS NOT NULL AND tc.ricorrenza > 0";
    } elseif ($filtro_ricorrenza === 'oneshot') {
        $where_conditions[] = "(tc.ricorrenza IS NULL OR tc.ricorrenza = 0)";
    }
}

if (count($where_conditions) > 1) {  // > 1 perché la prima è sempre "1=1"
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY tc.scadenza ASC, c.`Cognome_Ragione_sociale` ASC";

// DEBUG: Log della query per diagnostica
error_log("SQL Query: " . $sql);
error_log("Params: " . print_r($params, true));
error_log("Where conditions: " . print_r($where_conditions, true));

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// DEBUG: Log del numero di task trovati
error_log("Tasks found: " . count($tasks));

// Calcola statistiche ottimizzate (usando i dati già caricati invece di loop separati)
$stats = [
    'totali' => 0,
    'scaduti' => 0,
    'oggi' => 0,
    'settimana' => 0,
    'quindici_giorni' => 0,
    'ricorrenti' => 0,
    'oneshot' => 0
];

foreach ($tasks as $task) {
    $stats['totali']++;
    
    // Usa la categoria pre-calcolata nel SQL
    switch ($task['categoria_scadenza']) {
        case 'scaduto':
            $stats['scaduti']++;
            break;
        case 'oggi':
            $stats['oggi']++;
            break;
        case 'settimana':
            $stats['settimana']++;
            break;
        case 'quindici_giorni':
            $stats['quindici_giorni']++;
            break;
    }
    
    if (!empty($task['ricorrenza']) && $task['ricorrenza'] > 0) {
        $stats['ricorrenti']++;
    } else {
        $stats['oneshot']++;
    }
}

?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.task-header {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    position: relative;
}

.task-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.task-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.back-btn {
    position: absolute;
    left: 30px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.1em;
}

.back-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-50%) scale(1.05);
    color: white;
    text-decoration: none;
}

.alert {
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-weight: bold;
    animation: fadeIn 0.5s ease-out;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.main-container {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 30px;
    margin-bottom: 30px;
}

.form-panel {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    height: fit-content;
}

.quick-actions {
    text-align: center;
}

.quick-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

.info-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1rem;
    text-align: left;
}

.info-box p {
    margin: 0 0 1rem 0;
    color: #495057;
    font-weight: 600;
}

.info-box ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #6c757d;
}

.info-box li {
    margin-bottom: 0.5rem;
}

.tasks-panel {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.form-panel h3 {
    color: #6f42c1;
    border-bottom: 3px solid #e83e8c;
    padding-bottom: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tasks-panel h3 {
    color: #6f42c1;
    border-bottom: 3px solid #e83e8c;
    padding-bottom: 10px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: bold;
    color: #333;
    margin-bottom: 8px;
    display: block;
}

.form-group label.required::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1em;
    transition: all 0.3s ease;
    background: white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
    transform: translateY(-1px);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.ricorrenza-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    align-items: end;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 50px;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    margin: 5px;
}

.btn-primary {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
}

.task-item {
    background: #f8f9fa;
    border-left: 5px solid #6f42c1;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 0 10px 10px 0;
    transition: all 0.3s ease;
}

.task-item:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.task-item.ricorrente {
    border-left-color: #e83e8c;
}

.task-item.scaduto {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.task-header-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.task-title {
    font-weight: bold;
    color: #333;
    margin: 0;
    font-size: 1.1em;
}

.task-cliente {
    background: #6f42c1;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
}

.task-info {
    margin-top: 10px;
    display: flex;
    gap: 15px;
    align-items: center;
    font-size: 0.9em;
    color: #666;
}

.task-scadenza {
    display: flex;
    align-items: center;
    gap: 5px;
}

.task-ricorrenza {
    display: flex;
    align-items: center;
    gap: 5px;
}

.task-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.task-actions .btn {
    padding: 6px 15px;
    font-size: 0.9em;
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state i {
    font-size: 3em;
    color: #ddd;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .main-container {
        grid-template-columns: 1fr;
    }
    
    .task-header {
        padding: 20px;
    }
    
    .task-header h2 {
        font-size: 2em;
    }
    
    .back-btn {
        position: relative;
        left: 0;
        top: 0;
        transform: none;
        margin-bottom: 15px;
        display: block;
        width: fit-content;
    }
}

@media (max-width: 768px) {
    .task-header-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .task-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .task-actions {
        flex-wrap: wrap;
    }
    
    .ricorrenza-group {
        grid-template-columns: 1fr;
    }
}

/* **NUOVO**: Stili per i filtri */
.filters-section {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    border: 2px solid #e9ecef;
}

.filters-section h4 {
    margin: 0 0 15px 0;
    color: #6f42c1;
    font-weight: bold;
}

.filters-form {
    margin-bottom: 20px;
}

.filter-row {
    display: grid;
    grid-template-columns: 2fr 2fr 1.5fr 1.5fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
    font-size: 0.9em;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 0.9em;
    transition: border-color 0.3s ease;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #6f42c1;
}

.filter-actions {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.85em;
    margin: 0;
}

.stats-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.stat-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #e9ecef;
    min-width: 90px;
    transition: transform 0.2s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-item i {
    font-size: 1.5em;
    margin-bottom: 5px;
    color: #6f42c1;
}

.stat-item.danger i { color: #dc3545; }
.stat-item.warning i { color: #ffc107; }
.stat-item.info i { color: #17a2b8; }
.stat-item.success i { color: #28a745; }

.stat-number {
    display: block;
    font-size: 1.8em;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 0.8em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Pulsante crea task nelle statistiche */
.stat-item.create-button {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    border: 2px solid #6f42c1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 80px;
}

.stat-item.create-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.4);
}

.stat-item.create-button .btn {
    color: white;
    text-decoration: none;
    font-weight: bold;
}

.stat-item.create-button .btn:hover {
    color: white;
    text-decoration: none;
}

/* **NUOVO**: Area visualizzazione cliente */
.client-view-section {
    background: #fff;
    border-radius: 10px;
    padding: 25px;
    margin-top: 30px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
}

.client-view-section h3 {
    color: #6f42c1;
    margin-bottom: 20px;
    font-weight: bold;
}

.client-selector {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-bottom: 20px;
}

.client-selector select {
    flex: 1;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1em;
}

.client-tasks-list {
    display: none;
}

.client-tasks-list.show {
    display: block;
}

.client-task-item {
    background: #f8f9fa;
    border-left: 4px solid #6f42c1;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 0 8px 8px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.client-task-item.scaduto {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.client-task-item.ricorrente {
    border-left-color: #e83e8c;
}

.client-task-info h5 {
    margin: 0 0 5px 0;
    color: #333;
}

.client-task-meta {
    font-size: 0.9em;
    color: #666;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .stats-row {
        justify-content: center;
    }
    
    .stat-item {
        min-width: 80px;
    }
    
    .client-selector {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<?php if ($messaggio): ?>
    <div class="alert <?= strpos($messaggio, 'Errore') !== false ? 'alert-error' : 'alert-success' ?>">
        <i class="fas <?= strpos($messaggio, 'Errore') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
        <?= htmlspecialchars($messaggio) ?>
    </div>
<?php endif; ?>

<div class="main-container">
    <!-- Pannello Lista Task a larghezza piena -->
    <div class="tasks-panel" style="grid-column: 1 / -1;">
        <!-- **NUOVO**: Sezione Filtri -->
        <div class="filters-section">
            <h4><i class="fas fa-filter"></i> Filtri</h4>
            <form method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filter_search">Cerca</label>
                        <input type="text" name="search" id="filter_search" 
                               value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cerca in descrizione o cliente...">
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_cliente">Cliente</label>
                        <select name="cliente" id="filter_cliente">
                            <option value="">Tutti i clienti</option>
                            <?php foreach ($clienti as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" 
                                        <?= ($filtro_cliente == $cliente['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['Cognome_Ragione_sociale'] . ' ' . ($cliente['Nome'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_scadenza">Scadenza</label>
                        <select name="scadenza" id="filter_scadenza">
                            <option value="">Tutte</option>
                            <option value="scaduti" <?= ($filtro_scadenza === 'scaduti') ? 'selected' : '' ?>>Scaduti</option>
                            <option value="oggi" <?= ($filtro_scadenza === 'oggi') ? 'selected' : '' ?>>Oggi</option>
                            <option value="settimana" <?= ($filtro_scadenza === 'settimana') ? 'selected' : '' ?>>Entro 7 giorni</option>
                            <option value="15giorni" <?= ($filtro_scadenza === '15giorni') ? 'selected' : '' ?>>Entro 15 giorni</option>
                            <option value="mese" <?= ($filtro_scadenza === 'mese') ? 'selected' : '' ?>>Entro 30 giorni</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="filter_ricorrenza">Tipo</label>
                        <select name="ricorrenza" id="filter_ricorrenza">
                            <option value="">Tutti</option>
                            <option value="ricorrenti" <?= ($filtro_ricorrenza === 'ricorrenti') ? 'selected' : '' ?>>Ricorrenti</option>
                            <option value="oneshot" <?= ($filtro_ricorrenza === 'oneshot') ? 'selected' : '' ?>>One-shot</option>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Filtra
                        </button>
                        <a href="task_clienti.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Statistiche rapide -->
            <div class="stats-row">
                <div class="stat-item">
                    <i class="fas fa-tasks"></i>
                    <span class="stat-number"><?= $stats['totali'] ?></span>
                    <span class="stat-label">Totali</span>
                </div>
                <div class="stat-item danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="stat-number"><?= $stats['scaduti'] ?></span>
                    <span class="stat-label">Scaduti</span>
                </div>
                <div class="stat-item warning">
                    <i class="fas fa-calendar-day"></i>
                    <span class="stat-number"><?= $stats['oggi'] ?></span>
                    <span class="stat-label">Oggi</span>
                </div>
                <div class="stat-item info">
                    <i class="fas fa-calendar-week"></i>
                    <span class="stat-number"><?= $stats['settimana'] ?></span>
                    <span class="stat-label">7 giorni</span>
                </div>
                <div class="stat-item success">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="stat-number"><?= $stats['quindici_giorni'] ?></span>
                    <span class="stat-label">15 giorni</span>
                </div>
                
                <!-- Pulsante Crea Task -->
                <div class="stat-item create-button">
                    <a href="crea_task_clienti.php" class="btn btn-primary" style="margin: 0; padding: 12px 20px; border-radius: 8px;">
                        <i class="fas fa-plus"></i> Nuovo Task
                    </a>
                </div>
            </div>
        </div>
        
        <h3>
            <i class="fas fa-list"></i>
            Task Attivi (<?= count($tasks) ?>)
        </h3>
        
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h4>Nessun task presente</h4>
                <p>Crea il primo task per iniziare!</p>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task_item): ?>
                <?php
                $is_ricorrente = !empty($task_item['ricorrenza']);
                $is_scaduto = strtotime($task_item['scadenza']) < strtotime('today');
                $scadenza_formatted = date('d/m/Y', strtotime($task_item['scadenza']));
                
                $ricorrenza_text = '';
                if ($is_ricorrente) {
                    $giorni = $task_item['ricorrenza'];
                    if ($giorni % 365 == 0) {
                        $ricorrenza_text = ($giorni / 365) . ' anni';
                    } elseif ($giorni % 30 == 0) {
                        $ricorrenza_text = ($giorni / 30) . ' mesi';
                    } elseif ($giorni % 7 == 0) {
                        $ricorrenza_text = ($giorni / 7) . ' settimane';
                    } else {
                        $ricorrenza_text = $giorni . ' giorni';
                    }
                }
                ?>
                
                <div class="task-item <?= $is_ricorrente ? 'ricorrente' : '' ?> <?= $is_scaduto ? 'scaduto' : '' ?>">
                    <div class="task-header-info">
                        <h4 class="task-title"><?= htmlspecialchars($task_item['descrizione']) ?></h4>
                        <?php if ($task_item['nome_cliente']): ?>
                            <span class="task-cliente"><?= htmlspecialchars($task_item['nome_cliente']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-info">
                        <div class="task-scadenza">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?= $scadenza_formatted ?></span>
                            <?php if ($is_scaduto): ?>
                                <span style="color: #dc3545; font-weight: bold;">(Scaduto)</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_ricorrente): ?>
                            <div class="task-ricorrenza">
                                <i class="fas fa-redo-alt"></i>
                                <span>Ogni <?= $ricorrenza_text ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="task-actions">
                        <a href="crea_task_clienti.php?edit=<?= $task_item['id'] ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifica
                        </a>
                        
                        <a href="?completa=<?= $task_item['id'] ?>" 
                           class="btn btn-success" 
                           onclick="return confirm('Sei sicuro di voler completare questo task?<?= !empty($task_item['ricorrenza']) ? ' (Task ricorrente: verrà aggiornato con nuova scadenza)' : ' (Task one-shot: verrà eliminato definitivamente)' ?>')">
                            <i class="fas fa-check"></i> Completa
                        </a>
                        
                        <a href="?elimina=<?= $task_item['id'] ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Sei sicuro di voler eliminare questo task?')">
                            <i class="fas fa-trash"></i> Elimina
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- **NUOVO**: Area Visualizzazione Task per Cliente -->
<div class="client-view-section">
    <h3><i class="fas fa-user-tag"></i> Visualizza Task per Cliente</h3>
    
    <div class="client-selector">
        <label for="client_selector">Seleziona Cliente:</label>
        <select id="client_selector" onchange="loadClientTasks()">
            <option value="">-- Scegli un cliente --</option>
            <?php foreach ($clienti as $cliente): ?>
                <option value="<?= $cliente['id'] ?>">
                    <?= htmlspecialchars($cliente['Cognome_Ragione_sociale'] . ' ' . ($cliente['Nome'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" onclick="loadClientTasks()" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Carica
        </button>
    </div>
    
    <div id="client_tasks_container" class="client-tasks-list">
        <div class="loading" id="loading_tasks" style="display: none; text-align: center; padding: 20px;">
            <i class="fas fa-spinner fa-spin"></i> Caricamento task...
        </div>
        
        <div id="client_tasks_content"></div>
        
        <div id="no_tasks" style="display: none; text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-inbox"></i>
            <p>Nessun task trovato per questo cliente</p>
        </div>
    </div>
</div>

<script>
// **NUOVO**: Funzione per caricare i task di un cliente specifico
function loadClientTasks() {
    const clientId = document.getElementById('client_selector').value;
    const container = document.getElementById('client_tasks_container');
    const content = document.getElementById('client_tasks_content');
    const loading = document.getElementById('loading_tasks');
    const noTasks = document.getElementById('no_tasks');
    
    // Reset stato
    content.innerHTML = '';
    noTasks.style.display = 'none';
    
    if (!clientId) {
        container.classList.remove('show');
        return;
    }
    
    // Mostra loading
    container.classList.add('show');
    loading.style.display = 'block';
    
    // Carica i task del cliente via AJAX
    fetch(`?action=get_client_tasks&client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            
            if (data.success && data.tasks && data.tasks.length > 0) {
                content.innerHTML = data.tasks.map(task => `
                    <div class="client-task-item ${task.ricorrente ? 'ricorrente' : ''} ${task.scaduto ? 'scaduto' : ''}">
                        <div class="client-task-info">
                            <h5>${escapeHtml(task.descrizione)}</h5>
                            <div class="client-task-meta">
                                <i class="fas fa-calendar-alt"></i> Scadenza: ${task.scadenza_formatted}
                                ${task.scaduto ? '<span style="color: #dc3545; font-weight: bold;"> (Scaduto)</span>' : ''}
                                ${task.ricorrente ? `<br><i class="fas fa-sync-alt"></i> Ricorrente: ogni ${task.ricorrenza_text}` : ''}
                            </div>
                        </div>
                        <div class="task-actions">
                            <a href="crea_task_clienti.php?edit=${task.id}" class="btn btn-warning btn-sm" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?completa=${task.id}" class="btn btn-success btn-sm" 
                               onclick="return confirm('Confermi il completamento del task?')" title="Completa">
                                <i class="fas fa-check"></i>
                            </a>
                            <a href="?elimina=${task.id}" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Sei sicuro di voler eliminare questo task?')" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                `).join('');
            } else {
                noTasks.style.display = 'block';
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            console.error('Errore nel caricamento dei task:', error);
            alert('Errore nel caricamento dei task del cliente');
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function completaTask(taskId) {
    if (confirm('Confermi il completamento del task?')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=completa&task_id=' + taskId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Errore nel completamento del task');
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore nella comunicazione con il server');
        });
    }
}

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    // Focus sul campo di ricerca se disponibile
    const searchField = document.getElementById('filter_search');
    if (searchField) {
        searchField.focus();
    }
});
</script>

</main>
</body>
</html>
