<?php
// Debug version of modifica_cliente.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Modifica Cliente</h1>";

try {
    echo "1. Verificando i file di include...<br>";
    require_once __DIR__ . '/includes/auth.php';
    echo "✅ auth.php caricato<br>";
    
    require_login();
    echo "✅ Autenticazione verificata<br>";
    
    require_once __DIR__ . '/includes/db.php';
    echo "✅ db.php caricato<br>";
    
    require_once __DIR__ . '/includes/header.php';
    echo "✅ header.php caricato<br>";
    
} catch (Exception $e) {
    echo "❌ Errore durante il caricamento: " . $e->getMessage() . "<br>";
    die();
}

// Test connessione database
echo "<br>2. Testando connessione database...<br>";
try {
    $test_query = $pdo->query("SELECT COUNT(*) FROM clienti");
    $count = $test_query->fetchColumn();
    echo "✅ Database raggiungibile. Numero clienti: $count<br>";
} catch (Exception $e) {
    echo "❌ Errore database: " . $e->getMessage() . "<br>";
    die();
}

// Test parametri GET
echo "<br>3. Verificando parametri GET...<br>";
if (!isset($_GET['id'])) {
    echo "❌ Parametro 'id' mancante<br>";
    echo "URL attuale: " . $_SERVER['REQUEST_URI'] . "<br>";
    echo "Parametri GET: ";
    var_dump($_GET);
    die();
}

if (!is_numeric($_GET['id'])) {
    echo "❌ Parametro 'id' non numerico: " . $_GET['id'] . "<br>";
    die();
}

$id = intval($_GET['id']);
echo "✅ ID cliente: $id<br>";

// Test query cliente
echo "<br>4. Verificando esistenza cliente...<br>";
try {
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        echo "❌ Cliente non trovato per ID: $id<br>";
        
        // Verifica se esistono altri clienti
        $test_query = $pdo->query("SELECT id FROM clienti LIMIT 5");
        $existing_ids = $test_query->fetchAll(PDO::FETCH_COLUMN);
        echo "IDs esistenti nel database: " . implode(', ', $existing_ids) . "<br>";
        die();
    }
    
    echo "✅ Cliente trovato: " . htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Errore query cliente: " . $e->getMessage() . "<br>";
    die();
}

// Test POST (se presente)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<br>5. Processando dati POST...<br>";
    echo "Dati POST ricevuti: ";
    var_dump($_POST);
    
    // Test query UPDATE
    try {
        $campi = array_keys($_POST);
        $valori = array_values($_POST);
        
        echo "Campi da aggiornare: " . implode(', ', $campi) . "<br>";
        
        // Costruzione query più sicura
        $update_parts = [];
        foreach ($campi as $campo) {
            $update_parts[] = "`$campo` = ?";
        }
        $update_sql = implode(', ', $update_parts);
        
        echo "Query SQL: UPDATE clienti SET $update_sql WHERE id = ?<br>";
        
        $stmt = $pdo->prepare("UPDATE clienti SET $update_sql WHERE id = ?");
        $valori[] = $id; // Aggiungi ID alla fine
        
        if ($stmt->execute($valori)) {
            echo "✅ Aggiornamento riuscito!<br>";
        } else {
            echo "❌ Errore nell'aggiornamento<br>";
            var_dump($stmt->errorInfo());
        }
        
    } catch (Exception $e) {
        echo "❌ Errore durante l'aggiornamento: " . $e->getMessage() . "<br>";
    }
}

echo "<br><br>✅ <strong>Debug completato!</strong><br>";
echo "<br>Test links:<br>";
echo "<a href='modifica_cliente.php?id=1'>Modifica Cliente ID: 1</a><br>";
echo "<a href='modifica_cliente.php?id=2'>Modifica Cliente ID: 2</a><br>";
echo "<a href='modifica_cliente.php?id=3'>Modifica Cliente ID: 3</a><br>";
echo "<br><a href='clienti.php'>← Torna alla lista clienti</a>";
?>
