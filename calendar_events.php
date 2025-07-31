<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/vendor/autoload.php';

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
                $output[] = [
                    'id' => $event->getId(),
                    'title' => $event->getSummary(),
                    'start' => $event->start->dateTime ?: $event->start->date,
                    'end' => $event->end->dateTime ?: $event->end->date,
                ];
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
        dbg_log("POST event to insert: " . print_r($event, true));
        try {
            $createdEvent = $service->events->insert($calendarId, $event);
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
        if (!$input || !isset($input['id'], $input['title'], $input['start'], $input['end'])) {
            dbg_log("PUT Error: Parametri mancanti o input non valido");
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti o input non valido', 'input' => $input]);
            exit;
        }
        $timeZone = 'Europe/Rome';

        // Fix formato data/ora
        $start = ensureIso8601($input['start']);
        $end = ensureIso8601($input['end']);

        try {
            $event = $service->events->get($calendarId, $input['id']);
            $event->setSummary($input['title']);
            $event->setStart(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $start,
                'timeZone' => $timeZone
            ]));
            $event->setEnd(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $end,
                'timeZone' => $timeZone
            ]));
            dbg_log("PUT event to update: " . print_r($event, true));
            $updatedEvent = $service->events->update($calendarId, $event->getId(), $event);
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