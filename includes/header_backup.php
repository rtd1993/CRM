<?php
if (session_status() == PHP_SESSION_NONE) session_start();
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
    <!-- Chat Footer System CSS -->
    <link rel="stylesheet" href="/assets/css/chat-footer.css">
    <!-- Bootstrap CDN for modern style -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .crm-header .crm-menu > li {
            position: relative;
        }
        .crm-header .crm-menu > li > a {
            display: block;
            padding: 15px 20px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .crm-header .crm-menu > li > a:hover,
        .crm-header .crm-menu > li > a.active {
            background: rgba(255,255,255,0.15);
            border-bottom-color: #ffffff;
            color: #ffffff;
            transform: translateY(-1px);
        }
        .crm-header .crm-menu > li > a i {
            margin-right: 8px;
            font-size: 1rem;
        }
        .crm-header .crm-menu .dropdown-menu {
            background: #34495e;
            border: none;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            margin-top: 0;
        }
        .crm-header .crm-menu .dropdown-menu a {
            color: #ecf0f1 !important;
            padding: 12px 20px;
            transition: all 0.2s ease;
        }
        .crm-header .crm-menu .dropdown-menu a:hover {
            background: #2c3e50 !important;
            color: #ffffff !important;
            transform: translateX(5px);
        }
        @media (max-width: 991px) {
            .crm-header .crm-menu {
                flex-direction: column;
            }
            .crm-header .crm-menu > li > a {
                padding: 12px 20px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
        }
    </style>
</head>
<body>
    <header class="crm-header">
        <div class="container-fluid">
            <div class="row align-items-center py-3">
                <div class="col-md-6">
                    <h1 class="crm-title">
                        <img src="/logo.png" alt="Logo" class="crm-logo">
                        <?= SITE_NAME ?>
                    </h1>
                </div>
                <div class="col-md-6 text-end">
                    <div class="crm-user">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            Ciao, <strong><?= htmlspecialchars($nome_utente) ?></strong> (<?= htmlspecialchars($ruolo_utente) ?>)
                            | <a href="/logout.php">Logout</a>
                        <?php else: ?>
                            <a href="/login.php">Accedi</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
        <nav>
            <div class="container-fluid">
                <ul class="crm-menu">
                    <li><a href="/dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                    <li><a href="/clienti.php"><i class="fas fa-users"></i>Clienti</a></li>
                    <li><a href="/task.php"><i class="fas fa-tasks"></i>Task</a></li>
                    <li><a href="/calendario.php"><i class="fas fa-calendar"></i>Calendario</a></li>
                    <li><a href="/drive.php"><i class="fas fa-cloud"></i>Drive</a></li>
                    <li><a href="/email.php"><i class="fas fa-envelope"></i>Email</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i>Utilità
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/conto_termico.php">Conto Termico</a></li>
                            <li><a href="/enea.php">ENEA</a></li>
                            <li><a href="/telegram_config.php">Telegram</a></li>
                            <li><a href="/gestione_utenti.php">Gestione Utenti</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>
        <?php endif; ?>
    </header>

    <main style="padding: 20px;">

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php 
    // Includi il footer chat widget se l'utente è loggato
    if (isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])): 
        include __DIR__ . '/chat-footer-widget.php';
    endif; 
    ?>

    <!-- Chat Footer JavaScript -->
    <?php if (isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])): ?>
    <script src="/assets/js/chat-footer.js?v=<?= time() ?>"></script>
    <?php endif; ?>
