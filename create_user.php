<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!in_array($_SESSION['user_role'], ['administrator', 'developer'])) {
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
        $messaggio = "<p style='color: green;'>✅ Utente creato con successo.</p>";
    } else {
        $messaggio = "<p style='color: red;'>❌ Compila tutti i campi obbligatori.</p>";
    }
}
?>

<h2>➕ Crea Nuovo Utente</h2>
<?= $messaggio ?>

<form method="post" style="max-width: 400px;">
    <label>Nome*</label><br>
    <input type="text" name="nome" required><br><br>

    <label>Email*</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password*</label><br>
    <input type="password" name="password" required><br><br>

    <label>Ruolo*</label><br>
    <select name="ruolo" required>
        <option value="guest">Guest</option>
        <option value="impiegato">Impiegato</option>
        <option value="admin">Administrator</option>
        <option value="developer">Developer</option>
    </select><br><br>

    <label>Telegram Chat ID</label><br>
    <input type="text" name="telegram_chat_id"><br><br>

    <button type="submit">💾 Crea Utente</button>
</form>

<p><a href="gestione_utenti.php">⬅ Torna alla gestione utenti</a></p>

</main>
</body>
</html>
