<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';
?>

<h2>📅 Calendario Google</h2>

<!-- Google Calendar API + FullCalendar.js integration -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<div id="calendar" style="max-width:900px;margin:30px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 8px #eee;"></div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://apis.google.com/js/api.js"></script>
<script>
const CLIENT_ID = '279692811539-4478tr130h1i9tqr3jqia4ldgpfd1upc.apps.googleusercontent.com';
const API_KEY = 'AIzaSyA1eT8KZFAPv9-6ictIJCdmU1f89CB0qgQ';
const DISCOVERY_DOCS = ["https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest"];
const SCOPES = "https://www.googleapis.com/auth/calendar";

// --- GAPI Client Setup ---
let tokenClient;
let gapiInited = false;
let gisInited = false;

function gapiLoaded() {
  gapi.load('client', initializeGapiClient);
}
async function initializeGapiClient() {
  await gapi.client.init({
    apiKey: API_KEY,
    discoveryDocs: DISCOVERY_DOCS,
  });
  gapiInited = true;
  maybeEnableCalendar();
}

function gisLoaded() {
  tokenClient = google.accounts.oauth2.initTokenClient({
    client_id: CLIENT_ID,
    scope: SCOPES,
    callback: '', // defined later
  });
  gisInited = true;
  maybeEnableCalendar();
}

function maybeEnableCalendar() {
  if (gapiInited && gisInited) {
    document.getElementById("calendar").innerHTML += '<button id="authorize_button" style="margin-bottom:16px;padding:7px 18px;">Accedi a Google Calendar</button>';
    document.getElementById("authorize_button").onclick = handleAuthClick;
  }
}

function handleAuthClick() {
  tokenClient.callback = async (resp) => {
    if (resp.error !== undefined) {
      throw(resp);
    }
    document.getElementById("authorize_button").remove();
    loadCalendar();
  };
  tokenClient.requestAccessToken({prompt: 'consent'});
}

window.gapiLoaded = gapiLoaded;
window.gisLoaded = gisLoaded;

// --- FullCalendar Setup ---
let calendar;
function loadCalendar() {
  calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    height: 700,
    locale: 'it',
    selectable: true,
    editable: true,
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: fetchEvents,
    select: function(info) {
      let titolo = prompt("Titolo appuntamento:");
      if (titolo) {
        addEvent(titolo, info.startStr, info.endStr);
      }
      calendar.unselect();
    },
    eventClick: function(info) {
      if (confirm("Vuoi eliminare questo appuntamento?")) {
        deleteEvent(info.event);
      }
    },
    eventDrop: function(info) {
      updateEvent(info.event);
    },
    eventResize: function(info) {
      updateEvent(info.event);
    }
  });
  calendar.render();
}

// --- Google Calendar API Functions ---

async function fetchEvents(fetchInfo, successCallback, failureCallback) {
  try {
    const response = await gapi.client.calendar.events.list({
      'calendarId': 'primary',
      'timeMin': fetchInfo.startStr,
      'timeMax': fetchInfo.endStr,
      'showDeleted': false,
      'singleEvents': true,
      'orderBy': 'startTime'
    });
    const events = response.result.items.map(ev => ({
      id: ev.id,
      title: ev.summary,
      start: ev.start.dateTime || ev.start.date,
      end: ev.end.dateTime || ev.end.date,
      editable: true
    }));
    successCallback(events);
  } catch (e) {
    failureCallback(e);
  }
}

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
    calendar.refetchEvents();
  } catch (e) {
    alert("Errore nell'aggiunta dell'appuntamento.");
  }
}

async function deleteEvent(event) {
  try {
    await gapi.client.calendar.events.delete({
      calendarId: 'primary',
      eventId: event.id
    });
    calendar.refetchEvents();
  } catch (e) {
    alert("Errore nell'eliminazione dell'appuntamento.");
  }
}

async function updateEvent(event) {
  try {
    const ev = {
      summary: event.title,
      start: { dateTime: event.startStr },
      end: { dateTime: event.endStr }
    };
    await gapi.client.calendar.events.update({
      calendarId: 'primary',
      eventId: event.id,
      resource: ev
    });
    calendar.refetchEvents();
  } catch (e) {
    alert("Errore nell'aggiornamento dell'appuntamento.");
  }
}
</script>
<!-- Carica le librerie GAPI -->
<script src="https://apis.google.com/js/api.js?onload=gapiLoaded" async defer></script>
<script src="https://accounts.google.com/gsi/client" onload="gisLoaded()" async defer></script>

</main>
</body>
</html>