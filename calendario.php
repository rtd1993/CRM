<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!in_array($_SESSION['user_role'], ['administrator', 'developer'])) {
    die("Accesso non autorizzato.");
}
?>

<div class="container mt-4">
    <h2>Calendario Google</h2>
    <div style="text-align:center; margin-bottom: 18px;">
        <button id="authorize_button">Accedi con Google</button>
        <button id="signout_button" style="display:none;">Esci</button>
    </div>
    <div id="calendar"></div>
</div>

<!-- FullCalendar JS & CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<!-- Google API JS -->
<script src="https://apis.google.com/js/api.js"></script>
<!-- Google Identity Services -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
const CLIENT_ID = '279692811539-4478tr130h1i9tqr3jqia4ldgpfd1upc.apps.googleusercontent.com'; // <-- Sostituisci con il tuo
const API_KEY = 'AIzaSyA1eT8KZFAPv9-6ictIJCdmU1f89CB0qgQ'; // <-- Sostituisci con il tuo
const SCOPES = "https://www.googleapis.com/auth/calendar";
const DISCOVERY_DOC = "https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest";

let calendar;
let tokenClient;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('authorize_button').onclick = handleAuthClick;
    document.getElementById('signout_button').onclick = handleSignoutClick;
    gapiLoaded();
});

function showHideButtons(isSignedIn) {
    document.getElementById('authorize_button').style.display = isSignedIn ? 'none' : '';
    document.getElementById('signout_button').style.display = isSignedIn ? '' : 'none';
}

// Carica la libreria Google API
function gapiLoaded() {
    gapi.load('client', initializeGapiClient);
}

async function initializeGapiClient() {
    await gapi.client.init({
        apiKey: API_KEY,
        discoveryDocs: [DISCOVERY_DOC],
    });
    tokenClient = google.accounts.oauth2.initTokenClient({
        client_id: CLIENT_ID,
        scope: SCOPES,
        callback: async (response) => {
            if (response.error) {
                alert("Errore autenticazione: " + response.error);
                return;
            }
            showHideButtons(true);
            if (calendar) {
                calendar.refetchEvents();
            } else {
                renderCalendar();
            }
        },
    });
    maybeEnableCalendar();
}

function handleAuthClick() {
    tokenClient.requestAccessToken({prompt: 'consent'});
}

function handleSignoutClick() {
    google.accounts.oauth2.revoke(gapi.client.getToken().access_token, () => {
        gapi.client.setToken('');
        showHideButtons(false);
        if (calendar) calendar.removeAllEvents();
    });
}

function maybeEnableCalendar() {
    if (gapi.client.getToken()) {
        showHideButtons(true);
        renderCalendar();
    } else {
        showHideButtons(false);
    }
}

function renderCalendar() {
    let calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'it',
        selectable: true,
        editable: false,
        events: fetchEvents,
        dateClick: function(info) {
            const titolo = prompt("Titolo appuntamento:");
            if (titolo) {
                const start = info.dateStr + "T10:00:00+02:00";
                const end = info.dateStr + "T11:00:00+02:00";
                addEvent(titolo, start, end);
            }
        }
    });
    calendar.render();
}

// Recupera eventi da Google Calendar
function fetchEvents(fetchInfo, successCallback, failureCallback) {
    gapi.client.calendar.events.list({
        calendarId: 'primary',
        timeMin: fetchInfo.startStr,
        timeMax: fetchInfo.endStr,
        showDeleted: false,
        singleEvents: true,
        orderBy: 'startTime'
    }).then(response => {
        const events = response.result.items.map(ev => ({
            id: ev.id,
            title: ev.summary,
            start: ev.start.dateTime || ev.start.date,
            end: ev.end.dateTime || ev.end.date
        }));
        successCallback(events);
    }).catch(err => {
        console.error("Errore in fetchEvents:", err);
        failureCallback(err);
    });
}

// Aggiungi evento su Google Calendar
async function addEvent(title, start, end) {
    try {
        const event = {
            summary: title,
            start: { dateTime: start },
            end: { dateTime: end }
        };
        await gapi.client.calendar.events.insert({
            calendarId: 'primary',
            resource: event
        });
        alert("Appuntamento inserito!");
        calendar.refetchEvents();
    } catch (e) {
        console.error('Errore dettagliato:', e);
        alert("Errore nell'aggiunta dell'appuntamento: " + (e.result?.error?.message || e.message || e));
    }
}
</script>
</body>
</html>