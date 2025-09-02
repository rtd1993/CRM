    </main>

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
    <script>
        console.log('📂 Caricamento chat-footer.js...');
    </script>
    <script src="/assets/js/chat-footer.js?v=<?= time() ?>"></script>
    <script>
        console.log('✅ chat-footer.js caricato');
        
        // Verifica se la classe è disponibile
        setTimeout(() => {
            if (typeof ChatFooterSystem !== 'undefined') {
                console.log('🎯 ChatFooterSystem classe trovata');
                if (window.chatFooterSystem) {
                    console.log('🚀 Istanza chat system attiva:', window.chatFooterSystem);
                } else {
                    console.log('⚠️ Istanza chat system non trovata');
                }
            } else {
                console.log('❌ ChatFooterSystem classe non trovata');
            }
        }, 100);
    </script>
    <?php endif; ?>

</body>
</html>
