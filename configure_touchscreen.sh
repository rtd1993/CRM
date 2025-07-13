#!/bin/bash

# Script di configurazione avanzata per touchscreen 3.5" Raspberry Pi
# Eseguire dopo install_touchscreen.sh

echo "🔧 Configurazione avanzata touchscreen..."

# Ottimizza impostazioni Chromium per touch
echo "🌐 Ottimizzazione browser..."
sudo -u ubuntu mkdir -p /home/ubuntu/.config/chromium/Default

sudo -u ubuntu tee /home/ubuntu/.config/chromium/Default/Preferences > /dev/null << 'EOF'
{
   "browser": {
      "check_default_browser": false,
      "show_home_button": false
   },
   "distribution": {
      "import_bookmarks": false,
      "import_history": false,
      "import_search_engine": false,
      "make_chrome_default_for_user": false,
      "skip_first_run_ui": true
   },
   "first_run_tabs": [ ],
   "homepage": "http://localhost/touch_monitor.php",
   "homepage_is_newtabpage": false,
   "session": {
      "restore_on_startup": 4,
      "startup_urls": [ "http://localhost/touch_monitor.php" ]
   }
}
EOF

# Configura calibrazione touchscreen personalizzata
echo "👆 Calibrazione touchscreen..."
sudo tee /usr/local/bin/calibrate-touch.sh > /dev/null << 'EOF'
#!/bin/bash
# Script di calibrazione automatica touchscreen

# Ottieni ID del dispositivo touch
DEVICE_ID=$(xinput list | grep -i touch | grep -o 'id=[0-9]*' | cut -d= -f2 | head -1)

if [ -n "$DEVICE_ID" ]; then
    echo "Calibrazione dispositivo touch ID: $DEVICE_ID"
    
    # Imposta matrice di trasformazione per schermo 480x320
    xinput set-prop $DEVICE_ID "Coordinate Transformation Matrix" 1 0 0 0 1 0 0 0 1
    
    # Abilita touch events
    xinput enable $DEVICE_ID
    
    echo "Calibrazione completata"
else
    echo "Nessun dispositivo touch trovato"
fi
EOF

chmod +x /usr/local/bin/calibrate-touch.sh

# Configura watchdog per riavvio automatico del browser
echo "🔄 Configurazione watchdog browser..."
sudo tee /usr/local/bin/browser-watchdog.sh > /dev/null << 'EOF'
#!/bin/bash
# Watchdog per riavviare il browser se si chiude

while true; do
    if ! pgrep -f "chromium.*touch_monitor" > /dev/null; then
        echo "$(date): Browser non attivo, riavvio..."
        pkill -f chromium
        sleep 2
        sudo -u ubuntu DISPLAY=:0 /home/ubuntu/start_touch_ui.sh &
    fi
    sleep 30
done
EOF

chmod +x /usr/local/bin/browser-watchdog.sh

# Crea servizio watchdog
sudo tee /etc/systemd/system/browser-watchdog.service > /dev/null << EOF
[Unit]
Description=Browser Watchdog Service
After=touch-ui.service

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/browser-watchdog.sh
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Configura pulizia automatica cache browser
echo "🗑️ Configurazione pulizia cache..."
sudo tee /etc/cron.hourly/cleanup-browser > /dev/null << 'EOF'
#!/bin/bash
# Pulisce cache browser ogni ora per evitare accumulo

# Rimuovi cache temporanea
rm -rf /tmp/chrome_data/Default/Cache/* 2>/dev/null
rm -rf /tmp/chrome_data/Default/Code\ Cache/* 2>/dev/null
rm -rf /tmp/chrome_data/Default/GPUCache/* 2>/dev/null

# Mantieni solo gli ultimi 10 file di log
find /var/log -name "*.log" -type f -exec tail -n 100 {} \; -exec truncate -s 0 {} \; 2>/dev/null
EOF

chmod +x /etc/cron.hourly/cleanup-browser

# Configura timeout per screensaver (disabilita)
echo "🖥️ Disabilitazione screensaver..."
sudo -u ubuntu tee /home/ubuntu/.xsessionrc > /dev/null << 'EOF'
#!/bin/bash
# Disabilita screensaver e power management
xset s off
xset -dpms
xset s noblank

# Nasconde cursore
unclutter -idle 1 &

# Calibra touch
/usr/local/bin/calibrate-touch.sh &
EOF

chmod +x /home/ubuntu/.xsessionrc

# Abilita servizi
sudo systemctl daemon-reload
sudo systemctl enable browser-watchdog.service

# Ottimizza performance sistema per touchscreen
echo "⚡ Ottimizzazione performance..."
sudo tee -a /etc/sysctl.conf > /dev/null << EOF

# Ottimizzazioni per touchscreen
vm.swappiness=10
vm.dirty_background_ratio=5
vm.dirty_ratio=10
EOF

echo "✅ Configurazione avanzata completata!"
echo ""
echo "🔄 Riavvia per applicare tutte le modifiche:"
echo "    sudo reboot"
echo ""
echo "📋 Servizi configurati:"
echo "    - touch-ui.service (interfaccia principale)"
echo "    - browser-watchdog.service (watchdog browser)"
echo "    - Pulizia cache automatica (ogni ora)"
echo "    - Calibrazione touch automatica"
echo ""
echo "🛠️ Comandi utili:"
echo "    sudo systemctl status touch-ui"
echo "    sudo systemctl status browser-watchdog" 
echo "    /usr/local/bin/calibrate-touch.sh"
