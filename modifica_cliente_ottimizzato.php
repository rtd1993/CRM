<?php
// Versione ottimizzata di modifica_cliente.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M'); // Aumenta limite memoria

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
    error_log("Errore database in modifica_cliente.php: " . $e->getMessage());
    echo "<p>Errore durante il caricamento del cliente.</p></main></body></html>";
    exit;
}

// Inizializza variabile errore
$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Filtra i campi POST
        $campi_validi = [];
        $valori_validi = [];
        
        foreach ($_POST as $campo => $valore) {
            if (!empty($campo) && $campo !== 'submit') {
                $campi_validi[] = $campo;
                $valori_validi[] = $valore;
            }
        }
        
        if (empty($campi_validi)) {
            throw new Exception("Nessun campo valido da aggiornare");
        }
        
        // Costruisci la query UPDATE
        $update_parts = [];
        foreach ($campi_validi as $campo) {
            $update_parts[] = "`$campo` = ?";
        }
        $update_sql = implode(', ', $update_parts);
        
        // Esegui la query
        $stmt = $pdo->prepare("UPDATE clienti SET $update_sql WHERE id = ?");
        $valori_validi[] = $id;
        
        if ($stmt->execute($valori_validi)) {
            $successo = "Cliente aggiornato con successo!";
            // Ricarica dati aggiornati
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            throw new Exception("Errore durante l'esecuzione della query");
        }
        
    } catch (Exception $e) {
        error_log("Errore in modifica_cliente.php: " . $e->getMessage());
        $errore = "Errore durante l'aggiornamento: " . $e->getMessage();
    }
}

// Funzione semplificata per generare campi
function campo_input($nome, $valore, $type = 'text') {
    $nome_escaped = htmlspecialchars($nome);
    $valore_escaped = htmlspecialchars($valore ?? '');
    
    return "<div class=\"form-field\">
        <label class=\"form-label\">{$nome_escaped}</label>
        <input type=\"{$type}\" name=\"{$nome}\" value=\"{$valore_escaped}\" class=\"form-control\">
    </div>";
}

// Gruppi ridotti per evitare sovraccarico
$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA', 'Qualifica'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'User Aruba'],
    'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d'identità', 'Rilasciata dal Comune di'],
    'Fiscali' => ['Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Codice inps'],
    'Altro' => ['Colore', 'Inizio rapporto', 'Fine rapporto']
];
?>

<style>
/* CSS semplificato per ridurre il carico */
.client-header {
    background: linear-gradient(135deg, #007bff, #6610f2);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: center;
}

.client-header h2 {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 300;
}

.client-form {
    background: white;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 1px solid #e1e5e9;
    margin-bottom: 2rem;
}

.form-section {
    margin-bottom: 2rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 1.5rem;
    background: #f8f9fa;
}

.form-section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #007bff;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #007bff;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control {
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e1e5e9;
}

.btn {
    padding: 0.8rem 2rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #c3e6cb;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    margin-top: 1rem;
}

.back-link:hover {
    color: #007bff;
}

@media (max-width: 768px) {
    .client-header h2 {
        font-size: 1.8rem;
    }
    
    .client-form {
        padding: 1rem;
        margin: 0 0.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="client-header">
    <h2>✏️ Modifica Cliente</h2>
    <p>Aggiorna i dati del cliente selezionato</p>
</div>

<div class="client-form">
    <?php if (!empty($errore)): ?>
        <div class="error-message">
            <strong>⚠️</strong> <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($successo)): ?>
        <div class="success-message">
            <strong>✅</strong> <?= htmlspecialchars($successo) ?>
        </div>
    <?php endif; ?>
    
    <form method="post">
        <?php foreach ($gruppi as $titolo => $campi): ?>
            <div class="form-section">
                <div class="form-section-title"><?= htmlspecialchars($titolo) ?></div>
                <div class="form-grid">
                    <?php foreach ($campi as $campo): ?>
                        <?php
                        // Determina il tipo di campo
                        $type = 'text';
                        if (stripos($campo, 'data') !== false || 
                            stripos($campo, 'scadenza') !== false || 
                            stripos($campo, 'rinnovo') !== false || 
                            stripos($campo, 'inizio') !== false || 
                            stripos($campo, 'fine') !== false) {
                            $type = 'date';
                        }
                        echo campo_input($campo, $cliente[$campo] ?? '', $type);
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Salva Modifiche</button>
            <a href="info_cliente.php?id=<?= $id ?>" class="btn btn-secondary">❌ Annulla</a>
        </div>
    </form>
</div>

<a href="info_cliente.php?id=<?= $id ?>" class="back-link">⬅️ Torna ai dettagli cliente</a>

<!-- JavaScript semplificato -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Focus sul primo campo
    const firstInput = document.querySelector('.form-control');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Validazione form
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
    
    // Auto-uppercase per codice fiscale
    const cfInput = document.querySelector('input[name="Codice fiscale"]');
    if (cfInput) {
        cfInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
});
</script>

</main>
</body>
</html>
