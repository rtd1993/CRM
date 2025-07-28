# 🚀 LocalTunnel Auto-Start Configuration

Configurazione per avviare automaticamente LocalTunnel all'accensione del server.

## 📋 Panoramica

LocalTunnel viene configurato come servizio di sistema per:
- ✅ **Avvio automatico** all'accensione
- ✅ **Riavvio automatico** in caso di crash
- ✅ **Gestione tramite DevTools**
- ✅ **URL fisso**: `https://ascontabilemente.loca.lt`

## 🐧 Linux (Ubuntu/Debian)

### Installazione Automatica
```bash
# Rendi eseguibile lo script
chmod +x install_localtunnel.sh

# Esegui come root
sudo ./install_localtunnel.sh
```

### Installazione Manuale
```bash
# 1. Installa Node.js (se non presente)
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs

# 2. Installa LocalTunnel globalmente
sudo npm install -g localtunnel

# 3. Copia il file di servizio
sudo cp localtunnel.service /etc/systemd/system/

# 4. Abilita e avvia il servizio
sudo systemctl daemon-reload
sudo systemctl enable localtunnel.service
sudo systemctl start localtunnel.service
```

### Comandi di Gestione
```bash
# Stato del servizio
sudo systemctl status localtunnel

# Avvia servizio
sudo systemctl start localtunnel

# Ferma servizio
sudo systemctl stop localtunnel

# Riavvia servizio
sudo systemctl restart localtunnel

# Log in tempo reale
sudo journalctl -u localtunnel -f

# Disabilita avvio automatico
sudo systemctl disable localtunnel
```

## 🪟 Windows

### Configurazione Automatica
1. **Apri PowerShell come Amministratore**
2. **Esegui lo script:**
   ```powershell
   .\configure_localtunnel_windows.ps1
   ```
3. **Segui le istruzioni a schermo**

### Installazione Manuale
1. **Installa Node.js** da https://nodejs.org
2. **Apri CMD/PowerShell come Amministratore:**
   ```cmd
   npm install -g localtunnel
   npm install -g node-windows
   ```
3. **Crea il servizio** seguendo lo script PowerShell

### Gestione Servizio Windows
- **Gestione Servizi**: `services.msc`
- **Nome Servizio**: "LocalTunnel ASContabilmente"
- **Controlli**: Start/Stop/Restart dal pannello servizi

## 🎛️ DevTools Integration

La pagina **DevTools** ora include:

### 🚀 LocalTunnel Card
- **Stato in tempo reale**: 🟢 Attivo / 🔴 Inattivo
- **Controlli**: Start, Stop, Restart, Status, Logs
- **URL diretto**: Link a `https://ascontabilemente.loca.lt`

### 📊 Altri Servizi
- **Apache2**: Web server principale
- **MySQL**: Database
- **Node.js Socket**: Servizio chat/socket
- **WireGuard**: VPN (se configurato)

## 🔧 Risoluzione Problemi

### LocalTunnel non si avvia
```bash
# Verifica Node.js
node --version
npm --version

# Verifica LocalTunnel
lt --help

# Controlla logs
sudo journalctl -u localtunnel -f
```

### Porta 80 occupata
```bash
# Verifica cosa usa la porta 80
sudo netstat -tulpn | grep :80

# Se Apache non è in ascolto su 80
sudo systemctl stop apache2
sudo systemctl start localtunnel
sudo systemctl start apache2
```

### URL cambia sempre
- ✅ **Soluzione**: Usa sempre `--subdomain ascontabilemente`
- ✅ **Verifica**: Il servizio include il parametro subdomain
- ✅ **URL fisso**: `https://ascontabilemente.loca.lt`

## 🌐 Accesso al CRM

### URL Principale
```
https://ascontabilemente.loca.lt
```

### Bypass Automatico
Il CRM include automaticamente:
- **Header bypass** per LocalTunnel
- **Cookie persistente**
- **Redirect automatico**

### Test Connessione
1. **Verifica servizio**: DevTools → LocalTunnel → Status
2. **Test URL**: Apri `https://ascontabilemente.loca.lt`
3. **Login CRM**: Dovresti vedere direttamente il login

## 📱 Utilizzo DevTools

### Accesso DevTools
```
http://localhost/devtools.php
```
*Richiede ruolo "developer"*

### Funzionalità
- **🖥️ Gestione Servizi**: Controllo completo di tutti i servizi
- **💻 SQL Console**: Query dirette al database
- **📊 Visualizzazione Tabelle**: Esplora dati
- **🌐 Info Accesso Remoto**: SSH e connessioni

## 🔐 Sicurezza

### Considerazioni
- ✅ **LocalTunnel è pubblico**: Chiunque può accedere all'URL
- ✅ **CRM protetto**: Login richiesto per accesso
- ✅ **DevTools limitato**: Solo utenti "developer"
- ✅ **Database sicuro**: No accesso diretto dall'esterno

### Best Practices
- **Password forti** per utenti CRM
- **Backup regolari** del database
- **Monitoring** dei logs di accesso
- **Aggiornamenti** regolari del sistema

---

## ✅ Risultato Finale

Dopo la configurazione:
1. **🔄 Server si riavvia** → LocalTunnel parte automaticamente
2. **🌐 URL sempre disponibile**: `https://ascontabilemente.loca.lt`
3. **🎛️ Controllo completo** tramite DevTools
4. **📊 Monitoring** stato e logs in tempo reale
5. **🔧 Gestione facile** con un click

**Il CRM è ora completamente accessibile da remoto con avvio automatico!** 🎉
