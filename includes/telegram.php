<?php
function mandaNotificaTelegram($testo, $chat_id = null) {
    $token = TELEGRAM_BOT_TOKEN;
    if (!$chat_id) {
        $chat_id = TELEGRAM_CHAT_ID;
    }
    
    // Verifica che il token sia configurato
    if (!$token || $token === 'your_telegram_bot_token_here') {
        error_log("Telegram: Token non configurato");
        return false;
    }
    
    // Verifica che il chat_id sia valido
    if (!$chat_id || $chat_id === 'your_default_chat_id_here') {
        error_log("Telegram: Chat ID non valido");
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $testo,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];

    try {
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("Telegram: Errore nell'invio del messaggio");
            return false;
        }
        
        $response = json_decode($result, true);
        if (!$response['ok']) {
            error_log("Telegram: Errore API - " . $response['description']);
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Telegram: Eccezione - " . $e->getMessage());
        return false;
    }
}

// Funzione per testare la connessione al bot
function testConnessioneTelegram($chat_id) {
    $messaggio = "🔔 <b>Test Connessione CRM</b>\n\n";
    $messaggio .= "✅ La connessione con il bot Telegram è funzionante!\n";
    $messaggio .= "📅 " . date('d/m/Y H:i:s') . "\n\n";
    $messaggio .= "Ora riceverai notifiche quando non sei online nel CRM.";
    
    return mandaNotificaTelegram($messaggio, $chat_id);
}

// Funzione per formattare messaggi di notifica
function formatNotificaChat($mittente, $messaggio) {
    $testo = "🔔 <b>Nuovo messaggio CRM</b>\n\n";
    $testo .= "👤 <b>" . htmlspecialchars($mittente) . "</b>\n";
    $testo .= "💬 " . htmlspecialchars($messaggio) . "\n\n";
    $testo .= "📅 " . date('d/m/Y H:i');
    
    return $testo;
}

// Funzione per formattare notifiche di appunti
function formatNotificaAppunto($mittente, $cliente, $appunto) {
    $testo = "📌 <b>Nuovo appunto pratica</b>\n\n";
    $testo .= "👤 <b>" . htmlspecialchars($mittente) . "</b>\n";
    $testo .= "📋 <b>Cliente:</b> " . htmlspecialchars($cliente) . "\n";
    $testo .= "📝 " . htmlspecialchars($appunto) . "\n\n";
    $testo .= "📅 " . date('d/m/Y H:i');
    
    return $testo;
}
?>