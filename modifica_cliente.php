<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_err    'Documenti' => ['Numero carta d identita', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l espatrio'],rs', 1);
ini_set('memory_limit', '512M'); // Aumenta limite memoria

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Filtra i campi POST (escludi eventuali campi non validi)
        $campi_validi = [];
        $valori_validi = [];
        
        foreach ($_POST as $campo => $valore) {
            // Verifica che il campo esista nella tabella
            if (!empty($campo) && $campo !== 'submit') {
                $campi_validi[] = $campo;
                $valori_validi[] = $valore;
            }
        }
        
        if (empty($campi_validi)) {
            throw new Exception("Nessun campo valido da aggiornare");
        }
        
        // Costruisci la query UPDATE in modo sicuro
        $update_parts = [];
        foreach ($campi_validi as $campo) {
            $update_parts[] = "`$campo` = ?";
        }
        $update_sql = implode(', ', $update_parts);
        
        // Prepara ed esegui la query
        $stmt = $pdo->prepare("UPDATE clienti SET $update_sql WHERE id = ?");
        $valori_validi[] = $id; // Aggiungi ID alla fine
        
        if ($stmt->execute($valori_validi)) {
            header("Location: info_cliente.php?id=$id&success=1");
            exit;
        } else {
            throw new Exception("Errore durante l'esecuzione della query");
        }
        
    } catch (Exception $e) {
        error_log("Errore in modifica_cliente.php: " . $e->getMessage());
        $errore = "Errore durante l'aggiornamento del cliente: " . $e->getMessage();
    }
}

function campo_input($nome, $valore, $type = 'text') {
    // Escape del nome del campo per HTML
    $nome_escaped = htmlspecialchars($nome);
    $valore_escaped = htmlspecialchars($valore ?? '');
    
    return "<div class=\"form-field\">
        <label class=\"form-label\">{$nome_escaped}</label>
        <input type=\"{$type}\" name=\"{$nome}\" value=\"{$valore_escaped}\" class=\"form-control\">
    </div>";
}

$gruppi = [
    'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori', 'Titolare'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'Scadenza PEC', 'Rinnovo Pec', 'User Aruba', 'Password'],
    'Sedi' => ['Sede Legale', 'Sede Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d’identità', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l’espatrio'],
    'Fiscali' => ['Codice ditta', 'Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Dipendenti', 'Codice inps', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile', 'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'SDI'],
    'Altro' => ['Colore', 'Inserito gestionale', 'Inizio rapporto', 'Fine rapporto', 'Link cartella']
];
?>

<style>
/* Reset e base */
* {
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: 0;
    padding: 1rem;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Header Cliente */
.client-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.client-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.client-header h2 {
    margin: 0;
    font-size: 2.8rem;
    font-weight: 300;
    text-shadow: 0 4px 8px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.client-header p {
    margin: 1rem 0 0 0;
    opacity: 0.9;
    font-size: 1.2rem;
    position: relative;
    z-index: 1;
}

/* Form Container */
.client-form {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: 0 30px 60px rgba(0,0,0,0.12);
    border: 1px solid rgba(255,255,255,0.8);
    position: relative;
    overflow: hidden;
}

.client-form::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
    background-size: 200% 100%;
    animation: shimmer 3s ease-in-out infinite;
}

@keyframes shimmer {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

/* Sezioni Form */
.form-section {
    margin-bottom: 2.5rem;
    border: 2px solid #e8ecf4;
    border-radius: 16px;
    padding: 2rem;
    background: #f8fafe;
    position: relative;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.form-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.05) 50%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.form-section:hover {
    border-color: #667eea;
    background: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
}

.form-section:hover::before {
    opacity: 1;
}

.form-section.active {
    border-color: #667eea;
    background: white;
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
    transform: translateY(-4px);
}

.form-section.active .form-section-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.form-section-title {
    position: absolute;
    top: -16px;
    left: 25px;
    background: white;
    padding: 0.5rem 1.5rem;
    font-size: 1.1rem;
    font-weight: 700;
    color: #667eea;
    border-radius: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    z-index: 10;
}

/* Grid Form */
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 1.5rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    position: relative;
}

.form-label {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.8rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label::before {
    content: '•';
    color: #667eea;
    font-weight: bold;
    font-size: 1.2rem;
}

.form-control {
    padding: 1rem 1.2rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    position: relative;
    z-index: 1;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background: #f8fafe;
    transform: translateY(-1px);
}

.form-control:hover {
    border-color: #cbd5e0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.form-control[type="date"] {
    cursor: pointer;
}

.form-control.modified {
    border-color: #f6ad55;
    background: #fffaf0;
    box-shadow: 0 0 0 3px rgba(246, 173, 85, 0.1);
}

.form-control.modified::after {
    content: '✓';
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #f6ad55;
    font-weight: bold;
}

/* Messaggi */
.error-message {
    background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
    color: #9b2c2c;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid #fc8181;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    box-shadow: 0 4px 12px rgba(252, 129, 129, 0.3);
}

.success-message {
    background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
    color: #276749;
    padding: 1.5rem 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid #68d391;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    box-shadow: 0 4px 12px rgba(104, 211, 145, 0.3);
}

/* Bottoni */
.form-actions {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 2px solid #e2e8f0;
}

.btn {
    padding: 1rem 2.5rem;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}

.btn:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
}

.btn-secondary {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(113, 128, 150, 0.4);
}

.btn-secondary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(113, 128, 150, 0.5);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #718096;
    text-decoration: none;
    font-weight: 500;
    margin-top: 2rem;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    transform: translateX(-4px);
}

/* Responsive */
@media (max-width: 768px) {
    body {
        padding: 0.5rem;
    }
    
    .client-header h2 {
        font-size: 2.2rem;
    }
    
    .client-form {
        padding: 2rem;
        margin: 0;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}

/* Animazioni */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-section {
    animation: fadeInUp 0.6s ease-out;
}

.form-section:nth-child(1) { animation-delay: 0.1s; }
.form-section:nth-child(2) { animation-delay: 0.2s; }
.form-section:nth-child(3) { animation-delay: 0.3s; }
.form-section:nth-child(4) { animation-delay: 0.4s; }
.form-section:nth-child(5) { animation-delay: 0.5s; }
.form-section:nth-child(6) { animation-delay: 0.6s; }
</style>

<div class="container">
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
    
    <form method="post">
        <?php foreach ($gruppi as $titolo => $campi): ?>
            <div class="form-section">
                <div class="form-section-title"><?= htmlspecialchars($titolo) ?></div>
                <div class="form-grid">
                    <?php foreach ($campi as $campo): ?>
                        <?php
                        // Determina il tipo di campo
                        $campi_data = ['data', 'scadenza', 'rinnovo', 'inizio rapporto', 'fine rapporto', 'inserito gestionale'];
                        $type = 'text';
                        foreach ($campi_data as $parola) {
                            if (stripos($campo, $parola) !== false) {
                                $type = 'date';
                                break;
                            }
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
</div>

<script>
// Miglioramenti UX avanzati per form modifica cliente
document.addEventListener('DOMContentLoaded', function() {
    // Animazione di entrata per le sezioni
    const sections = document.querySelectorAll('.form-section');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        setTimeout(() => {
            section.style.transition = 'all 0.6s ease-out';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Focus automatico sul primo campo
    setTimeout(() => {
        const firstInput = document.querySelector('.form-control');
        if (firstInput) {
            firstInput.focus();
        }
    }, 500);
    
    // Evidenzia sezione attiva con effetti avanzati
    const formSections = document.querySelectorAll('.form-section');
    formSections.forEach(section => {
        const inputs = section.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                // Rimuovi classe active da tutte le sezioni
                formSections.forEach(s => s.classList.remove('active'));
                // Aggiungi classe active alla sezione corrente
                section.classList.add('active');
                
                // Scroll smooth alla sezione se necessario
                if (section.getBoundingClientRect().top < 100) {
                    section.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start',
                        inline: 'nearest'
                    });
                }
            });
        });
    });
    
    // Validazione form con feedback visivo
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span style="margin-right: 0.5rem;">⏳</span> Salvataggio in corso...';
                submitBtn.style.background = 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)';
                
                // Aggiungi loader
                const loader = document.createElement('div');
                loader.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 10000;
                `;
                loader.innerHTML = `
                    <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center;">
                        <div style="width: 50px; height: 50px; border: 3px solid #e2e8f0; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                        <p style="margin: 0; font-weight: 600; color: #4a5568;">Salvataggio in corso...</p>
                    </div>
                `;
                document.body.appendChild(loader);
                
                // Aggiungi CSS animation per il loader
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
        });
    }
    
    // Auto-formatting per campi specifici
    const cfInput = document.querySelector('input[name="Codice fiscale"]');
    if (cfInput) {
        cfInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
    
    // Formatting per Partita IVA
    const pivaInput = document.querySelector('input[name="Partita IVA"]');
    if (pivaInput) {
        pivaInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Evidenzia campi modificati con animazione
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        const originalValue = input.value;
        
        input.addEventListener('input', function() {
            if (this.value !== originalValue) {
                this.classList.add('modified');
                
                // Animazione di modifica
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 150);
            } else {
                this.classList.remove('modified');
            }
        });
        
        // Effetto hover migliorato
        input.addEventListener('mouseenter', function() {
            if (!this.matches(':focus')) {
                this.style.transform = 'translateY(-1px)';
            }
        });
        
        input.addEventListener('mouseleave', function() {
            if (!this.matches(':focus')) {
                this.style.transform = 'translateY(0)';
            }
        });
    });
    
    // Salvataggio automatico in localStorage (bozza)
    let autoSaveTimeout;
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem('modifica_cliente_bozza', JSON.stringify(data));
                
                // Mostra notifica di salvataggio bozza
                showNotification('💾 Bozza salvata automaticamente', 'success');
            }, 2000);
        });
    });
    
    // Ripristina bozza se presente
    const savedDraft = localStorage.getItem('modifica_cliente_bozza');
    if (savedDraft) {
        const draftData = JSON.parse(savedDraft);
        const showDraftBtn = document.createElement('button');
        showDraftBtn.type = 'button';
        showDraftBtn.className = 'btn btn-secondary';
        showDraftBtn.innerHTML = '📋 Ripristina bozza salvata';
        showDraftBtn.style.margin = '1rem 0';
        
        showDraftBtn.addEventListener('click', function() {
            Object.keys(draftData).forEach(key => {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = draftData[key];
                    input.classList.add('modified');
                }
            });
            this.remove();
            showNotification('📋 Bozza ripristinata con successo', 'success');
        });
        
        form.insertBefore(showDraftBtn, form.firstChild);
    }
    
    // Funzione per mostrare notifiche
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        if (type === 'success') {
            notification.style.background = 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)';
        } else {
            notification.style.background = 'linear-gradient(135deg, #4299e1 0%, #3182ce 100%)';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Aggiungi CSS per le animazioni delle notifiche
    const notificationStyle = document.createElement('style');
    notificationStyle.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(notificationStyle);
});
</script>

</main>
</body>
</html>