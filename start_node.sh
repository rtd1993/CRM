#!/bin/bash

# Configurazione
CRM_DIR="/var/www/CRM"
SOCKET_FILE="socket.js"
LOG_DIR="$CRM_DIR/logs"
PID_FILE="$CRM_DIR/socket.pid"
LOG_FILE="$LOG_DIR/socket.log"

# Crea directory logs se non esiste
mkdir -p "$LOG_DIR"

# Funzione per verificare se il processo è attivo
check_process() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if ps -p "$PID" > /dev/null 2>&1; then
            echo "Socket.js è già attivo con PID: $PID"
            return 0
        else
            echo "PID file esistente ma processo non attivo, rimuovo PID file..."
            rm -f "$PID_FILE"
        fi
    fi
    return 1
}

# Funzione per avviare il processo
start_process() {
    echo "Avvio socket.js..."
    cd "$CRM_DIR" || { echo "Errore: impossibile accedere a $CRM_DIR"; exit 1; }
    
    # Verifica che il file socket.js esista
    if [ ! -f "$SOCKET_FILE" ]; then
        echo "Errore: $SOCKET_FILE non trovato in $CRM_DIR"
        exit 1
    fi
    
    # Usa pm2 se disponibile, altrimenti node puro
    if command -v pm2 > /dev/null; then
        echo "Uso PM2 per avviare il processo..."
        pm2 start "$SOCKET_FILE" --name "socketjs" --log "$LOG_FILE"
        # Ottieni il PID da PM2
        PM2_PID=$(pm2 jlist | grep -o '"pid":[0-9]*' | head -1 | cut -d: -f2)
        if [ -n "$PM2_PID" ]; then
            echo "$PM2_PID" > "$PID_FILE"
            echo "Socket.js avviato con PM2, PID: $PM2_PID"
        fi
    else
        echo "Uso Node.js diretto..."
        nohup node "$SOCKET_FILE" > "$LOG_FILE" 2>&1 &
        NODE_PID=$!
        echo "$NODE_PID" > "$PID_FILE"
        echo "Socket.js avviato con Node.js, PID: $NODE_PID"
    fi
}

# Funzione principale
main() {
    echo "=== Gestore Socket.js ===="
    
    # Controlla se il processo è già attivo
    if check_process; then
        exit 0
    fi
    
    # Se non è attivo, avvialo
    start_process
    
    echo "=========================="
}

# Esegui la funzione principale
main