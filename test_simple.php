<?php
echo "Test pagina email template - Inizio<br>";

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Includo auth...<br>";
try {
    require_once 'includes/auth.php';
    echo "Auth OK<br>";
} catch (Exception $e) {
    echo "Errore auth: " . $e->getMessage() . "<br>";
    die();
}

echo "Includo db...<br>";
try {
    require_once 'includes/db.php';
    echo "DB OK<br>";
} catch (Exception $e) {
    echo "Errore db: " . $e->getMessage() . "<br>";
    die();
}

echo "Test query...<br>";
try {
    $result = $pdo->query("SELECT 1 as test")->fetch();
    echo "Query test OK: " . $result['test'] . "<br>";
} catch (Exception $e) {
    echo "Errore query: " . $e->getMessage() . "<br>";
    die();
}

echo "Test completato con successo!<br>";
echo "<a href='gestione_email_template.php'>Vai alla pagina template</a>";
?>
