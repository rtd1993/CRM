#!/bin/bash

# Script per installare il supporto touchscreen da 3.5" su Raspberry Pi Ubuntu Server

echo "=== Configurazione Touchscreen 3.5\" per Raspberry Pi ==="

# Aggiorna il sistema
echo "📦 Aggiornamento sistema..."
sudo apt update && sudo apt upgrade -y

# Installa X11 e window manager leggero
echo "🖥️ Installazione ambiente grafico minimale..."
sudo apt install -y xorg openbox chromium-browser unclutter

# Installa driver touchscreen generici
echo "👆 Installazione driver touchscreen..."
sudo apt install -y xserver-xorg-input-evdev xserver-xorg-input-libinput

# Configura X11 per touchscreen
echo "⚙️ Configurazione X11..."
sudo tee /etc/X11/xorg.conf.d/99-touchscreen.conf > /dev/null << EOF
Section "InputClass"
    Identifier "TouchScreen"
    MatchIsTouchscreen "on"
    Driver "libinput"
    Option "Calibration" "0 4095 0 4095"
    Option "SwapAxes" "0"
    Option "InvertX" "0"
    Option "InvertY" "0"
    Option "TransformationMatrix" "1 0 0 0 1 0 0 0 1"
EndSection
EOF

# Configura rotazione display per schermo 3.5"
echo "📱 Configurazione rotazione display..."
sudo tee -a /boot/firmware/config.txt > /dev/null << EOF

# Configurazione touchscreen 3.5" (480x320)
hdmi_group=2
hdmi_mode=87
hdmi_cvt=480 320 60 6 0 0 0
hdmi_drive=1
display_rotate=0
dtoverlay=vc4-kms-v3d
max_usb_current=1
hdmi_force_hotplug=1
EOF

# Crea script di avvio per l'interfaccia touch
echo "🚀 Creazione script di avvio..."
sudo tee /home/admin/start_touch_ui.sh > /dev/null << 'EOF'
#!/bin/bash
export DISPLAY=:0

# Avvia server X se non è già attivo
if ! pgrep -x "Xorg" > /dev/null; then
    startx /usr/bin/openbox-session &
    sleep 5
fi

# Nasconde il cursore del mouse
unclutter -idle 1 &

# Avvia browser in modalità kiosk
chromium-browser \
    --kiosk \
    --no-sandbox \
    --disable-web-security \
    --disable-features=Translate \
    --no-first-run \
    --fast \
    --fast-start \
    --disable-default-apps \
    --disable-infobars \
    --disable-session-crashed-bubble \
    --disable-pinch \
    --overscroll-history-navigation=0 \
    --touch-events=enabled \
    --force-device-scale-factor=1.0 \
    --window-size=480,320 \
    --user-data-dir=/tmp/chrome_data \
    --app=http://localhost/touch_monitor.php
EOF

chmod +x /home/admin/start_touch_ui.sh
# Configura avvio automatico come servizio systemd
echo "🔧 Configurazione avvio automatico..."
sudo tee /etc/systemd/system/touch-ui.service > /dev/null << EOF
[Unit]
Description=Touch UI Service per CRM Monitor
After=graphical-session.target
Wants=graphical-session.target

[Service]
Type=simple
User=admin
Group=admin
Environment=DISPLAY=:0
ExecStart=/home/admin/start_touch_ui.sh
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=graphical.target
EOF

# Abilita il servizio
sudo systemctl daemon-reload
sudo systemctl enable touch-ui.service

# Configura autologin per admin
echo "🔓 Configurazione autologin..."
sudo tee /etc/systemd/system/getty@tty1.service.d/override.conf > /dev/null << EOF
[Service]
ExecStart=
ExecStart=-/sbin/agetty --noissue --autologin admin %I \$TERM
Type=idle
EOF

# Aggiunge avvio automatico di X11 al login
echo "📱 Configurazione avvio X11..."
sudo -u admin tee -a /home/admin/.profile > /dev/null << EOF

# Avvio automatico X11 e touch UI per tty1
if [ "\$(tty)" = "/dev/tty1" ]; then
    startx /home/admin/start_touch_ui.sh
fi
EOF

echo "✅ Installazione completata!"
echo ""
echo "🔄 Riavvia il sistema per applicare le modifiche:"
echo "    sudo reboot"
echo ""
echo "📱 Dopo il riavvio, l'interfaccia touch si aprirà automaticamente su:"
echo "    http://localhost/touch_monitor.php"
echo ""
echo "🛠️ Per modifiche manuali:"
echo "    - Script avvio: /home/admin/start_touch_ui.sh"
echo "    - Servizio: sudo systemctl status touch-ui.service"
echo "    - Config touchscreen: /etc/X11/xorg.conf.d/99-touchscreen.conf"
