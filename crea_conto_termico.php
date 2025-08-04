<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_login();

$page_title = "Nuovo Record Conto Termico";

$errors = [];
$success_message = '';

// Carica lista clienti
$clienti = $pdo->query("SELECT id, CONCAT(cognome_ragione_sociale, ' ', COALESCE(nome, '')) as nome_completo FROM clienti ORDER BY cognome_ragione_sociale, nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validazione dati
    $cliente_id = $_POST['cliente_id'] ?? '';
    $anno = $_POST['anno'] ?? '';
    $esito = $_POST['esito'] ?? '';
    $prestazione = $_POST['prestazione'] ?? null;
    $incassato = $_POST['incassato'] ?? null;
    $user = $_POST['user'] ?? '';
    $password = $_POST['password'] ?? '';
    $modello_stufa = $_POST['modello_stufa'] ?? '';
    $data_termine = $_POST['data_termine'] ?? null;
    $mese = $_POST['mese'] ?? '';

    // Validazioni
    if (empty($cliente_id)) {
        $errors[] = "Il cliente è obbligatorio";
    }
    if (empty($anno)) {
        $errors[] = "L'anno è obbligatorio";
    }

    // Se non ci sono errori, inserisci nel database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO conto_termico 
                (cliente_id, anno, esito, prestazione, incassato, user, password, modello_stufa, data_termine, mese) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $cliente_id,
                $anno,
                $esito ?: null,
                $prestazione ?: null,
                $incassato ?: null,
                $user ?: null,
                $password ?: null,
                $modello_stufa ?: null,
                $data_termine ?: null,
                $mese ?: null
            ]);

            $success_message = "Record creato con successo!";
            
            // Reset form
            $_POST = [];
        } catch (Exception $e) {
            $errors[] = "Errore durante l'inserimento: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle text-success me-2"></i><?= $page_title ?>
                    </h4>
                    <a href="conto_termico.php" class="btn btn-secondary">
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

                    <form method="POST" id="contoTermicoForm">
                        <div class="row">
                            <!-- Cliente -->
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

                            <!-- Anno -->
                            <div class="col-md-6 mb-3">
                                <label for="anno" class="form-label">
                                    Anno <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="anno" name="anno" required>
                                    <option value="">Seleziona anno...</option>
                                    <?php for ($anno = date('Y'); $anno >= 2020; $anno--): ?>
                                        <option value="<?= $anno ?>" 
                                                <?= ($_POST['anno'] ?? '') == $anno ? 'selected' : '' ?>>
                                            <?= $anno ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Esito -->
                            <div class="col-md-6 mb-3">
                                <label for="esito" class="form-label">Esito</label>
                                <input type="text" class="form-control" id="esito" name="esito" 
                                       value="<?= htmlspecialchars($_POST['esito'] ?? '') ?>"
                                       placeholder="Es: Positivo, Negativo, In attesa...">
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
                                                <?= ($_POST['mese'] ?? '') == $mese ? 'selected' : '' ?>>
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
                                       value="<?= htmlspecialchars($_POST['prestazione'] ?? '') ?>"
                                       placeholder="0.00">
                            </div>

                            <!-- Incassato -->
                            <div class="col-md-6 mb-3">
                                <label for="incassato" class="form-label">Incassato (€)</label>
                                <input type="number" class="form-control" id="incassato" name="incassato" 
                                       step="0.01" min="0"
                                       value="<?= htmlspecialchars($_POST['incassato'] ?? '') ?>"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div class="row">
                            <!-- User -->
                            <div class="col-md-6 mb-3">
                                <label for="user" class="form-label">User</label>
                                <input type="text" class="form-control" id="user" name="user" 
                                       value="<?= htmlspecialchars($_POST['user'] ?? '') ?>"
                                       placeholder="Username...">
                            </div>

                            <!-- Password -->
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           value="<?= htmlspecialchars($_POST['password'] ?? '') ?>"
                                           placeholder="Password...">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Modello Stufa -->
                            <div class="col-md-8 mb-3">
                                <label for="modello_stufa" class="form-label">Modello Stufa</label>
                                <input type="text" class="form-control" id="modello_stufa" name="modello_stufa" 
                                       value="<?= htmlspecialchars($_POST['modello_stufa'] ?? '') ?>"
                                       placeholder="Modello e marca della stufa...">
                            </div>

                            <!-- Data Termine -->
                            <div class="col-md-4 mb-3">
                                <label for="data_termine" class="form-label">Data Termine</label>
                                <input type="date" class="form-control" id="data_termine" name="data_termine" 
                                       value="<?= htmlspecialchars($_POST['data_termine'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="conto_termico.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Salva Record
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
</style>

<?php include 'includes/chat_widget.php'; ?>
<?php include 'includes/chat_pratiche_widget.php'; ?>
