<?php
// Assicurati che queste dipendenze siano risolte:
// - Composer installato
// - google/apiclient installato: composer require google/apiclient:^2.0

require_once __DIR__ . '/vendor/autoload.php';

// Imposta QUI il percorso del file JSON della service account
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google-calendar.json');

// --- CONFIGURAZIONE ---
// Email del calendario Google (puoi usare anche l'ID del calendario)
// Di solito è l'indirizzo email dell'account Google che possiede il calendario, oppure l'ID del calendario condiviso
$calendarId = 'gestione.ascontabilmente@gmail.com'; // <-- Sostituisci con l'email/ID del tuo calendario

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope(Google_Service_Calendar::CALENDAR);

$service = new Google_Service_Calendar($client);

// --- VISUALIZZA EVENTI (GET) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $timeMin = $_GET['start'] ?? null;
    $timeMax = $_GET['end'] ?? null;
    $params = [
        'singleEvents' => true,
        'orderBy' => 'startTime'
    ];
    if ($timeMin) $params['timeMin'] = $timeMin;
    if ($timeMax) $params['timeMax'] = $timeMax;

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
    header('Content-Type: application/json');
    echo json_encode($output);
    exit;
}

// --- AGGIUNGI EVENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['title'], $input['start'], $input['end'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Parametri mancanti']);
        exit;
    }
    $event = new Google_Service_Calendar_Event([
        'summary' => $input['title'],
        'start' => ['dateTime' => $input['start']],
        'end' => ['dateTime' => $input['end']]
    ]);
    try {
        $createdEvent = $service->events->insert($calendarId, $event);
        header('Content-Type: application/json');
        echo json_encode([
            'id' => $createdEvent->getId(),
            'title' => $createdEvent->getSummary(),
            'start' => $createdEvent->start->dateTime,
            'end' => $createdEvent->end->dateTime
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// --- ELIMINA EVENTO (DELETE) ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $eventId = $_DELETE['id'] ?? null;
    if ($eventId) {
        try {
            $service->events->delete($calendarId, $eventId);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'ID mancante']);
    }
    exit;
}

// --- Se la richiesta non è gestita ---
http_response_code(405);
echo json_encode(['error' => 'Metodo non supportato']);
exit;
?>