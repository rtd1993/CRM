# Setup completo per Socket.js Service - Guida all'installazione

## 🔧 CORREZIONI APPORTATE AL start_node.sh

### ✅ Miglioramenti principali:
1. **Controllo PID**: Verifica se il processo è già attivo prima di avviarlo
2. **Gestione errori**: Controlli di sicurezza per directory e file
3. **Logging migliorato**: Log strutturato con timestamp
4. **Gestione PM2**: Supporto migliorato per PM2 con fallback a Node.js
5. **File PID**: Tracciamento corretto del PID del processo

### 📁 Script creati:
- `start_node.sh` - Script per avviare socket.js
- `stop_node.sh` - Script per fermare socket.js  
- `check_node.sh` - Script per verificare lo stato
- `node-socket.service` - File systemd per auto-avvio

## 🚀 INSTALLAZIONE E CONFIGURAZIONE

### 1. Copia i file sul server
```bash
# Copia tutti i file nella directory CRM
sudo cp *.sh /var/www/CRM/
sudo cp node-socket.service /etc/systemd/system/
```

### 2. Rendi gli script eseguibili
```bash
sudo chmod +x /var/www/CRM/*.sh
sudo chown www-data:www-data /var/www/CRM/*.sh
```

### 3. Configura il servizio systemd
```bash
# Ricarica i servizi systemd
sudo systemctl daemon-reload

# Abilita il servizio per l'avvio automatico
sudo systemctl enable node-socket.service

# Avvia il servizio
sudo systemctl start node-socket.service

# Verifica lo stato
sudo systemctl status node-socket.service
```

## 📋 UTILIZZO DEGLI SCRIPT

### Avvio manuale:
```bash
./start_node.sh
```
**Output se già attivo**: "Socket.js è già attivo con PID: 1234"
**Output se non attivo**: Avvia il processo e mostra il nuovo PID

### Controllo stato:
```bash
./check_node.sh
```
**Output**: Stato dettagliato del processo con PID, tempo di esecuzione, uso CPU/RAM

### Arresto:
```bash
./stop_node.sh
```

### Controllo con systemd:
```bash
# Stato del servizio
sudo systemctl status node-socket

# Avvia/ferma/riavvia
sudo systemctl start node-socket
sudo systemctl stop node-socket  
sudo systemctl restart node-socket

# Log del servizio
sudo journalctl -u node-socket -f
```

## 🔄 AVVIO AUTOMATICO ALL'ACCENSIONE

### Metodo 1: Systemd (Raccomandato)
```bash
# Il servizio si avvia automaticamente con:
sudo systemctl enable node-socket.service
```

### Metodo 2: Crontab (Alternativo)
```bash
# Aggiungi al crontab di root
sudo crontab -e

# Aggiungi questa riga:
@reboot /var/www/CRM/start_node.sh
```

## 🛠️ TROUBLESHOOTING

### Se il servizio non si avvia:
```bash
# Controlla i log
sudo journalctl -u node-socket -n 50

# Controlla i permessi
ls -la /var/www/CRM/

# Testa lo script manualmente
sudo -u www-data /var/www/CRM/start_node.sh
```

### Se il PID file è corrotto:
```bash
# Rimuovi il file PID e riavvia
sudo rm -f /var/www/CRM/socket.pid
sudo systemctl restart node-socket
```

## 📊 MONITORING

### Controlla se il servizio è attivo:
```bash
# Metodo 1: Systemd
sudo systemctl is-active node-socket

# Metodo 2: Script personalizzato  
./check_node.sh

# Metodo 3: Process check
ps aux | grep socket.js
```

### Log file:
- **Systemd logs**: `sudo journalctl -u node-socket`
- **Application logs**: `/var/www/CRM/logs/socket.log`

## ⚡ COMANDI RAPIDI

```bash
# Stato veloce
sudo systemctl status node-socket --no-pager

# Riavvio veloce
sudo systemctl restart node-socket

# Segui i log in tempo reale
sudo journalctl -u node-socket -f
```
