#!/bin/bash
# Script per aggiornare LocalTunnel con password fissa

echo "🚀 Aggiornamento LocalTunnel con password fissa..."

# Copia il file di servizio aggiornato
sudo cp /var/www/CRM/localtunnel.service /etc/systemd/system/

# Ricarica systemd
sudo systemctl daemon-reload

# Ferma il servizio attuale
sudo systemctl stop localtunnel

# Riavvia il servizio con la nuova configurazione
sudo systemctl start localtunnel

# Abilita l'avvio automatico
sudo systemctl enable localtunnel

# Mostra lo stato
echo "📊 Stato servizio LocalTunnel:"
sudo systemctl status localtunnel --no-pager

echo ""
echo "✅ LocalTunnel aggiornato!"
echo "🔐 Credenziali di accesso:"
echo "   Username: crm"
echo "   Password: admin123"
echo "🌐 URL: https://ascontabilemente.loca.lt"
echo ""
echo "💡 Per testare: curl -u crm:admin123 https://ascontabilemente.loca.lt"
