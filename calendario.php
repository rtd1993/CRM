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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            --primary-color: #667eea;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-color: #f093fb;
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-color: #4ecdc4;
            --warning-color: #ffe66d;
            --danger-color: #ff6b6b;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        body {
            background: white;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .calendar-container {
            padding: 2rem 1rem;
        }

        .calendar-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .calendar-card:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-5px);
        }

        .calendar-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: 20px 20px 0 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .calendar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .calendar-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .calendar-header .subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .calendar-body {
            padding: 2rem;
        }

        /* FullCalendar Styling */
        .fc {
            font-family: inherit;
        }

        .fc-header-toolbar {
            margin-bottom: 1.5rem !important;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: var(--primary-color) !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .fc-button, .fc-button-primary {
            background: var(--primary-gradient) !important;
            border: none !important;
            color: white !important;
            border-radius: 10px !important;
            padding: 0.6rem 1.2rem !important;
            margin: 0 0.3rem !important;
            font-weight: 600 !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .fc-button:hover, .fc-button-active {
            background: var(--secondary-gradient) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }

        .fc-button:disabled {
            opacity: 0.6 !important;
            transform: none !important;
        }

        .fc-daygrid-day {
            transition: all 0.3s ease;
        }

        .fc-daygrid-day:hover {
            background: rgba(102, 126, 234, 0.05) !important;
        }

        .fc-day-today {
            background: rgba(102, 126, 234, 0.1) !important;
            border: 2px solid var(--primary-color) !important;
            border-radius: 8px !important;
        }

        .fc-daygrid-day-number {
            font-weight: 600;
            color: #333;
            padding: 0.3rem;
        }

        .fc-day-today .fc-daygrid-day-number {
            color: var(--primary-color) !important;
            font-weight: 700 !important;
        }

        .fc-event {
            background: var(--primary-gradient) !important;
            border: none !important;
            color: white !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            padding: 0.2rem 0.5rem !important;
            margin: 0.1rem 0 !important;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .fc-event:hover {
            background: var(--secondary-gradient) !important;
            transform: scale(1.02) !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4) !important;
            cursor: pointer !important;
            z-index: 10 !important;
        }

        .fc-event-title {
            font-weight: 600 !important;
        }

        .fc-event-time {
            font-weight: 500 !important;
            opacity: 0.9 !important;
        }

        /* Stili per i giorni della settimana */
        .fc-col-header-cell {
            background: rgba(102, 126, 234, 0.1) !important;
            border: none !important;
            padding: 0.8rem !important;
            font-weight: 700 !important;
            color: var(--primary-color) !important;
            text-transform: uppercase !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.5px !important;
        }

        .fc-scrollgrid {
            border: none !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
        }

        .fc-scrollgrid-sync-table {
            border: none !important;
        }

        .fc-daygrid-day-frame {
            border: 1px solid rgba(102, 126, 234, 0.1) !important;
            min-height: 80px !important;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .calendar-container {
                padding: 1rem 0.5rem;
            }
            
            .calendar-header h2 {
                font-size: 1.4rem;
            }
            
            .calendar-body {
                padding: 1rem;
            }
            
            .fc-header-toolbar {
                flex-direction: column !important;
                gap: 1rem !important;
            }
            
            .fc-toolbar-chunk {
                display: flex !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
            }
            
            .fc-button {
                font-size: 0.8rem !important;
                padding: 0.5rem 0.8rem !important;
            }
        }

        /* Loading animation */
        .fc-loading {
            position: relative;
        }

        .fc-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            margin: -15px 0 0 -15px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }

        /* Stili per i campi del form */
        .form-control:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
            transform: translateY(-2px);
        }

        .form-control:hover {
            border-color: rgba(102, 126, 234, 0.4);
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Animazioni per la modale */
        .modal.fade .modal-dialog {
            transform: translate(0, -50px);
            transition: transform 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: translate(0, 0);
        }

        /* Stili per i giorni del weekend */
        .fc-day-sat, .fc-day-sun {
            background-color: rgba(255, 107, 107, 0.05) !important;
        }

        /* Stili per eventi passati */
        .fc-event-past {
            opacity: 0.6;
            filter: grayscale(30%);
        }
    </style>
</head>
<body>
<div class="container-fluid calendar-container">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="calendar-card">
                <div class="calendar-header">
                    <h2>📅 Calendario Appuntamenti</h2>
                    <div class="subtitle">Organizza i tuoi impegni con stile</div>
                </div>
                <div class="calendar-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modale Bootstrap per aggiunta/modifica evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form id="eventForm" class="modal-content" style="border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
      <div class="modal-header" style="background: var(--primary-gradient); color: white; border: none; padding: 1.5rem;">
        <h5 class="modal-title" id="eventModalLabel" style="font-weight: 600; font-size: 1.3rem;">
          <i class="fas fa-calendar-plus me-2"></i>Nuovo Appuntamento
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body" style="padding: 2rem;">
          <input type="hidden" id="eventId">
          <div class="mb-4">
              <label for="eventTitle" class="form-label" style="font-weight: 600; color: var(--primary-color); margin-bottom: 0.5rem;">
                <i class="fas fa-tag me-2"></i>Titolo dell'evento
              </label>
              <input type="text" class="form-control" id="eventTitle" required 
                     style="border: 2px solid rgba(102, 126, 234, 0.2); border-radius: 12px; padding: 0.8rem; font-size: 1rem; transition: all 0.3s ease;"
                     placeholder="Inserisci il titolo...">
          </div>
          <div class="row">
              <div class="col-md-6 mb-4">
                  <label for="eventStart" class="form-label" style="font-weight: 600; color: var(--primary-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-play me-2"></i>Data e ora inizio
                  </label>
                  <input type="datetime-local" class="form-control" id="eventStart" required
                         style="border: 2px solid rgba(102, 126, 234, 0.2); border-radius: 12px; padding: 0.8rem; font-size: 1rem; transition: all 0.3s ease;">
              </div>
              <div class="col-md-6 mb-4">
                  <label for="eventEnd" class="form-label" style="font-weight: 600; color: var(--primary-color); margin-bottom: 0.5rem;">
                    <i class="fas fa-stop me-2"></i>Data e ora fine
                  </label>
                  <input type="datetime-local" class="form-control" id="eventEnd" required
                         style="border: 2px solid rgba(102, 126, 234, 0.2); border-radius: 12px; padding: 0.8rem; font-size: 1rem; transition: all 0.3s ease;">
              </div>
          </div>
      </div>
      <div class="modal-footer" style="border: none; padding: 1.5rem 2rem; background: rgba(102, 126, 234, 0.05);">
        <button type="button" id="deleteEventBtn" class="btn btn-danger me-auto d-none" 
                style="background: var(--danger-color); border: none; border-radius: 10px; padding: 0.6rem 1.2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);">
          <i class="fas fa-trash me-2"></i>Elimina
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                style="background: #6c757d; border: none; border-radius: 10px; padding: 0.6rem 1.2rem; font-weight: 600;">
          <i class="fas fa-times me-2"></i>Annulla
        </button>
        <button type="submit" class="btn btn-primary"
                style="background: var(--primary-gradient); border: none; border-radius: 10px; padding: 0.6rem 1.2rem; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);">
          <i class="fas fa-save me-2"></i>Salva
        </button>
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
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        buttonText: {
            today: 'Oggi',
            month: 'Mese',
            week: 'Settimana',
            day: 'Giorno',
            list: 'Lista'
        },
        dayHeaderFormat: { weekday: 'short' },
        select: function(info) {
            // Aggiorna il titolo della modale
            document.getElementById('eventModalLabel').innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Nuovo Appuntamento';
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
            
            // Anima l'input del titolo
            setTimeout(() => {
                eventTitleInput.focus();
            }, 300);
        },
        eventClick: function(info) {
            // Aggiorna il titolo della modale
            document.getElementById('eventModalLabel').innerHTML = '<i class="fas fa-calendar-edit me-2"></i>Modifica Appuntamento';
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
            
            // Anima l'input del titolo
            setTimeout(() => {
                eventTitleInput.focus();
                eventTitleInput.select();
            }, 300);
        },
        eventDidMount: function(info) {
            // Aggiungi tooltip agli eventi
            info.el.title = info.event.title + '\n' + 
                           info.event.start.toLocaleString('it-IT') + 
                           (info.event.end ? ' - ' + info.event.end.toLocaleString('it-IT') : '');
        },
        loading: function(bool) {
            if (bool) {
                document.getElementById('calendar').classList.add('fc-loading');
            } else {
                document.getElementById('calendar').classList.remove('fc-loading');
            }
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