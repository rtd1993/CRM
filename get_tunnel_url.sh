#!/bin/bash

# Script per ottenere l'URL corrente di LocalTunnel
# Salva l'URL in un file per DevTools

LOG_FILE="/tmp/localtunnel_url.txt"

# Ottieni l'URL dai log di systemd
URL=$(journalctl -u localtunnel.service --no-pager -n 10 | grep -o 'https://[^[:space:]]*\.loca\.lt' | tail -1)

if [ ! -z "$URL" ]; then
    echo "$URL" > "$LOG_FILE"
    echo "URL LocalTunnel: $URL"
else
    echo "URL non trovato nei log"
    echo "" > "$LOG_FILE"
fi
