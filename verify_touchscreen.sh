#!/bin/bash

# Script di verifica installazione touchscreen
# Controlla che tutti i componenti siano installati e funzionanti

echo "🔍 Verifica Installazione Touchscreen CRM"
echo "========================================"
echo

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funzione per check con emoji
check_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
        return 0
    else
        echo -e "${RED}❌ $2${NC}"
        return 1
    fi
}

warning_status() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

info_status() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

echo "🔧 Controllo Servizi Sistema"
echo "----------------------------"

# Verifica servizi critici
systemctl is-active --quiet apache2
check_status $? "Apache2 Web Server"

systemctl is-active --quiet mysql
check_status $? "MySQL Database"

systemctl is-active --quiet node-socket
if [ $? -eq 0 ]; then
    check_status 0 "Node.js Socket Service"
else
    warning_status "Node.js Socket Service (opzionale)"
fi

systemctl is-active --quiet wg-quick@wg0
if [ $? -eq 0 ]; then
    check_status 0 "WireGuard VPN"
else
    warning_status "WireGuard VPN (opzionale)"
fi

echo
echo "📱 Controllo Ambiente Touchscreen"
echo "--------------------------------"

# Verifica X11
if command -v Xorg &> /dev/null; then
    check_status 0 "Server X11 installato"
else
    check_status 1 "Server X11 mancante"
fi

# Verifica Chromium
if command -v chromium-browser &> /dev/null; then
    check_status 0 "Browser Chromium installato"
else
    check_status 1 "Browser Chromium mancante"
fi

# Verifica servizio touch-ui
if systemctl list-unit-files | grep -q "touch-ui.service"; then
    check_status 0 "Servizio touch-ui configurato"
    
    systemctl is-enabled --quiet touch-ui
    check_status $? "Servizio touch-ui abilitato"
else
    check_status 1 "Servizio touch-ui non configurato"
fi

# Verifica file configurazione
if [ -f "/etc/X11/xorg.conf.d/99-touchscreen.conf" ]; then
    check_status 0 "Configurazione touchscreen presente"
else
    check_status 1 "Configurazione touchscreen mancante"
fi

# Verifica script di avvio
if [ -f "/home/ubuntu/start_touch_ui.sh" ] && [ -x "/home/ubuntu/start_touch_ui.sh" ]; then
    check_status 0 "Script avvio UI presente ed eseguibile"
else
    check_status 1 "Script avvio UI mancante o non eseguibile"
fi

echo
echo "🌐 Controllo File Web"
echo "---------------------"

# Verifica file PHP
if [ -f "/var/www/CRM/touch_monitor.php" ]; then
    check_status 0 "touch_monitor.php presente"
else
    check_status 1 "touch_monitor.php mancante"
fi

if [ -f "/var/www/CRM/includes/config.php" ]; then
    check_status 0 "config.php presente"
else
    check_status 1 "config.php mancante"
fi

echo
echo "💾 Controllo Sistema"
echo "-------------------"

# Verifica memoria
MEM_TOTAL=$(grep MemTotal /proc/meminfo | awk '{print $2}')
MEM_GB=$(($MEM_TOTAL / 1024 / 1024))

if [ $MEM_GB -ge 2 ]; then
    check_status 0 "RAM sufficiente (${MEM_GB}GB)"
else
    warning_status "RAM limitata (${MEM_GB}GB) - raccomandati 4GB+"
fi

# Verifica spazio disco
DISK_AVAIL=$(df / | tail -1 | awk '{print $4}')
DISK_GB=$(($DISK_AVAIL / 1024 / 1024))

if [ $DISK_GB -ge 2 ]; then
    check_status 0 "Spazio disco sufficiente (${DISK_GB}GB liberi)"
else
    warning_status "Spazio disco limitato (${DISK_GB}GB liberi)"
fi

# Verifica temperatura CPU
if [ -f "/sys/class/thermal/thermal_zone0/temp" ]; then
    TEMP_RAW=$(cat /sys/class/thermal/thermal_zone0/temp)
    TEMP_C=$(($TEMP_RAW / 1000))
    
    if [ $TEMP_C -lt 70 ]; then
        check_status 0 "Temperatura CPU normale (${TEMP_C}°C)"
    else
        warning_status "Temperatura CPU elevata (${TEMP_C}°C)"
    fi
else
    warning_status "Sensore temperatura non disponibile"
fi

echo
echo "🔗 Test Connettività"
echo "-------------------"

# Test connessione locale
if curl -s -o /dev/null -w "%{http_code}" http://localhost/touch_monitor.php | grep -q "200"; then
    check_status 0 "Interfaccia web accessibile localmente"
else
    check_status 1 "Interfaccia web non accessibile"
fi

# Test database
if mysql -u root -e "USE crm_db; SHOW TABLES;" &> /dev/null; then
    check_status 0 "Database CRM accessibile"
else
    check_status 1 "Database CRM non accessibile"
fi

echo
echo "📋 Riepilogo Configurazione"
echo "============================"

info_status "URL Touch Monitor: http://$(hostname -I | awk '{print $1}')/touch_monitor.php"
info_status "Risoluzione configurata: 480x320"
info_status "Path configurazione: /etc/X11/xorg.conf.d/99-touchscreen.conf"

if systemctl is-active --quiet touch-ui; then
    info_status "Stato servizio: ATTIVO"
else
    info_status "Stato servizio: INATTIVO"
fi

echo
echo "🛠️ Comandi Utili"
echo "==============="
echo "  • Riavvia UI:     sudo systemctl restart touch-ui"
echo "  • Status UI:      sudo systemctl status touch-ui"
echo "  • Log UI:         journalctl -u touch-ui -f"
echo "  • Calibra touch:  /usr/local/bin/calibrate-touch.sh"
echo "  • Test manuale:   chromium-browser --kiosk http://localhost/touch_monitor.php"

echo
if systemctl is-active --quiet touch-ui && [ -f "/var/www/CRM/touch_monitor.php" ]; then
    echo -e "${GREEN}🎉 Sistema touchscreen configurato e funzionante!${NC}"
    echo -e "${GREEN}   Riavvia con 'sudo reboot' per test completo.${NC}"
else
    echo -e "${RED}⚠️  Configurazione incompleta. Controlla gli errori sopra.${NC}"
    echo -e "${YELLOW}   Esegui: sudo bash install_touchscreen.sh${NC}"
fi
