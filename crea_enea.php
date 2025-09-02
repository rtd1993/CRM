<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Nuovo Record ENEA";

$errors = [];
$success_message = '';

// Carica lista clienti
try {
    $clienti = $pdo->query("SELECT id, CONCAT(`Cognome_Ragione_sociale`, ' ', COALESCE(`Nome`, '')) as nome_completo FROM clienti ORDER BY `Cognome_Ragione_sociale`, `Nome`")->fetchAll();
    // Debug temporaneo
    if (empty($clienti)) {
        $errors[] = "Nessun cliente trovato nel database. Controlla la tabella clienti.";
    }
} catch (Exception $e) {
    $errors[] = "Errore nel caricamento clienti: " . $e->getMessage();
    $clienti = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione dati
    $cliente_id = $_POST['cliente_id'] ?? '';
    $descrizione = $_POST['descrizione'] ?? '';
    $prima_tel = $_POST['prima_tel'] ?? null;
    $richiesta_doc = $_POST['richiesta_doc'] ?? null;
    $ns_prev_n_del = $_POST['ns_prev_n_del'] ?? '';
    $ns_ord_n_del = $_POST['ns_ord_n_del'] ?? '';
    $ns_fatt_n_del = $_POST['ns_fatt_n_del'] ?? '';
    $bonifico_ns = $_POST['bonifico_ns'] ?? null;
    
    // Campi documento
    $copia_fatt_fornitore = $_POST['copia_fatt_fornitore'] ?? 'PENDING';
    $schede_tecniche = $_POST['schede_tecniche'] ?? 'PENDING';
    $visura_catastale = $_POST['visura_catastale'] ?? 'PENDING';
    $firma_notorio = $_POST['firma_notorio'] ?? 'PENDING';
    $firma_delega_ag_entr = $_POST['firma_delega_ag_entr'] ?? 'PENDING';
    $firma_delega_enea = $_POST['firma_delega_enea'] ?? 'PENDING';
    $consenso = $_POST['consenso'] ?? 'PENDING';
    $ev_atto_notorio = $_POST['ev_atto_notorio'] ?? 'PENDING';

    // Validazioni
    if (empty($cliente_id)) {
        $errors[] = "Il cliente è obbligatorio";
    }

    // Se non ci sono errori, inserisci nel database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO enea 
                (cliente_id, descrizione, prima_tel, richiesta_doc, ns_prev_n_del, ns_ord_n_del, ns_fatt_n_del, 
                 bonifico_ns, copia_fatt_fornitore, schede_tecniche, visura_catastale, firma_notorio, 
                 firma_delega_ag_entr, firma_delega_enea, consenso, ev_atto_notorio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $cliente_id,
                $descrizione ?: null,
                $prima_tel ?: null,
                $richiesta_doc ?: null,
                $ns_prev_n_del ?: null,
                $ns_ord_n_del ?: null,
                $ns_fatt_n_del ?: null,
                $bonifico_ns ?: null,
                $copia_fatt_fornitore,
                $schede_tecniche,
                $visura_catastale,
                $firma_notorio,
                $firma_delega_ag_entr,
                $firma_delega_enea,
                $consenso,
                $ev_atto_notorio
            ]);

            $success_message = "Record ENEA creato con successo!";
            
            // Se è in modalità popup, chiudi il modal
            if (isset($_GET['popup'])) {
                echo "<script>
                    if (parent && parent.closeEneaModal) {
                        setTimeout(() => parent.closeEneaModal(), 1000);
                    }
                </script>";
            }
            
            // Reset form
            $_POST = [];
        } catch (Exception $e) {
            $errors[] = "Errore durante l'inserimento: " . $e->getMessage();
        }
    }
}

// Modalità popup - non includere header
$is_popup = isset($_GET['popup']);
if (!$is_popup) {
    include 'includes/header.php';
} else {
    // Header minimale per popup
    echo '<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $page_title . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { 
                margin: 0; 
                padding: 20px; 
                background: #f8f9fa;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .popup-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
            }
        </style>
    </head>
    <body>';
}
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle text-success me-2"></i><?= $page_title ?>
                    </h4>
                    <a href="enea.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Torna alla Lista
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6>Errori di validazione:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="eneaForm">
                        <!-- Informazioni Generali -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informazioni Generali</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="cliente_id" class="form-label">
                                            Cliente <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="cliente_id" name="cliente_id" required>
                                            <option value="">Seleziona cliente...</option>
                                            <?php foreach ($clienti as $cliente): ?>
                                                <option value="<?= $cliente['id'] ?>" 
                                                        <?= ($_POST['cliente_id'] ?? '') == $cliente['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cliente['nome_completo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="bonifico_ns" class="form-label">Bonifico Ns. (€)</label>
                                        <input type="number" class="form-control" id="bonifico_ns" name="bonifico_ns" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($_POST['bonifico_ns'] ?? '') ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="prima_tel" class="form-label">Prima Telefonata</label>
                                        <input type="date" class="form-control" id="prima_tel" name="prima_tel" 
                                               value="<?= htmlspecialchars($_POST['prima_tel'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="richiesta_doc" class="form-label">Richiesta Documenti</label>
                                        <input type="date" class="form-control" id="richiesta_doc" name="richiesta_doc" 
                                               value="<?= htmlspecialchars($_POST['richiesta_doc'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="descrizione" class="form-label">Descrizione</label>
                                        <textarea class="form-control" id="descrizione" name="descrizione" rows="3"
                                                  placeholder="Descrizione del lavoro/intervento..."><?= htmlspecialchars($_POST['descrizione'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Numerazioni -->
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-hashtag me-2"></i>Numerazioni</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="ns_prev_n_del" class="form-label">Ns. Prev. N. Del.</label>
                                        <input type="text" class="form-control" id="ns_prev_n_del" name="ns_prev_n_del" 
                                               value="<?= htmlspecialchars($_POST['ns_prev_n_del'] ?? '') ?>"
                                               placeholder="Numero preventivo...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="ns_ord_n_del" class="form-label">Ns. Ord. N. Del.</label>
                                        <input type="text" class="form-control" id="ns_ord_n_del" name="ns_ord_n_del" 
                                               value="<?= htmlspecialchars($_POST['ns_ord_n_del'] ?? '') ?>"
                                               placeholder="Numero ordine...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="ns_fatt_n_del" class="form-label">Ns. Fatt. N. Del.</label>
                                        <input type="text" class="form-control" id="ns_fatt_n_del" name="ns_fatt_n_del" 
                                               value="<?= htmlspecialchars($_POST['ns_fatt_n_del'] ?? '') ?>"
                                               placeholder="Numero fattura...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documenti e Firme -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-file-signature me-2"></i>Documenti e Firme</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php 
                                    $documenti = [
                                        'copia_fatt_fornitore' => 'Copia Fattura Fornitore',
                                        'schede_tecniche' => 'Schede Tecniche',
                                        'visura_catastale' => 'Visura Catastale',
                                        'firma_notorio' => 'Firma Notorio',
                                        'firma_delega_ag_entr' => 'Firma Delega Ag. Entr.',
                                        'firma_delega_enea' => 'Firma Delega ENEA',
                                        'consenso' => 'Consenso',
                                        'ev_atto_notorio' => 'Ev. Atto Notorio'
                                    ];
                                    
                                    foreach ($documenti as $campo => $label): ?>
                                        <div class="col-md-3 mb-3">
                                            <label for="<?= $campo ?>" class="form-label"><?= $label ?></label>
                                            <select class="form-select" id="<?= $campo ?>" name="<?= $campo ?>">
                                                <option value="PENDING" <?= ($_POST[$campo] ?? 'PENDING') == 'PENDING' ? 'selected' : '' ?>>
                                                    <i class="fas fa-clock"></i> In Attesa
                                                </option>
                                                <option value="SI" <?= ($_POST[$campo] ?? '') == 'SI' ? 'selected' : '' ?>>
                                                    ✅ Completato
                                                </option>
                                                <option value="NO" <?= ($_POST[$campo] ?? '') == 'NO' ? 'selected' : '' ?>>
                                                    ❌ Non Fatto
                                                </option>
                                            </select>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="enea.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Salva Record ENEA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto format euro inputs
document.querySelectorAll('input[type="number"][step="0.01"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
});

// Progress indicator per i documenti
function updateProgressIndicator() {
    const selects = document.querySelectorAll('select[name*="copia_fatt"], select[name*="schede_"], select[name*="visura_"], select[name*="firma_"], select[name*="consenso"], select[name*="ev_atto"]');
    let completed = 0;
    
    selects.forEach(select => {
        if (select.value === 'SI') completed++;
    });
    
    const percentage = Math.round((completed / selects.length) * 100);
    
    // Mostra indicatore visivo se necessario
    console.log(`Completamento: ${percentage}%`);
}

// Aggiungi listener ai selects
document.querySelectorAll('select[name*="copia_fatt"], select[name*="schede_"], select[name*="visura_"], select[name*="firma_"], select[name*="consenso"], select[name*="ev_atto"]').forEach(select => {
    select.addEventListener('change', updateProgressIndicator);
});
</script>

<style>
.form-label {
    font-weight: 600;
    color: #495057;
}

.text-danger {
    color: #dc3545 !important;
}

.card-header {
    font-weight: 600;
}

.card-header.bg-primary {
    background-color: #0d6efd !important;
}

.card-header.bg-info {
    background-color: #0dcaf0 !important;
}

.card-header.bg-warning {
    background-color: #ffc107 !important;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}
</style>

<?php 
if ($is_popup) {
    echo '</body></html>';
} else {
    include 'includes/chat_widget.php';
    include 'includes/chat_pratiche_widget.php';
}
?>
