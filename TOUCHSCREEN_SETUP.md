# 📱 CRM Touchscreen Monitor - Guida Installazione

Sistema di monitoraggio touch-friendly per Raspberry Pi con schermo da 3.5" (480x320).

## 🛠️ Requisiti Hardware

- Raspberry Pi 4/5 con Ubuntu Server 25
- Schermo touchscreen 3.5" (480x320 pixels)
- Connessione HDMI + USB per touch
- Almeno 4GB RAM, 16GB storage

## 📦 Installazione

### 1. Installazione Base Touchscreen

```bash
# Esegui lo script di installazione principale
sudo bash install_touchscreen.sh
```

Questo script:
- ✅ Installa ambiente grafico minimale (Xorg + Openbox)
- ✅ Configura driver touchscreen
- ✅ Imposta risoluzione 480x320
- ✅ Configura avvio automatico browser
- ✅ Crea servizio systemd per UI

### 2. Configurazione Avanzata (Opzionale)

```bash
# Esegui configurazioni aggiuntive
sudo bash configure_touchscreen.sh
```

Questo script aggiunge:
- 🔄 Watchdog per riavvio automatico browser
- 🗑️ Pulizia automatica cache
- 👆 Calibrazione automatica touch
- ⚡ Ottimizzazioni performance

### 3. Riavvio Sistema

```bash
sudo reboot
```

## 🖥️ Interfaccia Touch Monitor

L'interfaccia `touch_monitor.php` fornisce:

### 📊 Monitoraggio Sistema
- **WiFi**: Stato connessione e SSID
- **RAM**: Utilizzo memoria in percentuale
- **Disco**: Spazio utilizzato
- **CPU**: Temperatura processore
- **Uptime**: Tempo di attività sistema

### 🔧 Gestione Servizi
- **Apache**: Server web CRM
- **MySQL**: Database CRM
- **Socket**: Servizio Node.js per chat
- **VPN**: WireGuard per accesso remoto

### 🎛️ Controlli Touch
- **▶️ Start**: Avvia servizio
- **🔄 Restart**: Riavvia servizio  
- **⏹️ Stop**: Ferma servizio
- **🔄 Aggiorna**: Refresh dati
- **🔄 Riavvia**: Riavvio sistema

## 📐 Specifiche UI

- **Risoluzione**: 480x320 pixels
- **Layout**: Grid 2 colonne ottimizzato
- **Font**: 8-16px per leggibilità
- **Pulsanti**: Dimensioni touch-friendly (min 44px)
- **Feedback**: Animazioni touch, vibrazione visiva
- **Aggiornamento**: Auto-refresh ogni 15 secondi

## 🔧 Configurazione Servizi

### File Principali
```
/home/ubuntu/start_touch_ui.sh          # Script avvio UI
/etc/systemd/system/touch-ui.service    # Servizio principale
/etc/X11/xorg.conf.d/99-touchscreen.conf # Config touchscreen
```

### Comandi Utili
```bash
# Stato servizio UI
sudo systemctl status touch-ui

# Restart UI
sudo systemctl restart touch-ui

# Log UI
journalctl -u touch-ui -f

# Calibrazione manuale
/usr/local/bin/calibrate-touch.sh

# Test browser manuale
chromium-browser --kiosk http://localhost/touch_monitor.php
```

## 🐛 Troubleshooting

### Schermo Non Funziona
```bash
# Verifica config display
cat /boot/firmware/config.txt | grep hdmi

# Test output HDMI
tvservice -s

# Ricarica config
sudo systemctl restart systemd-logind
```

### Touch Non Risponde
```bash
# Lista dispositivi input
xinput list

# Test eventi touch
sudo evtest

# Riconfigura touch
sudo /usr/local/bin/calibrate-touch.sh
```

### Browser Non Si Avvia
```bash
# Controlla servizio
sudo systemctl status touch-ui

# Restart servizio
sudo systemctl restart touch-ui

# Log errori
journalctl -u touch-ui --since "10 min ago"
```

### Performance Lente
```bash
# Controlla RAM
free -h

# Pulisci cache
sudo /etc/cron.hourly/cleanup-browser

# Ottimizza swap
sudo sysctl vm.swappiness=10
```

## 🌐 Accesso Remoto

### Web Interface
- **Touch UI**: `http://[IP_RASPBERRY]/touch_monitor.php`
- **CRM Completo**: `http://[IP_RASPBERRY]/`
- **DevTools**: `http://[IP_RASPBERRY]/devtools.php`

### SSH Access
```bash
ssh ubuntu@[IP_RASPBERRY]
# Password: standard Ubuntu
```

## 📁 Struttura File

```
/var/www/CRM/
├── touch_monitor.php          # UI principale touchscreen
├── system_monitor.php         # UI monitor desktop
├── install_touchscreen.sh     # Script installazione
├── configure_touchscreen.sh   # Script configurazione avanzata
├── devtools.php              # Tools sviluppatore
└── includes/
    ├── config.php            # Configurazione database
    └── auth.php              # Autenticazione
```

## 🔄 Aggiornamenti

### Update UI
```bash
cd /var/www/CRM
git pull origin main
sudo systemctl restart touch-ui
```

### Update Sistema
```bash
sudo apt update && sudo apt upgrade -y
sudo reboot
```

## 📝 Note Tecniche

### Risoluzione Display
- **Nativa**: 480x320 @ 60Hz
- **Scaling**: 1.0x (no scaling per nitidezza)
- **Orientamento**: Landscape standard

### Performance
- **RAM Minima**: 1GB per UI + servizi
- **CPU Load**: <30% con monitoring attivo
- **Storage**: ~500MB per ambiente grafico

### Compatibilità
- ✅ Raspberry Pi 4B (4GB+)
- ✅ Raspberry Pi 5 (tutte le varianti)
- ✅ Ubuntu Server 22.04/24.04/25.04
- ✅ Schermi capacitivi e resistivi 3.5"

---

## 🆘 Supporto

Per problemi o miglioramenti, controlla:
1. Log sistema: `journalctl -u touch-ui`
2. Log Apache: `tail -f /var/log/apache2/error.log`
3. Status servizi: `sudo systemctl status`

**🎯 L'interfaccia è ottimizzata per uso touch professionale su Raspberry Pi!**
