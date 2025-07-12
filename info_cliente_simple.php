<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID cliente non valido.</p></main></body></html>";
    exit;
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo "<p>Cliente non trovato.</p></main></body></html>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>Errore database: " . $e->getMessage() . "</p></main></body></html>";
    exit;
}

function format_label($label) {
    $label = str_replace('_', ' ', $label);
    $label = str_replace('/', ' / ', $label);
    return ucwords($label);
}
?>

<style>
.simple-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.simple-header {
    background: #17a2b8;
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

.simple-section {
    margin-bottom: 30px;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.simple-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.simple-field {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.simple-label {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.simple-value {
    color: #666;
}

.simple-buttons {
    text-align: center;
    margin: 30px 0;
}

.simple-btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 0 10px;
    background: #17a2b8;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    border: none;
    cursor: pointer;
}

.simple-btn:hover {
    background: #138496;
}

.simple-btn.secondary {
    background: #6c757d;
}

.simple-btn.secondary:hover {
    background: #5a6268;
}
</style>

<div class="simple-container">
    <div class="simple-header">
        <h2>👤 Informazioni Cliente (Versione Semplificata)</h2>
        <p>Debug Mode - Cliente ID: <?= $id ?></p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            ✅ Cliente aggiornato con successo!
        </div>
    <?php endif; ?>

    <div class="simple-section">
        <h3>📋 Riepilogo Cliente</h3>
        <p><strong>Nome/Ragione Sociale:</strong> <?= htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($cliente['Mail'] ?? 'N/A') ?></p>
        <p><strong>Telefono:</strong> <?= htmlspecialchars($cliente['Telefono'] ?? 'N/A') ?></p>
        <p><strong>Codice Fiscale:</strong> <?= htmlspecialchars($cliente['Codice fiscale'] ?? 'N/A') ?></p>
    </div>

    <?php
    $gruppi = [
        'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA'],
        'Contatti' => ['Telefono', 'Mail', 'PEC'],
        'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
        'Altro' => ['Colore', 'Inizio rapporto', 'Fine rapporto']
    ];

    foreach ($gruppi as $titolo => $campi):
    ?>
        <div class="simple-section">
            <h3><?= htmlspecialchars($titolo) ?></h3>
            <div class="simple-grid">
                <?php foreach ($campi as $campo): ?>
                    <?php if (array_key_exists($campo, $cliente)): ?>
                        <div class="simple-field">
                            <div class="simple-label"><?= htmlspecialchars(format_label($campo)) ?></div>
                            <div class="simple-value"><?= htmlspecialchars($cliente[$campo] ?: 'Non specificato') ?></div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="simple-buttons">
        <a href="modifica_cliente.php?id=<?= $cliente['id'] ?>" class="simple-btn">
            ✏️ Modifica Cliente
        </a>
        <button onclick="window.print()" class="simple-btn secondary">
            🖨️ Stampa
        </button>
        <a href="clienti.php" class="simple-btn secondary">
            📋 Lista Clienti
        </a>
    </div>

    <p><a href="clienti.php">⬅️ Torna alla lista clienti</a></p>
</div>

</main>
</body>
</html>
