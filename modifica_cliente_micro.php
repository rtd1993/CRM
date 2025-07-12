<?php
// Versione MICRO - solo 3 campi per test estremo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1G');

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_GET['id'])) {
    die("ID mancante");
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();
    
    if (!$cliente) {
        die("Cliente non trovato");
    }
} catch (Exception $e) {
    die("Errore: " . $e->getMessage());
}

$msg = '';

if ($_POST) {
    try {
        // Aggiorna i 3 campi di base
        $stmt = $pdo->prepare("UPDATE clienti SET Nome = ?, Telefono = ?, Mail = ? WHERE id = ?");
        $stmt->execute([
            $_POST['Nome'] ?? '',
            $_POST['Telefono'] ?? '',
            $_POST['Mail'] ?? '',
            $id
        ]);
        $msg = "✅ Aggiornato!";
        
        // Ricarica
        $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
    } catch (Exception $e) {
        $msg = "❌ Errore: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Modifica MICRO</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .field { margin: 15px 0; }
        .field label { display: block; margin-bottom: 5px; font-weight: bold; }
        .field input { width: 100%; padding: 10px; border: 1px solid #ccc; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .msg { padding: 10px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔧 Modifica Cliente MICRO</h1>
    
    <?php if ($msg): ?>
        <div class="msg"><?= $msg ?></div>
    <?php endif; ?>
    
    <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A') ?></p>
    
    <form method="post">
        <div class="field">
            <label>Nome</label>
            <input type="text" name="Nome" value="<?= htmlspecialchars($cliente['Nome'] ?? '') ?>">
        </div>
        
        <div class="field">
            <label>Telefono</label>
            <input type="text" name="Telefono" value="<?= htmlspecialchars($cliente['Telefono'] ?? '') ?>">
        </div>
        
        <div class="field">
            <label>Mail</label>
            <input type="email" name="Mail" value="<?= htmlspecialchars($cliente['Mail'] ?? '') ?>">
        </div>
        
        <button type="submit" class="btn">💾 Salva</button>
        <a href="info_cliente.php?id=<?= $id ?>" style="margin-left: 10px;">Annulla</a>
    </form>
    
    <hr>
    <p><a href="info_cliente.php?id=<?= $id ?>">← Torna ai dettagli</a></p>

</body>
</html>
