    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php
    // Includi il nuovo widget chat semplificato se l'utente è loggato
    if (isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php'])):
        include __DIR__ . '/chat-widget-new.php';
    endif;
    ?></body>
</html>
