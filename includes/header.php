<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

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
    <!-- Chat Footer System CSS -->
    <link rel="stylesheet" href="/assets/css/chat-footer.css">
    <!-- Bootstrap CDN for modern style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Socket.IO Client -->
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
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
            position: relative;
            z-index: 1050;
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
        
        /* Dropdown Styles */
        .crm-header .crm-menu .dropdown {
            position: relative;
            z-index: 1000;
        }
        .crm-header .crm-menu .nav-link,
        .crm-header .crm-menu .dropdown-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #fff !important;
            text-decoration: none;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }
        .crm-header .crm-menu .nav-link:hover,
        .crm-header .crm-menu .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }
        .crm-header .crm-menu .nav-link.active,
        .crm-header .crm-menu .dropdown-toggle.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }
        .crm-header .crm-menu .dropdown-menu {
            /* Stili personalizzati che non interferiscono con Bootstrap */
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%) !important;
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.25) !important;
            min-width: 180px !important;
            z-index: 9999 !important;
        }
        .crm-header .crm-menu .dropdown-item {
            color: #fff !important;
            padding: 8px 20px;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }
        .crm-header .crm-menu .dropdown-item:hover {
            background-color: rgba(255,255,255,0.2) !important;
            color: #fff !important;
            transform: translateX(5px);
        }
        .crm-header .crm-menu .dropdown-divider {
            border-color: rgba(255,255,255,0.2);
            margin: 5px 0;
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
        <audio id="chatNotificationAudio" src="/assets/sounds/notification.mp3" preload="auto"></audio>

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
        <nav class="navbar navbar-expand-lg crm-nav">
        <div class="container-fluid">
        <ul class="navbar-nav crm-menu">
            <!-- Dashboard -->
            <li class="nav-item"><a href="/dashboard.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='dashboard.php') echo ' active';?>">Dashboard</a></li>
            
            <!-- Clienti Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle<?php if(in_array(basename($_SERVER['PHP_SELF']), ['clienti.php', 'richieste.php', 'crea_richiesta.php', 'modifica_richiesta.php', 'drive.php'])) echo ' active';?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Clienti 
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/clienti.php">Clienti</a></li>
                    <li><a class="dropdown-item" href="/richieste.php">Richieste</a></li>
                    <li><a class="dropdown-item" href="/drive.php">Drive</a></li>
                </ul>
            </li>
            
            <!-- Task & Calendario Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle<?php if(in_array(basename($_SERVER['PHP_SELF']), ['calendario.php', 'task.php', 'task_clienti.php'])) echo ' active';?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Task & Calendario 
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/calendario.php">Calendario</a></li>
                    <li><a class="dropdown-item" href="/task.php">Task</a></li>
                    <li><a class="dropdown-item" href="/task_clienti.php">Task Clienti</a></li>
                </ul>
            </li>
            
            <!-- Pratiche Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle<?php if(in_array(basename($_SERVER['PHP_SELF']), ['conto_termico.php', 'crea_conto_termico.php', 'modifica_conto_termico.php', 'enea.php', 'crea_enea.php', 'modifica_enea.php', 'procedure.php', 'crea_procedura.php', 'modifica_procedura.php', 'stampa_procedura.php'])) echo ' active';?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Pratiche 
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/conto_termico.php">Conto Termico</a></li>
                    <li><a class="dropdown-item" href="/enea.php">ENEA</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/procedure.php">Procedure</a></li>
                </ul>
            </li>
            
            <!-- Info & Email Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle<?php if(in_array(basename($_SERVER['PHP_SELF']), ['link_utili.php', 'contatti.php', 'email_invio.php', 'gestione_email_template.php', 'email_cronologia.php'])) echo ' active';?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Info & Email 
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/link_utili.php">Link Utili</a></li>
                    <li><a class="dropdown-item" href="/contatti.php">Contatti</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/email_invio.php">Email</a></li>
                </ul>
            </li>
            
            <!-- Amministrazione Dropdown -->
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle<?php if(in_array(basename($_SERVER['PHP_SELF']), ['gestione_utenti.php', 'telegram_config.php', 'devtools.php', 'admin_wireguard.php'])) echo ' active';?>" data-bs-toggle="dropdown" aria-expanded="false">
                    Amministrazione 
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/gestione_utenti.php">Utenti</a></li>
                    <?php if ($ruolo_utente === 'admin' || $ruolo_utente === 'developer'): ?>
                        <li><a class="dropdown-item" href="/telegram_config.php">Telegram</a></li>
                    <?php endif; ?>
                    <?php if ($ruolo_utente === 'developer'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/devtools.php">DevTools</a></li>
                        <li><a class="dropdown-item" href="/admin_wireguard.php">WireGuard</a></li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul>
        </div>
    </nav>
</header>

<!-- Bootstrap JavaScript for dropdown functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<main style="padding: 20px;">