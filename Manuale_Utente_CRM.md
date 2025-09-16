# 📋 Manuale Utente - Sistema CRM AS Contabilmente

> **Guida completa alle funzionalità disponibili per l'utente finale**

---

## 📖 Indice delle Funzionalità

1. [🔐 Sistema di Accesso e Profilo](#-sistema-di-accesso-e-profilo)
2. [🏠 Dashboard Principale](#-dashboard-principale)
3. [👥 Gestione Clienti](#-gestione-clienti)
4. [📋 Gestione Procedure](#-gestione-procedure)
5. [📞 Gestione Richieste](#-gestione-richieste)
6. [📧 Sistema Email e Comunicazioni](#-sistema-email-e-comunicazioni)
7. [📊 Documenti ENEA e Conto Termico](#-documenti-enea-e-conto-termico)
8. [📅 Task e Calendario](#-task-e-calendario)
9. [📁 Drive e Gestione Documenti](#-drive-e-gestione-documenti)
10. [⚙️ Funzionalità Aggiuntive](#️-funzionalità-aggiuntive)

---

## 🔐 Sistema di Accesso e Profilo

### 🚪 **Pagina di Login (`login.php`)**
**Cosa puoi fare:**
- **Accedere al sistema** inserendo email e password
- **Autenticazione sicura** con password criptata
- **Controllo automatico sessioni** per la sicurezza
- **Reindirizzamento automatico** alla dashboard dopo il login

**Come utilizzarla:**
1. Inserisci la tua email aziendale nel campo "Email"
2. Inserisci la password fornita dall'amministratore
3. Clicca su "Accedi" per entrare nel sistema
4. In caso di errore, viene mostrato un messaggio di credenziali non valide

### 👤 **Gestione Profilo**
**Cosa puoi fare:**
- **Visualizzare le tue informazioni** personali (nome, email, ruolo)
- **Modificare la password** del tuo account
- **Configurare notifiche Telegram** inserendo il Chat ID
- **Testare connessione Telegram** per verificare che funzioni
- **Vedere il tuo ruolo** nel sistema (Developer, Admin, Impiegato, Guest)

**Come utilizzarla:**
1. Dalla pagina profilo puoi vedere tutti i tuoi dati
2. Per cambiare password: inserisci la nuova password e confermala
3. Per Telegram: inserisci il Chat ID e clicca "Salva Modifiche"
4. Il sistema invierà un messaggio di test per confermare la configurazione

---

## 🏠 Dashboard Principale

### 📊 **Dashboard**
**Cosa puoi vedere:**
- **Appuntamenti di oggi** integrati con Google Calendar
- **Appuntamenti della settimana** con vista completa
- **Task in scadenza** organizzati per priorità
- **Scadenze documenti clienti** (carte d'identità, PEC, ecc.)
- **Statistiche generali** del sistema

**Funzionalità disponibili:**
1. **Visualizzazione eventi calendario** con colori personalizzati
2. **Lista task urgenti** con scadenze evidenziate
3. **Alert documenti in scadenza** entro 30 giorni
4. **Navigazione rapida** verso le sezioni del CRM
5. **Aggiornamento automatico** delle informazioni

---

## 👥 Gestione Clienti

### 📋 **Lista Clienti**
**Cosa puoi fare:**
- **Visualizzare tutti i clienti** in una tabella organizzata
- **Cercare clienti** per nome, codice fiscale, email, telefono
- **Filtrare per documenti in scadenza** entro 30 giorni
- **Ordinare** i risultati per diversi campi
- **Azioni di massa** su più clienti selezionati
- **Vedere alert** per documenti in scadenza (carta d'identità, PEC)

**Come utilizzarla:**
1. **Ricerca veloce**: scrivi nella barra di ricerca nome o dati del cliente
2. **Filtri avanzati**: usa i dropdown per filtrare per stato documenti
3. **Selezione multipla**: spunta i checkbox per operazioni di massa
4. **Ordinamento**: clicca sui titoli delle colonne per ordinare
5. **Alert visivi**: i clienti con documenti in scadenza hanno icone di avvertimento

### ➕ **Creazione Nuovo Cliente**
**Cosa puoi fare:**
- **Creare un nuovo profilo cliente** completo
- **Inserire tutti i dati anagrafici** (nome, cognome, CF, P.IVA)
- **Aggiungere informazioni fiscali** (codici ATECO, INPS, INAIL)
- **Configurare dati di contatto** (telefono, email, PEC)
- **Impostare sedi** (legale, operativa, residenza)
- **Documenti identità** con date di scadenza
- **Note personalizzate** salvate in file di testo
- **Creazione automatica cartella** per il cliente

**Sezioni disponibili:**
1. **Dati Generali**: informazioni base e codici identificativi
2. **Soci e Sedi**: amministratori e indirizzi
3. **Dati Anagrafici**: nascita, cittadinanza, residenza
4. **Documenti**: carta d'identità e validità
5. **Attività**: descrizione e codici ATECO
6. **Codici Fiscali**: INPS, INAIL, Casse
7. **Contabilità**: regime IVA e liquidazione
8. **Contatti**: telefono, email, PEC con scadenze
9. **Digital**: SDI, credenziali, link cartelle

**Dopo la creazione:**
- Viene creata automaticamente una **cartella personalizzata** nel drive
- Il sistema genera un **file README** con i dati principali
- Le **note vengono salvate** in un file di testo dedicato
- **Reindirizzamento automatico** alla pagina info del cliente

### 📝 **Informazioni Cliente**
**Cosa puoi vedere:**
- **Tutti i dati del cliente** organizzati per sezioni
- **Link diretto alla cartella** documenti del cliente
- **Note salvate** nel file di testo associato
- **Dati raggruppati logicamente** (anagrafica, contatti, sedi, documenti, fiscali)
- **Interfaccia pulita** con evidenziazione dei campi importanti

**Navigazione:**
1. I dati sono **organizzati in sezioni logiche** per facilità di lettura
2. **Link rapidi** per modifiche e operazioni
3. **Accesso diretto** alla cartella documenti del cliente
4. **Visualizzazione ottimizzata** per stampa e consultazione

### ✏️ **Modifica Cliente**
**Cosa puoi fare:**
- **Aggiornare qualsiasi dato** del cliente esistente
- **Modificare scadenze documenti** con nuove date
- **Aggiungere o cambiare note** nel file di testo
- **Aggiornare informazioni fiscali** e codici
- **Cambiare dati di contatto** (email, PEC, telefono)
- **Validazione automatica** dei dati inseriti

**Caratteristiche:**
- **Pre-compilazione** di tutti i campi esistenti
- **Validazione in tempo reale** (email, date, codici fiscali)
- **Backup automatico** delle modifiche
- **Log delle modifiche** per tracciabilità

### 🗑️ **Eliminazione Cliente**
**Cosa succede:**
- **Conferma obbligatoria** prima dell'eliminazione
- **Backup automatico** dei dati prima della rimozione
- **Mantenimento cartella** documenti (opzionale)
- **Log dell'operazione** per audit
- **Impossibile eliminare** clienti con procedure/pratiche attive

---

## 📋 Gestione Procedure

### 📋 **Lista Procedure**
**Cosa puoi fare:**
- **Visualizzare tutte le procedure** aziendali attive
- **Cercare procedure** per denominazione o contenuto
- **Filtrare per data** di validità
- **Creare nuove procedure** con il pulsante "+"
- **Modificare procedure esistenti** cliccando sull'icona modifica
- **Eliminare procedure obsolete** (con conferma)
- **Gestire allegati** (upload, download, eliminazione)

**Caratteristiche:**
1. **Vista tabellare** con tutte le informazioni principali
2. **Anteprima del contenuto** con testo troncato
3. **Indicatori visivi** per procedure con allegati
4. **Ordinamento** per data di creazione o validità
5. **Azioni rapide** per ogni procedura

### ➕ **Creazione Procedura**
**Cosa puoi fare:**
- **Inserire denominazione** univoca per la procedura
- **Definire data di validità** da quando è attiva
- **Scrivere il testo completo** della procedura
- **Allegare documenti** di supporto (PDF, DOC, XLS, immagini)
- **Validazione automatica** dei dati inseriti
- **Controllo duplicati** per evitare procedure con stesso nome

**Processo creazione:**
1. Compila il **nome della procedura** (obbligatorio e univoco)
2. Inserisci la **data di validità** (da quando è attiva)
3. Scrivi il **testo completo** nel campo descrizione
4. **Allega file** se necessario (massimo 10MB)
5. Clicca **"Crea Procedura"** per salvare

### ✏️ **Modifica Procedura**
**Cosa puoi modificare:**
- **Aggiornare denominazione** (verificando unicità)
- **Cambiare data di validità** per nuove versioni
- **Modificare il contenuto** testuale della procedura
- **Sostituire o eliminare allegati** esistenti
- **Aggiungere nuovi allegati** alla versione aggiornata

**Gestione allegati:**
1. **Elimina allegato esistente**: checkbox per rimuoverlo
2. **Carica nuovo allegato**: sostituisce quello precedente
3. **Mantieni allegato**: non fare nulla per conservarlo
4. **Controllo versioni**: ogni modifica viene tracciata

---

## 📞 Gestione Richieste

### 📋 **Lista Richieste**
**Cosa puoi vedere:**
- **Tutte le richieste** dei clienti in tabella
- **Stati delle richieste** (aperta, in lavorazione, completata, chiusa)
- **Informazioni di contatto** per ogni richiesta
- **Tipo di attività** (gratuita o a pagamento)
- **Importi** quando si tratta di attività fatturabili
- **Data di creazione** e priorità

**Azioni disponibili:**
1. **Creare nuova richiesta** con il pulsante "+"
2. **Modificare richieste esistenti** cliccando sull'icona modifica
3. **Stampare richieste** per documentazione
4. **Eliminare richieste** obsolete o errate
5. **Filtrare e cercare** tra le richieste

### ➕ **Nuova Richiesta**
**Cosa puoi inserire:**
- **Denominazione del cliente** o ente richiedente
- **Data della richiesta** (default oggi)
- **Descrizione dettagliata** del servizio richiesto
- **Dati di contatto** (telefono, email)
- **Tipo di attività** (gratuita o a pagamento)
- **Importo previsto** se l'attività è fatturabile
- **Stato iniziale** della richiesta
- **Note aggiuntive** per il team

**Stati richiesta:**
- 🔴 **Aperta**: appena ricevuta, da valutare
- 🟡 **In Lavorazione**: presa in carico dal team
- 🟢 **Completata**: servizio erogato
- ⚫ **Chiusa**: archiviata definitivamente

### 🖨️ **Stampa Richiesta**
**Cosa genera:**
- **Documento PDF formattato** con tutti i dati
- **Logo aziendale** e intestazione
- **Informazioni cliente** complete
- **Dettagli del servizio** richiesto
- **Condizioni economiche** se applicabili
- **Spazio per firme** e timbri

---

## 📧 Sistema Email e Comunicazioni

### 📧 **Invio Email**
**Cosa puoi fare:**
- **Selezionare clienti** dalla lista per invio multiplo
- **Scegliere template** pre-impostati per le email
- **Personalizzare oggetto** e contenuto prima dell'invio
- **Sostituzioni automatiche** con dati cliente ({nome_cliente}, {codice_fiscale}, ecc.)
- **Anteprima del messaggio** prima dell'invio
- **Invio di massa** a clienti multipli
- **Log automatico** di tutte le email inviate

**Processo invio:**
1. **Seleziona template** email dal dropdown
2. **Personalizza oggetto** e contenuto (supporta variabili)
3. **Seleziona destinatari** dalla lista clienti
4. **Anteprima messaggio** con dati sostituiti
5. **Invia email** e ricevi conferma per ognuna

**Variabili disponibili:**
- `{nome_cliente}` - Nome del cliente
- `{cognome_cliente}` - Cognome/Ragione sociale
- `{ragione_sociale}` - Ragione sociale aziendale  
- `{codice_fiscale}` - Codice fiscale
- `{partita_iva}` - Partita IVA

### 📋 **Template Email**
**Cosa puoi gestire:**
- **Creare template** riutilizzabili per comunicazioni standard
- **Modificare template esistenti** per aggiornamenti
- **Eliminare template obsoleti** non più utilizzati
- **Variabili dinamiche** per personalizzazione automatica
- **Categorie di template** (fatturazione, scadenze, comunicazioni)

**Tipi di template comuni:**
1. **Solleciti di pagamento** con dati personalizzati
2. **Comunicazioni di scadenza** documenti
3. **Auguri stagionali** per i clienti
4. **Aggiornamenti normativi** e fiscali
5. **Convocazioni** per appuntamenti

### 📊 **Cronologia Email**
**Cosa puoi consultare:**
- **Storico completo** di tutte le email inviate
- **Stato delle consegne** (inviate, fallite, in coda)
- **Dettagli destinatari** per ogni invio
- **Filtri per periodo** e tipo di comunicazione
- **Export dei dati** per reportistica
- **Statistiche invii** e tassi di successo

### 💬 **Sistema Chat)**
**Funzionalità:**
- **Chat generale** tra utenti del sistema
- **Chat per singolo cliente** con storico appunti
- **Notifiche in tempo reale** per nuovi messaggi
- **Archiviazione automatica** delle conversazioni
- **Ricerca nei messaggi** per trovare informazioni
- **Widget integrato** nella dashboard per accesso rapido

---

## 📊 Documenti ENEA e Conto Termico

### 🏠 **Gestione ENEA**
**Cosa puoi gestire:**
- **Lista completa** di tutte le pratiche ENEA
- **Stati dei documenti** richiesti per ogni pratica
- **Percentuale di completamento** calcolata automaticamente
- **Filtri di ricerca** per cliente, descrizione, stato
- **Creazione nuove pratiche** con wizard guidato
- **Tracciamento documenti** necessari per la pratica

**Documenti tracciati:**
1. 📄 **Copia fattura fornitore** (OK/NO/PENDING)
2. 📋 **Schede tecniche** prodotti installati
3. 🏠 **Visura catastale** dell'immobile
4. ✍️ **Firma atto notorio** del richiedente
5. 📝 **Firma delega Agenzia Entrate** 
6. 🏛️ **Firma delega ENEA**
7. ✅ **Consenso privacy** trattamento dati
8. 📊 **Eventuale atto notorio** aggiuntivo

**Visualizzazione:**
- **Barra di progresso** per ogni pratica con percentuale completamento
- **Colori identificativi** per stato (verde=OK, rosso=mancante, giallo=in attesa)
- **Filtri rapidi** per visualizzare solo pratiche incomplete
- **Ricerca avanzata** per cliente o descrizione lavori

### ➕ **Nuova Pratica ENEA**
**Cosa puoi inserire:**
- **Selezionare il cliente** dalla lista esistente
- **Anno fiscale** di riferimento per la detrazione
- **Prima telefonata** data e ora del primo contatto
- **Data richiesta documenti** al cliente
- **Descrizione dettagliata** dell'intervento effettuato
- **Checklist documenti** da spuntare man mano che arrivano
- **Note aggiuntive** per il team di lavoro

### 🔥 **Gestione Conto Termico**
**Cosa puoi gestire:**
- **Pratiche incentivi** per efficienza energetica
- **Stati delle domande** (bozza, presentata, istruttoria, accettata, liquidata)
- **Importi ammissibili** e contributi previsti
- **Numeri pratica** GSE assegnati
- **Date di presentazione** e scadenze
- **Tipi di intervento** effettuati

**Stati pratica:**
- 📝 **Bozza**: pratica in preparazione
- 📤 **Presentata**: inviata al GSE
- ⏳ **In Istruttoria**: in valutazione GSE
- ✅ **Accettata**: approvata per il contributo
- ❌ **Respinta**: non ammessa al contributo  
- 💰 **Liquidata**: contributo erogato

### 🖨️ **Stampe Documenti**
**Documenti stampabili:**
- **Stampa pratica ENEA** - documento completo con tutti i dati
- **Stampa Conto Termico** - riepilogo pratica incentivi
- **Format PDF professionale** con logo e intestazione aziendale
- **Tutti i dati organizzati** per sezioni logiche
- **Pronto per firma** e protocollazione

---

## 📅 Task e Calendario

### ✅ **Gestione Task**
**Cosa puoi fare:**
- **Visualizzare tutti i task** assegnati al team
- **Creare nuovi task** con scadenze e priorità
- **Assegnare task** a utenti specifici
- **Marcare task completati** con log automatico
- **Task ricorrenti** che si rigenerano automaticamente
- **Task fatturabili** con tracciamento per billing

**Tipi di task:**
1. **Task semplici**: attività una tantum con scadenza
2. **Task ricorrenti**: si rigenerano ogni X giorni dopo completamento
3. **Task fatturabili**: tracciati per fatturazione ai clienti
4. **Task urgenti**: evidenziati quando vicini alla scadenza

**Stati e azioni:**
- ✅ **Completa**: segna il task come terminato (task semplici vengono eliminati)
- 🔄 **Ricorrenti**: dopo completamento viene creato nuovo task con scadenza +X giorni
- 💰 **Fattura**: segna task fatturabile come "da fatturare"
- 🗑️ **Elimina**: rimuove definitivamente il task

### 👥 **Task Clienti**
**Funzionalità specifiche:**
- **Task collegati a clienti** specifici con link diretto
- **Popup di creazione rapida** dalla pagina cliente
- **Storico task** per ogni cliente
- **Notifiche automatiche** per scadenze task cliente
- **Integrazione** con cartelle documenti del cliente

### 📅 **Calendario Google**
**Cosa puoi vedere:**
- **Calendario FullCalendar** integrato con Google Calendar
- **Eventi aziendali** sincronizzati automaticamente
- **Codici colore** per diversi tipi di appuntamenti
- **Vista mensile/settimanale/giornaliera** selezionabile
- **Creazione eventi** direttamente dal calendario
- **Appuntamenti clienti** con link ai profili

**Integrazione Google Calendar:**
- **Sincronizzazione bidirezionale** con account aziendale
- **Colori personalizzabili** per categorie eventi
- **Notifiche automatiche** per appuntamenti imminenti
- **Condivisione calendario** tra team membri
- **Backup locale** degli eventi importanti

### 📊 **Eventi Calendario**
**Gestione eventi:**
- **Creare nuovi eventi** con dettagli completi
- **Modificare eventi esistenti** e sincronizzare
- **Eliminare eventi** obsoleti o annullati
- **Assegnare eventi** a utenti specifici
- **Categorie eventi** con colori identificativi
- **Ricorrenze** per appuntamenti fissi

---

## 📁 Drive e Gestione Documenti

### 🗂️ **Drive Aziendale**
**Cosa puoi fare:**
- **Navigare** nella struttura cartelle aziendali
- **Visualizzare file** con anteprima (immagini, PDF)
- **Caricare documenti** trascinandoli o selezionandoli
- **Creare cartelle** per organizzare documenti
- **Cercare file** per nome in tutte le cartelle
- **Ordinare contenuti** per nome, dimensione, data modifica
- **Operazioni batch** su file multipli

**Struttura organizzazione:**
1. **Cartelle clienti**: formato `ID_COGNOME.NOME`
2. **Cartelle ENEA**: `ENEA_ANNO_DESCRIZIONE` dentro cartella cliente  
3. **Cartelle Conto Termico**: `CT_PRATICA` dentro cartella cliente
4. **Cartelle procedure**: documenti aziendali e template
5. **Backup automatici**: copie di sicurezza documenti importanti

**Funzionalità avanzate:**
- **Ricerca globale** in tutte le cartelle
- **Filtri per tipo file** (PDF, immagini, documenti Office)
- **Statistiche utilizzo** spazio e numero file
- **Breadcrumb navigation** per navigazione facile
- **Icone tipo file** per identificazione rapida

### 📤 **Upload File**
**Modalità di caricamento:**
- **Drag & drop** diretto nella cartella
- **Selezione multipla** file dal computer
- **Upload in background** con barra di progresso
- **Validazione formato** file consentiti
- **Controllo dimensioni** massime per file
- **Antivirus scan** automatico (se configurato)

**Formati supportati:**
- 📄 **Documenti**: PDF, DOC, DOCX, XLS, XLSX, TXT
- 🖼️ **Immagini**: JPG, JPEG, PNG, GIF, BMP
- 🗜️ **Archivi**: ZIP, RAR (con limitazioni)
- 📊 **Altri**: CSV, XML per importazioni dati

### 📥 **Download File**
**Funzionalità:**
- **Download singolo** file con un clic
- **Download multiplo** come archivio ZIP
- **Anteprima nel browser** per file supportati  
- **Log dei download** per audit e sicurezza
- **Controllo permessi** per accesso file riservati
- **Link condivisione** temporanei per esterni

---

## ⚙️ Funzionalità Aggiuntive

### 📞 **Contatti Utili**
**Cosa trovi:**
- **Email enti pubblici** (INPS, INAIL, Agenzia Entrate)
- **Telefoni Camera di Commercio** e ordini professionali
- **Contatti notai** e commercialisti convenzionati
- **CAF e patronati** per pratiche delegate
- **Uffici comunali** e provinciali
- **Contatti tecnici** per assistenza specialistica

**Organizzazione:**
- **Email istituzionali** divise per categoria
- **Numeri telefono** con orari di disponibilità
- **Link diretti** per avviare chiamate o email
- **Note descrittive** per ogni contatto
- **Aggiornamento periodico** dei recapiti

### 👥 **Gestione Utenti**

#### **Per Utenti Base:**
- **Visualizzare il proprio profilo** con tutti i dati
- **Modificare la propria password** per sicurezza
- **Aggiornare Chat ID Telegram** per notifiche
- **Cambiare colore personalizzato** nell'interfaccia
- **Vedere informazioni altri utenti** (sola lettura)

#### **Per Amministratori e Developer:**
- **Gestire tutti gli utenti** del sistema
- **Creare nuovi account** utente
- **Modificare ruoli e permessi** (Developer > Admin > Impiegato > Guest)
- **Resettare password** di altri utenti
- **Eliminare account** non più necessari
- **Assegnare colori personalizzati** per identificazione

**Ruoli sistema:**
- 👨‍💻 **Developer**: accesso completo a tutto il sistema
- 👨‍💼 **Admin**: gestione utenti e configurazioni principali  
- 👩‍💼 **Impiegato**: accesso operativo completo ai dati
- 👤 **Guest**: accesso limitato in sola lettura

### 🔗 **Link Utili**
**Collegamenti rapidi:**
- **Portali fiscali** (Cassetto fiscale, F24 online)
- **Siti INPS/INAIL** per consulenze
- **Agenzia Entrate** per normative
- **Camera di Commercio** per visure
- **Banche dati pubbliche** per ricerche
- **Software gestionali** utilizzati dall'azienda

### 📊 **Informazioni Sistema**
**Cosa puoi consultare:**
- **Versione del CRM** e ultima data aggiornamento
- **Statistiche utilizzo** (clienti, procedure, task attivi)
- **Stato connessioni** servizi esterni (Google Calendar, email)
- **Log delle attività** recenti del sistema
- **Spazio disco utilizzato** per documenti
- **Performance sistema** e tempi di risposta

---

## 🔄 Flussi di Lavoro Comuni

### 📋 **Nuovo Cliente → Pratica ENEA**
1. **Crea nuovo cliente** da `crea_cliente.php` con tutti i dati
2. **Viene generata automaticamente** la cartella documenti
3. **Crea pratica ENEA** da `crea_enea.php` selezionando il cliente
4. **Traccia documenti** richiesti spuntando la checklist
5. **Carica documenti** nella sottocartella ENEA del cliente
6. **Completa pratica** quando tutti i documenti sono OK
7. **Stampa documentazione** finale con `stampa_enea.php`

### 📧 **Comunicazione di Massa Clienti**
1. **Crea o modifica template** email per la comunicazione
2. **Vai in Invio Email** e seleziona il template
3. **Personalizza oggetto e contenuto** con variabili dinamiche
4. **Seleziona clienti destinatari** dalla lista (multipli)
5. **Anteprima messaggio** per verifica
6. **Invia email** e verifica risultati nella cronologia

### ⏰ **Gestione Scadenze e Promemoria**
1. **Dashboard mostra automaticamente** documenti in scadenza
2. **Crea task ricorrenti** per controlli periodici
3. **Usa calendario** per appuntamenti di rinnovo documenti  
4. **Email automatiche** ai clienti per scadenze imminenti
5. **Aggiorna date** nei profili clienti dopo i rinnovi

---

## 🎨 Personalizzazione Interface

### 🎨 **Temi e Colori**
- **Colori personalizzati** per ogni utente nell'interfaccia
- **Temi responsive** che si adattano a desktop, tablet, mobile
- **Dark mode** disponibile per utilizzo notturno
- **Icone intuitive** per tutte le funzionalità principali

### 📱 **Accessibilità Mobile**
- **Interface responsive** ottimizzata per smartphone
- **Touch-friendly** per operazioni su dispositivi mobili  
- **Caricamento veloce** anche su connessioni lente
- **Funzionalità offline** per consultazione dati essenziali

### ⌨️ **Scorciatoie Tastiera**
- **Ctrl+S**: salvataggio rapido in tutte le form
- **Ctrl+F**: ricerca veloce nelle pagine con tabelle
- **Esc**: chiusura popup e modal di dialogo
- **Tab**: navigazione tra campi form ottimizzata

---

## 📞 Supporto e Assistenza

### 🆘 **In Caso di Problemi**
1. **Verifica connessione internet** per servizi cloud
2. **Ricarica la pagina** per risolvere problemi temporanei
3. **Controlla i log** nella sezione Info per errori
4. **Contatta l'amministratore** per problemi tecnici
5. **Backup automatici** proteggono i dati importanti

### 📚 **Risorse Aggiuntive**
- **Video tutorial** per le funzionalità principali (se disponibili)
- **FAQ interno** per domande ricorrenti
- **Changelog** per seguire aggiornamenti e novità
- **Documentazione tecnica** per amministratori sistema

---

> **Nota**: Questo manuale è aggiornato alla versione corrente del CRM. Le funzionalità possono essere aggiornate e migliorate nel tempo. Per supporto specifico o richieste di nuove funzionalità, contatta l'amministratore del sistema.

**Ultimo aggiornamento**: Settembre 2025  
**Versione manuale**: 1.0