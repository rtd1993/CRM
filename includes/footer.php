    </main>
    <?php
    // Debug: verifichiamo le condizioni per includere il widget
    echo "<!-- Debug Footer: user_id = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON_SETTATO') . " -->";
    echo "<!-- Debug Footer: current page = " . basename($_SERVER['PHP_SELF']) . " -->";
    
    // Includi il widget chat completo se l'utente è loggato
    if (isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])):
        echo "<!-- Debug Footer: Includo chat-widget-complete.php -->";
        include __DIR__ . '/chat-widget-complete.php';
        echo "<!-- Debug Footer: Chat widget incluso -->";
    else:
        echo "<!-- Debug Footer: Condizioni non soddisfatte per includere widget -->";
    endif;
    ?></body>
</html>
