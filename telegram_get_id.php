<?php
$token = 'INSERISCI_IL_TUO_BOT_TOKEN';

$updates = file_get_contents("https://api.telegram.org/bot$token/getUpdates");
$updates = json_decode($updates, true);

if (!isset($updates['result'])) {
    die("Nessun messaggio trovato. Invia un messaggio al tuo bot su Telegram.");
}

foreach ($updates['result'] as $update) {
    if (isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $nome = $update['message']['chat']['first_name'] ?? '';
        echo "✅ Chat ID di $nome: <strong>$chat_id</strong><br>";
    }
}
