<?php
function mandaNotificaTelegram($testo, $chat_id = null) {
    $token = TELEGRAM_BOT_TOKEN;
    if (!$chat_id) {
        $chat_id = TELEGRAM_CHAT_ID;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $testo,
        'parse_mode' => 'HTML'
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 3
        ]
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
