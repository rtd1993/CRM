<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

// Gestione eliminazione
if (isset($_POST['elimina_procedura'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM procedure_crm WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success_message = "Procedura eliminata con successo!";
    } else {
        $error_message = "Errore nell'eliminazione della procedura.";
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

@media (max-width: 768px) {
    .procedure-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .procedure-actions {
        flex-wrap: wrap;
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
    modal.innerHTML = '<div class="modal-backdrop"></div><div class="modal-content"><div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">Caricamento...</p></div></div>';
    
    fetch('/crea_procedura.php', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.text();
        })
        .then(html => {
            modal.innerHTML = html;
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Errore:', error);
            modal.innerHTML = '<div class="modal-backdrop" onclick="closeModal()"></div><div class="modal-content"><div class="alert alert-danger">Errore nel caricamento della pagina: ' + error.message + '</div></div>';
        });
}

function editProcedure(id) {
    const modal = document.getElementById('modalContainer');
    modal.innerHTML = '<div class="modal-backdrop"></div><div class="modal-content"><div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-2">Caricamento...</p></div></div>';
    
    fetch('/modifica_procedura.php?id=' + id, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.text();
        })
        .then(html => {
            modal.innerHTML = html;
            document.body.style.overflow = 'hidden';
        })
        .catch(error => {
            console.error('Errore:', error);
            modal.innerHTML = '<div class="modal-backdrop" onclick="closeModal()"></div><div class="modal-content"><div class="alert alert-danger">Errore nel caricamento della pagina: ' + error.message + '</div></div>';
        });
}

function viewProcedure(id) {
    editProcedure(id); // Per ora usa la stessa modal di modifica ma in modalità view
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

// Gestione escape key per chiudere modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Style per modal
const modalStyle = document.createElement('style');
modalStyle.textContent = `
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
    }
`;
document.head.appendChild(modalStyle);
</script>

</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
