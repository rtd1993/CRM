<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>Test Creazione Cliente - Debug Completo</h1>";

// Simuliamo dati POST completi
$_POST = [
    'Codice fiscale' => 'TESTCOMPLETO2025',
    'Cognome/Ragione sociale' => 'Test Cliente Completo SRL',
    'Nome' => 'Cliente',
    'Partita IVA' => '12345678901',
    'Telefono' => '333 1234567',
    'Mail' => 'test@example.com',
    'PEC' => 'test@pec.example.com',
    'Inizio rapporto' => '2025-01-01',
    'Fine rapporto' => '2025-12-31',
    'Inserito gestionale' => 'on',
    'Codice ditta' => 'CD001',
    'Colore' => '#FF5733',
    'Qualifica' => 'Amministratore',
    'Soci Amministratori' => 'Test Soci',
    'Sede Legale' => 'Via Test 123, Roma',
    'Sede Operativa' => 'Via Operativa 456, Milano',
    'Data di nascita/costituzione' => '1990-01-01',
    'Luogo di nascita' => 'Roma',
    'Dipendenti' => '10'
];

// Definisco i campi della tabella clienti
$campi_db = [
    'Inizio rapporto' => 'date',
    'Fine rapporto' => 'date',
    'Inserito gestionale' => 'checkbox',
    'Codice ditta' => 'text',
    'Colore' => 'color',
    'Cognome/Ragione sociale' => 'text',
    'Nome' => 'text',
    'Codice fiscale' => 'text',
    'Partita IVA' => 'text',
    'Qualifica' => 'text',
    'Soci Amministratori' => 'text',
    'Sede Legale' => 'textarea',
    'Sede Operativa' => 'textarea',
    'Data di nascita/costituzione' => 'date',
    'Luogo di nascita' => 'text',
    'Cittadinanza' => 'text',
    'Residenza' => 'textarea',
    'Numero carta d\'identità' => 'text',
    'Rilasciata dal Comune di' => 'text',
    'Data di rilascio' => 'date',
    'Valida per l\'espatrio' => 'checkbox',
    'Stato civile' => 'text',
    'Data di scadenza' => 'date',
    'Descrizione attivita' => 'textarea',
    'Codice ATECO' => 'text',
    'Camera di commercio' => 'text',
    'Dipendenti' => 'number',
    'Codice inps' => 'text',
    'Titolare' => 'text',
    'Codice inps_2' => 'text',
    'Codice inail' => 'text',
    'PAT' => 'text',
    'Cod.PIN Inail' => 'text',
    'Cassa Edile' => 'text',
    'Numero Cassa Professionisti' => 'text',
    'Contabilita' => 'text',
    'Liquidazione IVA' => 'text',
    'Telefono' => 'tel',
    'Mail' => 'email',
    'PEC' => 'email',
    'User Aruba' => 'text',
    'Password' => 'text',
    'Scadenza PEC' => 'date',
    'Rinnovo Pec' => 'date',
    'SDI' => 'text',
    'Link cartella' => 'url'
];

echo "<h2>Dati POST ricevuti (" . count($_POST) . " campi):</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h2>Campi DB definiti (" . count($campi_db) . " campi):</h2>";
echo "<pre>" . print_r(array_keys($campi_db), true) . "</pre>";

$updates = [];
$values = [];

echo "<h2>Processamento campi:</h2>";

// NUOVA LOGICA: Processo TUTTI i campi POST ricevuti
foreach ($_POST as $campo_post => $valore_post) {
    echo "<div style='border: 1px solid #ccc; margin: 5px; padding: 10px;'>";
    echo "<strong>Campo:</strong> '$campo_post'<br>";
    echo "<strong>Valore:</strong> '$valore_post'<br>";
    
    // Skip se è vuoto e non è un campo obbligatorio
    if (empty($valore_post) && $campo_post !== 'Codice fiscale') {
        echo "<strong>Status:</strong> <span style='color: orange;'>SALTATO (vuoto)</span><br>";
        echo "</div>";
        continue;
    }
    
    // Determina il tipo di campo (se definito, altrimenti usa 'text')
    $tipo = $campi_db[$campo_post] ?? 'text';
    echo "<strong>Tipo:</strong> '$tipo'<br>";
    
    $valore = $valore_post;
    
    // Gestione dei tipi di dato
    if ($tipo === 'checkbox') {
        $valore = $valore === 'on' ? 1 : 0;
        echo "<strong>Valore convertito:</strong> $valore<br>";
    } elseif ($tipo === 'number') {
        if ($valore === '' || $valore === null || trim($valore) === '') {
            $valore = null;
            echo "<strong>Valore convertito:</strong> NULL<br>";
        } else {
            $valore = is_numeric($valore) ? intval($valore) : null;
            echo "<strong>Valore convertito:</strong> $valore<br>";
        }
    } elseif ($tipo === 'date') {
        if ($valore === '' || $valore === null) {
            $valore = null;
            echo "<strong>Valore convertito:</strong> NULL<br>";
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $valore);
            if (!$date) {
                $valore = null;
                echo "<strong>Valore convertito:</strong> NULL (data non valida)<br>";
            } else {
                echo "<strong>Valore convertito:</strong> $valore (data valida)<br>";
            }
        }
    }
    
    $updates[] = "`$campo_post`";
    $values[] = $valore;
    
    echo "<strong>Status:</strong> <span style='color: green;'>AGGIUNTO</span><br>";
    echo "</div>";
}

echo "<h2>Query SQL da eseguire:</h2>";
echo "<strong>Updates (" . count($updates) . "):</strong><br>";
echo "<pre>" . print_r($updates, true) . "</pre>";

echo "<strong>Values (" . count($values) . "):</strong><br>";
echo "<pre>" . print_r($values, true) . "</pre>";

if (!empty($updates)) {
    $sql = "INSERT INTO clienti (" . implode(', ', $updates) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
    echo "<strong>SQL Query:</strong><br>";
    echo "<code style='background: #f0f0f0; padding: 10px; display: block;'>$sql</code>";
    
    try {
        echo "<h2>Esecuzione Query...</h2>";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $nuovo_cliente_id = $pdo->lastInsertId();
            echo "<div style='background: green; color: white; padding: 10px;'>";
            echo "✅ SUCCESSO! Cliente creato con ID: $nuovo_cliente_id";
            echo "</div>";
            
            // Verifica nel database
            echo "<h2>Verifica nel database:</h2>";
            $check_sql = "SELECT * FROM clienti WHERE id = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$nuovo_cliente_id]);
            $cliente_salvato = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<pre>" . print_r($cliente_salvato, true) . "</pre>";
            
        } else {
            echo "<div style='background: red; color: white; padding: 10px;'>";
            echo "❌ ERRORE nell'inserimento";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: red; color: white; padding: 10px;'>";
        echo "❌ ERRORE: " . $e->getMessage();
        echo "</div>";
    }
}
?>
