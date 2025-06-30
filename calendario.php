<?php
// Opzionale: verifica autenticazione PHP qui, se necessario
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Calendario Google</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
        }
        #calendar { 
            max-width: 900px; 
            margin: 40px auto; 
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 20px;
        }
        #authorize_button, #signout_button { 
            margin: 10px; 
            padding: 8px 18px;
            font-size: 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
        }
        #authorize_button { background: #4285f4; color: #fff; }
        #signout_button { background: #e53935; color: #fff; }
    </style>
</head>
<body>
    <header style="background: #4285f4; color: #fff; padding: 18px 0; text-align: center; border-radius:0 0 12px 12px;">
        <h1>Calendario Google</h1>
    </header>
    <div style="text-align:center;">
        <button id="authorize_button">Accedi con Google</button>
        <button id="signout_button" style="display:none;">Esci</button>
    </div>
    <div id="calendar"></div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- Google API JS -->
    <script src="https://apis.google.com/js/api.js"></script>
    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
    // ---- CONFIGURAZIONE ----
    const CLIENT_ID = '279692811539-4478tr130h1i9tqr3jqia4ldgpfd1upc.apps.googleusercontent.com'; // <-- Sostituisci con il tuo!
    const API_KEY = 'AIzaSyA1eT8KZFAPv9-6ictIJCdmU1f89CB0qgQ'; // <-- Sostituisci con il tuo!
    const SCOPES = "https://www.googleapis.com/auth/calendar";
    const DISCOVERY_DOC = "https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest";

    let calendar;
    let gapiInited = false;
    let tokenClient;

    document.getElementById('authorize_button').onclick = handleAuthClick;
    document.getElementById('signout_button').onclick = handleSignoutClick;

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
        gapiInited = true;
        maybeEnableCalendar();
    }

    // Inizializza il client OAuth
    window.onload = () => {
        gapiLoaded();
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
    };

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
        // Se già autenticato, mostra calendario e tasto logout
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