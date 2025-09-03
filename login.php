<?php
// File: login.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/tunnel_bypass.php';

session_start();

$error = '';
$debug_info = '';

// Debug POST data
if (!empty($_POST)) {
    $debug_info .= "<strong>Dati POST ricevuti:</strong><br>";
    foreach ($_POST as $key => $value) {
        $debug_info .= "- " . htmlspecialchars($key) . ": " . htmlspecialchars($value) . "<br>";
    }
    $debug_info .= "<br>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $debug_info .= "POST ricevuto. Email: " . htmlspecialchars($email) . "<br>";
    
    // Debug
    error_log("LOGIN ATTEMPT: Email = $email");
    
    $stmt = $pdo->prepare("SELECT id, nome, password, ruolo FROM utenti WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $debug_info .= "Utente trovato: " . htmlspecialchars($user['nome']) . " (ID: " . $user['id'] . ")<br>";
        error_log("USER FOUND: ID = " . $user['id'] . ", Nome = " . $user['nome']);
        
        $password_check = password_verify($password, $user['password']);
        $debug_info .= "Verifica password: " . ($password_check ? "SUCCESS" : "FAILED") . "<br>";
        error_log("PASSWORD CHECK: " . ($password_check ? 'SUCCESS' : 'FAILED'));
        
        if ($password_check) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['role'] = $user['ruolo'];
            
            $debug_info .= "Sessione creata. Reindirizzamento...<br>";
            error_log("LOGIN SUCCESS: User " . $user['id'] . " logged in");
            
            // Aggiungi un small delay per vedere il debug
            echo "<div style='position:fixed;top:10px;left:10px;background:green;color:white;padding:10px;z-index:9999;'>LOGIN SUCCESS! Reindirizzamento in 2 secondi...</div>";
            echo "<script>setTimeout(() => window.location.href = 'dashboard.php', 2000);</script>";
            exit();
        }
    } else {
        $debug_info .= "Utente NON trovato per email: " . htmlspecialchars($email) . "<br>";
        error_log("USER NOT FOUND for email: $email");
    }
    
    if (!$user || !password_verify($password, $user['password'] ?? '')) {
        $error = 'Credenziali non valide';
        $debug_info .= "LOGIN FALLITO<br>";
        error_log("LOGIN FAILED: Invalid credentials");
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accesso - <?= SITE_NAME ?></title>
    <?= getTunnelBypassMeta() ?>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Sfondo animato */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.08)"/><circle cx="50" cy="30" r="1.5" fill="rgba(52,152,219,0.1)"/><circle cx="80" cy="60" r="1" fill="rgba(255,255,255,0.06)"/><circle cx="20" cy="80" r="1.2" fill="rgba(52,152,219,0.08)"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateX(0) translateY(0); }
            33% { transform: translateX(10px) translateY(-10px); }
            66% { transform: translateX(-5px) translateY(10px); }
        }
        
        .login-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            color: #ecf0f1;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            border-bottom: 3px solid #3498db;
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(52,152,219,0.15)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="70" r="1.8" fill="rgba(52,152,219,0.12)"/><circle cx="70" cy="80" r="1" fill="rgba(255,255,255,0.08)"/></svg>');
            animation: float 15s ease-in-out infinite reverse;
        }
        
        .logo {
            width: 120px;
            height: 120px;
            margin-bottom: 1.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
            position: relative;
            z-index: 2;
            color: #bdc3c7;
        }
        
        .features {
            display: flex;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .feature {
            text-align: center;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .feature:hover {
            opacity: 1;
        }
        
        .feature i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
            color: #3498db;
        }
        
        .feature span {
            font-size: 0.9rem;
            color: #ecf0f1;
        }
        
        .login-section {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            z-index: 2;
            transition: color 0.3s ease;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input:focus + i,
        .form-group:focus-within i {
            color: #3498db;
        }
        
        .form-input::placeholder {
            color: #95a5a6;
        }
        
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fadbd8;
            color: #e74c3c;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #f1948a;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .footer-text {
            text-align: center;
            margin-top: 2rem;
            color: #999;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 20px;
            }
            
            .welcome-section {
                padding: 2rem;
                min-height: auto;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .features {
                gap: 1rem;
            }
            
            .login-section {
                padding: 2rem;
            }
        }
        
        /* Loading animation */
        .login-btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
        .login-btn.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <?= getTunnelBypassScript() ?>
</head>
<body>
<?php setupTunnelBypass(); ?>
    <div class="login-container">
        <div class="welcome-section">
            <img src="logo.png" alt="Logo <?= SITE_NAME ?>" class="logo">
            <h1 class="welcome-title">Benvenuto</h1>
            <p class="welcome-subtitle">
                Accedi al tuo sistema di gestione CRM professionale. 
                Organizza clienti, gestisci attività e monitora i tuoi processi aziendali in modo semplice ed efficace.
            </p>
            <div class="features">
                <div class="feature">
                    <i class="fas fa-users"></i>
                    <span>Gestione Clienti</span>
                </div>
                <div class="feature">
                    <i class="fas fa-tasks"></i>
                    <span>Task & Progetti</span>
                </div>
                <div class="feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </div>
            </div>
        </div>
        
        <div class="login-section">
            <div class="login-header">
                <h2>Accedi</h2>
                <p>Inserisci le tue credenziali per continuare</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($debug_info): ?>
                <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 15px 0; font-size: 12px; color: #333;">
                    <strong>🔍 DEBUG INFO:</strong><br>
                    <?= $debug_info ?>
                    <br>
                    <strong>Sessione attuale:</strong><br>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        - User ID: <?= $_SESSION['user_id'] ?><br>
                        - User Name: <?= $_SESSION['user_name'] ?? 'N/A' ?><br>
                        - User Role: <?= $_SESSION['role'] ?? 'N/A' ?><br>
                    <?php else: ?>
                        - Nessuna sessione attiva<br>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" id="loginForm">
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-input" placeholder="Email" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-input" placeholder="Password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Accedi
                </button>
            </form>
            
            <div class="footer-text">
                <i class="fas fa-shield-alt"></i>
                Connessione sicura e protetta
            </div>
        </div>
    </div>
    
    <script>
        // Aggiungi animazione al form di login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accesso in corso...';
        });
        
        // Focus automatico sul primo campo
        window.addEventListener('load', function() {
            const emailField = document.querySelector('input[name="email"]');
            if (emailField) {
                emailField.focus();
            }
        });
        
        // Animazione degli input
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#667eea';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('i').style.color = '#999';
            });
        });
    </script>
</body>
</html>
