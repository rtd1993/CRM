# Sistema di Archiviazione Automatica Chat Globale

## Panoramica
Il sistema archivia automaticamente i messaggi della chat globale più vecchi di 60 giorni ogni 1° del mese alle 02:30.

## File Coinvolti

### Script Principale
- **`archivio_chat_globale.sh`** - Script bash per l'archiviazione automatica
- **`gestione_archivio_chat.php`** - Interfaccia web per gestione manuale

### Directory
- **`/local_drive/ASContabilmente/Archivio_chat/`** - Cartella degli archivi
- **`/logs/chat_archivio.log`** - Log delle operazioni

## Funzionalità

### Archiviazione Automatica
- **Frequenza**: Ogni 1° del mese alle 02:30
- **Criteri**: Messaggi più vecchi di 60 giorni
- **Fonte**: Tabella `chat_messaggi` (solo chat globale)
- **Formato file**: `chat_MM_YYYY.txt` (es: `chat_06_2025.txt`)

### Processo di Archiviazione
1. **Conta** i messaggi da archiviare
2. **Esporta** i messaggi in formato tabella leggibile
3. **Elimina** i messaggi dal database
4. **Ottimizza** la tabella per recuperare spazio
5. **Registra** l'operazione nel log

### Gestione Manuale
- Accesso tramite: `gestione_archivio_chat.php`
- Permessi: Solo admin e developer
- Statistiche in tempo reale
- Esecuzione manuale dell'archiviazione
- Lista dei file di archivio con download

## Configurazione Cron
```bash
# Archiviazione automatica chat globale - ogni 1° del mese alle 02:30
30 2 1 * * /var/www/CRM/archivio_chat_globale.sh
```

## Formato File Archivio
Ogni file contiene:
- **Intestazione** con informazioni sull'archivio
- **Messaggi** in formato tabella con:
  - Data/Ora del messaggio
  - Nome utente
  - Testo del messaggio
- **Separatori** tra batch di archiviazione

## Sicurezza
- ✅ Solo messaggi della chat globale (non delle pratiche)
- ✅ Backup completo prima dell'eliminazione
- ✅ Log dettagliato di tutte le operazioni
- ✅ Accesso web limitato a admin/developer
- ✅ Ottimizzazione database automatica

## Monitoraggio
- **Log file**: `/var/www/CRM/logs/chat_archivio.log`
- **Statistiche web**: `gestione_archivio_chat.php`
- **Cron output**: Inviato via email se configurato

## Troubleshooting

### Problema: Script non trova i messaggi
**Soluzione**: Verificare che la tabella `chat_messaggi` esista e contenga dati

### Problema: Errore permessi file
**Soluzione**: Verificare permessi su directory `/local_drive/ASContabilmente/Archivio_chat/`

### Problema: Database connection error
**Soluzione**: Verificare credenziali database in `archivio_chat_globale.sh`

## Manutenzione
- I file di archivio possono essere compressi periodicamente per risparmiare spazio
- I log possono essere ruotati mensilmente
- Monitorare dimensione database dopo ogni archiviazione

## Test
Per testare il sistema:
```bash
# Esecuzione manuale script
/var/www/CRM/archivio_chat_globale.sh

# Verifica log
tail -f /var/www/CRM/logs/chat_archivio.log

# Controllo file archivio
ls -la /var/www/CRM/local_drive/ASContabilmente/Archivio_chat/
```

---
*Sistema implementato il 01/08/2025*
*Ultima modifica: 01/08/2025*
