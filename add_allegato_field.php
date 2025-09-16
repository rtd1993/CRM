<?php
require_once __DIR__ . '/includes/config.php';

try {
    // Aggiungi il campo allegato alla tabella procedure_crm se non esiste già
    $stmt = $pdo->prepare("SHOW COLUMNS FROM procedure_crm LIKE 'allegato'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $alter_stmt = $pdo->prepare("ALTER TABLE procedure_crm ADD COLUMN allegato VARCHAR(500) NULL AFTER procedura");
        $alter_stmt->execute();
        echo "Campo 'allegato' aggiunto con successo alla tabella procedure_crm.\n";
    } else {
        echo "Il campo 'allegato' esiste già nella tabella procedure_crm.\n";
    }
    
} catch (PDOException $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
?>
