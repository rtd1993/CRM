<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$user_id = $_SESSION['user_id'];
$success_message = null;
$error_message = null;

// Gestione aggiornamento profilo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validazione
    if (!empty($password) && $password !== $confirm_password) {
        $error_message = "Le password non coincidono";
    } else {
        try {
            // Aggiorna Chat ID Telegram
            $stmt = $pdo->prepare("UPDATE utenti SET telegram_chat_id = ? WHERE id = ?");
            $stmt->execute([$telegram_chat_id, $user_id]);
            
            // Aggiorna password se fornita
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
            }
            
            $success_message = "Profilo aggiornato con successo!";
            
            // Test notifica Telegram se Chat ID è stato configurato
            if (!empty($telegram_chat_id)) {
                require_once __DIR__ . '/includes/telegram.php';
                if (testConnessioneTelegram($telegram_chat_id)) {
                    $success_message .= " Test notifica Telegram inviato!";
                }
            }
            
        } catch (Exception $e) {
            $error_message = "Errore nell'aggiornamento: " . $e->getMessage();
        }
    }
}

// Recupera dati utente
$stmt = $pdo->prepare("SELECT nome, email, ruolo, telegram_chat_id FROM utenti WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.profile-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.profile-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.profile-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
}

.profile-info {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.profile-form {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
}

.section-title {
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

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.info-label {
    font-weight: 600;
    color: #495057;
}

.info-value {
    color: #6c757d;
    font-family: monospace;
}

.role-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    color: white;
}

.role-developer { background: #dc3545; }
.role-admin { background: #0056b3; }
.role-impiegato { background: #28a745; }
.role-guest { background: #6c757d; }

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

.telegram-help {
    background: #e3f2fd;
    border: 1px solid #90caf9;
    border-radius: 10px;
    padding: 1rem;
    margin-top: 1rem;
}

.telegram-help h4 {
    color: #1565c0;
    margin: 0 0 0.5rem 0;
}

.telegram-help p {
    color: #1976d2;
    margin: 0.5rem 0;
    font-size: 0.9rem;
}

.telegram-help a {
    color: #1565c0;
    text-decoration: none;
    font-weight: 600;
}

.telegram-help a:hover {
    text-decoration: underline;
}

.success-message {
    background: #d4edda;
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
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    border: 1px solid #f5c6cb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.error-message::before {
    content: '❌';
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-header h2 {
        font-size: 2rem;
    }
}
</style>

<div class="profile-header">
    <h2>👤 Il Mio Profilo</h2>
    <p>Gestisci le tue informazioni personali e le impostazioni di notifica</p>
</div>

<?php if ($success_message): ?>
    <div class="success-message">
        <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="error-message">
        <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<div class="profile-container">
    <!-- Informazioni Profilo -->
    <div class="profile-info">
        <h3 class="section-title">
            📋 Informazioni Account
        </h3>
        
        <div class="info-item">
            <div class="info-label">👤 Nome</div>
            <div class="info-value"><?= htmlspecialchars($user['nome']) ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">📧 Email</div>
            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">🎭 Ruolo</div>
            <div>
                <span class="role-badge role-<?= $user['ruolo'] ?>">
                    <?= ucfirst($user['ruolo']) ?>
                </span>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">🔔 Notifiche Telegram</div>
            <div class="info-value">
                <?php if ($user['telegram_chat_id']): ?>
                    <span style="color: #28a745;">✅ Attive</span>
                <?php else: ?>
                    <span style="color: #dc3545;">❌ Non configurate</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form Modifica -->
    <div class="profile-form">
        <h3 class="section-title">
            ⚙️ Impostazioni
        </h3>
        
        <form method="post">
            <div class="form-group">
                <label class="form-label">📱 Chat ID Telegram</label>
                <input type="text" 
                       name="telegram_chat_id" 
                       class="form-input" 
                       value="<?= htmlspecialchars($user['telegram_chat_id'] ?? '') ?>" 
                       placeholder="Es: 123456789">
                <div class="telegram-help">
                    <h4>🤖 Come ottenere il Chat ID:</h4>
                    <p>1. Vai su <a href="telegram_get_id.php" target="_blank">Trova Chat ID</a></p>
                    <p>2. Segui le istruzioni per configurare il bot</p>
                    <p>3. Copia il tuo Chat ID e incollalo qui</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">🔐 Nuova Password</label>
                <input type="password" 
                       name="password" 
                       class="form-input" 
                       placeholder="Lascia vuoto per non modificare">
            </div>
            
            <div class="form-group">
                <label class="form-label">🔐 Conferma Password</label>
                <input type="password" 
                       name="confirm_password" 
                       class="form-input" 
                       placeholder="Ripeti la nuova password">
            </div>
            
            <button type="submit" class="save-btn">
                💾 Salva Modifiche
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
