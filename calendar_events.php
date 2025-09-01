<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Verifica autenticazione per operazioni di scrittura
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Non autenticato']);
        exit;
    }
}

// DEBUG: Log everything for troubleshooting
function dbg_log($msg) {
    file_put_contents(__DIR__ . '/calendar_debug.log', date('c') . " " . $msg . "\n", FILE_APPEND);
}

// Funzione per forzare il formato ISO8601 richiesto da Google Calendar
function ensureIso8601($dt) {
    // Se il formato è YYYY-MM-DDTHH:MM, aggiunge :00+02:00 (Italia, ora legale)
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $dt)) {
        return $dt . ':00+02:00';
    }
    // Se il formato è già valido (contiene almeno i secondi e un offset/z), lo lascia invariato
    return $dt;
}

$calendarId = 'gestione.ascontabilmente@gmail.com';
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google-calendar.json');

try {
    $client = new Google_Client();
    $client->useApplicationDefaultCredentials();
    $client->addScope(Google_Service_Calendar::CALENDAR);
    $service = new Google_Service_Calendar($client);
} catch (Exception $e) {
    dbg_log("Google Client Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => "Google Client error: ".$e->getMessage()]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $timeMin = $_GET['start'] ?? null;
        $timeMax = $_GET['end'] ?? null;
        dbg_log("GET start=$timeMin end=$timeMax");
        $params = [
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ];
        if ($timeMin) $params['timeMin'] = $timeMin;
        if ($timeMax) $params['timeMax'] = $timeMax;
        try {
            $events = $service->events->listEvents($calendarId, $params);
            $output = [];
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
                ];
                
                // Applica il colore se disponibile nei metadati
                if ($meta && $meta['event_color']) {
                    $eventData['backgroundColor'] = $meta['event_color'];
                    $eventData['borderColor'] = $meta['event_color'];
                }
                
                $output[] = $eventData;
            }
            dbg_log("GET result: " . json_encode($output));
            echo json_encode($output);
        } catch (Exception $e) {
            dbg_log("GET Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'POST':
        $raw = file_get_contents('php://input');
        dbg_log("POST raw input: $raw");
        $input = json_decode($raw, true);
        dbg_log("POST parsed input: " . json_encode($input));
        if (!$input || !isset($input['title'], $input['start'], $input['end'])) {
            dbg_log("POST Error: Parametri mancanti o input non valido");
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti o input non valido', 'input' => $input]);
            exit;
        }
        $timeZone = 'Europe/Rome';

        // Fix formato data/ora
        $start = ensureIso8601($input['start']);
        $end = ensureIso8601($input['end']);

        $event = new Google_Service_Calendar_Event([
            'summary' => $input['title'],
            'start' => [
                'dateTime' => $start,
                'timeZone' => $timeZone
            ],
            'end' => [
                'dateTime' => $end,
                'timeZone' => $timeZone
            ]
        ]);
        
        // Imposta il colore se fornito
        if (isset($input['color']) && !empty($input['color'])) {
            // Google Calendar usa ID colore predefiniti, convertiamo il colore hex in ID
            $colorMap = [
                '#007BFF' => '1', // Blu
                '#28A745' => '11', // Verde
                '#DC3545' => '4', // Rosso
                '#FFC107' => '5', // Giallo
                '#6F42C1' => '3', // Viola
                '#FD7E14' => '6', // Arancione
                '#20C997' => '10', // Teal
                '#E83E8C' => '2', // Rosa
            ];
            
            $colorId = $colorMap[$input['color']] ?? '1'; // Default blu
            $event->setColorId($colorId);
        }
        
        dbg_log("POST event to insert: " . print_r($event, true));
        try {
            $createdEvent = $service->events->insert($calendarId, $event);
            
            // Salva i metadati dell'evento nel database locale
            if (isset($input['assignedTo']) && isset($input['color'])) {
                $stmt = $pdo->prepare("INSERT INTO calendar_events_meta (google_event_id, assigned_to_user_id, created_by_user_id, event_color) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $createdEvent->getId(),
                    $input['assignedTo'],
                    $_SESSION['user_id'],
                    $input['color']
                ]);
            }
            
            $response = [
                'id' => $createdEvent->getId(),
                'title' => $createdEvent->getSummary(),
                'start' => $createdEvent->start->dateTime,
                'end' => $createdEvent->end->dateTime
            ];
            dbg_log("POST event created: " . json_encode($response));
            echo json_encode($response);
        } catch (Exception $e) {
            dbg_log("POST Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        $raw = file_get_contents('php://input');
        dbg_log("PUT raw input: $raw");
        $input = json_decode($raw, true);
        dbg_log("PUT parsed input: " . json_encode($input));
        if (!$input || !isset($input['id'])) {
            dbg_log("PUT Error: ID mancante o input non valido");
            http_response_code(400);
            echo json_encode(['error' => 'ID mancante o input non valido', 'input' => $input]);
            exit;
        }
        
        // Se abbiamo solo id, start, end significa che stiamo spostando l'evento
        $isDateTimeUpdate = isset($input['start'], $input['end']) && !isset($input['title']);
        
        if (!$isDateTimeUpdate && !isset($input['title'], $input['start'], $input['end'])) {
            dbg_log("PUT Error: Parametri mancanti per aggiornamento completo");
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti per aggiornamento completo', 'input' => $input]);
            exit;
        }
        $timeZone = 'Europe/Rome';

        // Fix formato data/ora se fornite
        $start = isset($input['start']) ? ensureIso8601($input['start']) : null;
        $end = isset($input['end']) ? ensureIso8601($input['end']) : null;

        try {
            $event = $service->events->get($calendarId, $input['id']);
            
            // Aggiorna il titolo solo se fornito (aggiornamento completo)
            if (isset($input['title'])) {
                $event->setSummary($input['title']);
            }
            
            // Aggiorna sempre data/ora se fornite
            if (isset($input['start']) && isset($input['end'])) {
                $event->setStart(new Google_Service_Calendar_EventDateTime([
                    'dateTime' => $start,
                    'timeZone' => $timeZone
                ]));
                $event->setEnd(new Google_Service_Calendar_EventDateTime([
                    'dateTime' => $end,
                    'timeZone' => $timeZone
                ]));
            }
            dbg_log("PUT event to update: " . print_r($event, true));
            $updatedEvent = $service->events->update($calendarId, $event->getId(), $event);
            
            // Aggiorna i metadati nel database locale se forniti
            if (isset($input['assignedTo']) && isset($input['color'])) {
                // Usa INSERT ... ON DUPLICATE KEY UPDATE per gestire sia inserimento che aggiornamento
                $stmt = $pdo->prepare("
                    INSERT INTO calendar_events_meta (google_event_id, assigned_to_user_id, created_by_user_id, event_color) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    assigned_to_user_id = VALUES(assigned_to_user_id),
                    event_color = VALUES(event_color)
                ");
                $stmt->execute([
                    $input['id'],
                    $input['assignedTo'],
                    $_SESSION['user_id'],
                    $input['color']
                ]);
            }
            
            $response = [
                'id' => $updatedEvent->getId(),
                'title' => $updatedEvent->getSummary(),
                'start' => $updatedEvent->start->dateTime,
                'end' => $updatedEvent->end->dateTime
            ];
            dbg_log("PUT event updated: " . json_encode($response));
            echo json_encode($response);
        } catch (Exception $e) {
            dbg_log("PUT Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $raw = file_get_contents("php://input");
        dbg_log("DELETE raw input: $raw");
        $input = json_decode($raw, true);
        dbg_log("DELETE parsed input: " . json_encode($input));
        $eventId = $input['id'] ?? null;
        if ($eventId) {
            try {
                $service->events->delete($calendarId, $eventId);
                
                // Rimuovi anche i metadati dal database locale
                $stmt = $pdo->prepare("DELETE FROM calendar_events_meta WHERE google_event_id = ?");
                $stmt->execute([$eventId]);
                
                dbg_log("DELETE event deleted: $eventId");
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                dbg_log("DELETE Error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            dbg_log("DELETE Error: ID mancante");
            http_response_code(400);
            echo json_encode(['error' => 'ID mancante']);
        }
        break;

    default:
        dbg_log("Metodo non supportato: " . $_SERVER['REQUEST_METHOD']);
        http_response_code(405);
        echo json_encode(['error' => 'Metodo non supportato']);
        break;
}