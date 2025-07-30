<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Solo admin e developer possono accedere
if (!in_array($_SESSION['user_role'], ['admin', 'developer'])) {
    die("Accesso non autorizzato.");
}

// Gestione del test di notifica
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_notification'])) {
    $user_id = intval($_POST['user_id']);
    $stmt = $pdo->prepare("SELECT nome, telegram_chat_id FROM utenti WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['telegram_chat_id']) {
        require_once __DIR__ . '/includes/telegram.php';
        $test_message = "
🔔 <b>Test Notifica CRM</b>

👋 Ciao {$user['nome']}!

✅ Il tuo account è collegato correttamente a Telegram.
📱 Riceverai notifiche quando non sei online nel CRM.

📅 " . date('d/m/Y H:i');
        
        mandaNotificaTelegram($test_message, $user['telegram_chat_id']);
        $success_message = "Test notifica inviata a " . $user['nome'];
    } else {
        $error_message = "Chat ID Telegram non configurato per questo utente.";
    }
}

// Ottieni statistiche Telegram
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_users,
    COUNT(telegram_chat_id) as users_with_telegram,
    COUNT(CASE WHEN telegram_chat_id IS NOT NULL AND telegram_chat_id != '' THEN 1 END) as active_telegram
FROM utenti");
$stats = $stmt->fetch();

$stmt = $pdo->query("SELECT id, nome, email, ruolo, telegram_chat_id FROM utenti ORDER BY ruolo ASC, nome ASC");
$users = $stmt->fetchAll();
?>

<style>
.telegram-header {
    background: linear-gradient(135deg, #0088cc 0%, #229ED9 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.telegram-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.7; }
    50% { transform: scale(1.1); opacity: 1; }
}

.telegram-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.telegram-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-left: 4px solid #0088cc;
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #0088cc;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.telegram-setup {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.setup-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.setup-step {
    padding: 1.5rem;
    border: 2px dashed #0088cc;
    border-radius: 12px;
    text-align: center;
    position: relative;
}

.setup-step::before {
    content: attr(data-step);
    position: absolute;
    top: -15px;
    left: 20px;
    background: #0088cc;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9rem;
}

.users-table {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8f9fa;
    padding: 1rem;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-connected {
    background: #d4edda;
    color: #155724;
}

.status-not-connected {
    background: #f8d7da;
    color: #721c24;
}

.test-btn {
    background: #0088cc;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.test-btn:hover {
    background: #0077bb;
    transform: translateY(-1px);
}

.test-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

.get-chatid-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.get-chatid-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #c3e6cb;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}

.code-block {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #dee2e6;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    margin: 1rem 0;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .setup-steps {
        grid-template-columns: 1fr;
    }
    
    .telegram-header h2 {
        font-size: 2rem;
    }
}
</style>


<?php if (isset($success_message)): ?>
    <div class="success-message">
        ✅ <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="error-message">
        ❌ <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_users'] ?></div>
        <div class="stat-label">Utenti Totali</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['active_telegram'] ?></div>
        <div class="stat-label">Telegram Attivi</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= round(($stats['active_telegram'] / $stats['total_users']) * 100) ?>%</div>
        <div class="stat-label">Copertura</div>
    </div>
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <a href="telegram_get_id.php" class="get-chatid-btn">
        🔍 Trova Chat ID Telegram
    </a>
</div>

<div class="users-table">
    <h3>👥 Stato Telegram Utenti</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Utente</th>
                <th>Email</th>
                <th>Ruolo</th>
                <th>Chat ID</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($user['nome']) ?></strong></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="status-badge" style="background: 
                            <?= $user['ruolo'] === 'developer' ? '#dc3545' : 
                               ($user['ruolo'] === 'admin' ? '#0056b3' : 
                               ($user['ruolo'] === 'impiegato' ? '#28a745' : '#6c757d')) ?>; 
                            color: white;">
                            <?= ucfirst($user['ruolo']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['telegram_chat_id']): ?>
                            <code><?= htmlspecialchars($user['telegram_chat_id']) ?></code>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Non configurato</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['telegram_chat_id']): ?>
                            <span class="status-badge status-connected">✅ Connesso</span>
                        <?php else: ?>
                            <span class="status-badge status-not-connected">❌ Non connesso</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="test_notification" class="test-btn" 
                                    <?= !$user['telegram_chat_id'] ? 'disabled' : '' ?>>
                                📤 Test
                            </button>
                        </form>
                        <a href="gestione_utenti.php?edit_id=<?= $user['id'] ?>" class="test-btn" style="text-decoration: none; margin-left: 0.5rem;">
                            ✏️ Modifica
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// Auto-refresh ogni 30 secondi per aggiornare le statistiche
setTimeout(() => {
    location.reload();
}, 30000);
</script>

</main>
</body>
</html>
