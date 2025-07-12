<?php
// Versione ultra-leggera per test estremi
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

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

$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Aggiorna solo i campi inviati
        $updates = [];
        $values = [];
        
        foreach ($_POST as $campo => $valore) {
            if (!empty($campo) && $campo !== 'submit') {
                $updates[] = "`$campo` = ?";
                $values[] = $valore;
            }
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE clienti SET " . implode(', ', $updates) . " WHERE id = ?";
            $values[] = $id;
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($values)) {
                $successo = "Cliente aggiornato!";
                // Ricarica dati
                $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
                $stmt->execute([$id]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $errore = "Errore nell'aggiornamento";
            }
        }
        
    } catch (Exception $e) {
        $errore = "Errore: " . $e->getMessage();
    }
}

// Solo i campi essenziali
$campi_essenziali = [
    'Cognome/Ragione sociale',
    'Nome', 
    'Codice fiscale',
    'Partita IVA',
    'Telefono',
    'Mail',
    'Sede Legale',
    'Qualifica'
];
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
.header { background: #007bff; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
.form-container { background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd; }
.field { margin: 15px 0; }
.field label { display: block; font-weight: bold; margin-bottom: 5px; }
.field input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
.buttons { text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
.btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
.success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
</style>

<div class="header">
    <h1>Modifica Cliente - Ultra Leggero</h1>
    <p>Versione minimal per test di funzionamento</p>
</div>

<div class="form-container">
    <?php if ($errore): ?>
        <div class="error">❌ <?= htmlspecialchars($errore) ?></div>
    <?php endif; ?>
    
    <?php if ($successo): ?>
        <div class="success">✅ <?= htmlspecialchars($successo) ?></div>
    <?php endif; ?>
    
    <h2>Cliente: <?= htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A') ?></h2>
    
    <form method="post">
        <?php foreach ($campi_essenziali as $campo): ?>
            <div class="field">
                <label><?= htmlspecialchars($campo) ?></label>
                <input type="text" name="<?= htmlspecialchars($campo) ?>" value="<?= htmlspecialchars($cliente[$campo] ?? '') ?>">
            </div>
        <?php endforeach; ?>
        
        <div class="buttons">
            <button type="submit" class="btn btn-primary">💾 Salva</button>
            <a href="info_cliente.php?id=<?= $id ?>" class="btn btn-secondary">❌ Annulla</a>
        </div>
    </form>
</div>

<p><a href="info_cliente.php?id=<?= $id ?>">← Torna ai dettagli cliente</a></p>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Salvataggio...';
            }
        });
    }
});
</script>

</main>
</body>
</html>
