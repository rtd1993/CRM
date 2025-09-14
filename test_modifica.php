<?php
// Test modifica procedura semplificato
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/config.php';

$error_message = '';
$success = false;
$procedure_data = null;

// Recupera ID dalla query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $error_message = 'ID procedura non valido.';
} else {
    try {
        // Recupera i dati della procedura
        $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = ?");
        $stmt->execute([$id]);
        $procedure_data = $stmt->fetch();
        
        if (!$procedure_data) {
            $error_message = 'Procedura non trovata.';
        }
    } catch (Exception $e) {
        $error_message = 'Errore di connessione al database: ' . $e->getMessage();
    }
}

// Gestione aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $procedure_data) {
    $denominazione = trim($_POST['denominazione'] ?? '');
    $valida_dal = $_POST['valida_dal'] ?? '';
    $procedura = trim($_POST['procedura'] ?? '');
    
    if (empty($denominazione)) {
        $error_message = 'La denominazione è obbligatoria.';
    } elseif (empty($valida_dal)) {
        $error_message = 'La data di validità è obbligatoria.';
    } elseif (empty($procedura)) {
        $error_message = 'Il testo della procedura è obbligatorio.';
    } else {
        try {
            // Verifica se esiste già una procedura con la stessa denominazione (escludendo quella corrente)
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE denominazione = ? AND id != ?");
            $check_stmt->execute([$denominazione, $id]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'Esiste già un\'altra procedura con questa denominazione.';
            } else {
                // Aggiornamento nel database
                $stmt = $pdo->prepare("UPDATE procedure_crm SET denominazione = ?, valida_dal = ?, procedura = ? WHERE id = ?");
                
                if ($stmt->execute([$denominazione, $valida_dal, $procedura, $id])) {
                    $success = true;
                    $success_message = 'Procedura aggiornata con successo!';
                    
                    // Ricarica i dati aggiornati
                    $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = ?");
                    $stmt->execute([$id]);
                    $procedure_data = $stmt->fetch();
                } else {
                    $error_message = 'Errore durante l\'aggiornamento della procedura.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Errore di connessione al database: ' . $e->getMessage();
        }
    }
}

// Se ci sono errori critici, mostra solo l'errore
if (!$procedure_data && $error_message) {
    echo '<div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;">';
    echo '<h3>Errore</h3>';
    echo '<p>' . htmlspecialchars($error_message) . '</p>';
    echo '<button onclick="window.close()">Chiudi</button>';
    echo '</div>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Modifica Procedura</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>Test Modifica Procedura - ID: <?= $procedure_data['id'] ?></h2>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label>ID Procedura:</label>
            <input type="text" value="<?= $procedure_data['id'] ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Denominazione *:</label>
            <input type="text" 
                   name="denominazione" 
                   value="<?= htmlspecialchars($_POST['denominazione'] ?? $procedure_data['denominazione']) ?>"
                   required>
        </div>
        
        <div class="form-group">
            <label>Valida Dal *:</label>
            <input type="date" 
                   name="valida_dal" 
                   value="<?= htmlspecialchars($_POST['valida_dal'] ?? $procedure_data['valida_dal']) ?>"
                   required>
        </div>
        
        <div class="form-group">
            <label>Testo Procedura *:</label>
            <textarea name="procedura" 
                      rows="10" 
                      required><?= htmlspecialchars($_POST['procedura'] ?? $procedure_data['procedura']) ?></textarea>
        </div>
        
        <button type="submit">Salva Modifiche</button>
        <button type="button" onclick="window.close()">Annulla</button>
    </form>
    
    <hr>
    <h3>Info Debug:</h3>
    <p><strong>Data creazione:</strong> <?= $procedure_data['data_creazione'] ?></p>
    <p><strong>Data modifica:</strong> <?= $procedure_data['data_modifica'] ?></p>
</body>
</html>
