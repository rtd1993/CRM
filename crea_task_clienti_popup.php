<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

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
            SELECT tc.*, c.`Cognome_Ragione_sociale`, c.`Nome`
            FROM task_clienti tc
            LEFT JOIN clienti c ON tc.cliente_id = c.id
            WHERE tc.id = ?
        ");
        $stmt->execute([$task_id]);
        $task_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$task_data) {
            echo '<script>alert("Task non trovato"); window.parent.closeTaskClientModal();</script>';
            exit;
        }
    } catch (Exception $e) {
        echo '<script>alert("Errore nel caricamento del task"); window.parent.closeTaskClientModal();</script>';
        exit;
    }
}

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $descrizione = trim($_POST['descrizione'] ?? '');
        $scadenza = $_POST['scadenza'] ?? '';
        $ricorrenza = intval($_POST['ricorrenza'] ?? 0);
        $tipo_ricorrenza = $_POST['tipo_ricorrenza'] ?? '';
        $fatturabile = isset($_POST['fatturabile']) ? 1 : 0;
        $assegnato_a = isset($_POST['assegnato_a']) && $_POST['assegnato_a'] !== '' ? intval($_POST['assegnato_a']) : null;
        
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
        $ricorrenza_giorni = 0;
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
            
            $stmt = $pdo->prepare("
                UPDATE task_clienti 
                SET cliente_id = ?, titolo = ?, descrizione = ?, scadenza = ?, ricorrenza = ?, fatturabile = ?, assegnato_a = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$cliente_id, substr($descrizione, 0, 255), $descrizione, $scadenza, $ricorrenza_giorni, $fatturabile, $assegnato_a, $task_id]);
            
            if (!$result) {
                throw new Exception("Errore nell'aggiornamento del task");
            }
            
            $pdo->commit();
            echo '<script>alert("Task modificato con successo!"); window.parent.location.reload();</script>';
            exit;
        } else {
            // Nuovo task cliente
            $stmt = $pdo->prepare("
                INSERT INTO task_clienti (cliente_id, titolo, descrizione, scadenza, ricorrenza, fatturabile, assegnato_a) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$cliente_id, substr($descrizione, 0, 255), $descrizione, $scadenza, $ricorrenza_giorni, $fatturabile, $assegnato_a]);
            
            if (!$result) {
                throw new Exception("Errore nella creazione del task");
            }
            
            $pdo->commit();
            echo '<script>alert("Task creato con successo!"); window.parent.location.reload();</script>';
            exit;
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Errore: " . $e->getMessage();
    }
}

// Recupera la lista dei clienti
try {
    $stmt = $pdo->prepare("SELECT id, `Cognome_Ragione_sociale`, `Nome`, `Codice_fiscale` FROM clienti ORDER BY `Cognome_Ragione_sociale`, `Nome`");
    $stmt->execute();
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Errore nel caricamento dei clienti: " . $e->getMessage();
    $clienti = [];
}

// Carica lista utenti per assegnazione
try {
    $stmt_users = $pdo->prepare("SELECT id, nome FROM utenti ORDER BY nome");
    $stmt_users->execute();
    $utenti = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Errore nel caricamento degli utenti: " . $e->getMessage();
    $utenti = [];
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
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $edit_mode ? 'Modifica Task Cliente' : 'Crea Nuovo Task Cliente' ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }

        .popup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            margin: -20px -20px 20px -20px;
            text-align: center;
        }

        .popup-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 300;
        }

        .task-form {
            background: white;
            border-radius: 0 0 12px 12px;
            padding: 2rem;
            margin: 0 -20px -20px -20px;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: #667eea;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
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
            padding-top: 1rem;
            border-top: 1px solid #e1e5e9;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }

        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        @media (max-width: 768px) {
            .form-row,
            .ricorrenza-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="popup-header">
        <h2><?= $edit_mode ? '✏️ Modifica Task Cliente' : '➕ Crea Nuovo Task Cliente' ?></h2>
    </div>

    <div class="task-form">
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <strong>❌ Errore!</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="taskClientPopupForm">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="task_id" value="<?= htmlspecialchars($task_data['id']) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="cliente_search">👤 Cerca Cognome Cliente *</label>
                <input type="text" id="cliente_search" class="form-control" autocomplete="off" placeholder="Inizia a scrivere il cognome...">
                <ul id="clienti_list" style="list-style:none; padding-left:0; margin-top:8px; max-height:180px; overflow-y:auto; border:1px solid #e1e5e9; border-radius:8px; background:#fff; display:none;"></ul>
                <input type="hidden" name="cliente_id" id="cliente_id" required>
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
                <label for="assegnato_a">👤 Assegnato a</label>
                <select id="assegnato_a" name="assegnato_a" class="form-control">
                    <option value="">🌐 Task generale (visibile a tutti)</option>
                    <?php foreach ($utenti as $utente): ?>
                        <option value="<?= $utente['id'] ?>" 
                                <?= ($edit_mode && $task_data && ($task_data['assegnato_a'] ?? '') == $utente['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($utente['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">Se non assegnato, il task sarà visibile a tutti. Se assegnato, solo l'utente specifico e admin/developer potranno vederlo.</div>
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
                               max="100"
                               placeholder="0 = nessuna ricorrenza"
                               value="<?= htmlspecialchars($ricorrenza_value) ?>">
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

            <div class="form-group">
                <label>💰 Fatturazione</label>
                <div class="checkbox-group">
                    <input type="checkbox" 
                           name="fatturabile" 
                           id="fatturabile" 
                           value="1"
                           <?= $edit_mode && $task_data && $task_data['fatturabile'] ? 'checked' : '' ?>>
                    <label for="fatturabile">Task da fatturare</label>
                </div>
                <div class="help-text">Seleziona se questo task deve essere incluso nella fatturazione</div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <?= $edit_mode ? '💾 Salva Modifiche' : '➕ Crea Task' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.parent.closeTaskClientModal()">
                    ❌ Annulla
                </button>
            </div>
        </form>
    </div>

    <script>
        // Autocomplete cognome cliente
        const clienteSearch = document.getElementById('cliente_search');
        const clientiList = document.getElementById('clienti_list');
        const clienteIdInput = document.getElementById('cliente_id');

        clienteSearch.addEventListener('input', function() {
            const q = this.value.trim();
            clientiList.innerHTML = '';
            clienteIdInput.value = '';
            if (q.length < 1) {
                clientiList.style.display = 'none';
                return;
            }
            fetch('ajax/clienti_search.php?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    if (data.length === 0) {
                        clientiList.style.display = 'none';
                        return;
                    }
                    clientiList.innerHTML = '';
                    data.forEach(cliente => {
                        const li = document.createElement('li');
                        li.textContent = cliente.cognome + ' ' + cliente.nome;
                        li.style.padding = '8px 12px';
                        li.style.cursor = 'pointer';
                        li.addEventListener('click', function() {
                            clienteSearch.value = cliente.cognome + ' ' + cliente.nome;
                            clienteIdInput.value = cliente.id;
                            clientiList.style.display = 'none';
                        });
                        clientiList.appendChild(li);
                    });
                    clientiList.style.display = 'block';
                });
        });

        // Nascondi lista se clicco fuori
        document.addEventListener('click', function(e) {
            if (!clienteSearch.contains(e.target) && !clientiList.contains(e.target)) {
                clientiList.style.display = 'none';
            }
        });
        // Auto-focus sul primo campo
        document.addEventListener('DOMContentLoaded', function() {
            const clienteSelect = document.getElementById('cliente_id');
            if (clienteSelect && !clienteSelect.value) {
                clienteSelect.focus();
            }
        });

        // Validazione form
        document.getElementById('taskClientPopupForm').addEventListener('submit', function(e) {
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
            
            // Feedback visivo durante il salvataggio
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '⏳ Salvataggio...';
            submitBtn.disabled = true;
        });

        // Auto-resize textarea
        document.getElementById('descrizione').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>
