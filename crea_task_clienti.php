<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$success_message = '';
$error_message = '';
$task_data = null;
$edit_mode = false;

// Controlla se siamo in modalità modifica
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_mode = true;
    $task_id = intval($_GET['edit']);
    
    // Recupera i dati del task esistente dalla tabella task_clienti
    try {
        $stmt = $pdo->prepare("
            SELECT tc.*, c.`Cognome/Ragione sociale`, c.`Nome`
            FROM task_clienti tc
            LEFT JOIN clienti c ON tc.cliente_id = c.id
            WHERE tc.id = ?
        ");
        $stmt->execute([$task_id]);
        $task_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task_data) {
            $error_message = "Task non trovato.";
            $edit_mode = false;
        }
    } catch (Exception $e) {
        $error_message = "Errore nel caricamento del task: " . $e->getMessage();
        $edit_mode = false;
    }
}

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $descrizione = trim($_POST['descrizione'] ?? '');
        $scadenza = $_POST['scadenza'] ?? '';
        $priorita = $_POST['priorita'] ?? 'Media';
        $ricorrenza = intval($_POST['ricorrenza'] ?? 0);
        $tipo_ricorrenza = $_POST['tipo_ricorrenza'] ?? '';
        
        // Validazione
        if ($cliente_id <= 0) {
            throw new Exception("Seleziona un cliente");
        }
        
        if (empty($descrizione)) {
            throw new Exception("La descrizione è obbligatoria");
        }
        
        if (empty($scadenza)) {
            throw new Exception("La data di scadenza è obbligatoria");
        }
        
        // Converti ricorrenza in giorni
        $ricorrenza_giorni = null;
        if ($ricorrenza > 0) {
            switch ($tipo_ricorrenza) {
                case 'giorni':
                    $ricorrenza_giorni = $ricorrenza;
                    break;
                case 'settimane':
                    $ricorrenza_giorni = $ricorrenza * 7;
                    break;
                case 'mesi':
                    $ricorrenza_giorni = $ricorrenza * 30;
                    break;
                case 'anni':
                    $ricorrenza_giorni = $ricorrenza * 365;
                    break;
            }
        }

        $pdo->beginTransaction();

        if ($edit_mode && isset($_POST['task_id'])) {
            // Modifica task esistente
            $task_id = intval($_POST['task_id']);
            
            // Aggiorna la tabella task_clienti direttamente
            $stmt = $pdo->prepare("
                UPDATE task_clienti 
                SET descrizione = ?, scadenza = ?, priorita = ?, ricorrenza = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$descrizione, $scadenza, $priorita, $ricorrenza_giorni, $task_id]);
            
            if (!$result) {
                throw new Exception("Errore nell'aggiornamento del task");
            }
            
            $success_message = "Task modificato con successo!";
        } else {
            // Crea la tabella task_clienti se non esiste
            $pdo->exec("CREATE TABLE IF NOT EXISTS task_clienti (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cliente_id INT NOT NULL,
                descrizione TEXT NOT NULL,
                scadenza DATE NOT NULL,
                priorita ENUM('Alta', 'Media', 'Bassa') DEFAULT 'Media',
                completato TINYINT(1) DEFAULT 0,
                ricorrenza INT NULL,
                data_completamento DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (cliente_id),
                INDEX (scadenza),
                INDEX (completato),
                FOREIGN KEY (cliente_id) REFERENCES clienti(id) ON DELETE CASCADE
            )");
            
            // Nuovo task cliente
            $stmt = $pdo->prepare("
                INSERT INTO task_clienti (cliente_id, descrizione, scadenza, priorita, ricorrenza) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$cliente_id, $descrizione, $scadenza, $priorita, $ricorrenza_giorni]);
            
            if (!$result) {
                throw new Exception("Errore nella creazione del task");
            }
            
            $success_message = "Task creato con successo!";
        }
        
        $pdo->commit();
        
        // Debug: log dell'operazione
        error_log("Task cliente " . ($edit_mode ? "modificato" : "creato") . " con successo. Cliente ID: $cliente_id, Descrizione: $descrizione");
        
        // Reindirizza alla pagina task_clienti con messaggio di successo
        $redirect_url = "task_clienti.php?success=" . urlencode($success_message);
        header("Location: " . $redirect_url);
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Errore: " . $e->getMessage();
        error_log("Errore creazione task cliente: " . $e->getMessage() . " - File: " . $e->getFile() . " - Linea: " . $e->getLine());
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Errore database: " . $e->getMessage();
        error_log("Errore PDO task cliente: " . $e->getMessage() . " - Codice: " . $e->getCode());
    }
}

// Recupera la lista dei clienti
try {
    $stmt = $pdo->prepare("SELECT id, `Cognome/Ragione sociale`, `Nome`, `Codice fiscale` FROM clienti ORDER BY `Cognome/Ragione sociale`, `Nome`");
    $stmt->execute();
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Errore nel caricamento dei clienti: " . $e->getMessage();
    $clienti = [];
}

// Determina ricorrenza per la modalità modifica
$ricorrenza_value = '';
$tipo_ricorrenza_value = 'giorni';
if ($edit_mode && $task_data && !empty($task_data['ricorrenza'])) {
    $giorni = $task_data['ricorrenza'];
    if ($giorni % 365 == 0) {
        $ricorrenza_value = $giorni / 365;
        $tipo_ricorrenza_value = 'anni';
    } elseif ($giorni % 30 == 0) {
        $ricorrenza_value = $giorni / 30;
        $tipo_ricorrenza_value = 'mesi';
    } elseif ($giorni % 7 == 0) {
        $ricorrenza_value = $giorni / 7;
        $tipo_ricorrenza_value = 'settimane';
    } else {
        $ricorrenza_value = $giorni;
        $tipo_ricorrenza_value = 'giorni';
    }
}
?>

<style>
.form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.form-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.form-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.form-container {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    max-width: 800px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.ricorrenza-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    align-items: end;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    justify-content: center;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
}

.alert-dismiss {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.7;
    color: inherit;
}

.alert-dismiss:hover {
    opacity: 1;
}

.help-text {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .form-header h2 {
        font-size: 2rem;
    }
    
    .form-container {
        padding: 1.5rem;
        margin: 0 1rem;
    }
    
    .form-row,
    .ricorrenza-group {
        grid-template-columns: 1fr;
    }
    
    .btn-group {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="form-header">
    <h2><?= $edit_mode ? '✏️ Modifica Task Cliente' : '➕ Crea Nuovo Task Cliente' ?></h2>
    <p>Gestione task specifici per i clienti con scadenze e priorità</p>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <strong>✅ Successo!</strong> <?= htmlspecialchars($success_message) ?>
        <button class="alert-dismiss" onclick="this.parentElement.style.display='none';">×</button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error">
        <strong>❌ Errore!</strong> <?= htmlspecialchars($error_message) ?>
        <button class="alert-dismiss" onclick="this.parentElement.style.display='none';">×</button>
    </div>
<?php endif; ?>

<div class="form-container">
    <form method="post" id="taskForm">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="task_id" value="<?= htmlspecialchars($task_data['id']) ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="cliente_id">👤 Cliente *</label>
            <select name="cliente_id" id="cliente_id" class="form-control" required>
                <option value="">Seleziona un cliente...</option>
                <?php foreach ($clienti as $cliente): ?>
                    <option value="<?= $cliente['id'] ?>" 
                            <?= ($edit_mode && $task_data && $task_data['cliente_id'] == $cliente['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(trim(($cliente['Nome'] ?? '') . ' ' . ($cliente['Cognome/Ragione sociale'] ?? ''))) ?>
                        <?php if (!empty($cliente['Codice fiscale'])): ?>
                            - <?= htmlspecialchars($cliente['Codice fiscale']) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="descrizione">📝 Descrizione Task *</label>
            <textarea name="descrizione" 
                      id="descrizione" 
                      class="form-control" 
                      rows="3" 
                      required 
                      placeholder="Descrivi il task da eseguire..."><?= $edit_mode && $task_data ? htmlspecialchars($task_data['descrizione']) : '' ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="scadenza">📅 Data di Scadenza *</label>
                <input type="date" 
                       name="scadenza" 
                       id="scadenza" 
                       class="form-control" 
                       required
                       value="<?= $edit_mode && $task_data ? htmlspecialchars($task_data['scadenza']) : '' ?>">
                <div class="help-text">Entro quando deve essere completato il task</div>
            </div>

            <div class="form-group">
                <label for="priorita">⚡ Priorità</label>
                <select name="priorita" id="priorita" class="form-control">
                    <option value="Bassa" <?= ($edit_mode && $task_data && $task_data['priorita'] === 'Bassa') ? 'selected' : '' ?>>🟢 Bassa</option>
                    <option value="Media" <?= (!$edit_mode || !$task_data || $task_data['priorita'] === 'Media' || empty($task_data['priorita'])) ? 'selected' : '' ?>>🟡 Media</option>
                    <option value="Alta" <?= ($edit_mode && $task_data && $task_data['priorita'] === 'Alta') ? 'selected' : '' ?>>🔴 Alta</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>🔄 Ricorrenza (opzionale)</label>
            <div class="ricorrenza-group">
                <div>
                    <input type="number" 
                           name="ricorrenza" 
                           id="ricorrenza" 
                           class="form-control" 
                           min="0" 
                           placeholder="0"
                           value="<?= $ricorrenza_value ?>">
                </div>
                <div>
                    <select name="tipo_ricorrenza" id="tipo_ricorrenza" class="form-control">
                        <option value="giorni" <?= $tipo_ricorrenza_value === 'giorni' ? 'selected' : '' ?>>Giorni</option>
                        <option value="settimane" <?= $tipo_ricorrenza_value === 'settimane' ? 'selected' : '' ?>>Settimane</option>
                        <option value="mesi" <?= $tipo_ricorrenza_value === 'mesi' ? 'selected' : '' ?>>Mesi</option>
                        <option value="anni" <?= $tipo_ricorrenza_value === 'anni' ? 'selected' : '' ?>>Anni</option>
                    </select>
                </div>
            </div>
            <div class="help-text">Se il task deve ripetersi, specifica ogni quanto tempo (lascia 0 per task una tantum)</div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <?= $edit_mode ? '💾 Salva Modifiche' : '➕ Crea Task' ?>
            </button>
            <a href="task_clienti.php" class="btn btn-secondary">
                ❌ Annulla
            </a>
        </div>
    </form>
</div>

<script>
// Auto-focus sul primo campo
document.addEventListener('DOMContentLoaded', function() {
    const clienteSelect = document.getElementById('cliente_id');
    if (clienteSelect && !clienteSelect.value) {
        clienteSelect.focus();
    }
});

// Validazione form migliorata
document.getElementById('taskForm').addEventListener('submit', function(e) {
    const clienteId = document.getElementById('cliente_id').value;
    const descrizione = document.getElementById('descrizione').value.trim();
    const scadenza = document.getElementById('scadenza').value;
    
    if (!clienteId) {
        e.preventDefault();
        alert('⚠️ Seleziona un cliente');
        document.getElementById('cliente_id').focus();
        return;
    }
    
    if (!descrizione) {
        e.preventDefault();
        alert('⚠️ Inserisci la descrizione del task');
        document.getElementById('descrizione').focus();
        return;
    }
    
    if (!scadenza) {
        e.preventDefault();
        alert('⚠️ Seleziona la data di scadenza');
        document.getElementById('scadenza').focus();
        return;
    }
    
    // Controllo se la data è nel passato
    const today = new Date();
    const scadenzaDate = new Date(scadenza);
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (scadenzaDate < yesterday) {
        if (!confirm('⚠️ La data di scadenza è nel passato. Sei sicuro di voler continuare?')) {
            e.preventDefault();
            document.getElementById('scadenza').focus();
            return;
        }
    }
    
    // Feedback visivo durante il salvataggio
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '⏳ Salvataggio...';
    submitBtn.disabled = true;
    
    // Ripristina il pulsante se la form non viene inviata
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
});

// Auto-resize textarea
document.getElementById('descrizione').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Suggerimenti per la ricorrenza
document.getElementById('ricorrenza').addEventListener('input', function() {
    const value = parseInt(this.value) || 0;
    const tipo = document.getElementById('tipo_ricorrenza').value;
    
    if (value > 0) {
        let suggerimento = '';
        switch (tipo) {
            case 'giorni':
                if (value === 7) suggerimento = ' (settimanale)';
                else if (value === 30) suggerimento = ' (mensile)';
                else if (value === 365) suggerimento = ' (annuale)';
                break;
            case 'settimane':
                if (value === 4) suggerimento = ' (circa mensile)';
                else if (value === 52) suggerimento = ' (annuale)';
                break;
            case 'mesi':
                if (value === 12) suggerimento = ' (annuale)';
                break;
        }
        
        if (suggerimento) {
            this.title = `Ripeti ogni ${value} ${tipo}${suggerimento}`;
        }
    }
});
</script>

</main>
</body>
</html>
