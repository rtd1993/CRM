<?php
// Script di test per simulare la modifica procedura
require_once __DIR__ . '/includes/config.php';

echo "<h2>Test Modifica Procedura</h2>";

// Simula i dati POST che dovrebbero arrivare
$_POST = [
    'modifica_procedura' => '1',
    'id' => '3',
    'denominazione' => 'Test Procedura MODIFICATA VIA SCRIPT',
    'valida_dal' => '2024-01-01',
    'procedura' => 'Questa è una procedura di test MODIFICATA VIA SCRIPT per verificare il funzionamento del sistema.'
];

echo "<h3>Dati POST simulati:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Esegue la stessa logica della pagina principale
if (isset($_POST['modifica_procedura'])) {
    $id = (int)$_POST['id'];
    $denominazione = trim($_POST['denominazione'] ?? '');
    $valida_dal = $_POST['valida_dal'] ?? '';
    $procedura = trim($_POST['procedura'] ?? '');
    
    echo "<h3>Dati processati:</h3>";
    echo "ID: $id<br>";
    echo "Denominazione: $denominazione<br>";
    echo "Valida dal: $valida_dal<br>";
    echo "Procedura: " . substr($procedura, 0, 50) . "...<br>";
    
    if ($id <= 0) {
        echo "<div style='color: red;'>Errore: ID procedura non valido.</div>";
    } elseif (empty($denominazione)) {
        echo "<div style='color: red;'>Errore: La denominazione è obbligatoria.</div>";
    } elseif (empty($valida_dal)) {
        echo "<div style='color: red;'>Errore: La data di validità è obbligatoria.</div>";
    } elseif (empty($procedura)) {
        echo "<div style='color: red;'>Errore: Il testo della procedura è obbligatorio.</div>";
    } else {
        try {
            // Prima verifica se la procedura esiste
            $exists_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE id = ?");
            $exists_stmt->execute([$id]);
            
            if ($exists_stmt->fetchColumn() == 0) {
                echo "<div style='color: red;'>Errore: La procedura da modificare non esiste.</div>";
            } else {
                echo "<div style='color: green;'>✓ Procedura esiste nel database</div>";
                
                // Verifica se esiste già una procedura con la stessa denominazione (escludendo quella corrente)
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE denominazione = ? AND id != ?");
                $check_stmt->execute([$denominazione, $id]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    echo "<div style='color: red;'>Errore: Esiste già un'altra procedura con questa denominazione.</div>";
                } else {
                    echo "<div style='color: green;'>✓ Denominazione disponibile</div>";
                    
                    // Aggiornamento nel database
                    $stmt = $pdo->prepare("UPDATE procedure_crm SET denominazione = ?, valida_dal = ?, procedura = ? WHERE id = ?");
                    
                    $result = $stmt->execute([$denominazione, $valida_dal, $procedura, $id]);
                    $rowsAffected = $stmt->rowCount();
                    
                    echo "<div style='color: blue;'>Risultato update: " . ($result ? 'SUCCESS' : 'FAILED') . "</div>";
                    echo "<div style='color: blue;'>Righe modificate: $rowsAffected</div>";
                    
                    if ($result && $rowsAffected > 0) {
                        echo "<div style='color: green; font-weight: bold;'>✓ Procedura aggiornata con successo!</div>";
                    } elseif ($result && $rowsAffected == 0) {
                        echo "<div style='color: orange;'>⚠ Nessuna modifica necessaria (dati identici).</div>";
                    } else {
                        echo "<div style='color: red;'>✗ Errore durante l'aggiornamento della procedura.</div>";
                    }
                }
            }
        } catch (Exception $e) {
            echo "<div style='color: red;'>Eccezione: " . $e->getMessage() . "</div>";
        }
    }
}

// Mostra lo stato attuale della procedura
echo "<h3>Stato attuale della procedura ID 3:</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = 3");
    $stmt->execute();
    $proc = $stmt->fetch();
    
    if ($proc) {
        echo "<pre>";
        print_r($proc);
        echo "</pre>";
    } else {
        echo "Procedura non trovata";
    }
} catch (Exception $e) {
    echo "Errore: " . $e->getMessage();
}
?>
