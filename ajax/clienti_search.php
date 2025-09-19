<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, Cognome_Ragione_sociale, Nome FROM clienti WHERE Cognome_Ragione_sociale LIKE ? ORDER BY Cognome_Ragione_sociale ASC, Nome ASC LIMIT 20");
$stmt->execute([$q . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format for frontend
$list = array_map(function($row) {
    return [
        'id' => $row['id'],
        'label' => $row['Cognome_Ragione_sociale'] . ' ' . $row['Nome'],
        'cognome' => $row['Cognome_Ragione_sociale'],
        'nome' => $row['Nome']
    ];
}, $results);

echo json_encode($list);
