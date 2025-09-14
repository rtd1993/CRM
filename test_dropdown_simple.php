<?php
// Test minimo per i dropdown
require_once __DIR__ . '/includes/auth.php';
require_login();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Dropdown - CRM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">CRM Test</a>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        Test Dropdown
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Opzione 1</a></li>
                        <li><a class="dropdown-item" href="#">Opzione 2</a></li>
                        <li><a class="dropdown-item" href="#">Opzione 3</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    
    <div class="container mt-5">
        <h1>Test Bootstrap Dropdown</h1>
        <p>Se il dropdown sopra funziona, il problema è nella struttura dell'header personalizzato.</p>
        
        <div class="dropdown mt-3">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                Test Button Dropdown
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action 1</a></li>
                <li><a class="dropdown-item" href="#">Action 2</a></li>
                <li><a class="dropdown-item" href="#">Action 3</a></li>
            </ul>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    console.log('Bootstrap Dropdown available:', typeof bootstrap.Dropdown !== 'undefined');
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Testing dropdown functionality...');
        
        const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
        console.log('Found dropdowns:', dropdowns.length);
        
        dropdowns.forEach((dropdown, i) => {
            console.log(`Dropdown ${i + 1}:`, dropdown);
            dropdown.addEventListener('click', function(e) {
                console.log(`Dropdown ${i + 1} clicked`);
            });
        });
    });
    </script>
</body>
</html>
