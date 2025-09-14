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
        $error_message = 'Il testo della procedura è function getEditModalHTML(proc) {
    console.log('Creazione modal di modifica per procedura:', proc);
    
    return `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit me-2"></i>Modifica Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="editProcedureForm">
        <input type="hidden" name="id" value="${proc.id}" id="edit_id">
        <div class="modal-body">`;;
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
    // Debug dei dati ricevuti
    error_log('Modifica procedura - Dati POST: ' . print_r($_POST, true));
    
    $id = (int)$_POST['id'];
    $denominazione = trim($_POST['denominazione'] ?? '');
    $valida_dal = $_POST['valida_dal'] ?? '';
    $procedura = trim($_POST['procedura'] ?? '');
    
    error_log("Modifica procedura ID: $id, Denominazione: $denominazione");
    
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
                    
                    $result = $stmt->execute([$denominazione, $valida_dal, $procedura, $id]);
                    $rowsAffected = $stmt->rowCount();
                    
                    error_log("Update result: $result, Rows affected: $rowsAffected");
                    
                    if ($result && $rowsAffected > 0) {
                        $success_message = "Procedura aggiornata con successo!";
                        error_log("Procedura $id aggiornata con successo");
                    } elseif ($result && $rowsAffected == 0) {
                        $success_message = "Nessuna modifica necessaria (dati identici).";
                    } else {
                        $error_message = 'Errore durante l\'aggiornamento della procedura.';
                        error_log("Errore aggiornamento procedura $id");
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = 'Errore di connessione al database: ' . $e->getMessage();
            error_log("Eccezione modifica procedura: " . $e->getMessage());
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

// Recupero tutte le procedure
$stmt = $pdo->prepare("SELECT * FROM procedure_crm ORDER BY denominazione ASC");
$stmt->execute();
$procedure = $stmt->fetchAll();
?>

<style>
.page-header {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.page-header h2 {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.page-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.procedure-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.procedure-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.procedure-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.procedure-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.procedure-date {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0.3rem 0 0 0;
}

.procedure-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-view {
    background: #17a2b8;
    color: white;
}

.btn-view:hover {
    background: #138496;
    color: white;
    transform: translateY(-1px);
}

.btn-edit {
    background: #ffc107;
    color: #212529;
}

.btn-edit:hover {
    background: #e0a800;
    color: #212529;
    transform: translateY(-1px);
}

.btn-print {
    background: #28a745;
    color: white;
}

.btn-print:hover {
    background: #218838;
    color: white;
    transform: translateY(-1px);
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
    color: white;
    transform: translateY(-1px);
}

.create-btn {
    background: linear-gradient(135deg, #6f42c1, #e83e8c);
    color: white;
    padding: 0.8rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.create-btn:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.stats-bar {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
    color: #6c757d;
}

.procedure-preview {
    padding: 0 1.5rem 1.5rem;
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
}

.procedure-text {
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

/* Stili per i modal */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1040;
    backdrop-filter: blur(3px);
}

.modal-content {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 1050;
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
    width: 800px;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e1e5e9;
    background: linear-gradient(135deg, #6f42c1, #e83e8c);
    color: white;
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
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.btn-close:hover {
    background-color: rgba(255,255,255,0.2);
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
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
}

.form-help {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.3rem;
}

/* Stili per visualizzazione */
.view-field {
    margin-bottom: 1.5rem;
}

.view-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.view-content {
    background: #f8f9fa;
    border-left: 4px solid #17a2b8;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
    font-size: 1rem;
    line-height: 1.6;
}

.procedure-text-view {
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 300px;
    overflow-y: auto;
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
    background: linear-gradient(135deg, #6f42c1, #e83e8c);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .procedure-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .procedure-actions {
        flex-wrap: wrap;
    }
    
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

<main class="container mt-4">
    <div class="page-header">
        <h2><i class="fas fa-clipboard-list me-2"></i>Gestione Procedure</h2>
        <p>Gestisci tutte le procedure aziendali e operative</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="stats-bar flex-fill me-3">
            <strong><?= count($procedure) ?></strong> procedure totali
        </div>
        <button class="create-btn" onclick="openCreateModal()">
            <i class="fas fa-plus"></i>
            Crea Procedura
        </button>
    </div>

    <?php if (empty($procedure)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h3>Nessuna procedura presente</h3>
            <p>Inizia creando la prima procedura aziendale</p>
            <button class="create-btn mt-3" onclick="openCreateModal()">
                <i class="fas fa-plus"></i>
                Crea Prima Procedura
            </button>
        </div>
    <?php else: ?>
        <div class="procedure-list">
            <?php foreach ($procedure as $proc): ?>
                <div class="procedure-card">
                    <div class="procedure-header">
                        <div>
                            <h3 class="procedure-title"><?= htmlspecialchars($proc['denominazione']) ?></h3>
                            <p class="procedure-date">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Valida dal: <?= date('d/m/Y', strtotime($proc['valida_dal'])) ?>
                            </p>
                        </div>
                        <div class="procedure-actions">
                            <button class="btn-action btn-view" onclick="viewProcedure(<?= $proc['id'] ?>)" title="Visualizza">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-action btn-edit" onclick="editProcedure(<?= $proc['id'] ?>)" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-print" onclick="printProcedure(<?= $proc['id'] ?>)" title="Stampa">
                                <i class="fas fa-print"></i>
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteProcedure(<?= $proc['id'] ?>, '<?= htmlspecialchars($proc['denominazione'], ENT_QUOTES) ?>')" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="procedure-preview">
                        <div class="procedure-text">
                            <?= nl2br(htmlspecialchars(substr($proc['procedura'], 0, 200))) ?><?= strlen($proc['procedura']) > 200 ? '...' : '' ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Modal Container -->
<div id="modalContainer"></div>

<!-- Form Eliminazione (hidden) -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="elimina_procedura" value="1">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function openCreateModal() {
    const modal = document.getElementById('modalContainer');
    modal.innerHTML = getCreateModalHTML();
    document.body.style.overflow = 'hidden';
}

function getCreateModalHTML() {
    return `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-plus-circle me-2"></i>Nuova Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="createProcedureForm" action="">
        <div class="modal-body">
            <div class="form-group">
                <label for="create_denominazione" class="form-label required">Denominazione</label>
                <input type="text" 
                       class="form-control" 
                       id="create_denominazione" 
                       name="denominazione" 
                       placeholder="Es: Procedura Gestione Ordini"
                       required maxlength="255">
                <div class="form-help">
                    Nome identificativo della procedura (massimo 255 caratteri)
                </div>
            </div>
            
            <div class="form-group">
                <label for="create_valida_dal" class="form-label required">Valida Dal</label>
                <input type="date" 
                       class="form-control" 
                       id="create_valida_dal" 
                       name="valida_dal" 
                       value="${new Date().toISOString().split('T')[0]}"
                       required>
                <div class="form-help">
                    Data da cui la procedura entra in vigore
                </div>
            </div>
            
            <div class="form-group">
                <label for="create_procedura" class="form-label required">Testo Procedura</label>
                <textarea class="form-control" 
                          id="create_procedura" 
                          name="procedura" 
                          rows="12" 
                          placeholder="Inserisci qui il testo completo della procedura..."
                          required></textarea>
                <div class="form-help">
                    Descrizione dettagliata della procedura con tutti i passaggi necessari
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>Annulla
            </button>
            <button type="button" class="btn btn-primary" onclick="submitCreateForm()">
                <i class="fas fa-save me-2"></i>Salva Procedura
            </button>
        </div>
    </form>
</div>
<script>
function submitCreateForm() {
    const form = document.getElementById('createProcedureForm');
    const formData = new FormData(form);
    
    // Validazione
    const denominazione = formData.get('denominazione');
    const valida_dal = formData.get('valida_dal');
    const procedura = formData.get('procedura');
    
    if (!denominazione || !valida_dal || !procedura) {
        alert('Tutti i campi sono obbligatori.');
        return;
    }
    
    // Aggiungi il flag di creazione
    formData.append('crea_procedura', '1');
    
    // Disabilita il pulsante
    const btn = event.target;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    btn.disabled = true;
    
    // Crea form nascosto per il submit
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'post';
    hiddenForm.style.display = 'none';
    
    for (let pair of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = pair[0];
        input.value = pair[1];
        hiddenForm.appendChild(input);
    }
    
    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}
</script>`;
}

function editProcedure(id) {
    // Recupera i dati della procedura dal DOM
    const procedureCards = document.querySelectorAll('.procedure-card');
    let procedureData = null;
    
    // Trova la procedura nel DOM (metodo semplificato)
    // In alternativa potresti fare una fetch per recuperare i dati
    fetch('/get_procedure_data.php?id=' + id, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('modalContainer');
            modal.innerHTML = getEditModalHTML(data.procedure);
            document.body.style.overflow = 'hidden';
        } else {
            alert('Errore nel caricamento della procedura: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore nel caricamento della procedura');
    });
}

function getEditModalHTML(proc) {
    return `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fas fa-edit me-2"></i>Modifica Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form method="post" id="editProcedureForm">
        <input type="hidden" name="id" value="${proc.id}">
        <div class="modal-body">
            <div class="procedure-info">
                <p><strong>ID:</strong> ${proc.id}</p>
                <p><strong>Creata il:</strong> ${new Date(proc.data_creazione).toLocaleString('it-IT')}</p>
                ${proc.data_modifica !== proc.data_creazione ? '<p><strong>Ultima modifica:</strong> ' + new Date(proc.data_modifica).toLocaleString('it-IT') + '</p>' : ''}
            </div>
            
            <div class="form-group">
                <label for="edit_denominazione" class="form-label required">Denominazione</label>
                <input type="text" 
                       class="form-control" 
                       id="edit_denominazione" 
                       name="denominazione" 
                       value="${proc.denominazione}"
                       placeholder="Es: Procedura Gestione Ordini"
                       required maxlength="255">
                <div class="form-help">
                    Nome identificativo della procedura (massimo 255 caratteri)
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_valida_dal" class="form-label required">Valida Dal</label>
                <input type="date" 
                       class="form-control" 
                       id="edit_valida_dal" 
                       name="valida_dal" 
                       value="${proc.valida_dal}"
                       required>
                <div class="form-help">
                    Data da cui la procedura entra in vigore
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_procedura" class="form-label required">Testo Procedura</label>
                <textarea class="form-control" 
                          id="edit_procedura" 
                          name="procedura" 
                          rows="12" 
                          placeholder="Inserisci qui il testo completo della procedura..."
                          required>${proc.procedura}</textarea>
                <div class="form-help">
                    Descrizione dettagliata della procedura con tutti i passaggi necessari
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                <i class="fas fa-times me-2"></i>Annulla
            </button>
            <button type="button" class="btn btn-primary" onclick="submitEditForm()">
                <i class="fas fa-save me-2"></i>Salva Modifiche
            </button>
        </div>
    </form>
</div>
<script>
function submitEditForm() {
    const form = document.getElementById('editProcedureForm');
    const formData = new FormData(form);
    
    // Log per debug
    console.log('Submit form modifica - Dati form:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Validazione
    const id = formData.get('id');
    const denominazione = formData.get('denominazione');
    const valida_dal = formData.get('valida_dal');
    const procedura = formData.get('procedura');
    
    if (!id || !denominazione || !valida_dal || !procedura) {
        alert('Tutti i campi sono obbligatori.');
        return;
    }
    
    // Aggiungi il flag di modifica
    formData.append('modifica_procedura', '1');
    
    // Disabilita il pulsante
    const btn = event.target;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvataggio...';
    btn.disabled = true;
    
    // Crea form nascosto per il submit
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'post';
    hiddenForm.style.display = 'none';
    
    for (let pair of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = pair[0];
        input.value = pair[1];
        hiddenForm.appendChild(input);
    }
    
    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}
</script>`;
}

function viewProcedure(id) {
    // Recupera i dati della procedura per visualizzazione
    fetch('/get_procedure_data.php?id=' + id, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('modalContainer');
            modal.innerHTML = getViewModalHTML(data.procedure);
            document.body.style.overflow = 'hidden';
        } else {
            alert('Errore nel caricamento della procedura: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore nel caricamento della procedura');
    });
}

function getViewModalHTML(proc) {
    return `
<div class="modal-backdrop" onclick="closeModal()"></div>
<div class="modal-content">
    <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #20c997);">
        <h3><i class="fas fa-eye me-2"></i>Visualizza Procedura</h3>
        <button class="btn-close" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="modal-body">
        <div class="procedure-info">
            <p><strong>ID:</strong> ${proc.id}</p>
            <p><strong>Creata il:</strong> ${new Date(proc.data_creazione).toLocaleString('it-IT')}</p>
            ${proc.data_modifica !== proc.data_creazione ? '<p><strong>Ultima modifica:</strong> ' + new Date(proc.data_modifica).toLocaleString('it-IT') + '</p>' : ''}
        </div>
        
        <div class="view-field">
            <label class="view-label">Denominazione</label>
            <div class="view-content">${proc.denominazione}</div>
        </div>
        
        <div class="view-field">
            <label class="view-label">Valida Dal</label>
            <div class="view-content">${new Date(proc.valida_dal).toLocaleDateString('it-IT')}</div>
        </div>
        
        <div class="view-field">
            <label class="view-label">Testo Procedura</label>
            <div class="view-content procedure-text-view">${proc.procedura.replace(/\n/g, '<br>')}</div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">
            <i class="fas fa-times me-2"></i>Chiudi
        </button>
        <button type="button" class="btn btn-primary" onclick="closeModal(); editProcedure(${proc.id})">
            <i class="fas fa-edit me-2"></i>Modifica
        </button>
        <button type="button" class="btn btn-success" onclick="printProcedure(${proc.id})">
            <i class="fas fa-print me-2"></i>Stampa
        </button>
    </div>
</div>`;
}

function printProcedure(id) {
    window.open('/stampa_procedura.php?id=' + id, '_blank');
}

function deleteProcedure(id, nome) {
    if (confirm('Sei sicuro di voler eliminare la procedura "' + nome + '"?\n\nQuesta azione non può essere annullata.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function closeModal() {
    document.getElementById('modalContainer').innerHTML = '';
    document.body.style.overflow = '';
}

// Gli eventi di submit sono ora gestiti direttamente nei modal tramite le funzioni submitCreateForm() e submitEditForm()

// Gestione escape key per chiudere modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Gli stili dei modal sono ora inclusi nel CSS della pagina
</script>

</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
