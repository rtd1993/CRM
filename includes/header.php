<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$nome_utente = 'Sconosciuto';
$ruolo_utente = 'guest';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT nome, ruolo FROM utenti WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $nome_utente = $row['nome'];
        $ruolo_utente = $row['ruolo'];
        $_SESSION['user_name'] = $nome_utente;
        $_SESSION['user_role'] = $ruolo_utente;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header style="background-color: #004080; color: white; padding: 10px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 20px; font-weight: bold;">CRM ASContabilmente</div>
        <div style="font-size: 14px;">
            Utente: <?= htmlspecialchars($nome_utente) ?> |
            <a href="logout.php" style="color: #ffcc00; text-decoration: none;">Logout</a>
        </div>
    </div>
    <nav style="margin-top: 10px;">
        <a href="/dashboard.php" style="color: white; margin-right: 15px;">Dashboard</a>
        <a href="/clienti.php" style="color: white; margin-right: 15px;">Clienti</a>
        <a href="/drive.php" style="color: white; margin-right: 15px;">Drive</a>
        <a href="/calendario.php" style="color: white; margin-right: 15px;">Calendario</a>
        <a href="/task.php" style="color: white; margin-right: 15px;">Task</a>
        <a href="/chat.php" style="color: white; margin-right: 15px;">Chat</a>
        <a href="/info.php" style="color: white; margin-right: 15px;">Info</a>
        <?php if ($ruolo_utente === 'admin' || $ruolo_utente === 'developer'): ?>
            <a href="/gestione_utenti.php" style="color: white; margin-right: 15px;">Utenti</a>
        <?php endif; ?>
        <?php if ($ruolo_utente === 'developer'): ?>
            <a href="/devtools.php" style="color: white; margin-right: 15px;">DevTools</a>
        <?php endif; ?>
    </nav>
</header>
<?php include __DIR__ . '/chat_widget.php'; ?>

<main style="padding: 20px;">
