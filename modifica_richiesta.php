<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

$success_message = '';
$error_message = '';
$richiesta = null;

// Verifica se è stato fornito l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('<div class="alert alert-danger">ID richiesta non valido.</div>');
}

$richiesta_id = (int)$_GET['id'];

// Recupera i dati della richiesta
try {
    $stmt = $pdo->prepare("SELECT * FROM richieste WHERE id = ?");
    $stmt->execute([$richiesta_id]);
    $richiesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$richiesta) {
        die('<div class="alert alert-danger">Richiesta non trovata.</div>');
    }
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Errore nel recupero della richiesta: ' . $e->getMessage() . '</div>');
}

// Gestione aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $denominazione = trim($_POST['denominazione'] ?? '');
    $data_richiesta = $_POST['data_richiesta'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $attivita_pagamento = isset($_POST['attivita_pagamento']) ? 1 : 0;
    $importo = !empty($_POST['importo']) ? (float)$_POST['importo'] : null;
    $richiesta_text = trim($_POST['richiesta'] ?? '');
    $soluzione = trim($_POST['soluzione'] ?? '');
    $stato = $_POST['stato'] ?? 'aperta';

    // Validazione
    if (empty($denominazione) || empty($data_richiesta) || empty($richiesta_text)) {
        $error_message = "I campi Denominazione, Data e Richiesta sono obbligatori.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Aggiorna la richiesta
            $sql = "UPDATE richieste SET 
                        denominazione = ?, 
                        data_richiesta = ?, 
                        telefono = ?, 
                        email = ?, 
                        attivita_pagamento = ?, 
                        importo = ?, 
                        richiesta = ?, 
                        soluzione = ?, 
                        stato = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $denominazione,
                $data_richiesta,
                $telefono ?: null,
                $email ?: null,
                $attivita_pagamento,
                $importo,
                $richiesta_text,
                $soluzione ?: null,
                $stato,
                $richiesta_id
            ]);
            
            // Aggiorna anche il task associato se esiste
            $task_update_sql = "UPDATE task SET 
                                    descrizione = ?
                                WHERE descrizione LIKE ? AND descrizione LIKE ?";
            
            $task_stmt = $pdo->prepare($task_update_sql);
            $task_stmt->execute([
                "Gestire richiesta: \"" . $denominazione . "\" (ID: " . $richiesta_id . ")",
                '%Gestire richiesta:%',
                '%ID: ' . $richiesta_id . '%'
            ]);
            
            $pdo->commit();
                    // Se lo stato è completata o chiusa, elimina il task associato
                    if (in_array($stato, ['completata', 'chiusa'])) {
                        $task_delete_stmt = $pdo->prepare("DELETE FROM task WHERE descrizione LIKE ?");
                        $task_delete_stmt->execute(['%Gestire richiesta:%ID: ' . $richiesta_id . '%']);
                    }
            
            // Ricarica i dati aggiornati
            $stmt = $pdo->prepare("SELECT * FROM richieste WHERE id = ?");
            $stmt->execute([$richiesta_id]);
            $richiesta = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $success_message = "Richiesta aggiornata con successo!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Errore nell'aggiornamento della richiesta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Richiesta #<?= $richiesta['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid p-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-edit me-2"></i>Modifica Richiesta #<?= $richiesta['id'] ?>
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

                        <!-- Info richiesta -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-plus me-1"></i>
                                    Creata il: <?= date('d/m/Y H:i', strtotime($richiesta['created_at'])) ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-edit me-1"></i>
                                    Ultima modifica: <?= date('d/m/Y H:i', strtotime($richiesta['updated_at'])) ?>
                                </small>
                            </div>
                        </div>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <!-- Denominazione -->
                                <div class="col-md-8 mb-3">
                                    <label for="denominazione" class="form-label">
                                        <i class="fas fa-building me-1"></i>Denominazione *
                                    </label>
                                    <input type="text" class="form-control" id="denominazione" name="denominazione" 
                                           value="<?= htmlspecialchars($richiesta['denominazione']) ?>" required>
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
                                           value="<?= htmlspecialchars($richiesta['data_richiesta']) ?>" required>
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
                                           value="<?= htmlspecialchars($richiesta['telefono'] ?? '') ?>">
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($richiesta['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <!-- Attività a Pagamento -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="attivita_pagamento" 
                                               name="attivita_pagamento" <?= $richiesta['attivita_pagamento'] ? 'checked' : '' ?>
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
                                           value="<?= htmlspecialchars($richiesta['importo'] ?? '') ?>" 
                                           step="0.01" min="0" <?= $richiesta['attivita_pagamento'] ? '' : 'disabled' ?>>
                                </div>
                            </div>

                            <!-- Stato -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="stato" class="form-label">
                                        <i class="fas fa-flag me-1"></i>Stato
                                    </label>
                                    <select class="form-select" id="stato" name="stato">
                                        <option value="aperta" <?= $richiesta['stato'] === 'aperta' ? 'selected' : '' ?>>Aperta</option>
                                        <option value="in_lavorazione" <?= $richiesta['stato'] === 'in_lavorazione' ? 'selected' : '' ?>>In Lavorazione</option>
                                        <option value="completata" <?= $richiesta['stato'] === 'completata' ? 'selected' : '' ?>>Completata</option>
                                        <option value="chiusa" <?= $richiesta['stato'] === 'chiusa' ? 'selected' : '' ?>>Chiusa</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Richiesta -->
                            <div class="mb-3">
                                <label for="richiesta" class="form-label">
                                    <i class="fas fa-question-circle me-1"></i>Descrizione Richiesta *
                                </label>
                                <textarea class="form-control" id="richiesta" name="richiesta" rows="4" required><?= htmlspecialchars($richiesta['richiesta']) ?></textarea>
                                <div class="invalid-feedback">
                                    Inserisci la descrizione della richiesta.
                                </div>
                            </div>

                            <!-- Soluzione -->
                            <div class="mb-3">
                                <label for="soluzione" class="form-label">
                                    <i class="fas fa-lightbulb me-1"></i>Soluzione Proposta/Implementata
                                </label>
                                <textarea class="form-control" id="soluzione" name="soluzione" rows="3"><?= htmlspecialchars($richiesta['soluzione'] ?? '') ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary" onclick="parent.closeRichiestaModal()">
                                    <i class="fas fa-times me-1"></i>Chiudi
                                </button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-1"></i>Aggiorna Richiesta
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
        } else {
            importoField.disabled = true;
            importoField.value = '';
        }
    }

    // Chiudi il modal se la richiesta è stata aggiornata con successo
    <?php if ($success_message): ?>
        setTimeout(function() {
            if (parent.closeRichiestaModal) {
                parent.closeRichiestaModal();
            }
        }, 1500);
    <?php endif; ?>
    </script>
</body>
</html>
