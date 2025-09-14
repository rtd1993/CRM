<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

// Gestione creazione nuova procedura
if (isset($_POST['crea_procedura'])) {
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
            // Verifica se esiste già una procedura con la stessa denominazione
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE denominazione = ?");
            $check_stmt->execute([$denominazione]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'Esiste già una procedura con questa denominazione.';
            } else {
                // Inserimento nel database
                $stmt = $pdo->prepare("INSERT INTO procedure_crm (denominazione, valida_dal, procedura) VALUES (?, ?, ?)");
                
                if ($stmt->execute([$denominazione, $valida_dal, $procedura])) {
                    $success_message = "Procedura creata con successo!";
                } else {
                    $error_message = 'Errore durante il salvataggio della procedura.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Errore di connessione al database: ' . $e->getMessage();
        }
    }
}

// Gestione modifica procedura
if (isset($_POST['modifica_procedura'])) {
    $id = (int)$_POST['id'];
    $denominazione = trim($_POST['denominazione'] ?? '');
    $valida_dal = $_POST['valida_dal'] ?? '';
    $procedura = trim($_POST['procedura'] ?? '');
    
    if ($id <= 0) {
        $error_message = 'ID procedura non valido.';
    } elseif (empty($denominazione)) {
        $error_message = 'La denominazione è obbligatoria.';
    } elseif (empty($valida_dal)) {
        $error_message = 'La data di validità è obbligatoria.';
    } elseif (empty($procedura)) {
        $error_message = 'Il testo della procedura è obbligatorio.';
    } else {
        try {
            // Prima verifica se la procedura esiste
            $exists_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE id = ?");
            $exists_stmt->execute([$id]);
            
            if ($exists_stmt->fetchColumn() == 0) {
                $error_message = 'La procedura da modificare non esiste.';
            } else {
                // Verifica se esiste già una procedura con la stessa denominazione (escludendo quella corrente)
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM procedure_crm WHERE denominazione = ? AND id != ?");
                $check_stmt->execute([$denominazione, $id]);
                
                if ($check_stmt->fetchColumn() > 0) {
                    $error_message = 'Esiste già un\'altra procedura con questa denominazione.';
                } else {
                    // Aggiornamento nel database
                    $stmt = $pdo->prepare("UPDATE procedure_crm SET denominazione = ?, valida_dal = ?, procedura = ? WHERE id = ?");
                    
                    if ($stmt->execute([$denominazione, $valida_dal, $procedura, $id])) {
                        $success_message = "Procedura modificata con successo!";
                    } else {
                        $error_message = 'Errore durante l\'aggiornamento della procedura.';
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Errore di connessione al database: ' . $e->getMessage();
        }
    }
}

// Gestione eliminazione procedura
if (isset($_POST['elimina_procedura'])) {
    $id = (int)$_POST['id'];
    
    if ($id <= 0) {
        $error_message = 'ID procedura non valido.';
    } else {
        try {
            // Prima verifica se la procedura esiste e ottieni il nome
            $exists_stmt = $pdo->prepare("SELECT denominazione FROM procedure_crm WHERE id = ?");
            $exists_stmt->execute([$id]);
            $procedura_nome = $exists_stmt->fetchColumn();
            
            if (!$procedura_nome) {
                $error_message = 'La procedura da eliminare non esiste.';
            } else {
                // Eliminazione dal database
                $stmt = $pdo->prepare("DELETE FROM procedure_crm WHERE id = ?");
                
                if ($stmt->execute([$id])) {
                    $success_message = "Procedura '$procedura_nome' eliminata con successo!";
                } else {
                    $error_message = 'Errore durante l\'eliminazione della procedura.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Errore di connessione al database: ' . $e->getMessage();
        }
    }
}

// Caricamento procedure esistenti
try {
    $stmt = $pdo->query("SELECT * FROM procedure_crm ORDER BY data_creazione DESC");
    $procedure = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = 'Errore nel caricamento delle procedure: ' . $e->getMessage();
    $procedure = [];
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-list-check me-2"></i>Gestione Procedure
                    </h4>
                    <button class="btn btn-light" onclick="openCreateModal()">
                        <i class="fas fa-plus me-2"></i>Nuova Procedura
                    </button>
                </div>
                <div class="card-body">
                    <!-- Alert Messages -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Procedure List -->
                    <?php if (empty($procedure)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nessuna procedura presente</h5>
                            <p class="text-muted">Inizia creando la tua prima procedura</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Denominazione</th>
                                        <th>Valida dal</th>
                                        <th>Data Creazione</th>
                                        <th>Data Modifica</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($procedure as $proc): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($proc['denominazione']) ?></strong></td>
                                            <td><?= date('d/m/Y', strtotime($proc['valida_dal'])) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($proc['data_creazione'])) ?></td>
                                            <td><?= $proc['data_modifica'] ? date('d/m/Y H:i', strtotime($proc['data_modifica'])) : '-' ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-info btn-sm" onclick="viewProcedure(<?= $proc['id'] ?>)" title="Visualizza">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-warning btn-sm" onclick="editProcedure(<?= $proc['id'] ?>)" title="Modifica">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteProcedure(<?= $proc['id'] ?>, '<?= htmlspecialchars($proc['denominazione'], ENT_QUOTES) ?>')" title="Elimina">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <a href="stampa_procedura.php?id=<?= $proc['id'] ?>" class="btn btn-secondary btn-sm" title="Stampa" target="_blank">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Container -->
<div id="modalContainer"></div>

<!-- Form Eliminazione (hidden) -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="elimina_procedura" value="1">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
// Array delle procedure per JavaScript
const procedureData = <?= json_encode($procedure) ?>;

function openCreateModal() {
    const modalHTML = `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-plus me-2"></i>Nuova Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="createProcedureForm">
        <div class="modal-body">
            <div class="mb-3">
                <label for="denominazione" class="form-label">Denominazione *</label>
                <input type="text" class="form-control" id="denominazione" name="denominazione" required maxlength="255">
            </div>
            
            <div class="mb-3">
                <label for="valida_dal" class="form-label">Valida dal *</label>
                <input type="date" class="form-control" id="valida_dal" name="valida_dal" required>
            </div>
            
            <div class="mb-3">
                <label for="procedura" class="form-label">Testo Procedura *</label>
                <textarea class="form-control" id="procedura" name="procedura" rows="6" required placeholder="Inserire il testo della procedura..."></textarea>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>Annulla
            </button>
            <button type="submit" class="btn btn-primary" name="crea_procedura">
                <i class="fas fa-save me-2"></i>Salva Procedura
            </button>
        </div>
    </form>
</div>

<style>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    background: #28a745;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: end;
    gap: 10px;
}

.btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.btn-close:hover {
    opacity: 0.8;
}
</style>
    `;
    
    document.getElementById('modalContainer').innerHTML = modalHTML;
}

function viewProcedure(id) {
    const proc = procedureData.find(p => p.id == id);
    if (!proc) {
        alert('Procedura non trovata');
        return;
    }
    
    const modalHTML = `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-eye me-2"></i>Visualizza Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label fw-bold">Denominazione</label>
            <div class="form-control-plaintext border rounded p-2 bg-light">\${proc.denominazione}</div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Valida dal</label>
            <div class="form-control-plaintext border rounded p-2 bg-light">\${new Date(proc.valida_dal).toLocaleDateString('it-IT')}</div>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Testo Procedura</label>
            <div class="form-control-plaintext border rounded p-2 bg-light" style="white-space: pre-wrap; min-height: 100px;">\${proc.procedura}</div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <label class="form-label fw-bold">Data Creazione</label>
                <div class="form-control-plaintext border rounded p-2 bg-light">\${new Date(proc.data_creazione).toLocaleString('it-IT')}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Ultima Modifica</label>
                <div class="form-control-plaintext border rounded p-2 bg-light">\${proc.data_modifica ? new Date(proc.data_modifica).toLocaleString('it-IT') : 'Mai modificata'}</div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">
            <i class="fas fa-times me-2"></i>Chiudi
        </button>
        <a href="stampa_procedura.php?id=\${proc.id}" class="btn btn-primary" target="_blank">
            <i class="fas fa-print me-2"></i>Stampa
        </a>
    </div>
</div>

<style>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    background: #17a2b8;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: end;
    gap: 10px;
}

.btn-close {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.btn-close:hover {
    opacity: 0.8;
}
</style>
    `;
    
    document.getElementById('modalContainer').innerHTML = modalHTML;
}

function editProcedure(id) {
    const proc = procedureData.find(p => p.id == id);
    if (!proc) {
        alert('Procedura non trovata');
        return;
    }
    
    const modalHTML = `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit me-2"></i>Modifica Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="editProcedureForm">
        <input type="hidden" name="id" value="\${proc.id}">
        <div class="modal-body">
            <div class="mb-3">
                <label for="edit_denominazione" class="form-label">Denominazione *</label>
                <input type="text" class="form-control" id="edit_denominazione" name="denominazione" value="\${proc.denominazione}" required maxlength="255">
            </div>
            
            <div class="mb-3">
                <label for="edit_valida_dal" class="form-label">Valida dal *</label>
                <input type="date" class="form-control" id="edit_valida_dal" name="valida_dal" value="\${proc.valida_dal}" required>
            </div>
            
            <div class="mb-3">
                <label for="edit_procedura" class="form-label">Testo Procedura *</label>
                <textarea class="form-control" id="edit_procedura" name="procedura" rows="6" required>\${proc.procedura}</textarea>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>Annulla
            </button>
            <button type="submit" class="btn btn-warning" name="modifica_procedura">
                <i class="fas fa-save me-2"></i>Salva Modifiche
            </button>
        </div>
    </form>
</div>

<style>
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    background: #ffc107;
    color: #212529;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
    display: flex;
    justify-content: end;
    gap: 10px;
}

.btn-close {
    background: none;
    border: none;
    color: #212529;
    font-size: 18px;
    cursor: pointer;
}

.btn-close:hover {
    opacity: 0.8;
}
</style>
    `;
    
    document.getElementById('modalContainer').innerHTML = modalHTML;
}

function deleteProcedure(id, nome) {
    if (confirm('Sei sicuro di voler eliminare la procedura "' + nome + '"?\\n\\nQuesta azione non può essere annullata.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('modalContainer').innerHTML = '';
}

// Chiudi modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
