<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Calendario Google</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
        }
        .card {
            margin: 40px auto;
            max-width: 700px;
            border-radius: 1.5rem;
        }
        .fc-toolbar-title {
            font-size: 1.35rem;
            font-weight: 600;
        }
        .fc-button, .fc-button-primary {
            background: #0d6efd !important;
            border: none !important;
            color: #fff !important;
            border-radius: .5rem !important;
            padding: .5rem 1rem !important;
            margin: 0 .25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
        }
        .fc-button-active, .fc-button:hover {
            background: #0b5ed7 !important;
        }
        .fc-event {
            background-color: #0d6efd !important;
            border: none !important;
            color: #fff !important;
            border-radius: 0.4rem !important;
            font-size: 1rem !important;
        }
        .fc-event:hover {
            filter: brightness(0.92);
            box-shadow: 0 2px 12px rgba(33,37,41,.12);
            cursor: pointer;
        }
        #calendar {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow">
        <div class="card-body">
            <h2 class="text-center mb-4">Calendario Google</h2>
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Modale Bootstrap per aggiunta/modifica evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">Nuovo appuntamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">
          <input type="hidden" id="eventId">
          <div class="mb-3">
              <label for="eventTitle" class="form-label">Titolo</label>
              <input type="text" class="form-control" id="eventTitle" required>
          </div>
          <div class="mb-3">
              <label for="eventStart" class="form-label">Inizio</label>
              <input type="datetime-local" class="form-control" id="eventStart" required>
          </div>
          <div class="mb-3">
              <label for="eventEnd" class="form-label">Fine</label>
              <input type="datetime-local" class="form-control" id="eventEnd" required>
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="deleteEventBtn" class="btn btn-danger me-auto d-none">Elimina</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
        <button type="submit" class="btn btn-primary">Salva</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap 5 JS + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- FullCalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap Modal instance
    let eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
    let deleteBtn = document.getElementById('deleteEventBtn');
    let eventForm = document.getElementById('eventForm');
    let eventIdInput = document.getElementById('eventId');
    let eventTitleInput = document.getElementById('eventTitle');
    let eventStartInput = document.getElementById('eventStart');
    let eventEndInput = document.getElementById('eventEnd');
    let currentEvent = null; // For edit/delete

    // FullCalendar instance
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'it',
        selectable: true,
        editable: true,
        height: 500,
        events: '/calendar_events.php',
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        select: function(info) {
            // Reset modal fields for new event
            eventForm.reset();
            eventIdInput.value = '';
            eventTitleInput.value = '';
            // Default times: 09:00 - 10:00
            let start = info.startStr.substring(0,10) + 'T09:00';
            let end = info.startStr.substring(0,10) + 'T10:00';
            eventStartInput.value = start;
            eventEndInput.value = end;
            deleteBtn.classList.add('d-none');
            eventModal.show();
        },
        eventClick: function(info) {
            currentEvent = info.event;
            eventForm.reset();
            eventIdInput.value = currentEvent.id;
            eventTitleInput.value = currentEvent.title;
            // Format datetimes for input fields
            let start = currentEvent.start;
            let end = currentEvent.end ? currentEvent.end : currentEvent.start;
            eventStartInput.value = start.toISOString().slice(0,16);
            eventEndInput.value = end.toISOString().slice(0,16);
            deleteBtn.classList.remove('d-none');
            eventModal.show();
        }
    });
    calendar.render();

    // Submit form: crea/modifica evento
    eventForm.onsubmit = function(e) {
        e.preventDefault();
        let id = eventIdInput.value;
        let title = eventTitleInput.value.trim();
        let start = eventStartInput.value;
        let end = eventEndInput.value;
        if (!title || !start || !end) return;

        let payload = {
            title: title,
            start: start,
            end: end
        };

        fetch('/calendar_events.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Errore: ' + data.error);
            } else {
                eventModal.hide();
                calendar.refetchEvents();
            }
        })
        .catch(() => alert('Errore nella comunicazione col server.'));
    };

    // Elimina evento
    deleteBtn.onclick = function() {
        if (!eventIdInput.value) return;
        if (!confirm('Vuoi davvero eliminare questo evento?')) return;
        fetch('/calendar_events.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: eventIdInput.value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Errore: ' + data.error);
            } else {
                eventModal.hide();
                calendar.refetchEvents();
            }
        })
        .catch(() => alert('Errore nella comunicazione col server.'));
    };

    // Reset currentEvent all'apertura della modale
    document.getElementById('eventModal').addEventListener('hidden.bs.modal', () => {
        currentEvent = null;
    });
});
</script>
</body>
</html>