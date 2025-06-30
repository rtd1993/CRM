<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<div class="container mt-4" style="max-width: 600px;">
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
        height: 500, // Puoi regolare l'altezza
        events: {
            url: '/calendar_events.php',
            method: 'GET'
        },
        dateClick: function(info) {
            // Prompt per titolo e orario
            var titolo = prompt("Titolo appuntamento:");
            if (!titolo) return;
            var startTime = prompt("Orario di inizio (HH:MM, es: 14:30):", "10:00");
            if (!startTime) return;
            var endTime = prompt("Orario di fine (HH:MM, es: 15:00):", "11:00");
            if (!endTime) return;

            // Concatena data e orario
            var start = info.dateStr + "T" + startTime + ":00";
            var end = info.dateStr + "T" + endTime + ":00";
            fetch('/calendar_events.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: titolo, start: start, end: end })
            })
            .then(res => res.json())
            .then(() => calendar.refetchEvents());
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
<style>
/* Restringi ancora di più il calendario se vuoi */
#calendar {
    max-width: 500px;
    margin: 0 auto;
}
</style>