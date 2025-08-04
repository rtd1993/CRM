<?php
// Include l'header del sito (gestisce sessione e autenticazione)
require_once __DIR__ . '/includes/header.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Gestione azioni
$message = '';
$error = '';

if ($_POST) {
    if (isset($_POST['crea_template'])) {
        $nome = trim($_POST['nome']);
        $oggetto = trim($_POST['oggetto']);
        $corpo = trim($_POST['corpo']);
        
        if ($nome && $oggetto && $corpo) {
            $stmt = $pdo->prepare("INSERT INTO email_templates (nome, oggetto, corpo) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $oggetto, $corpo])) {
                $message = "Template creato con successo!";
            } else {
                $error = "Errore nella creazione del template.";
            }
        } else {
            $error = "Tutti i campi sono obbligatori.";
        }
    }
    
    if (isset($_POST['modifica_template'])) {
        $id = $_POST['template_id'];
        $nome = trim($_POST['nome']);
        $oggetto = trim($_POST['oggetto']);
        $corpo = trim($_POST['corpo']);
        
        if ($nome && $oggetto && $corpo) {
            $stmt = $pdo->prepare("UPDATE email_templates SET nome = ?, oggetto = ?, corpo = ? WHERE id = ?");
            if ($stmt->execute([$nome, $oggetto, $corpo, $id])) {
                $message = "Template modificato con successo!";
            } else {
                $error = "Errore nella modifica del template.";
            }
        } else {
            $error = "Tutti i campi sono obbligatori.";
        }
    }
    
    if (isset($_POST['elimina_template'])) {
        $id = $_POST['template_id'];
        $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Template eliminato!";
        }
    }
}

// Recupera template
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY nome")->fetchAll();
?>

<!-- Breadcrumb e Navigazione Email -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
                <div>
                    <h4 class="mb-0 text-warning">
                        <i class="fas fa-envelope-open-text me-2"></i>Gestione Template Email
                    </h4>
                    <small class="text-muted">Crea e gestisci i template per le email</small>
                </div>
                <div>
                    <a href="email_invio.php" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Invia Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-2"></i>Crea Template
                </button>
                <div class="d-flex align-items-center">
                    <div class="input-group" style="width: 300px;">
                        <span class="input-group-text bg-warning text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="searchTemplate" 
                               placeholder="Cerca template per nome...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Lista Template (ora a tutta larghezza) -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>Template Esistenti
                    </h5>
                    <span class="badge bg-white text-dark fs-6" id="templateCount"><?= count($templates) ?></span>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 700px; overflow-y: auto;" id="templateList">
                        <?php if (empty($templates)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nessun template trovato</p>
                                <small class="text-muted">Crea il tuo primo template usando il pulsante "Crea Template"</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($templates as $index => $template): ?>
                                <div class="border-bottom p-3 <?= $index % 2 == 0 ? 'bg-light' : 'bg-white' ?> template-item" data-template-name="<?= strtolower(htmlspecialchars($template['nome'])) ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-2 text-warning fw-bold">
                                                <i class="fas fa-file-alt me-2"></i>
                                                <?= htmlspecialchars($template['nome']) ?>
                                            </h6>
                                            <p class="mb-2 text-dark">
                                                <strong class="text-muted">Oggetto:</strong> 
                                                <?= htmlspecialchars($template['oggetto']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Creato: <?= date('d/m/Y H:i', strtotime($template['data_creazione'])) ?>
                                            </small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" 
                                                    class="btn btn-sm"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal"
                                                    data-template-id="<?= $template['id'] ?>"
                                                    data-template-nome="<?= htmlspecialchars($template['nome']) ?>"
                                                    data-template-oggetto="<?= htmlspecialchars($template['oggetto']) ?>"
                                                    data-template-corpo="<?= htmlspecialchars($template['corpo']) ?>"
                                                    title="Modifica template"
                                                    style="background: none; border: none; font-size: 1.2em;">
                                                <i class="fas fa-pen text-warning"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Sei sicuro di voler eliminare questo template?')">
                                                <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                                                <button type="submit" name="elimina_template" 
                                                        class="btn btn-sm"
                                                        title="Elimina template"
                                                        style="background: none; border: none; font-size: 1.2em;">
                                                    <i class="fas fa-times text-danger"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="mt-3 p-3 bg-white border rounded">
                                        <small class="text-dark" style="line-height: 1.4;">
                                            <div class="template-preview">
                                                <?= substr(strip_tags($template['corpo']), 0, 200) ?>
                                                <?= strlen(strip_tags($template['corpo'])) > 200 ? '<span class="text-muted">...</span>' : '' ?>
                                            </div>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per Creazione Template -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%);">
                <h5 class="modal-title" id="createModalLabel">
                    <i class="fas fa-plus me-2"></i>Crea Nuovo Template
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="newTemplateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-tag me-1 text-primary"></i>Nome Template
                        </label>
                        <input type="text" name="nome" class="form-control form-control-lg" 
                               placeholder="Es. Comunicazione Generale" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-heading me-1 text-primary"></i>Oggetto Email
                        </label>
                        <input type="text" name="oggetto" class="form-control form-control-lg" 
                               placeholder="Es. Comunicazione importante" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-align-left me-1 text-primary"></i>Corpo Email
                        </label>
                        <div id="newQuillEditor" style="height: 300px;"></div>
                        <textarea name="corpo" id="corpo" style="display: none;"></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Variabili disponibili:</strong> 
                            <code>{nome_cliente}</code>, <code>{email_cliente}</code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" name="crea_template" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Crea Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per Modifica Template -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient text-white" style="background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifica Template
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="template_id" id="edit_template_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-tag me-1 text-primary"></i>Nome Template
                        </label>
                        <input type="text" name="nome" id="edit_nome" class="form-control form-control-lg" 
                               placeholder="Es. Comunicazione Generale" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-heading me-1 text-primary"></i>Oggetto Email
                        </label>
                        <input type="text" name="oggetto" id="edit_oggetto" class="form-control form-control-lg" 
                               placeholder="Es. Comunicazione importante" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-align-left me-1 text-primary"></i>Corpo Email
                        </label>
                        <div id="edit-corpo-editor" style="height: 300px;"></div>
                        <textarea name="corpo" id="edit_corpo" style="display: none;"></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Variabili disponibili:</strong> 
                            <code>{nome_cliente}</code>, <code>{email_cliente}</code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" name="modifica_template" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.template-item {
    transition: all 0.2s ease;
}
.template-item:hover {
    background-color: #f8f9fa !important;
    border-left: 4px solid #e67e22;
}
.card {
    border-radius: 12px;
    overflow: hidden;
}
.card-header {
    border-bottom: none;
    padding: 1rem 1.5rem;
}
.form-control:focus {
    border-color: #e67e22;
    box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.25);
}
.btn-primary {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    border: none;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #d35400 0%, #c0392b 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
}
.btn-warning {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
    border: none;
    color: white;
}
.btn-warning:hover {
    background: linear-gradient(135deg, #d35400 0%, #c0392b 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(230, 126, 34, 0.3);
    color: white;
}
.template-preview {
    max-height: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    border: none;
}
.btn-success:hover {
    background: linear-gradient(135deg, #229954 0%, #1e7e34 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}
.template-item .btn:hover {
    transform: scale(1.2);
}
</style>

<!-- Quill Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Quill Editor JS -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    // Inizializzazione editor Quill
    let newQuill, editQuill;
    
    document.addEventListener('DOMContentLoaded', function() {
        // Inizializza editor Quill per creazione
        newQuill = new Quill('#newQuillEditor', {
            theme: 'snow',
            placeholder: 'Scrivi qui il contenuto dell\'email...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean']
                ]
            }
        });
        
        // Editor per modifica template
        editQuill = new Quill('#edit-corpo-editor', {
            theme: 'snow',
            placeholder: 'Modifica il contenuto dell\'email...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean']
                ]
            }
        });
        
        // Funzione di ricerca template
        const searchInput = document.getElementById('searchTemplate');
        const templateItems = document.querySelectorAll('.template-item');
        const templateCount = document.getElementById('templateCount');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let visibleCount = 0;
                
                templateItems.forEach(item => {
                    const templateName = item.getAttribute('data-template-name');
                    if (templateName.includes(searchTerm)) {
                        item.style.display = 'block';
                        visibleCount++;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                templateCount.textContent = visibleCount;
                
                // Mostra messaggio se nessun risultato
                const noResults = document.getElementById('noResults');
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResults) {
                        const noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noResults';
                        noResultsDiv.className = 'text-center py-5';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nessun template trovato per "${searchTerm}"</p>
                            <small class="text-muted">Prova con un termine di ricerca diverso</small>
                        `;
                        document.getElementById('templateList').appendChild(noResultsDiv);
                    }
                } else if (noResults) {
                    noResults.remove();
                }
            });
        }
        
        // Event listener per tutti i pulsanti di modifica
        document.querySelectorAll('[data-bs-target="#editModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-template-id');
                const nome = this.getAttribute('data-template-nome');
                const oggetto = this.getAttribute('data-template-oggetto');
                const corpo = this.getAttribute('data-template-corpo');
                
                document.getElementById('edit_template_id').value = id;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_oggetto').value = oggetto;
                // Carica il contenuto nell'editor Quill
                if (editQuill) {
                    editQuill.root.innerHTML = corpo || '';
                }
            });
        });
        
        // Form per nuovo template
        document.getElementById('newTemplateForm').addEventListener('submit', function(e) {
            // Sincronizza il contenuto dell'editor con il textarea nascosto
            if (newQuill) {
                const content = newQuill.root.innerHTML.trim();
                document.getElementById('corpo').value = content;
                
                // Validazione contenuto
                if (content === '<p><br></p>' || content === '' || newQuill.getText().trim() === '') {
                    alert('Il corpo dell\'email è obbligatorio!');
                    e.preventDefault();
                    return false;
                }
            }
            
            if (!confirm('Sei sicuro di voler creare questo template?')) {
                e.preventDefault();
            }
        });
        
        // Conferma modifica
        document.getElementById('editForm').addEventListener('submit', function(e) {
            // Sincronizza il contenuto dell'editor con il campo nascosto
            if (editQuill) {
                const content = editQuill.root.innerHTML.trim();
                document.getElementById('edit_corpo').value = content;
                
                // Validazione contenuto
                if (content === '<p><br></p>' || content === '' || editQuill.getText().trim() === '') {
                    alert('Il corpo dell\'email è obbligatorio!');
                    e.preventDefault();
                    return false;
                }
            }
            
            if (!confirm('Sei sicuro di voler modificare questo template?')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
