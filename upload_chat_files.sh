#!/bin/bash

# Script per caricare i file della chat sul server
echo "🚀 Caricamento file chat sul server..."

# Configurazione
SERVER="admin@192.168.1.80"
REMOTE_PATH="/var/www/html/ascontabilmente.homes"
LOCAL_PATH="/c/Users/rtd19/Documents/GitHub/CRM"

# Carica i file delle API chat
echo "📂 Caricando API chat..."

# Crea le directory se non esistono
ssh $SERVER "mkdir -p $REMOTE_PATH/api/chat/messages"
ssh $SERVER "mkdir -p $REMOTE_PATH/api/chat/notifications"

# Carica i file dei messaggi
echo "📤 Caricando get_history.php..."
scp "$LOCAL_PATH/api/chat/messages/get_history.php" "$SERVER:$REMOTE_PATH/api/chat/messages/"

echo "📤 Caricando get_new.php..."
scp "$LOCAL_PATH/api/chat/messages/get_new.php" "$SERVER:$REMOTE_PATH/api/chat/messages/"

# Carica i file delle notifiche
echo "📤 Caricando mark_read.php..."
scp "$LOCAL_PATH/api/chat/notifications/mark_read.php" "$SERVER:$REMOTE_PATH/api/chat/notifications/"

echo "📤 Caricando get_unread.php..."
scp "$LOCAL_PATH/api/chat/notifications/get_unread.php" "$SERVER:$REMOTE_PATH/api/chat/notifications/"

# Imposta i permessi corretti
echo "🔧 Impostando permessi..."
ssh $SERVER "chown -R www-data:www-data $REMOTE_PATH/api/chat/"
ssh $SERVER "chmod -R 644 $REMOTE_PATH/api/chat/*.php"

echo "✅ Caricamento completato!"
echo "🌐 Testa ora: https://ascontabilmente.homes"
