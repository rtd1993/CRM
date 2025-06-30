<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$utente_loggato_id = $_SESSION['user_id'];
$utente_loggato_ruolo = $_SESSION['user_role'];

if (!in_array($utente_loggato_ruolo, ['admin', 'developer'])) {
    die("Accesso non autorizzato.");
}

// Eliminazione utente
if (isset($_GET['delete_id']) && $utente_loggato_ruolo === 'developer') {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id !== $utente_loggato_id) { // prevenzione autodistruzione
        $stmt = $pdo->prepare("DELETE FROM utenti WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: gestione_utenti.php");
        exit;
    }
}

// Salva modifiche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $ruolo = $_POST['ruolo'] ?? '';
    $telegram_chat_id = $_POST['telegram_chat_id'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("UPDATE utenti SET nome = ?, email = ?, ruolo = ?, telegram_chat_id = ? WHERE id = ?");
    $ok = $stmt->execute([$nome, $email, $ruolo, $telegram_chat_id, $id]);

    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);
    }

    if ($ok) {
        header("Location: gestione_utenti.php?edit_id=$id&success=1");
        exit;
    } else {
        echo "<p style='color:red;'>Errore nel salvataggio!</p>";
    }
}

$stmt = $pdo->query("SELECT id, nome, email, ruolo, telegram_chat_id FROM utenti ORDER BY ruolo ASC, nome ASC");
$utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

$utenti_per_ruolo = [];
foreach ($utenti as $u) {
    $utenti_per_ruolo[$u['ruolo']][] = $u;
}

$utente_selezionato = null;
if (isset($_GET['edit_id'])) {
    $id_sel = intval($_GET['edit_id']);
    foreach ($utenti as $u) {
        if ($u['id'] === $id_sel) {
            $utente_selezionato = $u;
            break;
        }
    }
}
?>

<h2>Gestione Utenti</h2>
<a href="create_user.php">➕ Crea nuovo utente</a>

<div style="display: flex; gap: 30px; align-items: flex-start;">
    <!-- Lista utenti -->
    <div style="flex: 1; border-right: 1px solid #ccc; padding-right: 20px;">
        <h3>Utenti per ruolo</h3>
        <?php foreach ($utenti_per_ruolo as $ruolo => $lista): ?>
            <strong><?= ucfirst($ruolo) ?></strong>
            <ul style="list-style: none; padding-left: 10px;">
                <?php foreach ($lista as $u): ?>
                    <li>
                        <a href="?edit_id=<?= $u['id'] ?>">
                            <?= htmlspecialchars($u['nome']) ?> (<?= $u['email'] ?>)
                        </a>
                        <?php if ($utente_loggato_ruolo === 'developer' && $u['id'] !== $utente_loggato_id): ?>
                            <a href="?delete_id=<?= $u['id'] ?>" onclick="return confirm('Sei sicuro di voler eliminare l\'utente <?= addslashes($u['nome']) ?>?');" style="color:red; margin-left:10px;">🗑️</a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>

    <!-- Form modifica -->
    <div style="flex: 2;">
        <h3>Dati utente</h3>
        <?php if ($utente_selezionato): ?>
            <form method="post">
                <input type="hidden" name="id" value="<?= $utente_selezionato['id'] ?>">
                <label>Nome:</label><br>
                <input type="text" name="nome" value="<?= htmlspecialchars($utente_selezionato['nome']) ?>" required><br><br>
                <label>Email:</label><br>
                <input type="email" name="email" value="<?= htmlspecialchars($utente_selezionato['email']) ?>" required><br><br>
                <label>Ruolo:</label><br>
                <select name="ruolo" required>
                    <?php foreach (["guest", "impiegato", "admin", "developer"] as $ruolo): ?>
                        <option value="<?= $ruolo ?>" <?= $utente_selezionato['ruolo'] === $ruolo ? 'selected' : '' ?>><?= ucfirst($ruolo) ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <label>Telegram Chat ID:</label><br>
                <input type="text" name="telegram_chat_id" value="<?= htmlspecialchars($utente_selezionato['telegram_chat_id']) ?>"><br><br>
                <label>Nuova password (facoltativa):</label><br>
                <input type="password" name="password" placeholder="Lascia vuoto per non cambiare"><br><br>
                <button type="submit">💾 Salva modifiche</button>
            </form>
        <?php else: ?>
            <p>Seleziona un utente a destra per modificarne i dati.</p>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>