<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Accesso non autorizzato.");
}

$messaggio = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ruolo = $_POST['ruolo'];
    $telegram_chat_id = trim($_POST['telegram_chat_id']);

    if ($nome && $email && $password && $ruolo) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utenti (nome, email, password, ruolo, telegram_chat_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $hash, $ruolo, $telegram_chat_id ?: null]);
        $messaggio = "<div class='alert alert-success mt-3'>✅ Utente creato con successo.</div>";
    } else {
        $messaggio = "<div class='alert alert-danger mt-3'>❌ Compila tutti i campi obbligatori.</div>";
    }
}
?>

<div class="container my-5" style="max-width: 500px;">
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
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success shadow-sm"><span style="font-size:1.2em;">💾</span> Crea Utente</button>
                </div>
            </form>
            <div class="mt-4">
                <a href="gestione_utenti.php" class="btn btn-link text-decoration-none">⬅ Torna alla gestione utenti</a>
            </div>
        </div>
    </div>
</div>

</main>
</body>
</html>