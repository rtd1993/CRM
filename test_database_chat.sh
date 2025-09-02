#!/bin/bash

echo "🔍 Diagnostica Database Chat Sistema"
echo "===================================="

# Controlla configurazione database
echo "📋 Configurazione Database:"
if [ -f "includes/config.php" ]; then
    grep -E "(DB_HOST|DB_NAME|DB_USER)" includes/config.php | sed 's/define/  /'
else
    echo "❌ File config.php non trovato"
fi

echo ""
echo "📊 Tabelle Chat nel Database:"

# Connessione MySQL e verifica tabelle
php -r "
require_once 'includes/db.php';
try {
    \$stmt = \$pdo->query('SHOW TABLES LIKE \"%chat%\"');
    \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty(\$tables)) {
        echo '❌ Nessuna tabella chat trovata\\n';
        echo '🔧 Creazione tabella chat_messaggi...\\n';
        
        \$sql = \"
            CREATE TABLE chat_messaggi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                utente_id INT NOT NULL,
                messaggio TEXT NOT NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
                INDEX idx_timestamp (timestamp),
                INDEX idx_utente (utente_id)
            )
        \";
        \$pdo->exec(\$sql);
        echo '✅ Tabella chat_messaggi creata\\n';
    } else {
        foreach (\$tables as \$table) {
            echo \"✅ \$table\\n\";
        }
    }
    
    // Conta messaggi se esistono
    \$stmt = \$pdo->query('SELECT COUNT(*) as count FROM chat_messaggi');
    \$count = \$stmt->fetch()['count'];
    echo \"\\n📊 Messaggi chat globale: \$count\\n\";
    
    // Controlla utenti
    \$stmt = \$pdo->query('SELECT COUNT(*) as count FROM utenti');
    \$userCount = \$stmt->fetch()['count'];
    echo \"👥 Utenti registrati: \$userCount\\n\";
    
} catch (Exception \$e) {
    echo '❌ Errore database: ' . \$e->getMessage() . \"\\n\";
}
"

echo ""
echo "🚀 Test completato!"
