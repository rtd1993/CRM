#!/bin/bash

# Configurazione
CRM_DIR="/var/www/CRM"
PID_FILE="$CRM_DIR/socket.pid"

# Funzione per fermare il processo
stop_process() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        
        # Verifica se il processo è attivo
        if ps -p "$PID" > /dev/null 2>&1; then
            echo "Fermo il processo socket.js (PID: $PID)..."
            
            # Prova prima con PM2
            if command -v pm2 > /dev/null; then
                pm2 stop socketjs 2>/dev/null
                pm2 delete socketjs 2>/dev/null
            fi
            
            # Termina il processo
            kill "$PID" 2>/dev/null
            
            # Aspetta un po' e controlla se è terminato
            sleep 2
            if ps -p "$PID" > /dev/null 2>&1; then
                echo "Processo non terminato, forzo l'arresto..."
                kill -9 "$PID" 2>/dev/null
            fi
            
            echo "Processo terminato con successo"
        else
            echo "Processo non attivo"
        fi
        
        # Rimuovi il file PID
        rm -f "$PID_FILE"
    else
        echo "Nessun file PID trovato, processo probabilmente già fermo"
        
        # Prova comunque con PM2
        if command -v pm2 > /dev/null; then
            pm2 stop socketjs 2>/dev/null
            pm2 delete socketjs 2>/dev/null
        fi
    fi
}

# Esegui la funzione principale
echo "=== Arresto Socket.js ===="
stop_process
echo "=========================="
