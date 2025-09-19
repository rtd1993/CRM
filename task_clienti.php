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
                'cliente_id' => $task['cliente_id'],
                'fatturabile' => (bool)$task['fatturabile']
            ];
        }
        
        echo json_encode(['success' => true, 'tasks' => $formatted_tasks]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// **NUOVO**: Gestione copia task multipli
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        header("Location: task_clienti.php?success=" . urlencode($messaggio));
        exit;
        
    } catch (Exception $e) {
        $messaggio = "Errore: " . $e->getMessage();
        error_log("Errore creazione task cliente: " . $e->getMessage());
        
        // Verifica se è una richiesta AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

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
            
            // Salva il log del task completato nella cartella del cliente con nuovo formato
            if (!empty($task_data['cliente_id']) && isset($_SESSION['user_name'])) {
                // Crea nome cartella con formato id_Cognome.Nome (maiuscole)
                $cliente_folder = $task_data['cliente_id'] . '_' . 
                                preg_replace('/[^A-Za-z0-9]/', '', $task_data['Cognome_Ragione_sociale'] ?? 'Cliente');
                if (!empty($task_data['Nome'])) {
                    $nome_clean = preg_replace('/[^A-Za-z0-9]/', '', $task_data['Nome']);
                    $cliente_folder .= '.' . $nome_clean;
                }
                $cartella_cliente = __DIR__ . '/local_drive/' . $cliente_folder;
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
                // Scrivi il log locale per il cliente
                $handle = fopen($log_file, 'a');
                if ($handle) {
                    fwrite($handle, $log_entry);
                    fclose($handle);
                }
                // Scrivi anche nel log storico globale
                $global_log_file = '/var/www/CRM/local_drive/ASContabilmente/storico_taskclienti.txt';
                $global_log_entry = sprintf(
                    "[%s] TASK CLIENTE COMPLETATO: %s | Cliente: %s | Utente: %s | Scadenza: %s | Ricorrente: %s\n",
                    $data_completamento,
                    $task_data['descrizione'],
                    $nome_cliente,
                    $utente_completamento,
                    date('d/m/Y', strtotime($task_data['scadenza'])),
                    (!empty($task_data['ricorrenza']) && $task_data['ricorrenza'] > 0) ? "Sì (ogni {$task_data['ricorrenza']} giorni)" : "No"
                );
                file_put_contents($global_log_file, $global_log_entry, FILE_APPEND | LOCK_EX);
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

// Gestione fatturato task
if (isset($_GET['fatturato']) && is_numeric($_GET['fatturato'])) {
    try {
        $task_id = intval($_GET['fatturato']);
        
        // Prima verifica che il task sia fatturabile
        $stmt_check = $pdo->prepare("SELECT fatturabile, descrizione FROM task_clienti WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task_data = $stmt_check->fetch();
        
        if ($task_data && $task_data['fatturabile'] == 1) {
            // Segna il task come fatturato (impostando fatturabile a 0)
            $stmt = $pdo->prepare("UPDATE task_clienti SET fatturabile = 0 WHERE id = ?");
            if ($stmt->execute([$task_id])) {
                $messaggio = "Task segnato come fatturato con successo!";
                
                // Log dell'operazione
                $log_entry = sprintf(
                    "[%s] TASK FATTURATO: %s | Utente: %s\n",
                    date('d/m/Y H:i:s'),
                    $task_data['descrizione'],
                    $_SESSION['user_name']
                );
                file_put_contents(__DIR__ . '/logs/task_fatturati.txt', $log_entry, FILE_APPEND | LOCK_EX);
            }
        } else {
            $messaggio = "Errore: Il task non è fatturabile o non esiste!";
        }
    } catch (Exception $e) {
        $messaggio = "Errore nella marcatura come fatturato: " . $e->getMessage();
    }
}

// Gestione filtri (PRIMA di tutto)

$filtro_cliente = $_GET['cliente'] ?? '';
$filtro_scadenza = $_GET['scadenza'] ?? '';
$filtro_ricorrenza = $_GET['ricorrenza'] ?? '';
$search = $_GET['search'] ?? '';
$filtro_assegnato = $_GET['assegnato_a'] ?? '';
$filtro_fatturabile = $_GET['fatturabile'] ?? '';

// Carica lista clienti (ottimizzata - solo se necessario)
$clienti = [];
// Carica sempre i clienti per i filtri
$clienti = $pdo->query("SELECT id, `Cognome_Ragione_sociale`, Nome, `Codice_fiscale` FROM clienti ORDER BY `Cognome_Ragione_sociale`, Nome")->fetchAll();

// Carica lista task con clienti associati (query ottimizzata)
$where_conditions = ["1=1"]; // Base condition per semplificare la logica
$params = [];

// Determina i permessi dell'utente per task_clienti
$user_role = $_SESSION['user_role'] ?? 'employee';
$user_id = $_SESSION['user_id'] ?? 0;
$can_see_all = in_array($user_role, ['admin', 'developer']);

// Query ottimizzata con calcolo statistiche integrate e informazioni assegnazione
$sql = "SELECT tc.*, 
        CONCAT(c.`Cognome_Ragione_sociale`, ' ', COALESCE(c.Nome, '')) as nome_cliente,
        c.`Codice_fiscale` as codice_fiscale,
        u.nome as nome_assegnato,
        CASE 
            WHEN tc.scadenza < CURDATE() THEN 'scaduto'
            WHEN tc.scadenza = CURDATE() THEN 'oggi'
            WHEN tc.scadenza <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'settimana'
            WHEN tc.scadenza <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 'quindici_giorni'
            ELSE 'futuro'
        END as categoria_scadenza
        FROM task_clienti tc 
        LEFT JOIN clienti c ON tc.cliente_id = c.id
        LEFT JOIN utenti u ON tc.assegnato_a = u.id";

// Filtro per ruolo utente - solo se non è admin/developer
if (!$can_see_all) {
    $where_conditions[] = "(tc.assegnato_a IS NULL OR tc.assegnato_a = ?)";
    $params[] = $user_id;
}

if (!empty($search)) {
    $where_conditions[] = "(tc.descrizione LIKE ? OR c.`Cognome_Ragione_sociale` LIKE ? OR c.Nome LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Filtro assegnato_a
if ($filtro_assegnato !== '') {
    if ($filtro_assegnato === 'none') {
        $where_conditions[] = "(tc.assegnato_a IS NULL OR tc.assegnato_a = 0 OR tc.assegnato_a = '')";
    } elseif (is_numeric($filtro_assegnato)) {
        $where_conditions[] = "tc.assegnato_a = ?";
        $params[] = $filtro_assegnato;
    }
    // Se 'Tutti', non aggiunge filtro
}

// Filtro fatturabile
if ($filtro_fatturabile !== '') {
    if ($filtro_fatturabile === '1') {
        $where_conditions[] = "tc.fatturabile = 1";
    } elseif ($filtro_fatturabile === '0') {
        $where_conditions[] = "tc.fatturabile = 0";
    }
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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Calcola statistiche ottimizzate (usando i dati già caricati invece di loop separati)
$stats = [
    'totali' => 0,
    'scaduti' => 0,
    'oggi' => 0,
    'settimana' => 0,
    'quindici_giorni' => 0,
    'ricorrenti' => 0,
    'oneshot' => 0,
    'fatturabili' => 0,
    'scaduti_fatturabili' => 0,
    'oggi_fatturabili' => 0,
    'settimana_fatturabili' => 0,
    'quindici_giorni_fatturabili' => 0
];

foreach ($tasks as $task) {
    $stats['totali']++;
    
    // Conteggio task fatturabili
    $is_fatturabile = !empty($task['fatturabile']) && $task['fatturabile'] == 1;
    if ($is_fatturabile) {
        $stats['fatturabili']++;
    }
    
    // Usa la categoria pre-calcolata nel SQL
    switch ($task['categoria_scadenza']) {
        case 'scaduto':
            $stats['scaduti']++;
            if ($is_fatturabile) $stats['scaduti_fatturabili']++;
            break;
        case 'oggi':
            $stats['oggi']++;
            if ($is_fatturabile) $stats['oggi_fatturabili']++;
            break;
        case 'settimana':
            $stats['settimana']++;
            if ($is_fatturabile) $stats['settimana_fatturabili']++;
            break;
        case 'quindici_giorni':
            $stats['quindici_giorni']++;
            if ($is_fatturabile) $stats['quindici_giorni_fatturabili']++;
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
    .splitscreen-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .tasks-list-container,
    .client-view-section.right-panel .client-tasks-list {
        max-height: 400px;
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

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 2% auto;
    padding: 0;
    border: none;
    border-radius: 15px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.modal iframe {
    width: 100%;
    height: 600px;
    border: none;
    border-radius: 15px;
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

/* **NUOVO**: Stili per layout splitscreen */
.splitscreen-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
    min-height: 600px;
}

.left-panel,
.right-panel {
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
    overflow: hidden;
}

.left-panel {
    padding: 25px;
    border-left: 4px solid #6f42c1;
}

.right-panel {
    padding: 25px;
    border-left: 4px solid #e83e8c;
}

.tasks-list-container {
    max-height: calc(100vh - 400px);
    overflow-y: auto;
    padding-right: 5px;
}

.tasks-list-container::-webkit-scrollbar {
    width: 6px;
}

.tasks-list-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.tasks-list-container::-webkit-scrollbar-thumb {
    background: #6f42c1;
    border-radius: 3px;
}

.tasks-list-container::-webkit-scrollbar-thumb:hover {
    background: #5a31a3;
}

/* Ottimizzazione task item per splitscreen */
.splitscreen-container .task-item {
    margin-bottom: 12px;
    padding: 15px;
}

.splitscreen-container .task-title {
    font-size: 1em;
    margin-bottom: 8px;
}

.splitscreen-container .task-cliente {
    font-size: 0.75em;
    padding: 2px 8px;
}

.splitscreen-container .task-info {
    gap: 10px;
    font-size: 0.85em;
    margin-top: 8px;
}

.splitscreen-container .task-actions {
    margin-top: 10px;
    gap: 5px;
}

.btn-xs {
    padding: 4px 8px;
    font-size: 0.75em;
    margin: 0;
    border-radius: 4px;
}

/* Stili specifici per pannello destro */
.client-view-section.right-panel h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e83e8c;
}

.client-view-section.right-panel .client-tasks-list {
    max-height: calc(100vh - 500px);
    overflow-y: auto;
}

.client-view-section.right-panel .client-tasks-list::-webkit-scrollbar {
    width: 6px;
}

.client-view-section.right-panel .client-tasks-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.client-view-section.right-panel .client-tasks-list::-webkit-scrollbar-thumb {
    background: #e83e8c;
    border-radius: 3px;
}

.client-view-section.right-panel .client-tasks-list::-webkit-scrollbar-thumb:hover {
    background: #d63384;
}
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
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
}

.stat-item {
    background: white;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid #e9ecef;
    min-width: 70px;
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

/* Area visualizzazione cliente - ora integrata nel pannello destro */
.client-view-section {
    background: #fff;
    border-radius: 10px;
    padding: 25px;
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
    align-items: center;
    gap: 15px;
}

.client-task-item.scaduto {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.client-task-item.ricorrente {
    border-left-color: #e83e8c;
}

.task-checkbox {
    flex-shrink: 0;
}

.task-checkbox-input {
    width: 18px;
    height: 18px;
    margin: 0;
}

.client-task-info {
    flex: 1;
}

.client-task-info h5 {
    margin: 0 0 5px 0;
    color: #333;
}

.client-task-meta {
    font-size: 0.9em;
    color: #666;
}

.task-actions {
    flex-shrink: 0;
    display: flex;
    gap: 5px;
}

/* Stili per i controlli di copia */
#copy_controls {
    animation: slideDown 0.3s ease;
}

#copy_mode_button {
    animation: slideDown 0.3s ease;
}

.copy-mode-active .client-task-item {
    display: flex !important;
    align-items: center !important;
    gap: 15px !important;
}

.copy-mode-active .task-checkbox {
    display: flex !important;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#target_clients {
    min-height: 80px;
}

#target_clients option {
    padding: 5px;
}

.badge {
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .filter-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .stats-row {
        justify-content: center;
        gap: 8px;
    }
    
    .stat-item {
        min-width: 60px;
        padding: 8px;
    }
    
    .stat-item i {
        font-size: 1.2em;
    }
    
    .stat-number {
        font-size: 1.4em;
    }
    
    .client-selector {
        flex-direction: column;
        align-items: stretch;
    }
    
    .splitscreen-container .task-header-info {
        flex-direction: column;
        gap: 8px;
    }
    
    .splitscreen-container .task-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .splitscreen-container .task-actions {
        flex-wrap: wrap;
        gap: 3px;
    }
    
    .tasks-list-container,
    .client-view-section.right-panel .client-tasks-list {
        max-height: 300px;
    }
}

/* Stili per il modale task */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.2);
    border-radius: 15px 15px 0 0;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    border-top: 1px solid #e9ecef;
    border-radius: 0 0 15px 15px;
}

.form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #e67e22;
    box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.25);
}

.form-check-input:checked {
    background-color: #e67e22;
    border-color: #e67e22;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>

<?php if ($messaggio): ?>
    <div class="alert <?= strpos($messaggio, 'Errore') !== false ? 'alert-error' : 'alert-success' ?>">
        <i class="fas <?= strpos($messaggio, 'Errore') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
        <?= htmlspecialchars($messaggio) ?>
    </div>
<?php endif; ?>

<!-- **NUOVO**: Sezione Filtri a larghezza piena -->
<div class="filters-section" style="margin-bottom: 30px;">
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
            <div class="filter-group">
                <label for="filter_assegnato">Assegnazione</label>
                <select name="assegnato_a" id="filter_assegnato">
                    <option value="">Tutti</option>
                    <option value="none" <?= (($_GET['assegnato_a'] ?? '') === 'none') ? 'selected' : '' ?>>Generali</option>
                    <?php
                    // Carica utenti per filtro assegnazione
                    $utenti_filtro = $pdo->query("SELECT id, nome FROM utenti ORDER BY nome")->fetchAll();
                    foreach ($utenti_filtro as $utente) {
                        $selected = (($_GET['assegnato_a'] ?? '') == $utente['id']) ? 'selected' : '';
                        echo "<option value=\"{$utente['id']}\" $selected>{$utente['nome']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter_fatturabile">Fatturabilità</label>
                <select name="fatturabile" id="filter_fatturabile">
                    <option value="">Tutti</option>
                    <option value="1" <?= (($_GET['fatturabile'] ?? '') === '1') ? 'selected' : '' ?>>Fatturabili</option>
                    <option value="0" <?= (($_GET['fatturabile'] ?? '') === '0') ? 'selected' : '' ?>>Non fatturabili</option>
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
        </div>
    </form>
    
    <!-- Statistiche rapide -->
    <div class="stats-row">
        <div class="stat-item">
            <i class="fas fa-tasks"></i>
            <span class="stat-number"><?= $stats['totali'] ?></span>
            <span class="stat-label">Totali</span>
            <?php if ($stats['fatturabili'] > 0): ?>
                <div style="font-size: 0.8em; margin-top: 2px; color: #28a745;">
                    <i class="fas fa-euro-sign"></i> <?= $stats['fatturabili'] ?> da fatturare
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-item danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="stat-number"><?= $stats['scaduti'] ?></span>
            <span class="stat-label">Scaduti</span>
            <?php if ($stats['scaduti_fatturabili'] > 0): ?>
                <div style="font-size: 0.8em; margin-top: 2px; color: #28a745;">
                    <i class="fas fa-euro-sign"></i> <?= $stats['scaduti_fatturabili'] ?> da fatturare
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-item warning">
            <i class="fas fa-calendar-day"></i>
            <span class="stat-number"><?= $stats['oggi'] ?></span>
            <span class="stat-label">Oggi</span>
            <?php if ($stats['oggi_fatturabili'] > 0): ?>
                <div style="font-size: 0.8em; margin-top: 2px; color: #28a745;">
                    <i class="fas fa-euro-sign"></i> <?= $stats['oggi_fatturabili'] ?> da fatturare
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-item info">
            <i class="fas fa-calendar-week"></i>
            <span class="stat-number"><?= $stats['settimana'] ?></span>
            <span class="stat-label">7 giorni</span>
            <?php if ($stats['settimana_fatturabili'] > 0): ?>
                <div style="font-size: 0.8em; margin-top: 2px; color: #28a745;">
                    <i class="fas fa-euro-sign"></i> <?= $stats['settimana_fatturabili'] ?> da fatturare
                </div>
            <?php endif; ?>
        </div>
        <div class="stat-item success">
            <i class="fas fa-calendar-alt"></i>
            <span class="stat-number"><?= $stats['quindici_giorni'] ?></span>
            <span class="stat-label">15 giorni</span>
            <?php if ($stats['quindici_giorni_fatturabili'] > 0): ?>
                <div style="font-size: 0.8em; margin-top: 2px; color: #28a745;">
                    <i class="fas fa-euro-sign"></i> <?= $stats['quindici_giorni_fatturabili'] ?> da fatturare
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pulsante Crea Task -->
        <div class="stat-item create-button">
            <button type="button" class="btn btn-primary" style="margin: 0; padding: 12px 20px; border-radius: 8px;" onclick="openTaskClientModal()">
                <i class="fas fa-plus"></i> Nuovo Task
            </button>
        </div>
    </div>
</div>

<!-- **NUOVO**: Layout a due colonne splitscreen -->
<div class="splitscreen-container">
    <!-- Colonna di sinistra: Task Attivi -->
    <div class="tasks-panel left-panel">
        <h3>
            <i class="fas fa-list"></i>
            Task Attivi (<?= count($tasks) ?>)
        </h3>
        
        <div class="tasks-list-container">
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
                            <h4 class="task-title"><?= htmlspecialchars($task_item['titolo'] ?? $task_item['descrizione']) ?></h4>
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
                            
                            <?php if ($task_item['fatturabile']): ?>
                                <div class="task-fatturabile">
                                    <i class="fas fa-euro-sign" style="color: #28a745;"></i>
                                    <span style="color: #28a745; font-weight: bold;">Da fatturare</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($task_item['nome_assegnato']): ?>
                                <div class="task-assegnato">
                                    <i class="fas fa-user" style="color: #6c757d;"></i>
                                    <span style="color: #6c757d;">Assegnato a: <?= htmlspecialchars($task_item['nome_assegnato']) ?></span>
                                </div>
                            <?php else: ?>
                                <div class="task-generale">
                                    <i class="fas fa-globe" style="color: #17a2b8;"></i>
                                    <span style="color: #17a2b8;">Task generale</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="task-actions">
                            <button class="btn btn-warning btn-xs" onclick="openTaskClientModal(<?= $task_item['id'] ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <?php if (!empty($task_item['fatturabile']) && $task_item['fatturabile'] == 1): ?>
                                <a href="?fatturato=<?= $task_item['id'] ?>" 
                                   class="btn btn-info btn-xs" 
                                   onclick="return confirm('Confermi che questo task è stato fatturato?')"
                                   title="Segna come fatturato">
                                    <i class="fas fa-euro-sign"></i>
                                </a>
                            <?php endif; ?>
                            
                            <a href="?completa=<?= $task_item['id'] ?>" 
                               class="btn btn-success btn-xs" 
                               onclick="return confirm('Sei sicuro di voler completare questo task?<?= !empty($task_item['ricorrenza']) ? ' (Task ricorrente: verrà aggiornato con nuova scadenza)' : ' (Task one-shot: verrà eliminato definitivamente)' ?>')"
                               title="Completa">
                                <i class="fas fa-check"></i>
                            </a>
                            
                            <a href="?elimina=<?= $task_item['id'] ?>" 
                               class="btn btn-danger btn-xs" 
                               onclick="return confirm('Sei sicuro di voler eliminare questo task?')"
                               title="Elimina">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Colonna di destra: Visualizza Task per Cliente -->
    <div class="client-view-section right-panel">
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
        
        <!-- Pulsante per attivare modalità copia -->
        <div id="copy_mode_button" style="display: none; margin-bottom: 15px; text-align: center;">
            <button type="button" class="btn btn-info btn-sm" onclick="toggleCopyMode()">
                <i class="fas fa-copy"></i> Abilita Copia Task
            </button>
        </div>
        
        <!-- Controlli per copia multipla (inizialmente nascosti) -->
        <div id="copy_controls" style="display: none; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 2px solid #e9ecef;">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">
                        <i class="fas fa-check-square"></i> Azioni Selezionati
                    </label>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="selectAllTasks()">
                            <i class="fas fa-check-double"></i> Seleziona Tutti
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                            <i class="fas fa-times"></i> Deseleziona
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="target_clients" class="form-label">
                        <i class="fas fa-users"></i> Copia Task Selezionati a:
                    </label>
                    <select id="target_clients" class="form-select" multiple size="3">
                        <?php foreach ($clienti as $cliente): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['Cognome_Ragione_sociale'] . ' ' . ($cliente['Nome'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Tieni premuto Ctrl/Cmd per selezionare più clienti</small>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-success" onclick="copySelectedTasks()">
                        <i class="fas fa-copy"></i> Copia Task
                    </button>
                    <div class="mt-2">
                        <span id="selected_count" class="badge bg-info">0 selezionati</span>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleCopyMode()">
                            <i class="fas fa-times"></i> Annulla
                        </button>
                    </div>
                </div>
            </div>
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
    const copyControls = document.getElementById('copy_controls');
    const copyModeButton = document.getElementById('copy_mode_button');
    
    // Reset stato
    content.innerHTML = '';
    noTasks.style.display = 'none';
    copyControls.style.display = 'none';
    copyModeButton.style.display = 'none';
    
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
                // Mostra il pulsante per attivare la modalità copia
                copyModeButton.style.display = 'block';
                
                // Filtra il cliente corrente dalla lista target
                const targetSelect = document.getElementById('target_clients');
                Array.from(targetSelect.options).forEach(option => {
                    option.style.display = option.value === clientId ? 'none' : 'block';
                });
                
                content.innerHTML = data.tasks.map(task => `
                    <div class="client-task-item ${task.ricorrente ? 'ricorrente' : ''} ${task.scaduto ? 'scaduto' : ''}">
                        <div class="task-checkbox" style="display: none;">
                            <input type="checkbox" class="form-check-input task-checkbox-input" 
                                   value="${task.id}" onchange="updateSelectedCount()" 
                                   data-task='${JSON.stringify(task)}'>
                        </div>
                        <div class="client-task-info">
                            <h5>${escapeHtml(task.descrizione)}</h5>
                            <div class="client-task-meta">
                                <i class="fas fa-calendar-alt"></i> Scadenza: ${task.scadenza_formatted}
                                ${task.scaduto ? '<span style="color: #dc3545; font-weight: bold;"> (Scaduto)</span>' : ''}
                                ${task.ricorrente ? `<br><i class="fas fa-sync-alt"></i> Ricorrente: ogni ${task.ricorrenza_text}` : ''}
                            </div>
                        </div>
                        <div class="task-actions">
                            <button class="btn btn-warning btn-sm" onclick="openTaskClientModal(${task.id})" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${task.fatturabile ? `<a href="?fatturato=${task.id}" class="btn btn-info btn-sm" 
                                                     onclick="return confirm('Confermi che questo task è stato fatturato?')" title="Segna come fatturato">
                                                     <i class="fas fa-euro-sign"></i>
                                                   </a>` : ''}
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
                
                updateSelectedCount();
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

// **NUOVO**: Funzione per attivare/disattivare la modalità copia
function toggleCopyMode() {
    const copyControls = document.getElementById('copy_controls');
    const copyModeButton = document.getElementById('copy_mode_button');
    const checkboxes = document.querySelectorAll('.task-checkbox');
    
    if (copyControls.style.display === 'none' || !copyControls.style.display) {
        // Attiva modalità copia
        copyControls.style.display = 'block';
        copyModeButton.style.display = 'none';
        
        // Mostra tutte le checkbox
        checkboxes.forEach(checkbox => {
            checkbox.style.display = 'flex';
        });
        
        // Cambia il layout degli item per fare spazio alle checkbox
        const taskItems = document.querySelectorAll('.client-task-item');
        taskItems.forEach(item => {
            item.style.display = 'flex';
            item.style.alignItems = 'center';
            item.style.gap = '15px';
        });
        
        updateSelectedCount();
    } else {
        // Disattiva modalità copia
        copyControls.style.display = 'none';
        copyModeButton.style.display = 'block';
        
        // Nascondi tutte le checkbox e deseleziona
        checkboxes.forEach(checkbox => {
            checkbox.style.display = 'none';
            const input = checkbox.querySelector('input');
            if (input) input.checked = false;
        });
        
        // Ripristina il layout originale degli item
        const taskItems = document.querySelectorAll('.client-task-item');
        taskItems.forEach(item => {
            item.style.display = 'block';
            item.style.alignItems = '';
            item.style.gap = '';
        });
    }
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

// **NUOVO**: Funzione per aprire il modale task (sostituita con popup)
// Rimossa - ora usa openTaskClientModal() per popup esterno

// Funzione per toggle ricorrenza
function toggleRicorrenza() {
    const isChecked = document.getElementById('is_ricorrente').checked;
    const ricorrenzaSection = document.getElementById('ricorrenza_section');
    const tipoRicorrenzaSection = document.getElementById('tipo_ricorrenza_section');
    
    if (isChecked) {
        ricorrenzaSection.style.display = 'block';
        tipoRicorrenzaSection.style.display = 'block';
        document.getElementById('ricorrenza').value = '1';
    } else {
        ricorrenzaSection.style.display = 'none';
        tipoRicorrenzaSection.style.display = 'none';
        document.getElementById('ricorrenza').value = '';
    }
}

// Funzione per submit del task
function submitTask() {
    const form = document.getElementById('taskForm');
    const formData = new FormData(form);
    
    // Validazione client-side
    const clienteId = formData.get('cliente_id');
    const descrizione = formData.get('descrizione');
    const scadenza = formData.get('scadenza');
    
    if (!clienteId) {
        alert('Seleziona un cliente');
        return;
    }
    
    if (!descrizione.trim()) {
        alert('Inserisci una descrizione');
        return;
    }
    
    if (!scadenza) {
        alert('Seleziona una data di scadenza');
        return;
    }
    
    // Se non è ricorrente, rimuovi i valori di ricorrenza
    if (!document.getElementById('is_ricorrente').checked) {
        formData.set('ricorrenza', '0');
    }
    
    // Mostra loading
    const submitBtn = document.querySelector('[onclick="submitTask()"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creazione...';
    submitBtn.disabled = true;
    
    // Submit via AJAX
    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(data => {
        console.log('Risposta server:', data);
        
        // Prova a parsare come JSON
        try {
            const jsonData = JSON.parse(data);
            if (jsonData.success) {
                alert(jsonData.message);
                location.reload();
                return;
            } else if (jsonData.error) {
                alert('Errore: ' + jsonData.error);
                return;
            }
        } catch (e) {
            // Non è JSON, continua con la gestione normale
        }
        
        // Se la risposta contiene un redirect o successo, ricarica la pagina
        if (data.includes('success') || data.includes('Task creato') || data.includes('</html>')) {
            location.reload();
        } else {
            // Mostra l'errore specifico
            if (data.trim()) {
                alert('Errore: ' + data);
            } else {
                alert('Errore nella creazione del task. Controlla i dati inseriti.');
            }
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore di comunicazione con il server: ' + error.message);
    })
    .finally(() => {
        // Ripristina il pulsante
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// **NUOVO**: Funzioni per gestione selezione multipla task
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.task-checkbox-input:checked');
    const count = checkboxes.length;
    document.getElementById('selected_count').textContent = `${count} selezionati`;
}

function selectAllTasks() {
    const checkboxes = document.querySelectorAll('.task-checkbox-input');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedCount();
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.task-checkbox-input');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedCount();
}

function copySelectedTasks() {
    const selectedCheckboxes = document.querySelectorAll('.task-checkbox-input:checked');
    const targetClientsSelect = document.getElementById('target_clients');
    const selectedClients = Array.from(targetClientsSelect.selectedOptions).map(option => option.value);
    
    if (selectedCheckboxes.length === 0) {
        alert('Seleziona almeno un task da copiare');
        return;
    }
    
    if (selectedClients.length === 0) {
        alert('Seleziona almeno un cliente di destinazione');
        return;
    }
    
    // Prepara i dati dei task selezionati
    const tasksData = Array.from(selectedCheckboxes).map(checkbox => {
        return JSON.parse(checkbox.dataset.task);
    });
    
    const confirmMessage = `Confermi di voler copiare ${tasksData.length} task a ${selectedClients.length} client${selectedClients.length > 1 ? 'i' : 'e'}?`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Mostra loading
    const copyBtn = document.querySelector('[onclick="copySelectedTasks()"]');
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Copiando...';
    copyBtn.disabled = true;
    
    // Prepara i dati per l'invio
    const copyData = {
        action: 'copy_tasks',
        tasks: tasksData,
        target_clients: selectedClients
    };
    
    // Invia richiesta
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(copyData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Task copiati con successo! ${data.copied_count} task creati.`);
            // Ricarica i task del cliente corrente
            loadClientTasks();
            // Disattiva la modalità copia
            toggleCopyMode();
        } else {
            alert('Errore nella copia dei task: ' + (data.error || 'Errore sconosciuto'));
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore di comunicazione con il server');
    })
    .finally(() => {
        copyBtn.innerHTML = originalText;
        copyBtn.disabled = false;
    });
}
</script>

<!-- Modal per task clienti -->
<div id="taskClientModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeTaskClientModal()">&times;</span>
        <iframe id="taskClientModalFrame" src=""></iframe>
    </div>
</div>

</main>

<script>
// Funzioni per gestire il modal dei task clienti
function openTaskClientModal(taskId = null) {
    const modal = document.getElementById('taskClientModal');
    const iframe = document.getElementById('taskClientModalFrame');
    
    if (taskId) {
        iframe.src = 'crea_task_clienti_popup.php?edit=' + taskId;
    } else {
        iframe.src = 'crea_task_clienti_popup.php';
    }
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeTaskClientModal() {
    const modal = document.getElementById('taskClientModal');
    const iframe = document.getElementById('taskClientModalFrame');
    
    modal.style.display = 'none';
    iframe.src = '';
    document.body.style.overflow = 'auto';
}

// Chiudi modal cliccando fuori - specifico per i nostri modali
document.addEventListener('click', function(event) {
    const taskModal = document.getElementById('taskModal');
    const taskClientModal = document.getElementById('taskClientModal');
    
    // Solo se il click è esattamente sul modal (non sui suoi elementi figli)
    if (event.target === taskModal && taskModal && taskModal.style.display === 'block') {
        // closeTaskModal(); // Funzione non definita, commentata per evitare errori
        taskModal.style.display = 'none';
    }
    if (event.target === taskClientModal && taskClientModal.style.display === 'block') {
        closeTaskClientModal();
    }
});

// Chiudi modal con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const taskModal = document.getElementById('taskModal');
        const taskClientModal = document.getElementById('taskClientModal');
        
        if (taskModal && taskModal.style.display === 'block') {
            // closeTaskModal(); // Funzione non definita, commentata per evitare errori
            taskModal.style.display = 'none';
        }
        if (taskClientModal && taskClientModal.style.display === 'block') {
            closeTaskClientModal();
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
