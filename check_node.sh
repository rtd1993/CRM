#!/bin/bash

# Configurazione
CRM_DIR="/var/www/CRM"
PID_FILE="$CRM_DIR/socket.pid"
SOCKET_FILE="socket.js"

# Funzione per controllare lo stato
check_status() {
    echo "=== Stato Socket.js ===="
    
    # Controlla se il file PID esiste
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        
        # Verifica se il processo è attivo
        if ps -p "$PID" > /dev/null 2>&1; then
            echo "✅ Socket.js è ATTIVO"
            echo "📍 PID: $PID"
            
            # Mostra informazioni sul processo
            echo "📊 Informazioni processo:"
            ps -p "$PID" -o pid,ppid,cmd,etime,pcpu,pmem
            
            # Controlla la porta se netstat è disponibile
            if command -v netstat > /dev/null; then
                echo "🌐 Porte in ascolto:"
                netstat -tlnp 2>/dev/null | grep "$PID" || echo "Nessuna porta trovata per questo PID"
            fi
            
        else
            echo "❌ Socket.js NON è attivo (PID file presente ma processo morto)"
            echo "🗑️  Rimuovo file PID obsoleto..."
            rm -f "$PID_FILE"
        fi
    else
        echo "❌ Socket.js NON è attivo (nessun file PID)"
    fi
    
    # Controlla con PM2 se disponibile
    if command -v pm2 > /dev/null; then
        echo ""
        echo "📋 Stato PM2:"
        pm2 list | grep socketjs || echo "Nessun processo socketjs in PM2"
    fi
    
    # Verifica file socket.js
    if [ -f "$CRM_DIR/$SOCKET_FILE" ]; then
        echo "📄 File socket.js: ✅ Presente"
    else
        echo "📄 File socket.js: ❌ NON trovato in $CRM_DIR"
    fi
    
    echo "=========================="
}

# Esegui la funzione principale
check_status
