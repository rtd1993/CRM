<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

// Gestione eliminazione richiesta
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM richieste WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        // Elimina anche il task associato se esiste
        $task_stmt = $pdo->prepare("DELETE FROM task WHERE descrizione LIKE ?");
        $task_stmt->execute(['%Gestire richiesta:%ID: ' . $_GET['id'] . '%']);
        
        echo "<div class='alert alert-success'>Richiesta eliminata con successo!</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Errore nell'eliminazione: " . $e->getMessage() . "</div>";
    }
}

// Recupera tutte le richieste
try {
    $sql = "SELECT * FROM richieste ORDER BY data_richiesta DESC, created_at DESC";
    $stmt = $pdo->query($sql);
    $richieste = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $richieste = [];
    echo "<div class='alert alert-danger'>Errore nel recupero delle richieste: " . $e->getMessage() . "</div>";
}

// Funzione per formattare lo stato
function getStatoBadge($stato) {
    $badges = [
        'aperta' => 'bg-danger',
        'in_lavorazione' => 'bg-warning text-dark',
        'completata' => 'bg-success',
        'chiusa' => 'bg-secondary'
    ];
    return $badges[$stato] ?? 'bg-secondary';
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-headset me-2"></i>Gestione Richieste</h2>
                <button type="button" class="btn btn-primary" onclick="openRichiestaModal()">
                    <i class="fas fa-plus me-1"></i>Nuova Richiesta
                </button>
            </div>

            <!-- Barra di ricerca e filtri avanzati -->
            <div class="mb-4">
                <form method="get" class="search-form">
                    <div class="search-wrapper" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="search" class="form-control" placeholder="🔍 Cerca per denominazione, email, telefono, codice..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                        <button type="submit" class="btn btn-primary">Cerca</button>
                        <?php if (isset($_GET['search']) && $_GET['search']): ?>
                            <a href="richieste.php" class="btn btn-link">✕ Cancella filtro</a>
                        <?php endif; ?>
                    </div>
                    <div class="advanced-filters" id="advanced-filters" style="display: none; background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <div class="filter-row" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <select name="stato" class="form-select" style="max-width: 180px;">
                                <option value="">Tutti gli stati</option>
                                <option value="aperta" <?= (isset($_GET['stato']) && $_GET['stato'] == 'aperta') ? 'selected' : '' ?>>Aperta</option>
                                <option value="in_lavorazione" <?= (isset($_GET['stato']) && $_GET['stato'] == 'in_lavorazione') ? 'selected' : '' ?>>In lavorazione</option>
                                <option value="completata" <?= (isset($_GET['stato']) && $_GET['stato'] == 'completata') ? 'selected' : '' ?>>Completata</option>
                                <option value="chiusa" <?= (isset($_GET['stato']) && $_GET['stato'] == 'chiusa') ? 'selected' : '' ?>>Chiusa</option>
                            </select>
                            <input type="date" name="data_da" class="form-control" style="max-width: 160px;" value="<?= isset($_GET['data_da']) ? htmlspecialchars($_GET['data_da']) : '' ?>" placeholder="Da data">
                            <input type="date" name="data_a" class="form-control" style="max-width: 160px;" value="<?= isset($_GET['data_a']) ? htmlspecialchars($_GET['data_a']) : '' ?>" placeholder="A data">
                            <select name="tipo" class="form-select" style="max-width: 180px;">
                                <option value="">Tutti i tipi</option>
                                <option value="pagamento" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'pagamento') ? 'selected' : '' ?>>A pagamento</option>
                                <option value="gratuita" <?= (isset($_GET['tipo']) && $_GET['tipo'] == 'gratuita') ? 'selected' : '' ?>>Gratuita</option>
                            </select>
                            <select name="order" class="form-select" style="max-width: 180px;">
                                <option value="data_richiesta" <?= (!isset($_GET['order']) || $_GET['order'] == 'data_richiesta') ? 'selected' : '' ?>>Ordina per data richiesta</option>
                                <option value="created_at" <?= (isset($_GET['order']) && $_GET['order'] == 'created_at') ? 'selected' : '' ?>>Ordina per data creazione</option>
                                <option value="denominazione" <?= (isset($_GET['order']) && $_GET['order'] == 'denominazione') ? 'selected' : '' ?>>Ordina per denominazione</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Applica Filtri</button>
                        </div>
                    </div>
                </form>
                <div class="action-buttons mt-2" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-outline-primary" onclick="toggleAdvancedFilters()">🔧 Filtri Avanzati</button>
                    <button type="button" class="btn btn-outline-success" onclick="printTable()">🖨️ Stampa</button>
                </div>
            </div>

            <?php if (empty($richieste)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nessuna richiesta presente. Clicca su "Nuova Richiesta" per aggiungerne una.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Denominazione</th>
                                <th>Data Richiesta</th>
                                <th>Contatti</th>
                                <th>Attività</th>
                                <th>Importo</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($richieste as $richiesta): ?>
                                <tr>
                                    <td><strong>#<?= $richiesta['id'] ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($richiesta['denominazione']) ?></strong>
                                        <?php if (!empty($richiesta['richiesta'])): ?>
                                            <br><small class="text-muted">
                                                <?= htmlspecialchars(substr($richiesta['richiesta'], 0, 80)) ?>
                                                <?= strlen($richiesta['richiesta']) > 80 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($richiesta['data_richiesta'])) ?></td>
                                    <td>
                                        <?php if (!empty($richiesta['telefono'])): ?>
                                            <div><i class="fas fa-phone text-primary"></i> <?= htmlspecialchars($richiesta['telefono']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($richiesta['email'])): ?>
                                            <div><i class="fas fa-envelope text-info"></i> <?= htmlspecialchars($richiesta['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($richiesta['attivita_pagamento']): ?>
                                            <span class="badge bg-success">A Pagamento</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Gratuita</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($richiesta['attivita_pagamento'] && !empty($richiesta['importo'])): ?>
                                            <strong>€ <?= number_format($richiesta['importo'], 2, ',', '.') ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= getStatoBadge($richiesta['stato']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $richiesta['stato'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" onclick="openRichiestaModal(<?= $richiesta['id'] ?>)" 
                                               class="btn btn-outline-primary" title="Modifica richiesta">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" onclick="stampaRichiesta(<?= $richiesta['id'] ?>)" 
                                               class="btn btn-outline-success" title="Stampa richiesta">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <a href="?action=delete&id=<?= $richiesta['id'] ?>" 
                                               class="btn btn-outline-danger" title="Elimina richiesta"
                                               onclick="return confirm('Sei sicuro di voler eliminare questa richiesta?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <small class="text-muted">
                        Totale richieste: <strong><?= count($richieste) ?></strong>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal per Richiesta -->
<div class="modal fade" id="richiestaModal" tabindex="-1" aria-labelledby="richiestaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="richiestaModalLabel">Gestione Richiesta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="richiestaFrame" src="" width="100%" height="600" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Fix specifico per dropdown in richieste.php
document.addEventListener('DOMContentLoaded', function() {
    // Attendi che Bootstrap sia completamente caricato
    setTimeout(function() {
        // Force enable tutti i dropdown
        const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        dropdowns.forEach(function(dropdown) {
            // Force click handler manuale
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const menu = dropdown.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    // Toggle manuale
                    if (menu.style.display === 'block') {
                        menu.style.display = 'none';
                        menu.classList.remove('show');
                    } else {
                        // Chiudi altri dropdown
                        document.querySelectorAll('.dropdown-menu').forEach(function(otherMenu) {
                            otherMenu.style.display = 'none';
                            otherMenu.classList.remove('show');
                        });
                        
                        // Apri questo dropdown
                        menu.style.display = 'block';
                        menu.classList.add('show');
                    }
                }
            });
        });
        
        // Chiudi dropdown quando si clicca fuori
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.style.display = 'none';
                    menu.classList.remove('show');
                });
            }
        });
        
    }, 200);
});

// Funzione per aprire il modal di gestione richiesta
function openRichiestaModal(richiestaId = null) {
    const modal = document.getElementById('richiestaModal');
    const iframe = document.getElementById('richiestaFrame');
    const modalTitle = document.getElementById('richiestaModalLabel');
    
    if (richiestaId) {
        iframe.src = `modifica_richiesta.php?id=${richiestaId}`;
        modalTitle.textContent = 'Modifica Richiesta';
    } else {
        iframe.src = 'crea_richiesta.php';
        modalTitle.textContent = 'Nuova Richiesta';
    }
    
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

// Funzione per chiudere il modal
function closeRichiestaModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('richiestaModal'));
    if (modal) {
        modal.hide();
        location.reload(); // Ricarica la pagina per aggiornare la lista
    }
}

// Gestione click sul backdrop del modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('richiestaModal');
    modal.addEventListener('click', function(event) {
        // Solo se si clicca esattamente sul backdrop del modal
        if (event.target === modal) {
            closeRichiestaModal();
        }
    });
});

// Funzione per chiudere il modal da iframe (chiamata dalle pagine popup)
window.closeRichiestaModal = closeRichiestaModal;

// Funzione per stampare la richiesta
function stampaRichiesta(richiestaId) {
    const stampaUrl = `stampa_richiesta.php?id=${richiestaId}`;
    const printWindow = window.open(stampaUrl, '_blank', 'width=800,height=600,scrollbars=yes,resizable=yes');
    
    // Attendi che la pagina si carichi e poi avvia la stampa
    printWindow.onload = function() {
        printWindow.print();
    };
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
