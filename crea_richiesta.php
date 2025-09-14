<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $denominazione = trim($_POST['denominazione'] ?? '');
    $data_richiesta = $_POST['data_richiesta'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $attivita_pagamento = isset($_POST['attivita_pagamento']) ? 1 : 0;
    $importo = !empty($_POST['importo']) ? (float)$_POST['importo'] : null;
    $richiesta = trim($_POST['richiesta'] ?? '');
    $soluzione = trim($_POST['soluzione'] ?? '');
    $stato = $_POST['stato'] ?? 'aperta';

    // Validazione
    if (empty($denominazione) || empty($data_richiesta) || empty($richiesta)) {
        $error_message = "I campi Denominazione, Data e Richiesta sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Inserisci la richiesta
            $sql = "INSERT INTO richieste (denominazione, data_richiesta, telefono, email, attivita_pagamento, importo, richiesta, soluzione, stato) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $denominazione,
                $data_richiesta,
                $telefono ?: null,
                $email ?: null,
                $attivita_pagamento,
                $importo,
                $richiesta,
                $soluzione ?: null,
                $stato
            ]);
            
            $richiesta_id = $pdo->lastInsertId();
            
            // Crea il task associato con scadenza a 30 giorni
            $data_scadenza = date('Y-m-d', strtotime($data_richiesta . ' + 30 days'));
            
            $task_sql = "INSERT INTO task (utente_id, descrizione, data_scadenza, priorita, stato) 
                         VALUES (?, ?, ?, 'media', 'da iniziare')";
            
            $task_stmt = $pdo->prepare($task_sql);
            $task_stmt->execute([
                $_SESSION['user_id'],
                "Gestire richiesta: \"" . $denominazione . "\" (ID: " . $richiesta_id . ")",
                $data_scadenza
            ]);
            
            $pdo->commit();
            $success_message = "Richiesta creata con successo! Task associato creato con scadenza al " . date('d/m/Y', strtotime($data_scadenza)) . ".";
            
            // Reset form
            $denominazione = $data_richiesta = $telefono = $email = $richiesta = $soluzione = '';
            $attivita_pagamento = 0;
            $importo = null;
            $stato = 'aperta';
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Errore nella creazione della richiesta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuova Richiesta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus me-2"></i>Nuova Richiesta
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= htmlspecialchars($success_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error_message) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Denominazione -->
                                <div class="col-md-8 mb-3">
                                    <label for="denominazione" class="form-label">
                                        <i class="fas fa-building me-1"></i>Denominazione *
                                    </label>
                                    <input type="text" class="form-control" id="denominazione" name="denominazione" 
                                           value="<?= htmlspecialchars($denominazione ?? '') ?>" required>
                                    <div class="invalid-feedback">
                                        Inserisci la denominazione del cliente/azienda.
                                    </div>
                                </div>

                                <!-- Data Richiesta -->
                                <div class="col-md-4 mb-3">
                                    <label for="data_richiesta" class="form-label">
                                        <i class="fas fa-calendar me-1"></i>Data Richiesta *
                                    </label>
                                    <input type="date" class="form-control" id="data_richiesta" name="data_richiesta" 
                                           value="<?= htmlspecialchars($data_richiesta ?? date('Y-m-d')) ?>" required>
                                    <div class="invalid-feedback">
                                        Seleziona la data della richiesta.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Telefono -->
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Numero di Telefono
                                    </label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                                           value="<?= htmlspecialchars($telefono ?? '') ?>">
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($email ?? '') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <!-- Attività a Pagamento -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="attivita_pagamento" 
                                               name="attivita_pagamento" <?= ($attivita_pagamento ?? 0) ? 'checked' : '' ?>
                                               onchange="toggleImporto()">
                                        <label class="form-check-label" for="attivita_pagamento">
                                            <i class="fas fa-euro-sign me-1"></i>Attività a Pagamento
                                        </label>
                                    </div>
                                </div>

                                <!-- Importo -->
                                <div class="col-md-6 mb-3">
                                    <label for="importo" class="form-label">
                                        <i class="fas fa-euro-sign me-1"></i>Importo (€)
                                    </label>
                                    <input type="number" class="form-control" id="importo" name="importo" 
                                           value="<?= htmlspecialchars($importo ?? '') ?>" 
                                           step="0.01" min="0" <?= ($attivita_pagamento ?? 0) ? '' : 'disabled' ?>>
                                </div>
                            </div>

                            <!-- Stato -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="stato" class="form-label">
                                        <i class="fas fa-flag me-1"></i>Stato
                                    </label>
                                    <select class="form-select" id="stato" name="stato">
                                        <option value="aperta" <?= ($stato ?? 'aperta') === 'aperta' ? 'selected' : '' ?>>Aperta</option>
                                        <option value="in_lavorazione" <?= ($stato ?? '') === 'in_lavorazione' ? 'selected' : '' ?>>In Lavorazione</option>
                                        <option value="completata" <?= ($stato ?? '') === 'completata' ? 'selected' : '' ?>>Completata</option>
                                        <option value="chiusa" <?= ($stato ?? '') === 'chiusa' ? 'selected' : '' ?>>Chiusa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Richiesta -->
                            <div class="mb-3">
                                <label for="richiesta" class="form-label">
                                    <i class="fas fa-question-circle me-1"></i>Descrizione Richiesta *
                                </label>
                                <textarea class="form-control" id="richiesta" name="richiesta" rows="4" required><?= htmlspecialchars($richiesta ?? '') ?></textarea>
                                <div class="invalid-feedback">
                                    Inserisci la descrizione della richiesta.
                                </div>
                            </div>

                            <!-- Soluzione -->
                            <div class="mb-3">
                                <label for="soluzione" class="form-label">
                                    <i class="fas fa-lightbulb me-1"></i>Soluzione Proposta/Implementata
                                </label>
                                <textarea class="form-control" id="soluzione" name="soluzione" rows="3"><?= htmlspecialchars($soluzione ?? '') ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" onclick="parent.closeRichiestaModal()">
                                    <i class="fas fa-times me-1"></i>Annulla
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Salva Richiesta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Validazione form
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    // Funzione per abilitare/disabilitare il campo importo
    function toggleImporto() {
        const checkbox = document.getElementById('attivita_pagamento');
        const importoField = document.getElementById('importo');
        
        if (checkbox.checked) {
            importoField.disabled = false;
            importoField.focus();
        } else {
            importoField.disabled = true;
            importoField.value = '';
        }
    }

    // Chiudi il modal se la richiesta è stata creata con successo
    <?php if ($success_message): ?>
        setTimeout(function() {
            if (parent.closeRichiestaModal) {
                parent.closeRichiestaModal();
            }
        }, 2000);
    <?php endif; ?>
    </script>
</body>
</html>
