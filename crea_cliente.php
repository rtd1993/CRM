<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

// Rimuovi "Link cartella" dal form
$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori', 'Titolare'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'Scadenza PEC', 'Rinnovo Pec', 'User Aruba', 'Password'],
    'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d’identità', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l’espatrio'],
    'Fiscali' => ['Codice ditta', 'Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Dipendenti', 'Codice inps', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile', 'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'SDI'],
    'Altro' => ['Colore', 'Inserito gestionale', 'Inizio rapporto', 'Fine rapporto']
];

// Gestione POST e creazione cartella
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campi = [];
    $valori = [];
    foreach ($gruppi as $fields) {
        foreach ($fields as $campo) {
            $campi[] = $campo;
            $valori[] = $_POST[$campo] ?? null;
        }
    }
    $segnaposti = implode(', ', array_fill(0, count($campi), '?'));
    $campi_sql = '`' . implode('`, `', $campi) . '`';

    // Prepara il codice fiscale (usato per la cartella)
    $idx_cf = array_search('Codice fiscale', $campi);
    $codice_fiscale = ($idx_cf !== false) ? trim(strtoupper($valori[$idx_cf])) : '';
    $errore_cf = empty($codice_fiscale);

    // Salva nel database solo se c'è codice fiscale
    if (!$errore_cf) {
        $stmt = $pdo->prepare("INSERT INTO clienti ($campi_sql) VALUES ($segnaposti)");
        if ($stmt->execute($valori)) {
            // Crea cartella con nome = codice fiscale
            $cartella = '/var/www/CRM/local_drive/' . $codice_fiscale;
            if (!is_dir($cartella)) {
                mkdir($cartella, 0775, true);
            }
            header("Location: clienti.php?success=1");
            exit;
        } else {
            $errore = "Errore durante l'inserimento.";
        }
    } else {
        $errore = "Il campo Codice fiscale è obbligatorio e serve per creare la cartella.";
    }
}

function campo_input($nome) {
    // Lista di parole chiave per i campi data (case-insensitive)
    $campi_data = ['data', 'scadenza', 'rinnovo', 'inizio rapporto', 'fine rapporto', 'inserito gestionale'];
    $type = 'text';
    foreach ($campi_data as $parola) {
        if (stripos($nome, $parola) !== false) {
            $type = 'date';
            break;
        }
    }
    return "<div class=\"form-field\"><label class=\"form-label\">$nome</label><input type=\"$type\" name=\"$nome\" class=\"form-control\"></div>";
}
?>

<style>
.client-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.client-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.client-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.client-form {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    position: relative;
    overflow: hidden;
}

.client-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #28a745, #20c997);
}

.form-section {
    margin-bottom: 2rem;
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    padding: 1.5rem;
    background: #f8f9fa;
    position: relative;
    transition: all 0.3s ease;
}

.form-section:hover {
    border-color: #28a745;
    background: white;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
}

/* Sezione attiva */
.form-section.active {
    border-color: #28a745;
    background: white;
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.15);
    transform: translateY(-2px);
}

.form-section.active .form-section-title {
    background: #28a745;
    color: white;
}

.form-section-title {
    position: absolute;
    top: -12px;
    left: 20px;
    background: white;
    padding: 0 15px;
    font-size: 1.1rem;
    font-weight: 600;
    color: #28a745;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.form-control {
    padding: 0.8rem 1rem;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    background: #f8fff9;
}

.form-control[type="date"] {
    cursor: pointer;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #f5c6cb;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    margin-top: 1rem;
    transition: color 0.3s ease;
}

.back-link:hover {
    color: #28a745;
}

.required-field::after {
    content: " *";
    color: #dc3545;
    font-weight: bold;
}

@media (max-width: 768px) {
    .client-header h2 {
        font-size: 2rem;
    }
    
    .client-form {
        padding: 1.5rem;
        margin: 0 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
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
    <h2>👤 Crea Nuovo Cliente</h2>
    <p>Inserisci i dati del nuovo cliente nel sistema</p>
</div>

<div class="client-form">
    <?php if (!empty($errore)): ?>
        <div class="error-message">
            <strong>⚠️</strong> <?= htmlspecialchars($errore) ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <?php foreach ($gruppi as $titolo => $campi): ?>
            <div class="form-section">
                <div class="form-section-title"><?= htmlspecialchars($titolo) ?></div>
                <div class="form-grid">
                    <?php foreach ($campi as $campo): ?>
                        <?php
                        $is_required = (strpos($campo, 'Codice fiscale') !== false);
                        $field_html = str_replace('class="form-label"', 'class="form-label' . ($is_required ? ' required-field' : '') . '"', campo_input($campo));
                        echo $field_html;
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Salva Cliente</button>
            <a href="clienti.php" class="btn btn-secondary">❌ Annulla</a>
        </div>
    </form>
</div>

<a href="clienti.php" class="back-link">⬅️ Torna alla lista clienti</a>

</main>
</body>
</html>

<script>
// Miglioramenti UX per form clienti
document.addEventListener('DOMContentLoaded', function() {
    // Focus automatico sul primo campo
    const firstInput = document.querySelector('.form-control');
    if (firstInput) {
        firstInput.focus();
    }
    
    // Evidenzia sezione attiva
    const formSections = document.querySelectorAll('.form-section');
    formSections.forEach(section => {
        const inputs = section.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Rimuovi classe active da tutte le sezioni
                formSections.forEach(s => s.classList.remove('active'));
                // Aggiungi classe active alla sezione corrente
                section.classList.add('active');
            });
        });
    });
    
    // Validazione form
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = submitBtn.innerHTML.replace('Salva', 'Salvataggio...');
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