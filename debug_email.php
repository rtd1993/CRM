<?php
// Debug per gestione_email_template.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug gestione_email_template.php</h1>";

try {
    echo "<p>✓ PHP funziona</p>";
    
    require_once 'includes/auth.php';
    echo "<p>✓ Auth incluso</p>";
    
    require_once 'includes/db.php';
    echo "<p>✓ Database incluso</p>";
    
    // Test connessione database
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_templates'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<p>✓ Tabella email_templates esiste</p>";
        
        // Test query
        $templates = $pdo->query("SELECT COUNT(*) as count FROM email_templates")->fetch();
        echo "<p>✓ Template nel database: " . $templates['count'] . "</p>";
    } else {
        echo "<p>❌ Tabella email_templates non esiste</p>";
        echo "<p>Creazione tabella...</p>";
        
        // Crea tabella email_templates
        $pdo->exec("CREATE TABLE IF NOT EXISTS `email_templates` (
          `id` int NOT NULL AUTO_INCREMENT,
          `nome` varchar(255) NOT NULL,
          `oggetto` varchar(500) NOT NULL,
          `corpo` text NOT NULL,
          `data_creazione` datetime DEFAULT CURRENT_TIMESTAMP,
          `data_modifica` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        
        echo "<p>✓ Tabella email_templates creata</p>";
        
        // Crea tabella email_log
        $pdo->exec("CREATE TABLE IF NOT EXISTS `email_log` (
          `id` int NOT NULL AUTO_INCREMENT,
          `cliente_id` int NOT NULL,
          `template_id` int NOT NULL,
          `oggetto` varchar(500) NOT NULL,
          `corpo` text NOT NULL,
          `destinatario_email` varchar(255) NOT NULL,
          `destinatario_nome` varchar(255) NOT NULL,
          `data_invio` datetime DEFAULT CURRENT_TIMESTAMP,
          `stato` enum('inviata','fallita') DEFAULT 'inviata',
          `messaggio_errore` text,
          PRIMARY KEY (`id`),
          KEY `cliente_id` (`cliente_id`),
          KEY `template_id` (`template_id`),
          CONSTRAINT `email_log_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clienti` (`id`) ON DELETE CASCADE,
          CONSTRAINT `email_log_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
        
        echo "<p>✓ Tabella email_log creata</p>";
        
        // Inserisci template di esempio
        $pdo->exec("INSERT IGNORE INTO `email_templates` (`nome`, `oggetto`, `corpo`) VALUES
        ('Comunicazione generale', 'Comunicazione importante da AS Contabilmente', 
        'Gentile {nome_cliente},\n\nSperiamo che tutto proceda al meglio per lei e la sua attività.\n\nLe scriviamo per comunicarle:\n\n[INSERIRE TESTO DELLA COMUNICAZIONE]\n\nRimaniamo a disposizione per qualsiasi chiarimento.\n\nCordiali saluti,\nIl team di AS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),
        
        ('Scadenza documenti', 'Promemoria scadenze - {nome_cliente}',
        'Gentile {nome_cliente},\n\nLe ricordiamo che sono in scadenza i seguenti documenti/adempimenti:\n\n[ELENCO SCADENZE]\n\nLa preghiamo di contattarci al più presto per procedere con gli adempimenti necessari.\n\nGrazie per la collaborazione.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),
        
        ('Richiesta documenti', 'Richiesta documentazione - {nome_cliente}',
        'Gentile {nome_cliente},\n\nPer procedere con le pratiche in corso, abbiamo bisogno della seguente documentazione:\n\n[ELENCO DOCUMENTI RICHIESTI]\n\nLa preghiamo di inviarci i documenti richiesti entro [DATA SCADENZA].\n\nRimaniamo a disposizione per qualsiasi chiarimento.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com'),
        
        ('Auguri festività', 'Auguri di {festivita} da AS Contabilmente',
        'Gentile {nome_cliente},\n\nTutto il team di AS Contabilmente desidera farle i migliori auguri di {festivita}.\n\nLe auguriamo un periodo di serenità e felicità insieme ai suoi cari.\n\nCordiali saluti,\nAS Contabilmente\n\nTel: [TELEFONO]\nEmail: gestione.ascontabilmente@gmail.com')");
        
        echo "<p>✓ Template di esempio inseriti</p>";
    }
    
    require_once 'includes/header.php';
    echo "<p>✓ Header incluso</p>";
    
    echo "<h2>Test completato con successo!</h2>";
    echo "<p><a href='gestione_email_template.php'>Torna alla pagina principale</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Linea: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
