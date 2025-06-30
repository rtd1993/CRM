<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Qui puoi limitare la visualizzazione se vuoi, ad esempio:
// if (!in_array($_SESSION['user_role'], ['administrator', 'developer'])) { die("Accesso non autorizzato."); }

?>

<div class="container mt-4">
    <h2>Calendario Google</h2>
    <div id="calendar"></div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'it',
        selectable: true,
        editable: true,
        events: {
            url: '/calendar_events.php',
            method: 'GET'
        },
        dateClick: function(info) {
            var titolo = prompt("Titolo appuntamento:");
            if (titolo) {
                var start = info.dateStr + "T10:00:00";
                var end = info.dateStr + "T11:00:00";
                fetch('/calendar_events.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ title: titolo, start: start, end: end })
                })
                .then(res => res.json())
                .then(() => calendar.refetchEvents());
            }
        },
        eventClick: function(info) {
            if (confirm("Vuoi cancellare questo appuntamento?")) {
                fetch('/calendar_events.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(info.event.id)
                })
                .then(res => res.json())
                .then(() => calendar.refetchEvents());
            }
        }
    });
    calendar.render();
});
</script>