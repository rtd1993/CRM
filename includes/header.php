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
    <!-- Bootstrap CDN for modern style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if (!in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])): ?>
    <!-- Socket.IO Library via CDN with cache busting -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js?v=<?= time() ?>"></script>
    <?php endif; ?>
    <style>
        body {
            background: #f7f9fb;
        }
        header.crm-header {
            background: linear-gradient(90deg, #0056b3 0%, #003366 100%);
            color: #fff;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.09);
        }
        .crm-header .crm-title {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 2rem;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 0;
            padding: 0 0 2px 0;
            color: #ffcc00;
            text-shadow: 0 1px 0 #003366;
        }
        .crm-header .crm-user {
            font-size: 0.97rem;
        }
        .crm-header nav {
            background: rgba(0,0,0,0.05);
            border-top: 1px solid #003366;
        }
        .crm-header .crm-menu {
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
        }
        .crm-header .crm-menu li {
            margin: 0 0.5rem;
        }
        .crm-header .crm-menu a {
            color: #fff;
            padding: 10px 15px 8px 15px;
            display: block;
            border-radius: 0 0 6px 6px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .crm-header .crm-menu a:hover, .crm-header .crm-menu a.active {
            background: #ffcc00;
            color: #003366;
            text-shadow: none;
        }
        @media (max-width: 600px) {
            .crm-header .crm-title {
                font-size: 1.3rem;
            }
            .crm-header .crm-menu {
                flex-direction: column;
            }
            .crm-header .crm-menu li {
                margin: 0.25rem 0;
            }
        }
    </style>
</head>
<body>
<header class="crm-header mb-4">
    <div class="container-fluid py-2 d-flex justify-content-between align-items-center">
        <div>
            <span class="crm-title">
                <svg width="34" height="34" style="vertical-align: middle;margin-right:5px;" viewBox="0 0 100 100"><ellipse rx="45" ry="45" cx="50" cy="50" fill="#ffcc00"/><text x="50%" y="55%" fill="#003366" font-size="38" font-family="Segoe UI, Arial, sans-serif" font-weight="bold" text-anchor="middle" alignment-baseline="middle" dy=".3em">CRM</text></svg>
                ASContabilmente
            </span>
        </div>
        <div class="crm-user text-end">
            <span class="d-none d-md-inline">Utente:</span>
            <a href="/profilo.php" style="color: #ffcc00; text-decoration: none; margin-right: 10px;">
                <strong><?= htmlspecialchars($nome_utente) ?></strong>
            </a>
            <span class="badge bg-light text-dark ms-2"><?= htmlspecialchars($ruolo_utente) ?></span>
            |
            <a href="logout.php" style="color: #ffcc00; text-decoration: none;"><b>Logout</b></a>
        </div>
    </div>
    <nav>
        <ul class="crm-menu container-fluid">
            <li><a href="/dashboard.php"<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' class="active"';?>>Dashboard</a></li>
            <li><a href="/clienti.php"<?php if(basename($_SERVER['PHP_SELF'])=='clienti.php') echo ' class="active"';?>>Clienti</a></li>
            <li><a href="/drive.php"<?php if(basename($_SERVER['PHP_SELF'])=='drive.php') echo ' class="active"';?>>Drive</a></li>
            <li><a href="/calendario.php"<?php if(basename($_SERVER['PHP_SELF'])=='calendario.php') echo ' class="active"';?>>Calendario</a></li>
            <li><a href="/task.php"<?php if(basename($_SERVER['PHP_SELF'])=='task.php') echo ' class="active"';?>>Task</a></li>
            <li><a href="/task_clienti.php"<?php if(basename($_SERVER['PHP_SELF'])=='task_clienti.php') echo ' class="active"';?>>Task Clienti</a></li>
            <li><a href="/chat.php"<?php if(basename($_SERVER['PHP_SELF'])=='chat.php') echo ' class="active"';?>>Chat</a></li>
            <li><a href="/info.php"<?php if(basename($_SERVER['PHP_SELF'])=='info.php') echo ' class="active"';?>>Info</a></li>
            <?php if ($ruolo_utente === 'admin' || $ruolo_utente === 'developer'): ?>
                <li><a href="/gestione_utenti.php"<?php if(basename($_SERVER['PHP_SELF'])=='gestione_utenti.php') echo ' class="active"';?>>Utenti</a></li>
                <li><a href="/telegram_config.php"<?php if(basename($_SERVER['PHP_SELF'])=='telegram_config.php') echo ' class="active"';?>>Telegram</a></li>
                <li><a href="/admin_wireguard.php"<?php if(basename($_SERVER['PHP_SELF'])=='admin_wireguard.php') echo ' class="active"';?>>WireGuard</a></li>
            <?php endif; ?>
            <?php if ($ruolo_utente === 'developer'): ?>
                <li><a href="/devtools.php"<?php if(basename($_SERVER['PHP_SELF'])=='devtools.php') echo ' class="active"';?>>DevTools</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div id="crm-chat-container">
    <?php include __DIR__ . '/chat_pratiche_widget.php'; ?>
    <?php include __DIR__ . '/chat_widget.php'; ?>
</div>
<main style="padding: 20px;">