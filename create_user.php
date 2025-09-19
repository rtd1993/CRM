<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';

if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Accesso non autorizzato.");
}

// Colori standard disponibili
$colori_standard = [
    '#007BFF' => 'Blu',
    '#28A745' => 'Verde',
    '#DC3545' => 'Rosso',
    '#FFC107' => 'Giallo',
    '#6F42C1' => 'Viola',
    '#20C997' => 'Teal',
    '#FD7E14' => 'Arancione',
    '#E91E63' => 'Rosa',
    '#795548' => 'Marrone',
    '#6C757D' => 'Grigio'
];

// Ottieni colori già utilizzati
$stmt = $pdo->query("SELECT colore FROM utenti WHERE colore IS NOT NULL");
$colori_utilizzati = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'colore');

$messaggio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ruolo = $_POST['ruolo'];
    $telegram_chat_id = trim($_POST['telegram_chat_id']);
    $colore = $_POST['colore'] ?? '#007BFF';

    if ($nome && $email && $password && $ruolo) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, email, password, ruolo, telegram_chat_id, colore) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $hash, $ruolo, $telegram_chat_id ?: null, $colore]);
        
        // Ottieni l'ID del nuovo utente
        $new_user_id = $pdo->lastInsertId();
        
        // Aggiungi automaticamente il nuovo utente alla chat globale (conversation_id = 1)
        try {
            $stmt_chat = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, role, is_active) VALUES (1, ?, 'member', 1)");
            $stmt_chat->execute([$new_user_id]);
        } catch (Exception $e) {
            // Se fallisce l'aggiunta alla chat, logga l'errore ma non bloccare la creazione utente
            error_log("Errore aggiunta utente $new_user_id alla chat globale: " . $e->getMessage());
        }
        
        $messaggio = "<div class='alert alert-success mt-3'>✅ Utente creato con successo e aggiunto alla chat globale.</div>";
        
        // Se è in modalità popup, chiudi il modal
        if (isset($_GET['popup'])) {
            echo '<script>
                if (parent && parent.closeUserModal) {
                    setTimeout(function() { parent.closeUserModal(); }, 1000);
                }
            </script>';
        }
    } else {
        $messaggio = "<div class='alert alert-danger mt-3'>❌ Compila tutti i campi obbligatori.</div>";
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
        <title>Crea Nuovo Utente</title>
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

<div class="<?= $is_popup ? 'popup-container' : 'container my-5' ?>" style="max-width: 500px;">
    <div class="card shadow">
        <div class="card-body">
            <h2 class="card-title mb-4 text-primary"><span style="font-size:1.3em;">➕</span> Crea Nuovo Utente</h2>
            <?= $messaggio ?>
            <form method="post" novalidate>
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label for="ruolo" class="form-label">Ruolo <span class="text-danger">*</span></label>
                    <select class="form-select" id="ruolo" name="ruolo" required>
                        <option value="" disabled selected>Seleziona ruolo...</option>
                        <option value="guest">Guest</option>
                        <option value="employee">Impiegato</option>
                        <option value="admin">Administrator</option>
                        <option value="developer">Developer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="telegram_chat_id" class="form-label">Telegram Chat ID</label>
                    <input type="text" class="form-control" id="telegram_chat_id" name="telegram_chat_id">
                </div>
                
                <div class="mb-3">
                    <label for="colore" class="form-label">Colore Utente <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <?php foreach ($colori_standard as $hex => $nome): ?>
                            <?php $is_disabled = in_array($hex, $colori_utilizzati); ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input d-none" 
                                        type="radio" 
                                        name="colore" 
                                        id="colore_<?= str_replace('#', '', $hex) ?>" 
                                        value="<?= $hex ?>"
                                        <?= $is_disabled ? 'disabled' : '' ?>
                                        <?= $hex === '#007BFF' && !$is_disabled ? 'checked' : '' ?>
                                    >
                                    <label 
                                        class="color-option <?= $is_disabled ? 'disabled' : '' ?>" 
                                        for="colore_<?= str_replace('#', '', $hex) ?>"
                                        style="background-color: <?= $hex ?>;"
                                        title="<?= $nome ?><?= $is_disabled ? ' (già utilizzato)' : '' ?>"
                                    >
                                        <?= $is_disabled ? '✕' : '' ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text text-muted mt-2">
                        <i class="fas fa-info-circle"></i> 
                        Seleziona un colore per identificare l'utente. I colori già utilizzati sono disabilitati.
                    </small>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success shadow-sm"><span style="font-size:1.2em;">💾</span> Crea Utente</button>
                </div>
            </form>
            <?php if (!$is_popup): ?>
                <div class="mt-4">
                    <a href="gestione_utenti.php" class="btn btn-link text-decoration-none">⬅ Torna alla gestione utenti</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stili per la selezione colore -->
<style>
.color-option {
    display: block;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 3px solid #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.color-option:hover:not(.disabled) {
    border-color: #007bff;
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.color-option.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    position: relative;
}

.form-check-input:checked + .color-option {
    border-color: #007bff;
    border-width: 4px;
    transform: scale(1.1);
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.form-check-input:checked + .color-option:after {
    content: '✓';
    position: absolute;
    color: white;
    font-weight: bold;
    font-size: 18px;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
}
</style>

<?php 
if ($is_popup) {
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>';
    echo '</body></html>';
}
?>