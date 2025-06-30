<?php
// File: api/chat_cronologia.php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/db.php';

// Recupera ultimi 30 messaggi ordinati dal più vecchio al più recente
$stmt = $pdo->query("
    SELECT c.id, c.messaggio, c.timestamp, u.nome 
    FROM chat_messaggi c 
    JOIN utenti u ON c.utente_id = u.id 
    ORDER BY c.timestamp ASC 
    LIMIT 30
");
$risultati = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Imposta intestazione JSON e restituisce i dati
header('Content-Type: application/json');
echo json_encode($risultati);
