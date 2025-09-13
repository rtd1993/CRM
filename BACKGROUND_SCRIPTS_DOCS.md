# 🤖 Sistema di Automazione CRM - Background Scripts

## 📋 Panoramica

Il sistema CRM è dotato di script automatici che vengono eseguiti in background per:
- ✅ Archiviare le chat globali più vecchie di 1 anno
- ❌ Eliminare le chat private più vecchie di 1 anno  
- 🔧 Ottimizzare il database ogni notte alle 02:00
- 💾 Creare backup automatici
- 🧹 Pulire log e file temporanei

## 📁 Script Principali

### 1. 📅 Archiviazione Mensile Chat (`archivio_chat_mensile.sh`)
```bash
Percorso: /var/www/CRM/archivio_chat_mensile.sh
Schedule: 1° del mese alle 03:00
```

**Funzioni:**
- **Chat Globali**: Archiviate in `/var/www/CRM/local_drive/ASContabilmente/archivio/chat/ANNO/MESE/`
- **Chat Private**: Eliminate definitivamente (solo log statistiche per audit)
- **Criteri**: Messaggi più vecchi di 1 anno
- **Formato Archivio**: 
  ```
  local_drive/ASContabilmente/archivio/chat/
  ├── 2023/
  │   ├── 01/
  │   │   ├── chat_globali_01_2023.txt
  │   │   └── conversazioni_private_eliminate_01_2023.log
  │   ├── 02/
  │   └── ...
  └── 2024/
  ```

### 2. 🔧 Ottimizzazione Database Notturna (`optimize_database_nightly.sh`)
```bash
Percorso: /var/www/CRM/optimize_database_nightly.sh
Schedule: Ogni giorno alle 02:00
```

**Operazioni:**
- ✅ Ottimizza tutte le tabelle principali
- 📊 Aggiorna statistiche tabelle
- 🧹 Pulisce log più vecchi di 30 giorni
- 🔍 Analizza frammentazione indici
- 💾 Crea backup struttura database
- 📈 Monitora performance

### 3. ⚙️ Configurazione Automatica (`setup_cron_jobs.sh`)
```bash
Percorso: /var/www/CRM/setup_cron_jobs.sh
Utilizzo: sudo ./setup_cron_jobs.sh
```

**Configura automaticamente:**
- 🗓️ Tutti i cron job necessari
- 📁 Directory di log e backup
- 🔑 Permessi degli script
- 📋 File di stato sistema

### 4. 🔍 Verifica Stato (`check_cron_status.sh`)
```bash
Percorso: /var/www/CRM/check_cron_status.sh
Utilizzo: ./check_cron_status.sh
```

**Controlla:**
- ✅ Stato servizio cron
- 📊 Salute database
- 💾 Backup recenti
- 💿 Spazio disco
- 🔌 Servizi correlati

## 📅 Scheduling Completo

| Operazione | Frequenza | Orario | Script |
|------------|-----------|--------|---------|
| 🔧 Ottimizzazione DB | Giornaliera | 02:00 | `optimize_database_nightly.sh` |
| 📁 Archiviazione Chat | Mensile | 1° del mese 03:00 | `archivio_chat_mensile.sh` |
| 💾 Backup DB | Giornaliera | 01:00 | mysqldump (cron) |
| ☁️ Sync Cloud | Ogni ora | :30 | `sync_rclone.sh` |
| 🧹 Pulizia Log | Settimanale | Domenica 04:00 | find (cron) |
| 🔄 Rotazione Backup | Giornaliera | 05:00 | find (cron) |
| 📊 Monitor Spazio | Ogni 6 ore | - | df (cron) |
| 🔌 Check Servizi | Ogni 15 min | - | pgrep (cron) |
| 🔧 Ottimizza Chat | Settimanale | Lunedì 03:30 | mysql (cron) |

## 🚀 Installazione

### 1. Configurazione Iniziale
```bash
# Come root
sudo su -
cd /var/www/CRM

# Rendi eseguibili gli script
chmod +x archivio_chat_mensile.sh
chmod +x optimize_database_nightly.sh
chmod +x setup_cron_jobs.sh
chmod +x check_cron_status.sh

# Configura i cron job automaticamente
./setup_cron_jobs.sh
```

### 2. Verifica Installazione
```bash
# Controlla cron job installati
crontab -l

# Verifica stato sistema
./check_cron_status.sh

# Test connessione database
mysql -u root -pAdmin123! crm -e "SELECT COUNT(*) FROM chat_messages;"
```

### 3. Monitoraggio
```bash
# Log in tempo reale
tail -f /var/www/CRM/logs/database_optimization_nightly.log
tail -f /var/www/CRM/logs/chat_archivio_mensile.log

# Verifica backup
ls -la /var/www/CRM/backups/

# Verifica archivi chat
ls -la /var/www/CRM/local_drive/ASContabilmente/archivio/chat/
```

## 📂 Directory Structure

```
/var/www/CRM/
├── scripts/                    # Script di automazione
│   ├── archivio_chat_mensile.sh
│   ├── optimize_database_nightly.sh
│   ├── setup_cron_jobs.sh
│   └── check_cron_status.sh
├── logs/                       # Log di sistema
│   ├── database_optimization_nightly.log
│   ├── chat_archivio_mensile.log
│   ├── cron_database.log
│   ├── cron_archivio.log
│   ├── cron_backup.log
│   └── health_report_*.txt
├── backups/                    # Backup database
│   ├── backup_20241201_010000.sql.gz
│   └── structure_backup_*.sql.gz
└── local_drive/ASContabilmente/
    └── archivio/chat/          # Archivio chat
        ├── 2023/01/
        ├── 2023/02/
        └── 2024/...
```

## 🔧 Configurazione Database

### Credenziali
```bash
DB_NAME="crm"
DB_USER="root"
DB_PASS="Admin123!"
```

### Tabelle Gestite
- `chat_conversations`
- `chat_messages`
- `utenti`
- `clienti`
- `task`
- `task_clienti`
- `enea`
- `conto_termico`
- `email_cronologia`
- `email_templates`

## 📊 Metriche Monitorate

### Performance Database
- 🔍 Query lente
- 📊 Cache hit ratio
- 🔌 Connessioni attive
- 💾 Spazio utilizzato
- 🔧 Frammentazione tabelle

### Stato Sistema
- 💿 Spazio disco disponibile
- 📁 Dimensione archivi
- 💾 Numero backup
- 🔌 Servizi attivi
- 📈 Health score generale

## 🚨 Alerting

### Soglie di Attenzione
- 🔴 **Spazio disco > 85%**: Alert critico
- 🟡 **Spazio disco > 70%**: Warning
- 🔴 **Backup mancanti > 25 ore**: Critico
- 🟡 **Database > 1GB**: Ottimizzazione consigliata

### Notifiche
- 📧 Email: `admin@ascontabilmente.it`
- 📝 Log: `/var/www/CRM/logs/`
- 📊 Report: Health check automatici

## 🛠️ Troubleshooting

### Problemi Comuni

**1. Cron job non in esecuzione**
```bash
# Verifica servizio
systemctl status cron
sudo systemctl start cron

# Verifica sintassi crontab
crontab -l
```

**2. Errore connessione database**
```bash
# Test connessione
mysql -u root -pAdmin123! crm -e "SELECT 1;"

# Verifica servizio MySQL
systemctl status mysql
```

**3. Spazio disco esaurito**
```bash
# Verifica spazio
df -h /var/www/CRM

# Pulizia manuale
find /var/www/CRM/logs -name "*.log" -mtime +7 -delete
find /var/www/CRM/backups -name "*.gz" -mtime +14 -delete
```

**4. Script non eseguibili**
```bash
# Imposta permessi
chmod +x /var/www/CRM/*.sh
chown root:root /var/www/CRM/*.sh
```

### Log di Debug
```bash
# Abilita debug nei script
export DEBUG=1

# Esecuzione manuale con verbose
bash -x /var/www/CRM/archivio_chat_mensile.sh
```

## 📈 Performance Tuning

### Ottimizzazioni Consigliate
1. **Indici Database**: Verificare query lente
2. **Archivio Chat**: Compressione file archivio
3. **Backup**: Backup incrementali per DB grandi
4. **Monitoring**: Dashboard Grafana (opzionale)

### Scaling
- **Database**: Replica MySQL per read-only
- **Storage**: NFS per archivi condivisi
- **Backup**: S3/Cloud storage per long-term

## 🔐 Sicurezza

### Permessi File
```bash
# Script principali
chmod 750 *.sh
chown root:www-data *.sh

# Directory log
chmod 755 logs/
chown www-data:www-data logs/

# Directory backup
chmod 700 backups/
chown root:root backups/
```

### Accesso Database
- ✅ Connessione localhost only
- ✅ Password complessa
- ✅ User dedicato per backup
- ❌ No accesso remoto root

---

## 📞 Supporto

Per assistenza tecnica:
- 📧 Email: support@ascontabilmente.it
- 📱 Telegram: @ASContabilmenteBot
- 🌐 Web: Dashboard CRM → DevTools

**Ultimo aggiornamento**: $(date)
**Versione**: 2.0
