<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$error_message = '';
$success = false;
$procedure_data = null;

// Recupera ID dalla query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $error_message = 'ID procedura non valido.';
} else {
    try {
        require_once __DIR__ . '/includes/config.php';
        
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
    
    // Validazione
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
                    echo '<script>
                        parent.location.reload();
                        parent.closeModal();
                    </script>';
                    exit;
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
    echo '<div class="modal-backdrop" onclick="closeModal()"></div>
          <div class="modal-content">
              <div class="modal-header">
                  <h3><i class="fas fa-exclamation-triangle me-2"></i>Errore</h3>
                  <button class="btn-close" onclick="closeModal()">
                      <i class="fas fa-times"></i>
                  </button>
              </div>
              <div class="modal-body">
                  <div class="alert alert-danger">
                      <i class="fas fa-exclamation-triangle me-2"></i>' . htmlspecialchars($error_message) . '
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" onclick="closeModal()">
                      <i class="fas fa-times me-2"></i>Chiudi
                  </button>
              </div>
          </div>';
    exit;
}
?>

<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit me-2"></i>Modifica Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="editProcedureForm">
        <div class="modal-body">
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <div class="procedure-info">
                <p><strong>ID:</strong> <?= $procedure_data['id'] ?></p>
                <p><strong>Creata il:</strong> <?= date('d/m/Y H:i', strtotime($procedure_data['data_creazione'])) ?></p>
                <?php if ($procedure_data['data_modifica'] != $procedure_data['data_creazione']): ?>
                    <p><strong>Ultima modifica:</strong> <?= date('d/m/Y H:i', strtotime($procedure_data['data_modifica'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="denominazione" class="form-label required">Denominazione</label>
                <input type="text" 
                       class="form-control" 
                       id="denominazione" 
                       name="denominazione" 
                       value="<?= htmlspecialchars($_POST['denominazione'] ?? $procedure_data['denominazione']) ?>"
                       placeholder="Es: Procedura Gestione Ordini"
                       required maxlength="255">
                <div class="form-help">
                    Nome identificativo della procedura (massimo 255 caratteri)
                </div>
            </div>
            
            <div class="form-group">
                <label for="valida_dal" class="form-label required">Valida Dal</label>
                <input type="date" 
                       class="form-control" 
                       id="valida_dal" 
                       name="valida_dal" 
                       value="<?= htmlspecialchars($_POST['valida_dal'] ?? $procedure_data['valida_dal']) ?>"
                       required>
                <div class="form-help">
                    Data da cui la procedura entra in vigore
                </div>
            </div>
            
            <div class="form-group">
                <label for="procedura" class="form-label required">Testo Procedura</label>
                <textarea class="form-control" 
                          id="procedura" 
                          name="procedura" 
                          rows="12" 
                          placeholder="Inserisci qui il testo completo della procedura..."
                          required><?= htmlspecialchars($_POST['procedura'] ?? $procedure_data['procedura']) ?></textarea>
                <div class="form-help">
                    Descrizione dettagliata della procedura con tutti i passaggi necessari
                </div>
                <div class="char-counter">
                    <span id="charCount">0</span> caratteri
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>Annulla
            </button>
            <button type="button" class="btn btn-info me-2" onclick="previewProcedure()">
                <i class="fas fa-eye me-2"></i>Anteprima
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Salva Modifiche
            </button>
        </div>
    </form>
</div>

<style>
.modal-content {
    width: 900px;
    max-width: 95vw;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e1e5e9;
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.4rem;
    font-weight: 600;
}

.btn-close {
    background: none;
    border: none;
    color: #212529;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-close:hover {
    background-color: rgba(0,0,0,0.1);
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    padding: 1rem 2rem 2rem;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.procedure-info {
    background: #f8f9fa;
    border-left: 4px solid #6f42c1;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-radius: 0 8px 8px 0;
}

.procedure-info p {
    margin: 0.3rem 0;
    font-size: 0.9rem;
    color: #495057;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.form-label.required::after {
    content: ' *';
    color: #dc3545;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #ffc107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
}

.form-help {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.3rem;
}

.char-counter {
    font-size: 0.8rem;
    color: #6c757d;
    text-align: right;
    margin-top: 0.3rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
}

.btn-primary {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
    transform: translateY(-1px);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .modal-content {
        width: 95vw;
        margin: 1rem;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1rem 1.5rem 1.5rem;
        flex-direction: column-reverse;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function closeModal() {
    parent.closeModal();
}

function previewProcedure() {
    const proceduraText = document.getElementById('procedura').value;
    const denominazione = document.getElementById('denominazione').value;
    
    if (!proceduraText.trim()) {
        alert('Inserisci il testo della procedura per visualizzare l\'anteprima.');
        return;
    }
    
    // Crea una nuova finestra per l'anteprima
    const previewWindow = window.open('', '_blank', 'width=800,height=600');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Anteprima: ${denominazione}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 2rem; line-height: 1.6; }
                h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem; }
                .meta { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 2rem; }
                .content { white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <h1>${denominazione}</h1>
            <div class="meta">
                <strong>Data validità:</strong> ${document.getElementById('valida_dal').value}<br>
                <strong>Anteprima generata il:</strong> ${new Date().toLocaleString('it-IT')}
            </div>
            <div class="content">${proceduraText}</div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}

// Character counter
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('procedura');
    const charCount = document.getElementById('charCount');
    
    function updateCharCount() {
        charCount.textContent = textarea.value.length;
    }
    
    textarea.addEventListener('input', updateCharCount);
    updateCharCount(); // Initial count
    
    // Auto-resize textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.max(this.scrollHeight, 200) + 'px';
    });
});

// Validazione form
document.getElementById('editProcedureForm').addEventListener('submit', function(e) {
    const denominazione = document.getElementById('denominazione').value.trim();
    const valida_dal = document.getElementById('valida_dal').value;
    const procedura = document.getElementById('procedura').value.trim();
    
    if (!denominazione) {
        alert('La denominazione è obbligatoria.');
        e.preventDefault();
        return;
    }
    
    if (!valida_dal) {
        alert('La data di validità è obbligatoria.');
        e.preventDefault();
        return;
    }
    
    if (!procedura) {
        alert('Il testo della procedura è obbligatorio.');
        e.preventDefault();
        return;
    }
    
    // Mostra loading
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    submitBtn.disabled = true;
});

// Conferma modifiche se ci sono cambiamenti
let originalData = {
    denominazione: document.getElementById('denominazione').value,
    valida_dal: document.getElementById('valida_dal').value,
    procedura: document.getElementById('procedura').value
};

function hasChanges() {
    return (
        document.getElementById('denominazione').value !== originalData.denominazione ||
        document.getElementById('valida_dal').value !== originalData.valida_dal ||
        document.getElementById('procedura').value !== originalData.procedura
    );
}

// Override closeModal per confermare se ci sono modifiche
window.closeModal = function() {
    if (hasChanges() && !confirm('Ci sono modifiche non salvate. Sei sicuro di voler chiudere?')) {
        return;
    }
    parent.closeModal();
};
</script>
