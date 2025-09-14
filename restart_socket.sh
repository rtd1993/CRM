#!/bin/bash

echo "🔄 Riavvio del server Socket.IO..."

# Uccidi tutti i processi node esistenti
echo "🛑 Fermando processi node esistenti..."
pkill -f "node.*socket.js" 2>/dev/null || true
pkill -f "node socket.js" 2>/dev/null || true
sleep 2

# Verifica che non ci siano più processi
EXISTING=$(ps aux | grep "node.*socket.js" | grep -v grep | wc -l)
if [ $EXISTING -gt 0 ]; then
    echo "⚠️  Ancora $EXISTING processi attivi, forzo la chiusura..."
    pkill -9 -f "node.*socket.js" 2>/dev/null || true
    sleep 1
fi

# Controlla se la porta 3001 è ancora in uso
PORT_IN_USE=$(netstat -tlnp 2>/dev/null | grep ":3001 " | wc -l)
if [ $PORT_IN_USE -gt 0 ]; then
    echo "⚠️  Porta 3001 ancora in uso, liberando..."
    fuser -k 3001/tcp 2>/dev/null || true
    sleep 1
fi

# Vai alla directory corretta
cd /var/www/CRM

# Avvia il server in background
echo "🚀 Avvio nuovo server Socket.IO..."
nohup node socket.js > socket.log 2>&1 &

# Aspetta un momento per verificare che si avvii
sleep 3

# Verifica che il server sia attivo
if ps aux | grep "node.*socket.js" | grep -v grep > /dev/null; then
    echo "✅ Server Socket.IO avviato con successo!"
    echo "📋 PID: $(ps aux | grep "node.*socket.js" | grep -v grep | awk '{print $2}')"
    echo "📄 Log: /var/www/CRM/socket.log"
else
    echo "❌ Errore nell'avvio del server!"
    echo "📄 Ultimi log:"
    tail -10 socket.log 2>/dev/null || echo "Nessun log disponibile"
    exit 1
fi

# Test connessione
echo "🔍 Test connessione..."
if curl -s -I http://localhost:3001 >/dev/null 2>&1; then
    echo "✅ Server risponde correttamente sulla porta 3001"
else
    echo "⚠️  Server potrebbe non essere completamente pronto"
fi
