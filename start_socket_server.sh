#!/bin/bash

# Script di avvio automatico per Socket.IO Chat Server
# Posiziona questo file in /etc/systemd/system/chat-socket.service

echo "Avvio Chat Socket.IO Server..."

# Cambia directory
cd /var/www/CRM

# Avvia node con pm2 per auto-restart
if command -v pm2 &> /dev/null; then
    echo "Usando PM2 per gestire il processo..."
    pm2 start socket.js --name "chat-socket" --watch --ignore-watch="node_modules"
    pm2 save
    pm2 startup
else
    echo "PM2 non trovato. Avvio diretto con node..."
    node socket.js
fi
