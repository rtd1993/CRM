# 🕐 **CRON JOBS AUTOMATICI CRM**
*Aggiornato: 14 Settembre 2025*

---

## 📋 **PROGRAMMI AUTOMATICI ATTIVI**

### 🔄 **OGNI 2 MINUTI**
```bash
*/2 * * * * /usr/bin/php /var/www/CRM/api/cleanup_offline_users.php
```
**Funzione:** Pulizia utenti offline inattivi >5 minuti  
**Log:** `/var/www/CRM/logs/session_heartbeat.log`  
**Status:** ✅ ATTIVO

---

### 🌙 **OGNI NOTTE ORE 02:00**
```bash
0 2 * * * /var/www/CRM/optimize_database_nightly.sh
```
**Funzione:** 
- Ottimizzazione tabelle database
- Pulizia indici frammentati  
- Analisi statistiche tabelle
- Backup struttura database
- Pulizia utenti offline >10 minuti

**Log:** `/var/www/CRM/logs/database_optimization_nightly.log`  
**Status:** ✅ ATTIVO

---

### 📅 **1° OGNI MESE ORE 03:00**
```bash
0 3 1 * * /usr/bin/php /var/www/CRM/cleanup_old_chats.php
```
**Funzione:**
- Elimina messaggi chat più vecchi di 1 anno
- Rimuove conversazioni vuote (tranne globale)  
- Elimina partecipanti orfani
- Ottimizza tabelle chat dopo pulizia

**Log:** `/var/www/CRM/logs/chat_cleanup_monthly.log`  
**Status:** ✅ ATTIVO

---

### 🧹 **1° OGNI MESE ORE 04:00**
```bash
0 4 1 * * /var/www/CRM/cleanup_logs_monthly.sh
```
**Funzione:**
- Elimina log CRM più vecchi di 30 giorni
- Pulisce log Apache/MySQL vecchi  
- Rimuove log sistema scaduti
- Elimina sessioni PHP scadute
- Rotazione log grandi (>100MB)

**Log:** `/var/www/CRM/logs/log_cleanup_monthly.log`  
**Status:** ✅ ATTIVO

---

### ☁️ **OGNI 6 ORE**
```bash
0 */6 * * * /var/www/CRM/sync_rclone.sh
```
**Funzione:**
- Sincronizzazione Google Drive
- Backup files locali su cloud
- Mantiene storico modifiche

**Log:** `/var/www/CRM/logs/rclone_sync.log`  
**Status:** ✅ ATTIVO

---

## 📊 **TIMELINE ESECUZIONE**

### **Giornaliera:**
- `00:00, 06:00, 12:00, 18:00` → Sync Google Drive
- `02:00` → Ottimizzazione Database  
- `Ogni 2 min` → Cleanup utenti offline

### **Mensile (1° del mese):**
- `03:00` → Pulizia chat vecchie (>1 anno)
- `04:00` → Pulizia log (>30 giorni)

---

## 📁 **DIRECTORY LOG**
```
/var/www/CRM/logs/
├── chat_cleanup_monthly.log      # Pulizia chat mensile
├── log_cleanup_monthly.log       # Pulizia log mensile  
├── database_optimization_nightly.log  # Ottimizzazione DB
├── rclone_sync.log               # Sync Google Drive
├── session_heartbeat.log         # Cleanup sessioni
└── chat_errors.log               # Errori sistema chat
```

---

## 🔍 **MONITORAGGIO**

### **Controllo Status Cron:**
```bash
systemctl status cron
crontab -l
```

### **Controllo Log Recenti:**
```bash
tail -f /var/www/CRM/logs/database_optimization_nightly.log
tail -f /var/www/CRM/logs/chat_cleanup_monthly.log
tail -f /var/www/CRM/logs/log_cleanup_monthly.log
```

### **Test Manuali:**
```bash
# Test pulizia utenti offline
php /var/www/CRM/api/cleanup_offline_users.php

# Test ottimizzazione database  
/var/www/CRM/optimize_database_nightly.sh

# Test pulizia chat
php /var/www/CRM/cleanup_old_chats.php

# Test pulizia log
/var/www/CRM/cleanup_logs_monthly.sh

# Test sync Google Drive
/var/www/CRM/sync_rclone.sh
```

---

## ⚙️ **CONFIGURAZIONE PERSONALIZZATA**

### **Modificare Frequenza:**
```bash
crontab -e
```

### **Disabilitare Temporaneamente:**
```bash
# Commenta la riga con #
# */2 * * * * /usr/bin/php /var/www/CRM/api/cleanup_offline_users.php
```

### **Aggiungere Nuovi Job:**
```bash
# Esempio: backup completo ogni domenica alle 01:00
0 1 * * 0 /var/www/CRM/backup_complete.sh
```

---

## 📈 **STATISTICHE SISTEMA**

### **Risparmio Spazio:**
- **Chat Cleanup:** Mantiene solo 1 anno di storico messaggi
- **Log Cleanup:** Mantiene solo 30 giorni di log  
- **DB Optimization:** Recupera spazio da tabelle frammentate

### **Performance:**
- **Offline Cleanup:** Mantiene indicatori online accurati
- **DB Optimization:** Query più veloci con statistiche aggiornate
- **Google Sync:** Backup automatico senza intervento manuale

### **Affidabilità:**
- **Backup Automatico:** Protezione dati su cloud
- **Pulizia Regolare:** Previene accumulo dati spazzatura  
- **Monitoraggio Log:** Tracciabilità di tutti i processi

---

## ✅ **TUTTI I SERVIZI CONFIGURATI E FUNZIONANTI**

🟢 **Cleanup Utenti Offline** - Ogni 2 minuti  
🟢 **Ottimizzazione Database** - Ogni notte  
🟢 **Pulizia Chat Vecchie** - Ogni mese  
🟢 **Pulizia Log** - Ogni mese  
🟢 **Sync Google Drive** - Ogni 6 ore  

**Sistema completamente automatizzato! 🚀**
