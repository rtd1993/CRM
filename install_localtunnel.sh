#!/bin/bash

# Script di installazione del servizio LocalTunnel
# Da eseguire come root o con sudo

echo "🚀 Installazione servizio LocalTunnel per ASContabilmente CRM"
echo "================================================================"

# Verifica se Node.js è installato
if ! command -v node &> /dev/null; then
    echo "❌ Node.js non trovato. Installazione in corso..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

# Verifica se LocalTunnel è installato globalmente
if ! command -v lt &> /dev/null; then
    echo "📦 Installazione LocalTunnel..."
    sudo npm install -g localtunnel
fi

# Copia il file di servizio
echo "📋 Configurazione servizio systemd..."
sudo cp localtunnel.service /etc/systemd/system/

# Ricarica systemd
echo "🔄 Ricaricamento systemd..."
sudo systemctl daemon-reload

# Abilita il servizio per l'avvio automatico
echo "⚡ Abilitazione avvio automatico..."
sudo systemctl enable localtunnel.service

# Avvia il servizio
echo "▶️ Avvio servizio LocalTunnel..."
sudo systemctl start localtunnel.service

# Verifica stato
echo ""
echo "📊 Stato del servizio:"
sudo systemctl status localtunnel.service --no-pager

echo ""
echo "✅ Installazione completata!"
echo ""
echo "🌐 URL di accesso: https://ascontabilemente.loca.lt"
echo ""
echo "Comandi utili:"
echo "  sudo systemctl status localtunnel    # Stato servizio"
echo "  sudo systemctl stop localtunnel      # Ferma servizio"
echo "  sudo systemctl start localtunnel     # Avvia servizio"
echo "  sudo systemctl restart localtunnel   # Riavvia servizio"
echo "  sudo journalctl -u localtunnel -f    # Log in tempo reale"
