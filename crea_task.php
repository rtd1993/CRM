
<?php
// Avvia buffer di output per evitare problemi con header
ob_start();

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Verifica se siamo in modalità modifica
$edit_mode = isset($_GET['edit']) && is_numeric($_GET['edit']);
$task_id = $edit_mode ? intval($_GET['edit']) : null;
$task_data = null;

// Se siamo in modalità modifica, carica i dati del task
if ($edit_mode) {
    $stmt = $pdo->prepare("SELECT * FROM task WHERE id = ?");
    $stmt->execute([$task_id]);
    $task_data = $stmt->fetch();
    
    if (!$task_data) {
        header("Location: task.php?error=Task non trovato");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descrizione = $_POST['descrizione'] ?? '';
    $scadenza = $_POST['scadenza'] ?? '';
    $ricorrenza = isset($_POST['ricorrenza']) && $_POST['ricorrenza'] !== '' ? intval($_POST['ricorrenza']) : null;
    
    // Se c'è un campo hidden con l'ID, siamo in modalità modifica
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : null;
    $is_edit = !empty($edit_id);

    if (!empty($descrizione) && !empty($scadenza)) {
        if ($is_edit) {
            // Modifica task esistente
            $stmt = $pdo->prepare("UPDATE task SET descrizione = ?, scadenza = ?, ricorrenza = ? WHERE id = ?");
            $stmt->bindValue(1, $descrizione);
            $stmt->bindValue(2, $scadenza);
            $stmt->bindValue(3, $ricorrenza, is_null($ricorrenza) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(4, $edit_id);
            $stmt->execute();
            
            // Redirect alla lista task con messaggio di successo
            header("Location: task.php?success=2");
            exit;
        } else {
            // Crea nuovo task
            $stmt = $pdo->prepare("INSERT INTO task (descrizione, scadenza, ricorrenza) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $descrizione);
            $stmt->bindValue(2, $scadenza);
            $stmt->bindValue(3, $ricorrenza, is_null($ricorrenza) ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->execute();
            
            // Redirect alla lista task con messaggio di successo
            header("Location: task.php?success=1");
            exit;
        }
    } else {
        $errore = "Inserisci almeno descrizione e scadenza.";
    }
}
?>

<style>
.task-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.task-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.task-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.task-form {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
}

.task-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
    font-size: 1.1rem;
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control[type="date"] {
    cursor: pointer;
}

.form-control[type="number"] {
    max-width: 200px;
}

.form-help {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
    margin-bottom: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.btn {
    padding: 0.8rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
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

.ricorrenza-info {
    background: #e3f2fd;
    border: 1px solid #bbdefb;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.ricorrenza-info h4 {
    margin: 0 0 0.5rem 0;
    color: #1976d2;
    font-size: 1rem;
}

.ricorrenza-info ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #424242;
}

@media (max-width: 768px) {
    .task-header h2 {
        font-size: 2rem;
    }
    
    .task-form {
        padding: 1.5rem;
        margin: 0 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="task-header">
    <h2><?= $edit_mode ? '✏️ Modifica Task' : '➕ Crea Nuovo Task' ?></h2>
    <p><?= $edit_mode ? 'Modifica il task esistente' : 'Aggiungi un nuovo task al sistema di gestione' ?></p>
</div>

<div class="task-form">
    <?php if (!empty($errore)): ?>
        <div class="error-message">
            <strong>⚠️ Errore:</strong> <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php if ($edit_mode): ?>
            <input type="hidden" name="edit_id" value="<?= $task_data['id'] ?>">
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="descrizione">📝 Descrizione</label>
            <input type="text" 
                   id="descrizione" 
                   name="descrizione" 
                   class="form-control" 
                   required 
                   placeholder="Inserisci una descrizione dettagliata del task"
                   value="<?= htmlspecialchars($task_data['descrizione'] ?? $_POST['descrizione'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="scadenza">📅 Scadenza</label>
            <input type="date" 
                   id="scadenza" 
                   name="scadenza" 
                   class="form-control" 
                   required
                   value="<?= htmlspecialchars($task_data['scadenza'] ?? $_POST['scadenza'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="ricorrenza">🔄 Ricorrenza (giorni)</label>
            <input type="number" 
                   id="ricorrenza" 
                   name="ricorrenza" 
                   class="form-control" 
                   min="1" 
                   max="365"
                   placeholder="Lascia vuoto se non ricorrente"
                   value="<?= htmlspecialchars($task_data['ricorrenza'] ?? $_POST['ricorrenza'] ?? '') ?>">
            <div class="form-help">
                Se inserisci un numero, il task si ripeterà ogni X giorni dopo il completamento
            </div>
            
            <div class="ricorrenza-info">
                <h4>💡 Esempi di ricorrenza:</h4>
                <ul>
                    <li><strong>7</strong> = Settimanale</li>
                    <li><strong>30</strong> = Mensile</li>
                    <li><strong>90</strong> = Trimestrale</li>
                    <li><strong>365</strong> = Annuale</li>
                </ul>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 <?= $edit_mode ? 'Salva Modifiche' : 'Salva Task' ?></button>
            <a href="task.php" class="btn btn-secondary">❌ Annulla</a>
        </div>
    </form>
</div>

<script>
// Focus automatico sul campo descrizione
document.getElementById('descrizione').focus();

// Validazione form
document.querySelector('form').addEventListener('submit', function(e) {
    const descrizione = document.getElementById('descrizione').value.trim();
    const scadenza = document.getElementById('scadenza').value;
    
    if (!descrizione || !scadenza) {
        e.preventDefault();
        alert('⚠️ Inserisci almeno descrizione e scadenza.');
        return;
    }
    
    // Mostra loading
    const submitBtn = document.querySelector('.btn-primary');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Salvataggio...';
});
</script>

</main>
</body>
</html>

<?php
// Flush del buffer di output
ob_end_flush();
?>