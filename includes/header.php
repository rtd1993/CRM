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
            background: #f8f9fa;
        }
        header.crm-header {
            background: linear-gradient(135deg, #d35400 0%, #e67e22 50%, #d35400 100%);
            color: #fff;
            padding: 0;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-bottom: 3px solid #e67e22;
        }
        .crm-header .crm-title {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0;
            padding: 0;
            color: #ecf0f1;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .crm-logo {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }
        .crm-logo:hover {
            transform: scale(1.05);
        }
        .crm-header .crm-user {
            font-size: 0.95rem;
            color: #ffffff;
        }
        .crm-header .crm-user a {
            color: #ffffff !important;
            transition: color 0.2s ease;
            font-weight: bold;
        }
        .crm-header .crm-user a:hover {
            color: #ecf0f1 !important;
            text-decoration: none !important;
        }
        .crm-header nav {
            background: rgba(0,0,0,0.1);
            border-top: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
        }
        .crm-header .crm-menu {
            padding-left: 0;
            margin-bottom: 0;
            list-style: none;
            display: flex;
            flex-wrap: wrap;
        }
        .crm-header .crm-menu li {
            margin: 0 0.25rem;
        }
        .crm-header .crm-menu a {
            color: #ecf0f1;
            padding: 12px 16px 10px 16px;
            display: block;
            border-radius: 0 0 8px 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.9rem;
        }
        .crm-header .crm-menu a:hover, .crm-header .crm-menu a.active {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(230, 126, 34, 0.3);
        }
        .crm-header .crm-menu a:hover::before, .crm-header .crm-menu a.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: #e74c3c;
            border-radius: 50%;
        }
        @media (max-width: 600px) {
            .crm-header .crm-title {
                font-size: 1.4rem;
            }
            .crm-logo {
                width: 36px;
                height: 36px;
            }
            .crm-header .crm-menu {
                flex-direction: column;
            }
            .crm-header .crm-menu li {
                margin: 0.2rem 0;
            }
            .crm-header .crm-menu a {
                padding: 10px 14px;
                border-radius: 6px;
                margin: 2px 0;
            }
        }
    </style>
</head>
<body>
<header class="crm-header mb-4">
    <div class="container-fluid py-3 d-flex justify-content-between align-items-center">
        <div>
            <span class="crm-title">
                <img src="logo.png" alt="Logo PratiKo" class="crm-logo">
                PratiKo
            </span>
        </div>
        <div class="crm-user text-end">
            <span class="d-none d-md-inline">Utente:</span>
            <a href="/profilo.php" style="text-decoration: none; margin-right: 10px;">
                <strong><?= htmlspecialchars($nome_utente) ?></strong>
            </a>
            <span class="badge bg-primary text-white ms-2"><?= htmlspecialchars($ruolo_utente) ?></span>
            |
            <a href="logout.php" style="color: #ffffff; text-decoration: none; margin-left: 8px;"><b>Logout</b></a>
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
            <li><a href="/conto_termico.php"<?php if(in_array(basename($_SERVER['PHP_SELF']), ['conto_termico.php', 'crea_conto_termico.php', 'modifica_conto_termico.php'])) echo ' class="active"';?>>Conto Termico</a></li>
            <li><a href="/enea.php"<?php if(in_array(basename($_SERVER['PHP_SELF']), ['enea.php', 'crea_enea.php', 'modifica_enea.php'])) echo ' class="active"';?>>ENEA</a></li>
            <li><a href="/email_invio.php"<?php if(in_array(basename($_SERVER['PHP_SELF']), ['email_invio.php', 'gestione_email_template.php', 'email_cronologia.php'])) echo ' class="active"';?>>Email</a></li>
            <li><a href="/chat.php"<?php if(basename($_SERVER['PHP_SELF'])=='chat.php') echo ' class="active"';?>>Chat</a></li>
            <li><a href="/info.php"<?php if(basename($_SERVER['PHP_SELF'])=='info.php') echo ' class="active"';?>>Info</a></li>
            <li><a href="/gestione_utenti.php"<?php if(basename($_SERVER['PHP_SELF'])=='gestione_utenti.php') echo ' class="active"';?>>Utenti</a></li>
            <?php if ($ruolo_utente === 'admin' || $ruolo_utente === 'developer'): ?>
                <li><a href="/telegram_config.php"<?php if(basename($_SERVER['PHP_SELF'])=='telegram_config.php') echo ' class="active"';?>>Telegram</a></li>
                
            <?php endif; ?>
            <?php if ($ruolo_utente === 'developer'): ?>
                <li><a href="/devtools.php"<?php if(basename($_SERVER['PHP_SELF'])=='devtools.php') echo ' class="active"';?>>DevTools</a></li>
                <li><a href="/admin_wireguard.php"<?php if(basename($_SERVER['PHP_SELF'])=='admin_wireguard.php') echo ' class="active"';?>>WireGuard</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div id="crm-chat-container">
    <?php include __DIR__ . '/chat_pratiche_widget.php'; ?>
    <?php include __DIR__ . '/chat_widget.php'; ?>
</div>
<main style="padding: 20px;">