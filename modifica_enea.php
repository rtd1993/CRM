<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Modifica Record ENEA";

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: enea.php');
    exit;
}

$errors = [];
$success_message = '';

// Carica il record esistente
try {
    $stmt = $pdo->prepare("SELECT * FROM enea WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        header('Location: enea.php');
        exit;
    }
    
    // Determina il percorso della cartella ENEA per i pulsanti upload
    $enea_folder_path = '';
    $enea_folder_relative = '';
    if (!empty($record['cliente_id'])) {
        try {
            $cliente_stmt = $pdo->prepare("SELECT Cognome_Ragione_sociale, Nome, link_cartella FROM clienti WHERE id = ?");
            $cliente_stmt->execute([$record['cliente_id']]);
            $cliente_data = $cliente_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cliente_data) {
                // Usa il link_cartella esistente invece di ricreare il percorso
                if (!empty($cliente_data['link_cartella'])) {
                    $cliente_path = $cliente_data['link_cartella'];
                    // Estrai il nome della cartella dal percorso completo
                    $cliente_folder = basename($cliente_path);
                } else {
                    // Fallback: cerca la cartella esistente nella directory
                    $base_path = '/var/www/CRM/local_drive';
                    $cliente_path = null;
                    
                    // Cerca cartelle che iniziano con l'ID del cliente
                    if (is_dir($base_path)) {
                        $dirs = scandir($base_path);
                        foreach ($dirs as $dir) {
                            if ($dir !== '.' && $dir !== '..' && is_dir($base_path . '/' . $dir)) {
                                // Controlla se la cartella inizia con l'ID del cliente seguito da underscore
                                if (strpos($dir, $record['cliente_id'] . '_') === 0) {
                                    $cliente_path = $base_path . '/' . $dir;
                                    $cliente_folder = $dir;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Se non trova la cartella, genera il nome corretto (mantenendo maiuscole)
                    if (!$cliente_path) {
                        $cognome_clean = preg_replace('/[^A-Za-z0-9]/', '', $cliente_data['Cognome_Ragione_sociale']);
                        $cliente_folder = $record['cliente_id'] . '_' . $cognome_clean;
                        
                        // Aggiungi il nome se presente
                        if (!empty($cliente_data['Nome'])) {
                            $nome_clean = preg_replace('/[^A-Za-z0-9]/', '', $cliente_data['Nome']);
                            $cliente_folder .= '.' . $nome_clean;
                        }
                        
                        $cliente_path = $base_path . '/' . $cliente_folder;
                    }
                }
                
                // Nome cartella ENEA: ENEA_ANNO_DESCRIZIONE
                $folder_name = 'ENEA_' . $record['anno_fiscale'];
                if (!empty($record['descrizione'])) {
                    $desc_clean = preg_replace('/[^A-Za-z0-9\s]/', '', $record['descrizione']);
                    $desc_clean = preg_replace('/\s+/', '_', trim($desc_clean));
                    $folder_name .= '_' . $desc_clean;
                }
                
                $enea_folder_path = $cliente_path . '/' . $folder_name;
                $enea_folder_relative = $cliente_folder . '/' . $folder_name;
            }
        } catch (Exception $e) {
            error_log("Errore nel determinare percorso cartella ENEA: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    $errors[] = "Errore nel caricamento del record: " . $e->getMessage();
}

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
    $codice_enea = $_POST['codice_enea'] ?? '';
    $anno_fiscale = $_POST['anno_fiscale'] ?? date('Y');
    $tipo_detrazione = $_POST['tipo_detrazione'] ?? '50';
    $importo_spesa = $_POST['importo_spesa'] ?? null;
    $importo_detrazione = $_POST['importo_detrazione'] ?? null;
    $stato = $_POST['stato'] ?? 'bozza';
    $data_trasmissione = $_POST['data_trasmissione'] ?? null;
    $note = $_POST['note'] ?? '';
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

    // Se non ci sono errori, aggiorna nel database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE enea 
                SET cliente_id = ?, codice_enea = ?, anno_fiscale = ?, tipo_detrazione = ?, 
                    importo_spesa = ?, importo_detrazione = ?, stato = ?, data_trasmissione = ?, 
                    note = ?, descrizione = ?, prima_tel = ?, richiesta_doc = ?, ns_prev_n_del = ?, 
                    ns_ord_n_del = ?, ns_fatt_n_del = ?, bonifico_ns = ?, copia_fatt_fornitore = ?, 
                    schede_tecniche = ?, visura_catastale = ?, firma_notorio = ?, firma_delega_ag_entr = ?, 
                    firma_delega_enea = ?, consenso = ?, ev_atto_notorio = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $cliente_id,
                $codice_enea ?: null,
                $anno_fiscale,
                $tipo_detrazione,
                $importo_spesa ?: null,
                $importo_detrazione ?: null,
                $stato,
                $data_trasmissione ?: null,
                $note ?: null,
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
                $ev_atto_notorio,
                $id
            ]);

            $success_message = "Record ENEA aggiornato con successo!";
            
            // Se è in modalità popup, chiudi il modal
            if (isset($_GET['popup'])) {
                echo "<script>
                    if (parent && parent.closeEneaModal) {
                        setTimeout(() => parent.closeEneaModal(), 1000);
                    }
                </script>";
            }
            
            // Ricarica il record aggiornato
            $stmt = $pdo->prepare("SELECT * FROM enea WHERE id = ?");
            $stmt->execute([$id]);
            $record = $stmt->fetch();
        } catch (Exception $e) {
            $errors[] = "Errore durante l'aggiornamento: " . $e->getMessage();
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

<div class="<?= $is_popup ? 'popup-container' : 'container-fluid mt-4' ?>">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit text-primary me-2"></i><?= $page_title ?> #<?= $record['id'] ?>
                    </h4>
                    <?php if (!$is_popup): ?>
                    <div>
                        <?php
                        // Calcola progresso
                        $campi_doc = [
                            'copia_fatt_fornitore', 'schede_tecniche', 'visura_catastale', 
                            'firma_notorio', 'firma_delega_ag_entr', 'firma_delega_enea', 
                            'consenso', 'ev_atto_notorio'
                        ];
                        $completati = 0;
                        foreach ($campi_doc as $campo) {
                            if ($record[$campo] === 'OK') $completati++;
                        }
                        $percentuale = round(($completati / count($campi_doc)) * 100);
                        ?>
                        <span class="badge bg-<?= $percentuale >= 80 ? 'success' : ($percentuale >= 50 ? 'warning' : 'danger') ?> me-2">
                            Completamento: <?= $percentuale ?>%
                        </span>
                        <a href="enea.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Torna alla Lista
                        </a>
                    </div>
                    <?php endif; ?>
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
                                                        <?= ($record['cliente_id'] ?? '') == $cliente['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cliente['nome_completo']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="codice_enea" class="form-label">Codice ENEA</label>
                                        <input type="text" class="form-control" id="codice_enea" name="codice_enea" 
                                               value="<?= htmlspecialchars($record['codice_enea'] ?? '') ?>"
                                               placeholder="Inserisci codice ENEA">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="anno_fiscale" class="form-label">Anno Fiscale</label>
                                        <select class="form-select" id="anno_fiscale" name="anno_fiscale">
                                            <?php 
                                            $anno_corrente = date('Y');
                                            $anno_selezionato = $record['anno_fiscale'] ?? $anno_corrente;
                                            for ($i = $anno_corrente; $i >= $anno_corrente - 5; $i--): ?>
                                                <option value="<?= $i ?>" <?= $anno_selezionato == $i ? 'selected' : '' ?>>
                                                    <?= $i ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="tipo_detrazione" class="form-label">Tipo Detrazione</label>
                                        <select class="form-select" id="tipo_detrazione" name="tipo_detrazione">
                                            <?php 
                                            $tipi = [
                                                '50' => '50%',
                                                '65' => '65%', 
                                                '110' => 'Superbonus 110%',
                                                'bonus_facciate' => 'Bonus Facciate'
                                            ];
                                            $tipo_selezionato = $record['tipo_detrazione'] ?? '50';
                                            foreach ($tipi as $valore => $label): ?>
                                                <option value="<?= $valore ?>" <?= $tipo_selezionato == $valore ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="importo_spesa" class="form-label">Importo Spesa (€)</label>
                                        <input type="number" class="form-control" id="importo_spesa" name="importo_spesa" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($record['importo_spesa'] ?? '') ?>"
                                               placeholder="0.00">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="importo_detrazione" class="form-label">Importo Detrazione (€)</label>
                                        <input type="number" class="form-control" id="importo_detrazione" name="importo_detrazione" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($record['importo_detrazione'] ?? '') ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="stato" class="form-label">Stato Pratica</label>
                                        <select class="form-select" id="stato" name="stato">
                                            <?php 
                                            $stati = [
                                                'bozza' => '📝 Bozza',
                                                'trasmessa' => '📤 Trasmessa',
                                                'accettata' => '✅ Accettata',
                                                'respinta' => '❌ Respinta'
                                            ];
                                            $stato_selezionato = $record['stato'] ?? 'bozza';
                                            foreach ($stati as $valore => $label): ?>
                                                <option value="<?= $valore ?>" <?= $stato_selezionato == $valore ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="data_trasmissione" class="form-label">Data Trasmissione</label>
                                        <input type="date" class="form-control" id="data_trasmissione" name="data_trasmissione" 
                                               value="<?= $record['data_trasmissione'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="descrizione" class="form-label">Descrizione</label>
                                        <textarea class="form-control" id="descrizione" name="descrizione" rows="1"
                                                  placeholder="Descrizione breve della pratica"><?= htmlspecialchars($record['descrizione'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="note" class="form-label">Note</label>
                                        <textarea class="form-control" id="note" name="note" rows="3"
                                                  placeholder="Note aggiuntive sulla pratica..."><?= htmlspecialchars($record['note'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="bonifico_ns" class="form-label">Bonifico Ns. (€)</label>
                                        <input type="number" class="form-control" id="bonifico_ns" name="bonifico_ns" 
                                               step="0.01" min="0"
                                               value="<?= htmlspecialchars($record['bonifico_ns'] ?? '') ?>"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="prima_tel" class="form-label">Prima Telefonata</label>
                                        <input type="date" class="form-control" id="prima_tel" name="prima_tel" 
                                               value="<?= $record['prima_tel'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="richiesta_doc" class="form-label">Richiesta Documenti</label>
                                        <input type="date" class="form-control" id="richiesta_doc" name="richiesta_doc" 
                                               value="<?= $record['richiesta_doc'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="descrizione" class="form-label">Descrizione</label>
                                        <textarea class="form-control" id="descrizione" name="descrizione" rows="3"
                                                  placeholder="Descrizione del lavoro/intervento..."><?= htmlspecialchars($record['descrizione'] ?? '') ?></textarea>
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
                                               value="<?= htmlspecialchars($record['ns_prev_n_del'] ?? '') ?>"
                                               placeholder="Numero preventivo...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="ns_ord_n_del" class="form-label">Ns. Ord. N. Del.</label>
                                        <input type="text" class="form-control" id="ns_ord_n_del" name="ns_ord_n_del" 
                                               value="<?= htmlspecialchars($record['ns_ord_n_del'] ?? '') ?>"
                                               placeholder="Numero ordine...">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="ns_fatt_n_del" class="form-label">Ns. Fatt. N. Del.</label>
                                        <input type="text" class="form-control" id="ns_fatt_n_del" name="ns_fatt_n_del" 
                                               value="<?= htmlspecialchars($record['ns_fatt_n_del'] ?? '') ?>"
                                               placeholder="Numero fattura...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documenti e Firme con Progress Bar -->
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-file-signature me-2"></i>Documenti e Firme</h5>
                                <div class="progress" style="width: 200px; height: 25px;">
                                    <div class="progress-bar bg-<?= $percentuale >= 80 ? 'success' : ($percentuale >= 50 ? 'warning' : 'danger') ?>" 
                                         role="progressbar" style="width: <?= $percentuale ?>%" id="progressBar">
                                        <strong><?= $percentuale ?>%</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row" id="documentiContainer">
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
                                            <div class="d-flex align-items-center gap-2">
                                                <select class="form-select documento-select flex-grow-1" id="<?= $campo ?>" name="<?= $campo ?>">
                                                    <option value="PENDING" <?= ($record[$campo] ?? 'PENDING') == 'PENDING' ? 'selected' : '' ?>>
                                                        🟡 In Attesa
                                                    </option>
                                                    <option value="OK" <?= ($record[$campo] ?? '') == 'OK' ? 'selected' : '' ?>>
                                                        ✅ Completato
                                                    </option>
                                                    <option value="NO" <?= ($record[$campo] ?? '') == 'NO' ? 'selected' : '' ?>>
                                                        ❌ Non Richiesto
                                                    </option>
                                                </select>
                                                <?php if (!empty($enea_folder_relative)): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="openUploadModal('<?= $label ?>', '<?= $enea_folder_relative ?>', '<?= $campo ?>')"
                                                            title="Carica <?= $label ?>">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            onclick="openFolderInDrive('<?= $enea_folder_relative ?>')"
                                                            title="Apri cartella">
                                                        <i class="fas fa-folder-open"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Info di sistema -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body py-2">
                                        <small class="text-muted">
                                            <strong>Creato:</strong> <?= date('d/m/Y H:i', strtotime($record['created_at'])) ?> | 
                                            <strong>Ultimo aggiornamento:</strong> <?= date('d/m/Y H:i', strtotime($record['updated_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <a href="enea.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Aggiorna Record ENEA
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

// Progress indicator real-time per i documenti
function updateProgressIndicator() {
    const selects = document.querySelectorAll('.documento-select');
    let completed = 0;
    
    selects.forEach(select => {
        if (select.value === 'OK') completed++;
    });
    
    const percentage = Math.round((completed / selects.length) * 100);
    const progressBar = document.getElementById('progressBar');
    
    // Aggiorna la progress bar
    progressBar.style.width = percentage + '%';
    progressBar.textContent = percentage + '%';
    
    // Cambia colore in base alla percentuale
    progressBar.className = 'progress-bar bg-' + (percentage >= 80 ? 'success' : (percentage >= 50 ? 'warning' : 'danger'));
    
    // Aggiorna anche il badge nell'header
    const badge = document.querySelector('.card-header .badge');
    if (badge) {
        badge.textContent = 'Completamento: ' + percentage + '%';
        badge.className = 'badge bg-' + (percentage >= 80 ? 'success' : (percentage >= 50 ? 'warning' : 'danger')) + ' me-2';
    }
}

// Aggiungi listener ai selects
document.querySelectorAll('.documento-select').forEach(select => {
    select.addEventListener('change', updateProgressIndicator);
});

// Inizializza al caricamento
document.addEventListener('DOMContentLoaded', updateProgressIndicator);

// Funzioni per gestione upload documenti ENEA
function openUploadModal(documentName, folderPath, fieldName) {
    // Apri il modal di upload del drive puntando alla cartella ENEA
    const uploadUrl = `upload.php?path=${encodeURIComponent(folderPath)}&document=${encodeURIComponent(documentName)}&field=${fieldName}&enea_id=<?= $id ?>`;
    
    // Crea un modal popup per l'upload
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Carica ${documentName}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <iframe src="${uploadUrl}" style="width: 100%; height: 400px; border: none;"></iframe>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    // Rimuovi il modal quando viene chiuso
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

function openFolderInDrive(folderPath) {
    // Apri la cartella ENEA nel drive
    const driveUrl = `drive.php?path=${encodeURIComponent(folderPath)}`;
    window.open(driveUrl, '_blank');
}

// Funzione per aggiornare automaticamente lo stato del documento dopo upload
function updateDocumentStatus(fieldName, status = 'OK') {
    const select = document.getElementById(fieldName);
    if (select) {
        select.value = status;
        updateProgressIndicator();
        
        // Salva automaticamente la modifica
        const form = document.getElementById('eneaForm');
        if (form) {
            const formData = new FormData(form);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    // Mostra notifica di successo
                    showNotification('Documento aggiornato con successo!', 'success');
                }
            }).catch(error => {
                console.error('Errore aggiornamento documento:', error);
            });
        }
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Rimuovi automaticamente dopo 3 secondi
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}
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

.progress {
    background-color: #e9ecef;
}

.documento-select {
    transition: all 0.3s ease;
}

.documento-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bg-light {
    background-color: #f8f9fa !important;
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
