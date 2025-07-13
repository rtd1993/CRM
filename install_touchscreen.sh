#!/bin/bash

# Script per installare il supporto touchscreen da 3.5" su Raspberry Pi Ubuntu Server

echo "=== Configurazione Touchscreen 3.5\" per Raspberry Pi ==="

# Aggiorna il sistema
echo "Aggiornamento sistema..."
sudo apt update && sudo apt upgrade -y

# Installa X11 e window manager leggero
echo "Installazione ambiente grafico minimale..."
sudo apt install -y xorg openbox chromium-browser unclutter

# Installa driver touchscreen generici
echo "Installazione driver touchscreen..."
sudo apt install -y xserver-xorg-input-evdev

# Configura X11 per touchscreen
echo "Configurazione X11..."
sudo tee /etc/X11/xorg.conf.d/99-touchscreen.conf > /dev/null << EOF
Section "InputClass"
    Identifier "TouchScreen"
    MatchIsTouchscreen "on"
    Driver "evdev"
    Option "SwapAxes" "1"
    Option "InvertX" "1"
    Option "InvertY" "1"
EndSection
EOF

# Configura rotazione display
echo "Configurazione rotazione display..."
sudo tee -a /boot/firmware/config.txt > /dev/null << EOF

# Configurazione touchscreen 3.5"
display_rotate=1
dtoverlay=vc4-kms-v3d
max_usb_current=1
hdmi_force_hotplug=1
hdmi_cvt=480 320 60 1 0 0 0
hdmi_group=2
hdmi_mode=87
EOF

# Crea script di avvio per l'interfaccia
echo "Creazione script di avvio..."
sudo tee /home/ubuntu/start_ui.sh > /dev/null << 'EOF'
#!/bin/bash
export DISPLAY=:0
cd /var/www/CRM
python3 -m http.server 8080 &
sleep 3
chromium-browser --kiosk --disable-features=Translate --no-first-run --fast --fast-start --disable-infobars --disable-session-crashed-bubble --disable-pinch --overscroll-history-navigation=0 --touch-events=enabled --disable-web-security --user-data-dir=/tmp/chrome_data http://localhost/CRM/system_monitor.php
EOF

chmod +x /home/ubuntu/start_ui.sh

# Configura avvio automatico
echo "Configurazione avvio automatico..."
sudo tee /etc/systemd/system/touch-ui.service > /dev/null << EOF
[Unit]
Description=Touch UI Service
After=graphical-session.target

[Service]
Type=simple
User=ubuntu
Environment=DISPLAY=:0
ExecStart=/home/ubuntu/start_ui.sh
Restart=always
RestartSec=5

[Install]
WantedBy=graphical.target
EOF

sudo systemctl enable touch-ui.service

echo "=== Installazione completata! ==="
echo "Riavvia il sistema per applicare le modifiche:"
echo "sudo reboot"
echo ""
echo "Dopo il riavvio, l'interfaccia dovrebbe avviarsi automaticamente."
