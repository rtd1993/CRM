<?php
// Enable error reporting for debugging (remove in production)
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
    
    // Caricamento delle note dal file con nuovo formato cartella id_cognome.nome
    $cliente_folder = $cliente['id'] . '_' . 
                     strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Cognome_Ragione_sociale']));
    
    // Aggiungi il nome se presente
    if (!empty($cliente['Nome'])) {
        $nome_clean = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Nome']));
        $cliente_folder .= '.' . $nome_clean;
    }
    
    $note_file = __DIR__ . '/local_drive/' . $cliente_folder . '/note_' . $cliente['id'] . '.txt';
    $note_content = file_exists($note_file) ? file_get_contents($note_file) : '';
    
} catch (Exception $e) {
    error_log("Errore database in info_cliente.php: " . $e->getMessage());
    echo "<p>Errore durante il caricamento del cliente.</p></main></body></html>";
    exit;
}

function format_label($label) {
    $label = str_replace('_', ' ', $label);
    $label = str_replace('/', ' / ', $label);
    return ucwords($label);
}

$gruppi = [
    'Anagrafica' => ['cognome_ragione_sociale', 'Nome', 'Data_di_nascita_costituzione', 'Luogo_di_nascita', 'Cittadinanza', 'Stato_civile', 'Codice_fiscale', 'Partita_IVA', 'Qualifica', 'Soci_Amministratori', 'Titolare'],
    'Contatti' => ['Telefono', 'Mail', 'PEC', 'Scadenza_PEC', 'Rinnovo_Pec', 'User_Aruba', 'Password'],
    'Sedi' => ['Sede_Legale', 'Sede_Operativa', 'Residenza'],
    'Documenti' => ['Numero carta d’identità', 'Rilasciata_dal_Comune_di', 'Data_di_rilascio', 'Valida per l’espatrio'],
    'Fiscali' => ['Codice_ditta', 'Codice_ATECO', 'Descrizione_attivita', 'Camera_di_commercio', 'Dipendenti', 'Codice_inps', 'Codice_inps_2', 'Codice_inail', 'PAT', 'Cod_PIN_Inail', 'Cassa_Edile', 'Numero_Cassa_Professionisti', 'Contabilita', 'Liquidazione_IVA', 'SDI'],
    'Altro' => ['Colore', 'Inserito_gestionale', 'Inizio_rapporto', 'Fine_rapporto', 'Link_cartella']
];
?>

<style>
.client-header {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.client-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.client-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 1;
}

.client-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

.client-info {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}

.client-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #17a2b8, #6f42c1);
}

.info-section {
    margin-bottom: 2rem;
    border: 2px solid #e1e5e9;
    border-radius: 12px;
    padding: 1.5rem;
    background: #f8f9fa;
    position: relative;
    transition: all 0.3s ease;
}

.info-section:hover {
    border-color: #17a2b8;
    background: white;
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.1);
    transform: translateY(-2px);
}

.info-section-title {
    position: absolute;
    top: -12px;
    left: 20px;
    background: white;
    padding: 0 15px;
    font-size: 1.2rem;
    font-weight: 600;
    color: #17a2b8;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.info-field {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    transition: all 0.3s ease;
    position: relative;
    min-height: 70px;
}

.info-field:hover {
    border-color: #17a2b8;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.1);
}

.info-field.highlight {
    border-color: #ffc107;
    background: #fff3cd;
}

.info-field.empty {
    opacity: 0.6;
    border-style: dashed;
}

.info-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 500;
    word-wrap: break-word;
    min-height: 24px;
    display: flex;
    align-items: center;
}

.info-value.empty {
    color: #6c757d;
    font-style: italic;
}

.client-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 2rem 0;
    flex-wrap: wrap;
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
    min-width: 160px;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
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
    color: #17a2b8;
}

.client-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid #dee2e6;
    position: relative;
}

.client-summary::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #17a2b8, #6f42c1);
    border-radius: 12px 12px 0 0;
}

.client-summary h3 {
    margin: 0 0 1rem 0;
    color: #2c3e50;
    font-size: 1.5rem;
    font-weight: 600;
}

.client-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.client-summary-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    color: #495057;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-dismiss {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    opacity: 0.7;
    color: inherit;
}

.alert-dismiss:hover {
    opacity: 1;
}

@media (max-width: 768px) {
    .client-header h2 {
        font-size: 2rem;
    }
    
    .client-info {
        padding: 1.5rem;
        margin: 0 1rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .client-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}

@media print {
    .client-actions, .back-link {
        display: none;
    }
    
    .info-section {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    
    .client-header {
        background: #17a2b8 !important;
        color: white !important;
    }
}
</style>

<?php
// Messaggi di successo
if (isset($_GET['success'])) {
    echo '<div class="alert alert-success">✅ Cliente aggiornato con successo! <button class="alert-dismiss" onclick="this.parentElement.style.display=\'none\'">×</button></div>';
}

// Icone per le sezioni
$section_icons = [
    'Anagrafica' => '👤',
    'Contatti' => '📞',
    'Sedi' => '🏢',
    'Documenti' => '📄',
    'Fiscali' => '💼',
    'Note' => '📝',
    'Altro' => '📋'
];
?>

<div class="client-summary">
    <h3>
        <!-- Indicatore stato completezza -->
        <span class="status-indicator" 
              style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; 
                     background-color: <?= ($cliente['completo'] ?? 0) ? '#28a745' : '#dc3545' ?>; 
                     margin-right: 10px; box-shadow: 0 0 0 3px rgba(<?= ($cliente['completo'] ?? 0) ? '40, 167, 69' : '220, 53, 69' ?>, 0.2);"
              title="<?= ($cliente['completo'] ?? 0) ? 'Cliente completo - Tutti i dati sono stati inseriti' : 'Cliente incompleto - Mancano informazioni' ?>"></span>
        
        <?= htmlspecialchars($cliente['Cognome_Ragione_sociale'] ?? 'N/A') ?>
        
        <?php if ($cliente['completo'] ?? 0): ?>
            <small style="color: #28a745; font-weight: normal; margin-left: 10px;">✓ Completo</small>
        <?php else: ?>
            <small style="color: #dc3545; font-weight: normal; margin-left: 10px;">⚠ Incompleto</small>
        <?php endif; ?>
    </h3>
    <div class="client-summary-grid">
        <div class="client-summary-item">
            <strong>🆔 ID:</strong> <?= htmlspecialchars($cliente['id'] ?? 'N/A') ?>
        </div>
        <div class="client-summary-item">
            <strong>📧 Email:</strong> <?= htmlspecialchars($cliente['Mail'] ?? 'N/A') ?>
        </div>
        <div class="client-summary-item">
            <strong>📞 Telefono:</strong> <?= htmlspecialchars($cliente['Telefono'] ?? 'N/A') ?>
        </div>
        <div class="client-summary-item">
            <strong>🏛️ Cod. Fiscale:</strong> <?= htmlspecialchars($cliente['Codice_fiscale'] ?? 'N/A') ?>
        </div>
    </div>
</div>

<div class="client-info">
    <?php foreach ($gruppi as $titolo => $campi): ?>
        <div class="info-section">
            <div class="info-section-title">
                <?= $section_icons[$titolo] ?? '📋' ?> <?= htmlspecialchars($titolo) ?>
            </div>
            <div class="info-grid">
                <?php foreach ($campi as $campo): ?>
                    <?php 
                    // Gestione speciale per il campo note (caricamento da file)
                    if ($campo === 'note') {
                        $valore = $note_content;
                    } else {
                        $valore = $cliente[$campo] ?? '';
                    }
                    
                    $is_empty = empty($valore);
                    $is_important = in_array($campo, ['Codice_fiscale', 'Partita_IVA', 'Mail', 'Telefono']);
                    
                    // Se il cliente è completo, mostra solo i campi non vuoti
                    $cliente_completo = isset($cliente['completo']) && $cliente['completo'] == 1;
                    if ($cliente_completo && $is_empty) {
                        continue; // Salta questo campo se è vuoto e il cliente è completo
                    }
                    ?>
                    <div class="info-field <?= $is_empty ? 'empty' : '' ?> <?= $is_important && !$is_empty ? 'highlight' : '' ?>">
                        <div class="info-label"><?= htmlspecialchars(format_label($campo)) ?></div>
                        <div class="info-value <?= $is_empty ? 'empty' : '' ?>">
                            <?php if ($campo === 'note' && !$is_empty): ?>
                                <div style="background: #f8f9fa; border: 1px solid #e1e5e9; border-radius: 8px; padding: 12px; white-space: pre-wrap; font-family: Arial, sans-serif; max-height: 200px; overflow-y: auto;">
                                    <?= htmlspecialchars($valore) ?>
                                </div>
                            <?php else: ?>
                                <?= $is_empty ? 'Non specificato' : htmlspecialchars($valore) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="client-actions">
    <a href="modifica_cliente.php?id=<?= $cliente['id'] ?>" class="btn btn-primary">
        ✏️ Modifica Cliente
    </a>
    <button onclick="window.print()" class="btn btn-secondary">
        🖨️ Stampa PDF
    </button>
    <a href="clienti.php" class="btn btn-success">
        📋 Lista Clienti
    </a>
</div>

<a href="clienti.php" class="back-link">⬅️ Torna alla lista clienti</a>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Evidenzia campi importanti
    const importantFields = document.querySelectorAll('.info-field.highlight');
    importantFields.forEach(field => {
        field.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        field.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Smooth scroll per sezioni
    const sections = document.querySelectorAll('.info-section');
    sections.forEach((section, index) => {
        section.style.animationDelay = `${index * 0.1}s`;
        section.classList.add('fade-in');
    });
    
    // Copia contenuto al click
    const infoValues = document.querySelectorAll('.info-value:not(.empty)');
    infoValues.forEach(value => {
        value.addEventListener('click', function() {
            const text = this.textContent.trim();
            if (text && text !== 'Non specificato') {
                navigator.clipboard.writeText(text).then(() => {
                    // Feedback visivo
                    const original = this.innerHTML;
                    this.innerHTML = '✅ Copiato!';
                    this.style.color = '#28a745';
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.style.color = '';
                    }, 1000);
                }).catch(() => {
                    // Fallback per browser non supportati
                    const selection = window.getSelection();
                    const range = document.createRange();
                    range.selectNodeContents(this);
                    selection.removeAllRanges();
                    selection.addRange(range);
                });
            }
        });
        
        // Tooltip per indicare che è cliccabile
        value.setAttribute('title', 'Clicca per copiare');
        value.style.cursor = 'pointer';
    });
    
    // Filtro campi vuoti
    const toggleEmpty = document.createElement('button');
    toggleEmpty.innerHTML = '👁️ Nascondi campi vuoti';
    toggleEmpty.className = 'btn btn-secondary';
    toggleEmpty.style.position = 'fixed';
    toggleEmpty.style.bottom = '20px';
    toggleEmpty.style.right = '20px';
    toggleEmpty.style.zIndex = '1000';
    toggleEmpty.style.fontSize = '0.9rem';
    toggleEmpty.style.padding = '0.5rem 1rem';
    
    let hideEmpty = false;
    toggleEmpty.addEventListener('click', function() {
        hideEmpty = !hideEmpty;
        const emptyFields = document.querySelectorAll('.info-field.empty');
        emptyFields.forEach(field => {
            field.style.display = hideEmpty ? 'none' : 'block';
        });
        this.innerHTML = hideEmpty ? '👁️ Mostra campi vuoti' : '👁️ Nascondi campi vuoti';
    });
    
    document.body.appendChild(toggleEmpty);
    
    // Evidenzia sezione al click sul titolo
    const sectionTitles = document.querySelectorAll('.info-section-title');
    sectionTitles.forEach(title => {
        title.addEventListener('click', function() {
            const section = this.parentElement;
            section.classList.toggle('active');
            
            // Rimuovi active da altre sezioni
            sections.forEach(s => {
                if (s !== section) {
                    s.classList.remove('active');
                }
            });
        });
        title.style.cursor = 'pointer';
        title.setAttribute('title', 'Clicca per evidenziare questa sezione');
    });
});
</script>

<style>
.fade-in {
    animation: fadeIn 0.5s ease-in-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.info-section.active {
    border-color: #17a2b8;
    background: white;
    box-shadow: 0 8px 25px rgba(23, 162, 184, 0.2);
    transform: translateY(-5px);
}

.info-section.active .info-section-title {
    background: #17a2b8;
    color: white;
    transform: scale(1.05);
}

.info-value:not(.empty):hover {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 2px 4px;
    margin: -2px -4px;
}
</style>
