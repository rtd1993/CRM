#!/bin/bash
cd /var/www/CRM/socket || exit 1
# Usa pm2 se disponibile, altrimenti node puro
if command -v pm2 > /dev/null; then
  pm2 start socket.js --name socketjs
else
  nohup node socket.js > /var/www/CRM/logs/socket.log 2>&1 &
fi