<?php
// Versione semplificata di modifica_cliente.php per debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Modifica Cliente - Versione Semplificata</h1>";

try {
    require_once __DIR__ . '/includes/auth.php';
    require_login();
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/header.php';
} catch (Exception $e) {
    die("Errore caricamento: " . $e->getMessage());
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID cliente non valido");
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("Cliente non trovato");
    }
} catch (Exception $e) {
    die("Errore database: " . $e->getMessage());
}

// Gestione POST semplificata
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Dati POST ricevuti:</h2>";
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";
    
    // Test base - aggiorna solo il campo Nome se presente
    if (isset($_POST['Nome'])) {
        try {
            $stmt = $pdo->prepare("UPDATE clienti SET `Nome` = ? WHERE id = ?");
            if ($stmt->execute([$_POST['Nome'], $id])) {
                echo "<p style='color: green;'>✅ Aggiornamento riuscito!</p>";
            } else {
                echo "<p style='color: red;'>❌ Errore nell'aggiornamento</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Errore: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem; }
.form-field { margin: 1rem 0; }
.form-label { display: block; font-weight: bold; margin-bottom: 0.5rem; }
.form-control { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
.btn { padding: 0.8rem 1.5rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 0.5rem; }
.btn:hover { background: #0056b3; }
</style>

<h2>Cliente: <?= htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A') ?></h2>

<form method="post">
    <div class="form-field">
        <label class="form-label">Nome</label>
        <input type="text" name="Nome" value="<?= htmlspecialchars($cliente['Nome'] ?? '') ?>" class="form-control">
    </div>
    
    <div class="form-field">
        <label class="form-label">Cognome/Ragione sociale</label>
        <input type="text" name="Cognome/Ragione sociale" value="<?= htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? '') ?>" class="form-control">
    </div>
    
    <div class="form-field">
        <label class="form-label">Telefono</label>
        <input type="text" name="Telefono" value="<?= htmlspecialchars($cliente['Telefono'] ?? '') ?>" class="form-control">
    </div>
    
    <div class="form-field">
        <label class="form-label">Mail</label>
        <input type="email" name="Mail" value="<?= htmlspecialchars($cliente['Mail'] ?? '') ?>" class="form-control">
    </div>
    
    <button type="submit" class="btn">Salva Modifiche</button>
    <a href="info_cliente.php?id=<?= $id ?>" class="btn" style="background: #6c757d; text-decoration: none;">Annulla</a>
</form>

<hr>
<h3>Debug Info:</h3>
<p><strong>ID Cliente:</strong> <?= $id ?></p>
<p><strong>Metodo Request:</strong> <?= $_SERVER['REQUEST_METHOD'] ?></p>
<p><strong>Dati Cliente:</strong></p>
<pre><?= json_encode($cliente, JSON_PRETTY_PRINT) ?></pre>

<hr>
<p><a href="debug_modifica_cliente.php?id=<?= $id ?>">🔍 Debug Completo</a></p>
<p><a href="modifica_cliente.php?id=<?= $id ?>">📝 Versione Normale</a></p>
<p><a href="clienti.php">📋 Lista Clienti</a></p>

</main>
</body>
</html>
