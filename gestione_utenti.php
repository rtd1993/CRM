<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$utente_loggato_id = $_SESSION['user_id'];
$utente_loggato_ruolo = $_SESSION['user_role'];

// Ora tutti gli utenti possono accedere, ma con permessi diversi
$is_admin_or_dev = in_array($utente_loggato_ruolo, ['admin', 'developer']);

// Reset password per admin/developer
if (isset($_POST['reset_password']) && $is_admin_or_dev) {
    $target_user_id = intval($_POST['target_user_id']);
    if ($target_user_id !== $utente_loggato_id) {
        $default_password = "Password01!";
        $hash = password_hash($default_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
        $success = $stmt->execute([$hash, $target_user_id]);
        
        if ($success) {
            // Log dell'operazione
            error_log("Password reset per utente ID $target_user_id da " . $_SESSION['username']);
            header("Location: gestione_utenti.php?edit_id=$target_user_id&success=2");
            exit;
        }
    }
}

// Eliminazione utente (solo admin e developer)
if (isset($_GET['delete_id']) && $is_admin_or_dev) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id !== $utente_loggato_id) { // prevenzione autodistruzione
        try {
            // Verifica se l'utente esiste prima di eliminarlo
            $stmt = $pdo->prepare("SELECT nome FROM utenti WHERE id = ?");
            $stmt->execute([$delete_id]);
            $user_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_to_delete) {
                // Elimina l'utente
                $stmt = $pdo->prepare("DELETE FROM utenti WHERE id = ?");
                $result = $stmt->execute([$delete_id]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Eliminazione riuscita
                    header("Location: gestione_utenti.php");
                } else {
                    // Eliminazione fallita
                    header("Location: gestione_utenti.php");
                }
            } else {
                // Utente non trovato
                header("Location: gestione_utenti.php");
            }
        } catch (Exception $e) {
            // Errore database
            error_log("Errore eliminazione utente ID $delete_id: " . $e->getMessage());
            header("Location: gestione_utenti.php");
        }
        exit;
    } else {
        // Tentativo di auto-eliminazione
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
    
    // Gli utenti base possono modificare solo i propri dati
    if (!$is_admin_or_dev && $id !== $utente_loggato_id) {
        die("Non autorizzato a modificare altri utenti.");
    }
    
    // Gli utenti base non possono cambiare il proprio ruolo
    // Gli admin non possono cambiare il proprio ruolo (solo i developer possono)
    if (!$is_admin_or_dev || ($utente_loggato_ruolo === 'admin' && $id === $utente_loggato_id)) {
        // Recupera il ruolo attuale dall'utente
        $stmt = $pdo->prepare("SELECT ruolo FROM utenti WHERE id = ?");
        $stmt->execute([$id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $ruolo = $user_data['ruolo']; // Mantieni il ruolo esistente
    }

    $stmt = $pdo->prepare("UPDATE utenti SET nome = ?, email = ?, ruolo = ?, telegram_chat_id = ? WHERE id = ?");
    $ok = $stmt->execute([$nome, $email, $ruolo, $telegram_chat_id, $id]);

    // Solo admin/developer possono cambiare password di altri utenti
    if (!empty($password)) {
        if ($id === $utente_loggato_id) {
            // Cambio della propria password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
        } elseif ($is_admin_or_dev) {
            // Admin/Developer può cambiare password di altri utenti
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
        }
    }

    if ($ok) {
        $redirect_id = $is_admin_or_dev ? $id : $utente_loggato_id;
        header("Location: gestione_utenti.php?edit_id=$redirect_id&success=1");
        exit;
    } else {
        echo "<p style='color:red;'>Errore nel salvataggio!</p>";
    }
}

$stmt = $pdo->query("SELECT id, nome, email, ruolo, telegram_chat_id FROM utenti ORDER BY ruolo ASC, nome ASC");
$utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ottieni dati dell'utente loggato
$stmt = $pdo->prepare("SELECT id, nome, email, ruolo, telegram_chat_id FROM utenti WHERE id = ?");
$stmt->execute([$utente_loggato_id]);
$utente_loggato = $stmt->fetch(PDO::FETCH_ASSOC);

$utenti_per_ruolo = [];
if ($is_admin_or_dev) {
    foreach ($utenti as $u) {
        $utenti_per_ruolo[$u['ruolo']][] = $u;
    }
}

$utente_selezionato = null;
if (isset($_GET['edit_id']) && $is_admin_or_dev) {
    $id_sel = intval($_GET['edit_id']);
    
    foreach ($utenti as $u) {
        if ($u['id'] === $id_sel) {
            $utente_selezionato = $u;
            break;
        }
    }
} else {
    // Per tutti gli utenti (compresi quelli base), seleziona automaticamente se stesso
    $utente_selezionato = $utente_loggato;
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

.reset-password-btn {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
    color: #212529;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.reset-password-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
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

.role-employee {
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

.error-message {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border: 1px solid #f5c6cb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    animation: shake 0.5s ease-in-out;
}

.error-message::before {
    content: '❌';
    font-size: 1.2rem;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
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

<?php if (isset($_GET['success'])): ?>
    <div class="success-message">
        <?php if ($_GET['success'] == 1): ?>
            Modifiche salvate con successo!
        <?php elseif ($_GET['success'] == 2): ?>
            Password resettata con successo! Nuova password: <strong>Password01!</strong>
        <?php elseif ($_GET['success'] == 'deleted'): ?>
            <?php $nome_eliminato = isset($_GET['nome']) ? urldecode($_GET['nome']) : 'Utente'; ?>
            ✅ Utente "<?= htmlspecialchars($nome_eliminato) ?>" eliminato con successo!
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="error-message">
        <?php if ($_GET['error'] == 'delete_failed'): ?>
            ❌ Errore durante l'eliminazione dell'utente. Riprova.
        <?php elseif ($_GET['error'] == 'user_not_found'): ?>
            ❌ Utente non trovato. Potrebbe essere già stato eliminato.
        <?php elseif ($_GET['error'] == 'database_error'): ?>
            ❌ Errore del database durante l'eliminazione. Contatta l'amministratore.
        <?php elseif ($_GET['error'] == 'self_delete_forbidden'): ?>
            ❌ Non puoi eliminare il tuo stesso account.
        <?php else: ?>
            ❌ Si è verificato un errore sconosciuto.
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="users-header">
    <h2><?= $is_admin_or_dev ? '👥 Gestione Utenti' : '👤 Il Mio Profilo' ?></h2>
    <p><?= $is_admin_or_dev ? 'Gestisci gli account utente del sistema' : 'Modifica i tuoi dati personali e cambia password' ?></p>
</div>

<?php if ($is_admin_or_dev): ?>
<a href="create_user.php" class="create-user-btn">
    ➕ Crea nuovo utente
</a>
<?php endif; ?>

<div class="users-container">
    <?php if ($is_admin_or_dev): ?>
    <!-- Sezione profilo personale sempre visibile -->
    <div class="users-list">
        <h3>Il mio profilo</h3>
        <div class="role-section">
            <div class="role-title role-<?= $utente_loggato['ruolo'] ?>">
                <?php 
                $icons = [
                    'developer' => '👨‍💻',
                    'admin' => '👨‍💼', 
                    'employee' => '👤',
                    'guest' => '👥'
                ];
                echo $icons[$utente_loggato['ruolo']] ?? '👤';
                ?>
                I miei dati (<?= $utente_loggato['ruolo'] === 'employee' ? 'Impiegato' : ucfirst($utente_loggato['ruolo']) ?>)
            </div>
            <div class="user-item <?= (!isset($_GET['edit_id']) || $_GET['edit_id'] == $utente_loggato['id']) ? 'selected' : '' ?>">
                <a href="?edit_id=<?= $utente_loggato['id'] ?>" class="user-link">
                    <div><?= htmlspecialchars($utente_loggato['nome']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($utente_loggato['email']) ?></div>
                </a>
            </div>
        </div>
        
        <h3>Altri utenti</h3>
        <?php foreach ($utenti_per_ruolo as $ruolo => $lista): ?>
            <div class="role-section">
                <div class="role-title role-<?= $ruolo ?>">
                    <?php 
                    echo $icons[$ruolo] ?? '👤';
                    ?>
                    <?= $ruolo === 'employee' ? 'Impiegati' : ucfirst($ruolo) ?> (<?= count($lista) ?>)
                </div>
                <?php foreach ($lista as $u): ?>
                    <?php if ($u['id'] !== $utente_loggato_id): // Non mostrare se stesso nella lista altri utenti ?>
                    <div class="user-item <?= (isset($_GET['edit_id']) && $_GET['edit_id'] == $u['id']) ? 'selected' : '' ?>">
                        <a href="?edit_id=<?= $u['id'] ?>" class="user-link">
                            <div><?= htmlspecialchars($u['nome']) ?></div>
                            <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                        </a>
                        <?php if ($is_admin_or_dev && $u['id'] !== $utente_loggato_id): ?>
                            <a href="?delete_id=<?= $u['id'] ?>" 
                               class="delete-btn"
                               onclick="return confirm('Sei sicuro di voler eliminare l\'utente <?= addslashes($u['nome']) ?>?');" 
                               title="Elimina utente">🗑️</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Form modifica -->
    <div class="edit-form">
        <h3><?= $is_admin_or_dev ? 'Dati utente' : 'I miei dati' ?></h3>
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
                    <?php if ($is_admin_or_dev && ($utente_selezionato['id'] !== $utente_loggato_id || $utente_loggato_ruolo === 'developer')): ?>
                    <select name="ruolo" class="form-select" required>
                        <?php foreach (["guest", "employee", "admin", "developer"] as $ruolo): ?>
                            <option value="<?= $ruolo ?>" <?= $utente_selezionato['ruolo'] === $ruolo ? 'selected' : '' ?>>
                                <?= $ruolo === 'employee' ? 'Impiegato' : ucfirst($ruolo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" class="form-input" value="<?= $utente_selezionato['ruolo'] === 'employee' ? 'Impiegato' : ucfirst($utente_selezionato['ruolo']) ?>" readonly style="background-color: #f8f9fa;">
                    <input type="hidden" name="ruolo" value="<?= $utente_selezionato['ruolo'] ?>">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📱 Telegram Chat ID:</label>
                    <input type="text" name="telegram_chat_id" class="form-input" value="<?= htmlspecialchars($utente_selezionato['telegram_chat_id']) ?>" placeholder="Opzionale">
                </div>
                
                <?php if ($is_admin_or_dev && $utente_selezionato['id'] !== $utente_loggato_id): ?>
                <!-- Reset password e cambio password per admin/developer su altri utenti -->
                <div class="form-group">
                    <label class="form-label">🔒 Password:</label>
                    <input type="password" name="password" class="form-input" placeholder="Inserisci nuova password per cambiarla">
                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: #e3f2fd; border-radius: 6px; font-size: 0.9rem; color: #1976d2;">
                        💡 <strong>Alternativa rapida:</strong> Usa il pulsante "Reset Password" per impostare automaticamente "Password01!"
                    </div>
                    <form method="post" style="display: inline; margin-top: 1rem;" onsubmit="return confirm('Vuoi resettare la password per questo utente?\n\nLa nuova password sarà: Password01!')">
                        <input type="hidden" name="target_user_id" value="<?= $utente_selezionato['id'] ?>">
                        <button type="submit" name="reset_password" class="reset-password-btn">
                            🔄 Reset Password a "Password01!"
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <!-- Campo password per il proprio account -->
                <div class="form-group">
                    <label class="form-label">🔒 Cambia password:</label>
                    <input type="password" name="password" class="form-input" placeholder="Inserisci nuova password per cambiarla (lascia vuoto per non cambiare)">
                </div>
                <?php endif; ?>
                
                <button type="submit" class="save-btn">💾 Salva modifiche</button>
            </form>
        <?php else: ?>
            <div class="no-user-selected">
                <div style="font-size: 3rem; margin-bottom: 1rem;">👆</div>
                <div><?= $is_admin_or_dev ? 'Seleziona un utente dalla lista per modificarne i dati' : 'Errore nel caricamento dei tuoi dati' ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_admin_or_dev): ?>
<style>
.users-container {
    display: block !important;
}

.edit-form {
    margin: 0 auto;
    max-width: 600px;
}
</style>
<?php endif; ?>

</main>
</body>
</html>