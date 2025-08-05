# 🚀 CRM Database Optimization Suite

Raccolta completa di script per l'ottimizzazione e il monitoraggio del database MySQL del sistema CRM, aggiornata per supportare le nuove tabelle: `conto_termico`, `enea`, e `chat_read_status`.

## 📋 Indice

- [Script Disponibili](#script-disponibili)
- [Installazione e Configurazione](#installazione-e-configurazione)
- [Utilizzo](#utilizzo)
- [Monitoraggio](#monitoraggio)
- [Configurazione MySQL](#configurazione-mysql)
- [Troubleshooting](#troubleshooting)

---

## 📁 Script Disponibili

### 1. `optimize_crm_master.sh` 🎯
**Script principale** che esegue l'ottimizzazione completa in 7 fasi:
- Backup di sicurezza automatico
- Creazione indici ottimizzati
- Ottimizzazione tabelle e statistiche
- Monitoraggio performance
- Configurazione MySQL
- Pulizia backup vecchi
- Report finale

### 2. `optimize_mysql.sh` ⚡
Script di ottimizzazione database con funzionalità avanzate:
- Analisi frammentazione tabelle
- Ottimizzazione automatica tabelle frammentate
- Aggiornamento statistiche per **tutte** le tabelle CRM
- Creazione indici mancanti
- Report performance dettagliato
- Output colorato e user-friendly

### 3. `monitor_performance.sh` 📊
Script di monitoraggio dettagliato delle performance:
- Statistiche dimensioni database
- Conteggi record per tabella
- Test performance query frequenti
- Analisi utilizzo indici
- Monitoraggio frammentazione
- Raccomandazioni automatiche

### 4. `create_indexes.sql` 🔧
Script SQL per creare indici ottimizzati:
- **Tabella `conto_termico`**: 8 indici strategici
- **Tabella `enea`**: 15 indici per documenti e workflow
- **Tabella `chat_read_status`**: 5 indici per performance chat
- **Full-text search** per ricerche testuali
- Verifica indici esistenti

### 5. `mysql_optimization.cnf` ⚙️
Configurazione MySQL ottimizzata per CRM:
- Buffer pool aumentato a 1.5GB
- Cache query ottimizzata (128MB)
- Timeout e connessioni bilanciate
- Logging migliorato per debug
- Ottimizzazioni specifiche per CRM

---

## 🛠 Installazione e Configurazione

### Prerequisiti
```bash
# MySQL in esecuzione
sudo systemctl status mysql

# Utente database configurato
mysql -u crmuser -pAdmin123! crm -e "SELECT 1;"

# Permessi script
chmod +x *.sh
```

### Configurazione Credenziali
Modifica le credenziali negli script se necessario:
```bash
# In tutti i file .sh
DB_NAME="crm"
DB_USER="crmuser"
DB_PASS="Admin123!"
```

### Directory Log
```bash
# Crea directory log se non esiste
sudo mkdir -p /var/log
sudo mkdir -p /var/backups/mysql_crm
sudo chown $USER:$USER /var/log/mysql_*.log
```

---

## 🚀 Utilizzo

### Ottimizzazione Completa (CONSIGLIATO)
```bash
# Esecuzione completa con tutte le fasi
./optimize_crm_master.sh
```

### Ottimizzazione Singola
```bash
# Solo ottimizzazione database
./optimize_mysql.sh

# Solo monitoraggio
./monitor_performance.sh

# Solo creazione indici
mysql -u crmuser -pAdmin123! crm < create_indexes.sql
```

### Automazione con Cron
```bash
# Modifica crontab
crontab -e

# Aggiungi riga per esecuzione settimanale (domenica alle 02:00)
0 2 * * 0 /percorso/verso/optimize_crm_master.sh

# Per monitoraggio giornaliero
0 8 * * * /percorso/verso/monitor_performance.sh
```

---

## 📊 Monitoraggio

### Log Files
```bash
# Log ottimizzazione principale
tail -f /var/log/mysql_optimization.log

# Log monitoraggio performance
tail -f /var/log/mysql_performance_monitor.log

# Log master ottimizzazione
tail -f /var/log/crm_master_optimization.log
```

### Metriche Chiave da Monitorare

#### 🎯 Performance Database
- **Dimensione totale DB**: < 2GB per performance ottimali
- **Query lente**: < 1% del totale query
- **Hit rate buffer pool**: > 95%
- **Hit rate query cache**: > 80%

#### 📈 Crescita Dati
- **Clienti**: crescita costante
- **Conto Termico**: picchi stagionali
- **ENEA**: crescita per progetti
- **Chat**: crescita lineare uso sistema

#### 💾 Frammentazione
- **Soglia critica**: > 15% frammentazione
- **Ottimizzazione automatica**: > 5MB spazio libero

---

## ⚙️ Configurazione MySQL

### Applicazione Configurazione Ottimizzata
```bash
# Backup configurazione esistente
sudo cp /etc/mysql/my.cnf /etc/mysql/my.cnf.backup

# Applica nuova configurazione
sudo cp mysql_optimization.cnf /etc/mysql/conf.d/

# Riavvia MySQL
sudo systemctl restart mysql

# Verifica configurazione
mysql -u root -p -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
```

### Parametri Principali Ottimizzati

| Parametro | Valore Precedente | Valore Ottimizzato | Beneficio |
|-----------|-------------------|-------------------|-----------|
| `innodb_buffer_pool_size` | 1G | 1.5G | +50% cache dati |
| `query_cache_size` | 64M | 128M | +100% cache query |
| `table_open_cache` | 1000 | 2000 | +100% tabelle in cache |
| `max_connections` | 100 | 150 | +50% connessioni simultanee |
| `tmp_table_size` | 64M | 128M | +100% tabelle temporanee |

---

## 🔍 Analisi Risultati

### Indici Creati per Tabella

#### `conto_termico` (8 indici)
- `idx_conto_termico_cliente_id` - Ricerche per cliente
- `idx_conto_termico_anno` - Filtro principale per anno
- `idx_conto_termico_esito` - Filtro per esito
- `idx_conto_termico_anno_cliente` - Query combinate ottimizzate
- Altri indici per date e timestamp

#### `enea` (15 indici)
- `idx_enea_cliente_id` - Ricerche per cliente
- 8 indici per stati documenti (statistiche completamento)
- Indici per date e numerazioni
- Indici composti per query complesse

#### `chat_read_status` (5 indici)
- `idx_chat_read_user_pratica` - Query principali chat
- Indici per conteggi non letti
- Indici per timestamp

### Query Ottimizzate

#### Prima dell'ottimizzazione:
```sql
-- Query lenta senza indici
SELECT * FROM conto_termico ct
JOIN clienti c ON ct.cliente_id = c.id
WHERE ct.anno = 2025; -- Scan completo tabella
```

#### Dopo l'ottimizzazione:
```sql
-- Query veloce con indici
SELECT * FROM conto_termico ct
JOIN clienti c ON ct.cliente_id = c.id
WHERE ct.anno = 2025; -- Usa idx_conto_termico_anno
```

---

## ⚠️ Troubleshooting

### Problemi Comuni

#### 1. Script non eseguibile
```bash
chmod +x *.sh
```

#### 2. Errore connessione database
```bash
# Verifica servizio MySQL
sudo systemctl status mysql

# Verifica credenziali
mysql -u crmuser -pAdmin123! crm -e "SELECT 1;"
```

#### 3. Spazio disco insufficiente
```bash
# Verifica spazio
df -h /var/lib/mysql

# Pulisci log vecchi
sudo find /var/log -name "*.log" -mtime +30 -delete
```

#### 4. MySQL non si riavvia dopo configurazione
```bash
# Verifica sintassi configurazione
sudo mysql --help --verbose | head -20

# Ripristina backup
sudo cp /etc/mysql/my.cnf.backup /etc/mysql/my.cnf
sudo systemctl restart mysql
```

### Rollback Procedure

#### 1. Ripristino da Backup
```bash
# Lista backup disponibili
ls -la /var/backups/mysql_crm/

# Ripristina backup specifico
mysql -u crmuser -pAdmin123! crm < /var/backups/mysql_crm/crm_backup_YYYYMMDD_HHMMSS.sql
```

#### 2. Rimozione Indici
```sql
-- Se gli indici causano problemi
DROP INDEX idx_conto_termico_cliente_id ON conto_termico;
DROP INDEX idx_enea_cliente_id ON enea;
-- etc...
```

---

## 📅 Pianificazione Manutenzione

### Frequenza Consigliata

| Operazione | Frequenza | Script |
|------------|-----------|---------|
| **Ottimizzazione Completa** | Settimanale | `optimize_crm_master.sh` |
| **Monitoraggio Performance** | Giornaliero | `monitor_performance.sh` |
| **Backup Database** | Giornaliero | Incluso in master |
| **Verifica Indici** | Mensile | `create_indexes.sql` |
| **Aggiornamento Configurazione** | Quando necessario | `mysql_optimization.cnf` |

### Checklist Pre-Produzione

- [ ] Backup database completo
- [ ] Test script in ambiente di sviluppo
- [ ] Verifica spazio disco disponibile
- [ ] Pianifica finestra di manutenzione
- [ ] Notifica utenti della manutenzione
- [ ] Monitora performance post-ottimizzazione

---

## 📞 Supporto

Per problemi o domande:
1. Controlla i log in `/var/log/mysql_*.log`
2. Verifica la documentazione MySQL
3. Testa in ambiente di sviluppo prima della produzione

---

**🎉 Con questa suite di ottimizzazione, il tuo CRM avrà performance ottimali per gestire clienti, conto termico, ENEA e chat in modo efficiente!**
