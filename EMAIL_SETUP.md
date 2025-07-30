# Sistema Email CRM - Guida alla Configurazione

## Panoramica
Il sistema email del CRM permette di:
- Creare template email personalizzabili
- Inviare email multiple ai clienti selezionati
- Tracciare la cronologia degli invii
- Sostituire automaticamente variabili cliente nei messaggi

## Configurazione Gmail SMTP

### 1. Preparazione Account Gmail
1. Vai su [Google Account Security](https://myaccount.google.com/security)
2. Attiva la **"Verifica in due passaggi"** se non già attiva
3. Nella sezione "Verifica in due passaggi", clicca su **"Password per le app"**
4. Seleziona "App personalizzata" e inserisci "CRM AS Contabilmente"
5. **Copia la password generata** (16 caratteri senza spazi)

### 2. Configurazione Server
1. Apri il file `includes/email_config.php`
2. Trova la riga: `define('SMTP_PASSWORD', '');`
3. Inserisci la password app tra le virgolette: `define('SMTP_PASSWORD', 'tuapasswordapp');`
4. Salva il file

### 3. Verifica Configurazione
- Vai alla pagina "Email" del CRM
- Se configurato correttamente, non vedrai più l'avviso giallo
- Il pulsante "Invia Email" sarà abilitato

## Utilizzo del Sistema

### Creazione Template
1. Vai a **"Gestisci Template"** dalla pagina Email
2. Inserisci nome, oggetto e corpo del template
3. Usa le variabili disponibili:
   - `{nome_cliente}` - Nome del cliente
   - `{cognome_cliente}` - Cognome/Ragione sociale
   - `{ragione_sociale}` - Ragione sociale
   - `{codice_fiscale}` - Codice fiscale
   - `{partita_iva}` - Partita IVA

### Invio Email
1. Seleziona un template dalla lista
2. Scegli i clienti destinatari (con checkbox o "Seleziona tutti")
3. Modifica oggetto e corpo se necessario
4. Clicca "Invia Email"

### Monitoraggio
- **Cronologia Email**: Visualizza tutti gli invii con filtri per cliente, template, stato e data
- **Statistiche**: Mostra totali invii, successi, errori, clienti raggiunti

## Template Predefiniti
Il sistema include 4 template di esempio:
- **Comunicazione generale**: Per comunicazioni standard
- **Scadenza documenti**: Promemoria scadenze
- **Richiesta documenti**: Richiesta documentazione
- **Auguri festività**: Messaggi augurali

## Funzionalità Avanzate

### Log Automatico
Ogni invio viene registrato con:
- Dati cliente e template utilizzato
- Oggetto e corpo email finale (con variabili sostituite)
- Stato (inviata/fallita) e eventuali errori
- Timestamp invio

### Sicurezza
- Autenticazione SMTP con TLS
- Password app dedicata (non la password Gmail principale)
- Log dettagliato per audit trail

### Limitazioni
- Gmail: 500 email/giorno per account gratuito
- Timeout SMTP: 30 secondi per invio
- Solo testo semplice (no HTML per semplicità)

## Risoluzione Problemi

### "Configurazione email incompleta"
- Verifica che SMTP_PASSWORD sia impostata correttamente
- Controlla che la password app sia valida (16 caratteri)

### "Errore nell'invio"
- Verifica connessione internet
- Controlla che l'account Gmail sia attivo
- Verifica limite giornaliero Gmail non superato

### Email non ricevute
- Controlla cartelle spam/promozioni destinatario
- Verifica indirizzo email cliente corretto
- Consulta log cronologia per dettagli errore

## Support
Per problemi tecnici:
1. Consulta la cronologia email per errori specifici
2. Verifica configurazione SMTP
3. Contatta l'amministratore sistema
