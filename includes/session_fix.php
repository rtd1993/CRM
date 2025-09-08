<?php
// File: includes/session_fix.php
// Fix per problema sessioni che si sovrascrivono

// Avvia la sessione solo se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    // Configura la sessione prima di avviarla
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    ini_set('session.gc_maxlifetime', 1440);
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    session_start();
}
?>
