<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Modifica Record Conto Termico";

$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: conto_termico.php');
    exit;
}

$errors = [];
$success_message = '';

// Carica il record esistente
try {
    $stmt = $pdo->prepare("SELECT * FROM conto_termico WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        header('Location: conto_termico.php');
        exit;
    }
} catch (Exception $e) {
    $errors[] = "Errore nel caricamento del record: " . $e->getMessage();
}

// Carica lista clienti
$clienti = $pdo->query("SELECT id, CONCAT(`Cognome_Ragione_sociale`, ' ', COALESCE(`Nome`, '')) as nome_completo FROM clienti ORDER BY `Cognome_Ragione_sociale`, `Nome`")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione dati
    $cliente_id = $_POST['cliente_id'] ?? '';
    $anno = $_POST['anno'] ?? '';
    $numero_pratica = $_POST['numero_pratica'] ?? '';
    $data_presentazione = $_POST['data_presentazione'] ?? null;
    $tipo_intervento = $_POST['tipo_intervento'] ?? '';
    $esito = $_POST['esito'] ?? '';
    $prestazione = $_POST['prestazione'] ?? null;
    $incassato = $_POST['incassato'] ?? null;
    $user = $_POST['user'] ?? '';
    $password = $_POST['password'] ?? '';
    $modello_stufa = $_POST['modello_stufa'] ?? '';
    $data_termine = $_POST['data_termine'] ?? null;
    $mese = $_POST['mese'] ?? '';
    $importo_ammissibile = $_POST['importo_ammissibile'] ?? null;
    $contributo = $_POST['contributo'] ?? null;
    $stato = $_POST['stato'] ?? 'bozza';
    $note = $_POST['note'] ?? '';

    // Validazioni
    if (empty($cliente_id)) {
        $errors[] = "Il cliente è obbligatorio";
    }
    if (empty($anno)) {
        $errors[] = "L'anno è obbligatorio";
    } elseif (!preg_match('/^[0-9]{4}$/', $anno)) {
        $errors[] = "L'anno deve essere formato da 4 cifre numeriche";
    } elseif ($anno < 2020 || $anno > (date('Y') + 5)) {
        $errors[] = "L'anno deve essere compreso tra 2020 e " . (date('Y') + 5);
    }

    // Se non ci sono errori, aggiorna nel database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE conto_termico 
                SET cliente_id = ?, anno = ?, numero_pratica = ?, data_presentazione = ?, 
                    tipo_intervento = ?, esito = ?, prestazione = ?, incassato = ?, 
                    user = ?, password = ?, modello_stufa = ?, data_termine = ?, mese = ?,
                    importo_ammissibile = ?, contributo = ?, stato = ?, note = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $cliente_id,
                $anno,
                $numero_pratica ?: null,
                $data_presentazione ?: null,
                $tipo_intervento ?: null,
                $esito ?: null,
                $prestazione ?: null,
                $incassato ?: null,
                $user ?: null,
                $password ?: null,
                $modello_stufa ?: null,
                $data_termine ?: null,
                $mese ?: null,
                $importo_ammissibile ?: null,
                $contributo ?: null,
                $stato,
                $note ?: null,
                $id
            ]);

            $success_message = "Record aggiornato con successo!";
            
            // Se è in modalità popup, chiudi il modal
            if (isset($_GET['popup'])) {
                echo "<script>
                    if (parent && parent.closeContoTermicoModal) {
                        setTimeout(() => parent.closeContoTermicoModal(), 1000);
                    }
                </script>";
            }
            
            // Ricarica il record aggiornato
            $stmt = $pdo->prepare("SELECT * FROM conto_termico WHERE id = ?");
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
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit text-primary me-2"></i><?= $page_title ?> #<?= $record['id'] ?>
                    </h4>
                    <?php if (!$is_popup): ?>
                        <a href="conto_termico.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Torna alla Lista
                        </a>
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

                    <form method="POST" id="contoTermicoForm">
                        <div class="row">
                            <!-- Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="cliente_autocomplete" class="form-label">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <div class="mb-1 text-muted" id="cliente_attuale_label">
                                    Cliente attuale: <strong><?= htmlspecialchars($record['cliente_id'] ? ($clienti[array_search($record['cliente_id'], array_column($clienti, 'id'))]['nome_completo'] ?? '') : '') ?></strong>
                                </div>
                                <input type="text" class="form-control" id="cliente_autocomplete" placeholder="Cognome o nome cliente..." autocomplete="off" value="<?= htmlspecialchars($record['cliente_id'] ? ($clienti[array_search($record['cliente_id'], array_column($clienti, 'id'))]['nome_completo'] ?? '') : '') ?>">
                                <input type="hidden" name="cliente_id" id="cliente_id" value="<?= htmlspecialchars($record['cliente_id']) ?>">
                                <div id="autocomplete_suggestions" class="list-group position-absolute w-100" style="z-index:1000;"></div>
                            </div>
<script>
// Autocomplete clienti
const clienti = <?php echo json_encode($clienti); ?>;
const input = document.getElementById('cliente_autocomplete');
const hiddenId = document.getElementById('cliente_id');
const suggestions = document.getElementById('autocomplete_suggestions');

input.addEventListener('input', function() {
    const val = this.value.trim().toLowerCase();
    suggestions.innerHTML = '';
    if (val.length < 2) return;
    const matches = clienti.filter(c => c.nome_completo.toLowerCase().includes(val));
    matches.forEach(c => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action';
        item.textContent = c.nome_completo;
        item.onclick = function() {
            input.value = c.nome_completo;
            hiddenId.value = c.id;
            suggestions.innerHTML = '';
        };
        suggestions.appendChild(item);
    });
});

document.addEventListener('click', function(e) {
    if (!suggestions.contains(e.target) && e.target !== input) {
        suggestions.innerHTML = '';
    }
});
</script>

                            <!-- Anno -->
                            <div class="col-md-6 mb-3">
                                <label for="anno" class="form-label">
                                    Anno <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="anno" name="anno" 
                                       value="<?= htmlspecialchars($record['anno'] ?? date('Y')) ?>" 
                                       required maxlength="4" minlength="4" pattern="[0-9]{4}"
                                       placeholder="Es. <?= date('Y') ?>"
                                       title="Inserire un anno valido di 4 cifre">
                                <div class="form-text">Inserire l'anno come 4 cifre numeriche</div>
                            </div>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Esito -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Esito</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="esito" id="esito_positivo" value="positivo"
                                               <?= ($record['esito'] ?? '') == 'positivo' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-success" for="esito_positivo">
                                            <i class="fas fa-check-circle me-1"></i>Positivo
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="esito" id="esito_negativo" value="negativo"
                                               <?= ($record['esito'] ?? '') == 'negativo' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-danger" for="esito_negativo">
                                            <i class="fas fa-times-circle me-1"></i>Negativo
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="esito" id="esito_attesa" value="in_attesa"
                                               <?= ($record['esito'] ?? 'in_attesa') == 'in_attesa' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-warning" for="esito_attesa">
                                            <i class="fas fa-clock me-1"></i>In Attesa
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Mese -->
                            <div class="col-md-6 mb-3">
                                <label for="mese" class="form-label">Mese</label>
                                <select class="form-select" id="mese" name="mese">
                                    <option value="">Seleziona mese...</option>
                                    <?php 
                                    $mesi = [
                                        'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
                                        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
                                    ];
                                    foreach ($mesi as $mese): ?>
                                        <option value="<?= $mese ?>" 
                                                <?= ($record['mese'] ?? '') == $mese ? 'selected' : '' ?>>
                                            <?= $mese ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Prestazione -->
                            <div class="col-md-6 mb-3">
                                <label for="prestazione" class="form-label">Prestazione (€)</label>
                                <input type="number" class="form-control" id="prestazione" name="prestazione" 
                                       step="0.01" min="0"
                                       value="<?= htmlspecialchars($record['prestazione'] ?? '') ?>"
                                       placeholder="0.00">
                            </div>

                            <!-- Incassato -->
                            <div class="col-md-6 mb-3">
                                <label for="incassato" class="form-label">Incassato (€)</label>
                                <input type="number" class="form-control" id="incassato" name="incassato" 
                                       step="0.01" min="0"
                                       value="<?= htmlspecialchars($record['incassato'] ?? '') ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="row">
                            <!-- User -->
                            <div class="col-md-6 mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" 
                                       value="<?= htmlspecialchars($record['user'] ?? '') ?>"
                                       placeholder="Username...">
                            </div>

                            <!-- Password -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="password" name="password" 
                                           value="<?= htmlspecialchars($record['password'] ?? '') ?>"
                                           placeholder="Password...">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Modello Stufa -->
                            <div class="col-md-8 mb-3">
                                <label for="modello_stufa" class="form-label">Modello Stufa</label>
                                <input type="text" class="form-control" id="modello_stufa" name="modello_stufa" 
                                       value="<?= htmlspecialchars($record['modello_stufa'] ?? '') ?>"
                                       placeholder="Modello e marca della stufa...">
                            </div>

                            <!-- Data Termine -->
                            <div class="col-md-4 mb-3">
                                <label for="data_termine" class="form-label">Data Termine</label>
                                <input type="date" class="form-control" id="data_termine" name="data_termine" 
                                       value="<?= $record['data_termine'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Sezione Pratica -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-file-alt me-2"></i>Dati Pratica
                                </h6>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Numero Pratica -->
                            <div class="col-md-4 mb-3">
                                <label for="numero_pratica" class="form-label">Numero Pratica</label>
                                <input type="text" class="form-control" id="numero_pratica" name="numero_pratica" 
                                       value="<?= htmlspecialchars($record['numero_pratica'] ?? '') ?>"
                                       placeholder="N. pratica...">
                            </div>

                            <!-- Data Presentazione -->
                            <div class="col-md-4 mb-3">
                                <label for="data_presentazione" class="form-label">Data Presentazione</label>
                                <input type="date" class="form-control" id="data_presentazione" name="data_presentazione" 
                                       value="<?= $record['data_presentazione'] ?? '' ?>">
                            </div>

                            <!-- Stato -->
                            <div class="col-md-4 mb-3">
                                <label for="stato" class="form-label">Stato</label>
                                <select class="form-select" id="stato" name="stato">
                                    <option value="bozza" <?= ($record['stato'] ?? 'bozza') == 'bozza' ? 'selected' : '' ?>>Bozza</option>
                                    <option value="presentata" <?= ($record['stato'] ?? '') == 'presentata' ? 'selected' : '' ?>>Presentata</option>
                                    <option value="istruttoria" <?= ($record['stato'] ?? '') == 'istruttoria' ? 'selected' : '' ?>>Istruttoria</option>
                                    <option value="accettata" <?= ($record['stato'] ?? '') == 'accettata' ? 'selected' : '' ?>>Accettata</option>
                                    <option value="respinta" <?= ($record['stato'] ?? '') == 'respinta' ? 'selected' : '' ?>>Respinta</option>
                                    <option value="liquidata" <?= ($record['stato'] ?? '') == 'liquidata' ? 'selected' : '' ?>>Liquidata</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tipo Intervento -->
                            <div class="col-md-12 mb-3">
                                <label for="tipo_intervento" class="form-label">Tipo Intervento</label>
                                <input type="text" class="form-control" id="tipo_intervento" name="tipo_intervento" 
                                       value="<?= htmlspecialchars($record['tipo_intervento'] ?? '') ?>"
                                       placeholder="Descrizione del tipo di intervento...">
                            </div>
                        </div>

                        <!-- Sezione Economica -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-success border-bottom pb-2 mb-3">
                                    <i class="fas fa-euro-sign me-2"></i>Dati Economici
                                </h6>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Importo Ammissibile -->
                            <div class="col-md-6 mb-3">
                                <label for="importo_ammissibile" class="form-label">Importo Ammissibile (€)</label>
                                <input type="number" class="form-control" id="importo_ammissibile" name="importo_ammissibile" 
                                       step="0.01" min="0"
                                       value="<?= $record['importo_ammissibile'] ?? '' ?>"
                                       placeholder="0.00">
                            </div>

                            <!-- Contributo -->
                            <div class="col-md-6 mb-3">
                                <label for="contributo" class="form-label">Contributo (€)</label>
                                <input type="number" class="form-control" id="contributo" name="contributo" 
                                       step="0.01" min="0"
                                       value="<?= $record['contributo'] ?? '' ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="note" class="form-label">Note</label>
                                <textarea class="form-control" id="note" name="note" rows="4"
                                          placeholder="Note aggiuntive..."><?= htmlspecialchars($record['note'] ?? '') ?></textarea>
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
                            <a href="conto_termico.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Aggiorna Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Auto format euro inputs
document.querySelectorAll('input[type="number"][step="0.01"]').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
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

.input-group .btn {
    border-color: #ced4da;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>

<?php 
if ($is_popup) {
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</body></html>';
}
?>
