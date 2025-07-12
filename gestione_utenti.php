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

<style>
.users-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.users-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.users-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.users-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

.create-user-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.create-user-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

.users-container {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 2rem;
    margin-top: 2rem;
}

.users-list {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
    height: fit-content;
}

.users-list h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 3px solid #667eea;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.users-list h3::before {
    content: '👥';
    font-size: 1.2rem;
}

.role-section {
    margin-bottom: 2rem;
}

.role-title {
    color: #495057;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.8rem;
    padding: 0.5rem 0.8rem;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.role-developer {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    color: white;
}

.role-admin {
    background: linear-gradient(135deg, #0056b3 0%, #6f42c1 100%);
    color: white;
}

.role-impiegato {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.role-guest {
    background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
    color: white;
}

.user-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.8rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.user-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
    border-left-color: #667eea;
}

.user-item.selected {
    background: #e3f2fd;
    border-left-color: #2196f3;
}

.user-link {
    color: #495057;
    text-decoration: none;
    font-weight: 500;
    flex: 1;
}

.user-link:hover {
    color: #667eea;
    text-decoration: none;
}

.user-email {
    font-size: 0.9rem;
    color: #6c757d;
    display: block;
    margin-top: 0.2rem;
}

.delete-btn {
    color: #dc3545;
    text-decoration: none;
    padding: 0.3rem 0.5rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.delete-btn:hover {
    background: #dc3545;
    color: white;
    transform: scale(1.1);
}

.edit-form {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.edit-form h3 {
    color: #2c3e50;
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 3px solid #667eea;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.edit-form h3::before {
    content: '✏️';
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    color: #495057;
    font-weight: 600;
    font-size: 0.95rem;
}

.form-input {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-select {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 1rem;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.save-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.no-user-selected {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 3rem;
    background: #f8f9fa;
    border-radius: 15px;
    border: 2px dashed #dee2e6;
}

.success-message {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border: 1px solid #c3e6cb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.success-message::before {
    content: '✅';
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .users-container {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .users-header h2 {
        font-size: 2rem;
    }
    
    .users-list, .edit-form {
        padding: 1rem;
    }
    
    .user-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .delete-btn {
        align-self: flex-end;
    }
}
</style>

<div class="users-header">
    <h2>👥 Gestione Utenti</h2>
    <p>Amministrazione account e permessi utenti</p>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="success-message">
        Modifiche salvate con successo!
    </div>
<?php endif; ?>

<a href="create_user.php" class="create-user-btn">
    ➕ Crea nuovo utente
</a>

<div class="users-container">
    <!-- Lista utenti -->
    <div class="users-list">
        <h3>Utenti per ruolo</h3>
        <?php foreach ($utenti_per_ruolo as $ruolo => $lista): ?>
            <div class="role-section">
                <div class="role-title role-<?= $ruolo ?>">
                    <?php 
                    $icons = [
                        'developer' => '👨‍💻',
                        'admin' => '👨‍💼', 
                        'impiegato' => '👤',
                        'guest' => '👥'
                    ];
                    echo $icons[$ruolo] ?? '👤';
                    ?>
                    <?= ucfirst($ruolo) ?> (<?= count($lista) ?>)
                </div>
                <?php foreach ($lista as $u): ?>
                    <div class="user-item <?= (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['id']) ? 'selected' : '' ?>">
                        <a href="?edit_id=<?= $u['id'] ?>" class="user-link">
                            <div><?= htmlspecialchars($u['nome']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                        </a>
                        <?php if ($utente_loggato_ruolo === 'developer' && $u['id'] !== $utente_loggato_id): ?>
                            <a href="?delete_id=<?= $u['id'] ?>" 
                               class="delete-btn"
                               onclick="return confirm('Sei sicuro di voler eliminare l\'utente <?= addslashes($u['nome']) ?>?');" 
                               title="Elimina utente">🗑️</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Form modifica -->
    <div class="edit-form">
        <h3>Dati utente</h3>
        <?php if ($utente_selezionato): ?>
            <form method="post">
                <input type="hidden" name="id" value="<?= $utente_selezionato['id'] ?>">
                
                <div class="form-group">
                    <label class="form-label">👤 Nome:</label>
                    <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($utente_selezionato['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📧 Email:</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($utente_selezionato['email']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">🎭 Ruolo:</label>
                    <select name="ruolo" class="form-select" required>
                        <?php foreach (["guest", "impiegato", "admin", "developer"] as $ruolo): ?>
                            <option value="<?= $ruolo ?>" <?= $utente_selezionato['ruolo'] === $ruolo ? 'selected' : '' ?>>
                                <?= ucfirst($ruolo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📱 Telegram Chat ID:</label>
                    <input type="text" name="telegram_chat_id" class="form-input" value="<?= htmlspecialchars($utente_selezionato['telegram_chat_id']) ?>" placeholder="Opzionale">
                </div>
                
                <div class="form-group">
                    <label class="form-label">🔒 Nuova password:</label>
                    <input type="password" name="password" class="form-input" placeholder="Lascia vuoto per non cambiare">
                </div>
                
                <button type="submit" class="save-btn">💾 Salva modifiche</button>
            </form>
        <?php else: ?>
            <div class="no-user-selected">
                <div style="font-size: 3rem; margin-bottom: 1rem;">👆</div>
                <div>Seleziona un utente dalla lista per modificarne i dati</div>
            </div>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>