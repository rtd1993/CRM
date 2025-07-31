<?php
// Test completo form crea_cliente.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h1>🧪 Test Completo Form Creazione Cliente</h1>";

// Simula dati POST completi - tutti i campi del form
$_POST = [
    'Codice fiscale' => 'TESTCOMPLETO25',
    'Cognome/Ragione sociale' => 'Test Cliente Completo SRL',
    'Nome' => 'TestCompleto',
    'Partita IVA' => '55555555555',
    'Telefono' => '333 5555555',
    'Mail' => 'testcompleto@example.com',
    'PEC' => 'testcompleto@pec.example.com',
    'Inizio rapporto' => '2025-01-01',
    'Fine rapporto' => '2025-12-31',
    'Inserito gestionale' => 'on',
    'Codice ditta' => 'COMPL001',
    'Colore' => '#FF00FF',
    'Qualifica' => 'CEO',
    'Soci Amministratori' => 'Mario Rossi (50%), Luigi Verdi (50%)',
    'Sede Legale' => 'Via Nazionale 100, 00100 Roma (RM)',
    'Sede Operativa' => 'Via Commerciale 200, 20100 Milano (MI)',
    'Data di nascita/costituzione' => '1990-03-15',  
    'Luogo di nascita' => 'Roma',
    'Cittadinanza' => 'Italiana',
    'Residenza' => 'Via Residenza 50, 00100 Roma (RM)',
    'Numero carta d\'identità' => 'AZ9876543',
    'Rilasciata dal Comune di' => 'Roma Capitale',
    'Data di rilascio' => '2020-03-01',
    'Valida per l\'espatrio' => 'on',
    'Stato civile' => 'Coniugato/a',
    'Data di scadenza' => '2030-03-01',
    'Descrizione attivita' => 'Sviluppo software e consulenza informatica per aziende. Creazione di soluzioni digitali innovative.',
    'Codice ATECO' => '62.01.00',
    'Camera di commercio' => 'CCIAA di Roma',
    'Dipendenti' => '12',
    'Codice inps' => '12345678',
    'Titolare' => 'Mario Rossi',
    'Codice inps_2' => '87654321',
    'Codice inail' => '111222333',
    'PAT' => 'PAT444555',
    'Cod.PIN Inail' => 'PIN666777',
    'Cassa Edile' => 'CE888999',
    'Numero Cassa Professionisti' => 'CP000111',
    'Contabilita' => 'Semplificata',
    'Liquidazione IVA' => 'Trimestrale',
    'User Aruba' => 'testcompleto',
    'Password' => 'SecurePass123!',
    'Scadenza PEC' => '2025-06-30',
    'Rinnovo Pec' => '2025-05-15',
    'SDI' => 'XYZ789'
];

$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<div style='background: #e3f2fd; padding: 15px; margin: 10px 0; border-left: 5px solid #2196f3;'>";
echo "<h3>📋 Dati Test Simulati</h3>";
echo "<p><strong>Campi POST totali:</strong> " . count($_POST) . "</p>";
echo "<p><strong>Codice Fiscale:</strong> " . $_POST['Codice fiscale'] . "</p>";
echo "<p><strong>Ragione Sociale:</strong> " . $_POST['Cognome/Ragione sociale'] . "</p>";
echo "</div>";

// Stesso array campi_db di crea_cliente.php
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

echo "<h2>🔄 Processamento Campi (Logica crea_cliente.php)</h2>";

try {
    $updates = [];
    $values = [];
    $dettagli_processamento = [];
    
    // IDENTICA LOGICA di crea_cliente.php
    foreach ($_POST as $campo_post => $valore_post) {
        $dettaglio = ['campo' => $campo_post, 'valore_originale' => $valore_post];
        
        // Skip se è vuoto e non è un campo obbligatorio
        if (empty($valore_post) && $campo_post !== 'Codice fiscale') {
            $dettaglio['azione'] = 'SALTATO (vuoto)';
            $dettagli_processamento[] = $dettaglio;
            continue;
        }
        
        // Determina il tipo di campo
        $tipo = $campi_db[$campo_post] ?? 'text';
        $dettaglio['tipo'] = $tipo;
        
        $valore = $valore_post;
        
        // Gestione dei tipi di dato
        if ($tipo === 'checkbox') {
            $valore = $valore === 'on' ? 1 : 0;
        } elseif ($tipo === 'number') {  
            if ($valore === '' || $valore === null || trim($valore) === '') {
                $valore = null;
            } else {
                $valore = is_numeric($valore) ? intval($valore) : null;
            }
        } elseif ($tipo === 'date') {
            if ($valore === '' || $valore === null) {
                $valore = null;
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $valore);
                if (!$date) {
                    $valore = null;
                }
            }
        }
        
        $dettaglio['valore_processato'] = $valore;
        $dettaglio['azione'] = 'PROCESSATO';
        
        $updates[] = "`$campo_post`";
        $values[] = $valore;
        $dettagli_processamento[] = $dettaglio;
    }
    
    // Mostra dettagli processamento
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
    echo "<tr><th>Campo</th><th>Valore Originale</th><th>Tipo</th><th>Valore Processato</th><th>Azione</th></tr>";
    
    foreach ($dettagli_processamento as $det) {
        $colore = $det['azione'] === 'PROCESSATO' ? '#e8f5e8' : '#fff3cd';
        echo "<tr style='background: $colore;'>";
        echo "<td><strong>{$det['campo']}</strong></td>";
        echo "<td>" . htmlspecialchars($det['valore_originale']) . "</td>";
        echo "<td>{$det['tipo']}</td>";
        echo "<td>" . htmlspecialchars($det['valore_processato']) . "</td>";
        echo "<td>{$det['azione']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 15px 0; border: 1px solid #0066cc;'>";
    echo "<h3>📊 Riepilogo Processamento</h3>";
    echo "<p><strong>Campi ricevuti via POST:</strong> " . count($_POST) . "</p>";
    echo "<p><strong>Campi processati per DB:</strong> " . count($updates) . "</p>";
    echo "<p><strong>Campi saltati (vuoti):</strong> " . (count($_POST) - count($updates)) . "</p>";
    echo "</div>";
    
    // Validazione codice fiscale
    $codice_fiscale = $_POST['Codice fiscale'] ?? '';
    if (empty($codice_fiscale) || trim($codice_fiscale) === '') {
        throw new Exception("Il Codice Fiscale è obbligatorio");
    }
    
    // Creazione cartella (come in crea_cliente.php)
    $codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $codice_fiscale);
    $cartella_path = __DIR__ . '/local_drive/' . $codice_fiscale_clean;
    $link_cartella = 'drive.php?path=' . urlencode($codice_fiscale_clean);
    
    if (!is_dir($cartella_path)) {
        if (!mkdir($cartella_path, 0755, true)) {
            throw new Exception("Impossibile creare la cartella per il cliente");
        }
        
        // File di benvenuto
        $welcome_file = $cartella_path . '/README.txt';
        $welcome_content = "Cartella cliente: " . $codice_fiscale . "\n";
        $welcome_content .= "Cognome/Ragione sociale: " . $_POST['Cognome/Ragione sociale'] . "\n";
        $welcome_content .= "Creata il: " . date('d/m/Y H:i:s') . "\n";
        file_put_contents($welcome_file, $welcome_content);
        
        echo "<p style='color: green;'>✅ Cartella creata: $cartella_path</p>";
    }
    
    // Aggiunta link cartella
    $updates[] = "`Link cartella`";
    $values[] = $link_cartella;
    
    // Inserimento nel database
    if (!empty($updates)) {
        $sql = "INSERT INTO clienti (" . implode(', ', $updates) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
        
        echo "<h3>💾 Inserimento Database</h3>";
        echo "<p><strong>Query SQL:</strong></p>";
        echo "<code style='background: #f5f5f5; padding: 10px; display: block; white-space: pre-wrap; font-size: 11px;'>$sql</code>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($values);
        
        if ($result) {
            $nuovo_cliente_id = $pdo->lastInsertId();
            
            echo "<div style='background: #4caf50; color: white; padding: 20px; margin: 20px 0; border-radius: 5px; font-size: 16px; font-weight: bold;'>";
            echo "🎉 SUCCESSO COMPLETO!<br>";
            echo "Cliente creato con ID: $nuovo_cliente_id<br>";
            echo "Campi salvati: " . count($updates) . "/" . count($_POST);
            echo "</div>";
            
            // Verifica completa nel database  
            echo "<h3>🔍 Verifica Dati Salvati</h3>";
            $check_stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $check_stmt->execute([$nuovo_cliente_id]);
            $cliente_salvato = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cliente_salvato) {
                $campi_ok = 0;
                $campi_vuoti = 0;
                
                echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 11px;'>";
                echo "<tr><th>Campo</th><th>Inviato</th><th>Salvato</th><th>Status</th></tr>";
                
                foreach ($_POST as $campo => $valore_inviato) {
                    $valore_salvato = $cliente_salvato[$campo] ?? 'NULL';
                    
                    if ($valore_salvato !== null && $valore_salvato !== '') {
                        $campi_ok++;
                        $status = '<span style="color: green;">✅ OK</span>';
                        $bg = '#e8f5e8';
                    } else {
                        $campi_vuoti++;
                        $status = '<span style="color: orange;">⚠️ VUOTO</span>';
                        $bg = '#fff3cd';
                    }
                    
                    echo "<tr style='background: $bg;'>";
                    echo "<td><strong>$campo</strong></td>";
                    echo "<td>" . htmlspecialchars($valore_inviato) . "</td>";
                    echo "<td>" . htmlspecialchars($valore_salvato) . "</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Statistiche finali
                $percentuale = round(($campi_ok / count($_POST)) * 100, 1);
                echo "<div style='background: #e1f5fe; padding: 20px; margin: 20px 0; border-left: 5px solid #03a9f4;'>";
                echo "<h4>📈 Statistiche Finali</h4>";
                echo "<p><strong>Campi inviati:</strong> " . count($_POST) . "</p>";
                echo "<p><strong>Campi salvati correttamente:</strong> $campi_ok</p>";
                echo "<p><strong>Campi vuoti/NULL:</strong> $campi_vuoti</p>";
                echo "<p><strong>Percentuale successo:</strong> $percentuale%</p>";
                echo "<p><strong>Link info cliente:</strong> <a href='info_cliente.php?id=$nuovo_cliente_id'>Visualizza cliente →</a></p>";
                echo "</div>";
                
            }
            
        } else {
            throw new Exception("Errore nell'inserimento nel database");
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f44336; color: white; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "❌ <strong>ERRORE:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<div style='background: #f5f5f5; padding: 15px; margin: 20px 0; text-align: center;'>";
echo "<h3>🏁 Test Completato</h3>";
echo "<p>Il test ha simulato completamente il processo di creazione cliente</p>";
echo "</div>";
?>
