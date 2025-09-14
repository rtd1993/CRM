<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Richieste - Dropdown</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">CRM Test</a>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        Clienti
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/clienti.php">Clienti</a></li>
                        <li><a class="dropdown-item" href="/richieste.php">Richieste</a></li>
                        <li><a class="dropdown-item" href="/drive.php">Drive</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        Pratiche
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/conto_termico.php">Conto Termico</a></li>
                        <li><a class="dropdown-item" href="/enea.php">ENEA</a></li>
                        <li><a class="dropdown-item" href="/procedure.php">Procedure</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h1>Test Dropdown in Richieste</h1>
        <p>Se i dropdown sopra funzionano, il problema è nell'header personalizzato o nel JavaScript della pagina richieste.php originale.</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
