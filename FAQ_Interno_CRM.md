# ❓ FAQ Interno - Sistema CRM AS Contabilmente

> **Domande frequenti e soluzioni rapide per gli utenti del sistema**

---

## 📖 Indice FAQ

1. [🔐 Accesso e Login](#-accesso-e-login)
2. [👥 Gestione Clienti](#-gestione-clienti)
3. [📧 Sistema Email](#-sistema-email)
4. [📅 Calendario e Task](#-calendario-e-task)
5. [📁 Drive e Documenti](#-drive-e-documenti)
6. [📊 Pratiche ENEA/Conto Termico](#-pratiche-eneaconto-termico)
7. [⚠️ Problemi Comuni](#️-problemi-comuni)
8. [🔧 Configurazioni](#-configurazioni)

---

## 🔐 Accesso e Login

### **❓ Ho dimenticato la password, come faccio?**
**Risposta:** Contatta l'amministratore del sistema. Solo gli utenti con ruolo Admin o Developer possono resettare le password degli altri utenti.

### **❓ Il sistema dice "sessione scaduta", cosa significa?**
**Risposta:** Per motivi di sicurezza, dopo un periodo di inattività il sistema disconnette automaticamente l'utente. Basta rifare il login per continuare a lavorare.

### **❓ Posso cambiare la mia password da solo?**
**Risposta:** Sì! Vai nella sezione "Profilo" (icona utente in alto a destra) e compila i campi "Nuova Password" e "Conferma Password", poi clicca "Salva Modifiche".

### **❓ A cosa servono i ruoli (Developer, Admin, Impiegato, Guest)?**
**Risposta:**
- **Developer**: accesso completo, può modificare tutto
- **Admin**: gestione utenti e configurazioni principali
- **Impiegato**: accesso operativo completo ai dati clienti
- **Guest**: accesso limitato, solo visualizzazione

### **❓ Come configuro le notifiche Telegram?**
**Risposta:**
1. Cerca il bot del CRM su Telegram o chiedi il link all'admin
2. Avvia una conversazione con il bot (`/start`)
3. Il bot ti darà il tuo Chat ID
4. Inserisci questo numero nella sezione Profilo
5. Clicca "Salva Modifiche" per testare la connessione

---

## 👥 Gestione Clienti

### **❓ Posso eliminare un cliente per errore?**
**Risposta:** Il sistema chiede sempre conferma prima di eliminare. Inoltre, non puoi eliminare clienti che hanno pratiche ENEA, Conto Termico o richieste attive.

### **❓ Come funziona la ricerca clienti?**
**Risposta:** Puoi cercare per:
- Nome o cognome/ragione sociale
- Codice fiscale (anche parziale)
- Email o PEC
- Telefono
- Codice ditta

### **❓ Cosa sono gli "alert documenti in scadenza"?**
**Risposta:** Il sistema controlla automaticamente le date di scadenza di:
- Carta d'identità
- PEC (certificato)
- Altri documenti con data inserita

I clienti con documenti in scadenza entro 30 giorni vengono evidenziati con icone rosse/gialle.

### **❓ Come vengono create le cartelle dei clienti?**
**Risposta:** Quando crei un nuovo cliente, il sistema genera automaticamente una cartella con formato `ID_COGNOME.NOME` nel drive aziendale. Esempio: `125_ROSSI.MARIO`.

### **❓ Posso modificare i dati di un cliente dopo averlo creato?**
**Risposta:** Sì, clicca sull'icona "modifica" nella lista clienti o vai nella pagina info del cliente e usa il pulsante "Modifica".

---

## 📧 Sistema Email

### **❓ Posso inviare email a più clienti contemporaneamente?**
**Risposta:** Sì! Nella sezione "Invio Email":
1. Scegli il template
2. Personalizza il messaggio
3. Seleziona i clienti destinatari (checkbox multipli)
4. Clicca "Invia Email"

### **❓ Come funzionano le variabili nei template email?**
**Risposta:** Puoi usare queste variabili che verranno sostituite automaticamente:
- `{nome_cliente}` → Nome del cliente
- `{cognome_cliente}` → Cognome/Ragione sociale
- `{codice_fiscale}` → Codice fiscale
- `{partita_iva}` → Partita IVA

### **❓ Come vedo se le email sono state inviate correttamente?**
**Risposta:** Vai in "Cronologia Email" per vedere:
- Stato degli invii (inviate/fallite)
- Dettagli errori eventuali
- Storico completo delle comunicazioni

### **❓ Posso creare i miei template email personalizzati?**
**Risposta:** Solo gli Admin possono creare/modificare template email. Chiedi all'amministratore se hai bisogno di nuovi template.

---

## 📅 Calendario e Task

### **❓ Gli eventi del calendario si sincronizzano con Google?**
**Risposta:** Sì, il sistema è integrato con Google Calendar aziendale. Gli eventi vengono sincronizzati automaticamente in entrambe le direzioni.

### **❓ Come funzionano i task ricorrenti?**
**Risposta:** Quando completi un task ricorrente:
1. Il task attuale viene eliminato
2. Viene creato automaticamente un nuovo task identico
3. La nuova scadenza è calcolata aggiungendo X giorni dalla scadenza originale

### **❓ Cosa sono i "task fatturabili"?**
**Risposta:** Sono task legati ad attività che verranno fatturate ai clienti. Quando li completi, puoi segnarli come "fatturato" per tenere traccia del billing.

### **❓ Posso assegnare task ad altri colleghi?**
**Risposta:** Sì, nel form di creazione task c'è il campo "Assegna a" dove puoi scegliere l'utente responsabile.

### **❓ Come vedo solo i miei task?**
**Risposta:** Gli utenti "Impiegato" vedono automaticamente solo i task non assegnati o assegnati a loro. Admin e Developer vedono tutti i task.

---

## 📁 Drive e Documenti

### **❓ Quali tipi di file posso caricare?**
**Risposta:** Formati supportati:
- **Documenti**: PDF, DOC, DOCX, XLS, XLSX, TXT
- **Immagini**: JPG, JPEG, PNG, GIF, BMP
- **Archivi**: ZIP, RAR (con limitazioni di dimensione)

### **❓ Qual è la dimensione massima per i file?**
**Risposta:** Generalmente 10MB per file singolo, ma può variare in base alla configurazione del server.

### **❓ Come cerco un file in tutte le cartelle?**
**Risposta:** Usa la barra di ricerca in alto nella pagina Drive. La ricerca funziona sui nomi file in tutte le cartelle.

### **❓ Posso scaricare più file insieme?**
**Risposta:** Sì, seleziona i file che ti interessano (checkbox) e usa il pulsante "Download selezionati" per creare un archivio ZIP.

### **❓ Le cartelle clienti si creano automaticamente?**
**Risposta:** Sì, quando crei un nuovo cliente viene generata automaticamente la sua cartella nel formato `ID_COGNOME.NOME`.

---

## 📊 Pratiche ENEA/Conto Termico

### **❓ Come tengo traccia dei documenti per le pratiche ENEA?**
**Risposta:** Ogni pratica ENEA ha una checklist di 8 documenti standard:
- Copia fattura fornitore
- Schede tecniche
- Visura catastale
- Firma atto notorio
- Firma delega Agenzia Entrate
- Firma delega ENEA
- Consenso privacy
- Eventuale atto notorio

Spunta i documenti man mano che arrivano.

### **❓ Cosa significa la percentuale di completamento nelle pratiche?**
**Risposta:** È calcolata automaticamente in base ai documenti marcati come "OK" rispetto al totale richiesto. 100% = tutti i documenti ricevuti.

### **❓ Posso stampare la documentazione delle pratiche?**
**Risposta:** Sì, usa i pulsanti "Stampa" nelle pagine ENEA e Conto Termico per generare PDF professionali con tutti i dati.

### **❓ Come organizzo i documenti delle pratiche?**
**Risposta:** Il sistema crea automaticamente sottocartelle nella cartella del cliente:
- `ENEA_2025_DESCRIZIONE` per pratiche ENEA
- `CT_NUMERO_PRATICA` per Conto Termico

### **❓ Qual è la differenza tra gli stati del Conto Termico?**
**Risposta:**
- **Bozza**: pratica in preparazione
- **Presentata**: inviata al GSE
- **In Istruttoria**: in valutazione GSE  
- **Accettata**: approvata per il contributo
- **Respinta**: non ammessa al contributo
- **Liquidata**: contributo erogato

---

## ⚠️ Problemi Comuni

### **❓ La pagina non si carica o è lenta**
**Soluzioni:**
1. Ricarica la pagina (F5 o Ctrl+F5)
2. Svuota cache del browser (Ctrl+Shift+Del)
3. Controlla connessione internet
4. Prova con un altro browser
5. Se il problema persiste, contatta l'admin

### **❓ Le email non vengono inviate**
**Soluzioni:**
1. Verifica che il server SMTP sia configurato (chiedi all'admin)
2. Controlla che gli indirizzi email siano corretti
3. Verifica nella cronologia email se ci sono messaggi di errore
4. Alcuni provider (Gmail, Outlook) possono bloccare email automatiche

### **❓ Gli eventi del calendario non si sincronizzano**
**Soluzioni:**
1. Verifica connessione internet
2. Controlla che l'integrazione Google sia attiva (chiedi all'admin)
3. Ricarica la pagina calendario
4. Gli eventi potrebbero impiegare qualche minuto per sincronizzarsi

### **❓ Non riesco a caricare file nel drive**
**Soluzioni:**
1. Verifica dimensione file (max 10MB di solito)
2. Controlla formato file (deve essere supportato)
3. Prova a ricaricare la pagina
4. Controlla spazio disponibile sul server

### **❓ I task non si salvano**
**Soluzioni:**
1. Verifica che tutti i campi obbligatori siano compilati
2. Controlla che la data di scadenza sia valida
3. Ricarica la pagina e riprova
4. Se assegni ad altri, verifica che l'utente esista

---

## 🔧 Configurazioni

### **❓ Come cambio il colore della mia interfaccia?**
**Risposta:** Nella sezione Profilo c'è un campo "Colore Personalizzato" dove puoi inserire un codice colore (es: #3498db) o scegliere dal selettore.

### **❓ Posso personalizzare la dashboard?**
**Risposta:** La dashboard mostra automaticamente le informazioni più rilevanti (appuntamenti, scadenze, task). La personalizzazione non è al momento disponibile per gli utenti base.

### **❓ Come funzionano le notifiche del sistema?**
**Risposta:** Il sistema può inviare notifiche tramite:
- **Telegram** (se configurato nel profilo)
- **Email** (per alcune operazioni)
- **Messaggi in-app** (nella chat interna)

### **❓ Posso creare backup dei miei dati?**
**Risposta:** Il sistema fa backup automatici regolari. Se hai necessità specifiche di export dati, contatta l'amministratore.

### **❓ Come accedo al sistema da mobile?**
**Risposta:** Il CRM è responsive e funziona da browser mobile. Non serve installare app, basta aprire l'indirizzo del CRM dal browser dello smartphone.

---

## 📞 Supporto Rapido

### **🆘 In caso di urgenze:**
1. **Problema tecnico grave**: contatta immediatamente l'amministratore
2. **Dati persi**: non preoccuparti, ci sono backup automatici
3. **Sistema non accessibile**: verifica connessione, poi contatta l'admin
4. **Errore sconosciuto**: fai screenshot e invialo all'admin con descrizione

### **📧 Per richieste non urgenti:**
- Nuove funzionalità
- Miglioramenti interfaccia  
- Training aggiuntivo
- Template email personalizzati

### **📱 Contatti Admin:**
- Email interna: rtd1993@gmail.com
- Telefono: 3484212857

---

> **Suggerimento**: Tieni sempre questo FAQ a portata di mano! La maggior parte dei problemi comuni ha una soluzione rapida qui descritta.

**Ultimo aggiornamento**: Settembre 2025  
**Versione FAQ**: 1.0