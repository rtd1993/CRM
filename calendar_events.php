<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$calendarId = 'gestione.ascontabilmente@gmail.com';
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/google-calendar.json');

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope(Google_Service_Calendar::CALENDAR);
$service = new Google_Service_Calendar($client);

header('Content-Type: application/json');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $timeMin = $_GET['start'] ?? null;
        $timeMax = $_GET['end'] ?? null;
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
            echo json_encode($output);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['title'], $input['start'], $input['end'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti']);
            exit;
        }
        $timeZone = 'Europe/Rome';
        $event = new Google_Service_Calendar_Event([
            'summary' => $input['title'],
            'start' => [
                'dateTime' => $input['start'],
                'timeZone' => $timeZone
            ],
            'end' => [
                'dateTime' => $input['end'],
                'timeZone' => $timeZone
            ]
        ]);
        try {
            $createdEvent = $service->events->insert($calendarId, $event);
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
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'], $input['title'], $input['start'], $input['end'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Parametri mancanti']);
            exit;
        }
        $timeZone = 'Europe/Rome';
        try {
            $event = $service->events->get($calendarId, $input['id']);
            $event->setSummary($input['title']);
            $event->setStart(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $input['start'],
                'timeZone' => $timeZone
            ]));
            $event->setEnd(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $input['end'],
                'timeZone' => $timeZone
            ]));
            $updatedEvent = $service->events->update($calendarId, $event->getId(), $event);
            echo json_encode([
                'id' => $updatedEvent->getId(),
                'title' => $updatedEvent->getSummary(),
                'start' => $updatedEvent->start->dateTime,
                'end' => $updatedEvent->end->dateTime
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        $eventId = $input['id'] ?? null;
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
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Metodo non supportato']);
        break;
}