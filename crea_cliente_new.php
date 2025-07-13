<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';

// Definisco i campi della tabella clienti in base alla struttura del DB (stesso array di modifica_cliente.php)
$campi_db = [
    'Inizio rapporto' => 'date',
    'Fine rapporto' => 'date',
    'Inserito gestionale' => 'checkbox',
    'Codice ditta' => 'text',
    'Colore' => 'color',
    'Cognome/Ragione sociale' => 'text',
    'Nome' => 'text',
    'Codice fiscale' => 'text',
    'Partita IVA' => 'text',
    'Qualifica' => 'text',
    'Soci Amministratori' => 'text',
    'Sede Legale' => 'textarea',
    'Sede Operativa' => 'textarea',
    'Data di nascita/costituzione' => 'date',
    'Luogo di nascita' => 'text',
    'Cittadinanza' => 'text',
    'Residenza' => 'textarea',
    'Numero carta d\'identità' => 'text',
    'Rilasciata dal Comune di' => 'text',
    'Data di rilascio' => 'date',
    'Valida per l\'espatrio' => 'checkbox',
    'Stato civile' => 'text',
    'Data di scadenza' => 'date',
    'Descrizione attivita' => 'textarea',
    'Codice ATECO' => 'text',
    'Camera di commercio' => 'text',
    'Dipendenti' => 'number',
    'Codice inps' => 'text',
    'Titolare' => 'text',
    'Codice inps_2' => 'text',
    'Codice inail' => 'text',
    'PAT' => 'text',
    'Cod.PIN Inail' => 'text',
    'Cassa Edile' => 'text',
    'Numero Cassa Professionisti' => 'text',
    'Contabilita' => 'text',
    'Liquidazione IVA' => 'text',
    'Telefono' => 'tel',
    'Mail' => 'email',
    'PEC' => 'email',
    'User Aruba' => 'text',
    'Password' => 'password',
    'Scadenza PEC' => 'date',
    'Rinnovo Pec' => 'date',
    'SDI' => 'text',
    'Link cartella' => 'url'
];

// Gestione dell'inserimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [];
        $values = [];
        
        foreach ($campi_db as $campo => $tipo) {
            if (isset($_POST[$campo])) {
                $valore = $_POST[$campo];
                
                // Validazione in base al tipo
                if ($tipo === 'email' && !empty($valore) && !filter_var($valore, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email non valida per il campo: $campo");
                }
                
                if ($tipo === 'url' && !empty($valore) && !filter_var($valore, FILTER_VALIDATE_URL)) {
                    throw new Exception("URL non valido per il campo: $campo");
                }
                
                // Gestione dei tipi di dato
                if ($tipo === 'checkbox') {
                    $valore = $valore === 'on' ? 1 : 0;
                } elseif ($tipo === 'number') {
                    // Per i campi numerici, converti stringa vuota in NULL
                    if ($valore === '' || $valore === null || trim($valore) === '') {
                        $valore = null;
                    } else {
                        $valore = is_numeric($valore) ? intval($valore) : null;
                    }
                } elseif ($tipo === 'date') {
                    // Per le date, converti stringa vuota in NULL
                    if ($valore === '' || $valore === null) {
                        $valore = null;
                    } else {
                        $date = DateTime::createFromFormat('Y-m-d', $valore);
                        if (!$date) {
                            throw new Exception("Data non valida per il campo: $campo");
                        }
                    }
                } else {
                    // Per i campi di testo, mantieni stringa vuota se fornita
                    if ($valore === null) {
                        $valore = '';
                    }
                }
                
                $updates[] = "`$campo`";
                $values[] = $valore;
            }
        }
        
        // Validazione obbligatoria per alcuni campi
        $codice_fiscale = $_POST['Codice fiscale'] ?? '';
        $cognome = $_POST['Cognome/Ragione sociale'] ?? '';
        
        if (empty($codice_fiscale)) {
            throw new Exception("Il Codice Fiscale è obbligatorio");
        }
        
        if (empty($cognome)) {
            throw new Exception("Cognome/Ragione sociale è obbligatorio");
        }
        
        // Creazione cartella e link
        $codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $codice_fiscale);
        $cartella_path = '/var/www/CRM/local_drive/' . $codice_fiscale_clean;
        $link_cartella = 'drive.php?path=' . urlencode($codice_fiscale_clean);
        
        // Crea la cartella se non esiste
        if (!is_dir($cartella_path)) {
            if (!mkdir($cartella_path, 0755, true)) {
                throw new Exception("Impossibile creare la cartella per il cliente");
            }
            
            // Crea file di benvenuto nella cartella
            $welcome_file = $cartella_path . '/README.txt';
            $welcome_content = "Cartella cliente: " . $codice_fiscale . "\n";
            $welcome_content .= "Cognome/Ragione sociale: " . $cognome . "\n";
            $welcome_content .= "Creata il: " . date('d/m/Y H:i:s') . "\n\n";
            $welcome_content .= "Questa cartella contiene i file relativi al cliente.\n";
            
            file_put_contents($welcome_file, $welcome_content);
        }
        
        // Aggiorna il valore del Link cartella
        $idx = array_search('Link cartella', $updates);
        if ($idx !== false) {
            $values[$idx] = $link_cartella;
        } else {
            $updates[] = "`Link cartella`";
            $values[] = $link_cartella;
        }
        
        if (!empty($updates)) {
            $sql = "INSERT INTO clienti (" . implode(', ', $updates) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                $nuovo_cliente_id = $pdo->lastInsertId();
                $success_message = "Cliente creato con successo!";
                
                // Log dell'operazione
                error_log("Nuovo cliente creato ID: $nuovo_cliente_id, Cartella: $cartella_path");
                
                // Redirect dopo 2 secondi per mostrare il messaggio
                header("refresh:2;url=info_cliente.php?id=$nuovo_cliente_id");
            } else {
                throw new Exception("Errore nell'inserimento nel database");
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Errore nella creazione: " . $e->getMessage();
    }
}

// Raggruppo i campi per sezioni (stesso raggruppamento di modifica_cliente.php)
$sezioni = [
    'Dati Generali' => [
        'Inizio rapporto', 'Fine rapporto', 'Inserito gestionale', 'Codice ditta', 'Colore',
        'Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA', 'Qualifica'
    ],
    'Soci e Sedi' => [
        'Soci Amministratori', 'Sede Legale', 'Sede Operativa'
    ],
    'Dati Anagrafici' => [
        'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Residenza'
    ],
    'Documento di Identità' => [
        'Numero carta d\'identità', 'Rilasciata dal Comune di', 'Data di rilascio', 
        'Valida per l\'espatrio', 'Stato civile', 'Data di scadenza'
    ],
    'Attività' => [
        'Descrizione attivita', 'Codice ATECO', 'Camera di commercio', 'Dipendenti'
    ],
    'Codici Fiscali' => [
        'Codice inps', 'Titolare', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail',
        'Cassa Edile', 'Numero Cassa Professionisti'
    ],
    'Contabilità' => [
        'Contabilita', 'Liquidazione IVA'
    ],
    'Contatti' => [
        'Telefono', 'Mail', 'PEC', 'User Aruba', 'Password', 'Scadenza PEC', 'Rinnovo Pec', 'SDI'
    ]
];
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.crea-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    position: relative;
}

.crea-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.crea-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.back-btn {
    position: absolute;
    left: 30px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 1.1em;
}

.back-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-50%) scale(1.05);
    color: white;
    text-decoration: none;
}

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: bold;
            animation: fadeIn 0.5s ease-out;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section {
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .section:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .section-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }

        .section-header:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        }

        .section-header i {
            transition: transform 0.3s ease;
        }

        .section.collapsed .section-header i {
            transform: rotate(-90deg);
        }

        .section-content {
            padding: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            max-height: 1000px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .section.collapsed .section-content {
            max-height: 0;
            padding: 0 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group label.required::after {
            content: " *";
            color: #dc3545;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
            transform: translateY(-1px);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 8px;
        }

        .checkbox-group label {
            margin-bottom: 0;
            margin-left: 8px;
        }

        .form-group input[type="color"] {
            width: 60px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .submit-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .loader {
            display: none;
            margin-left: 10px;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #28a745;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .form-content {
            padding: 30px;
        }

        @media (max-width: 768px) {
            .crea-header {
                padding: 20px;
            }
            
            .crea-header h2 {
                font-size: 2em;
            }
            
            .back-btn {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                margin-bottom: 15px;
                display: block;
                width: fit-content;
            }
            
            .section-content {
                grid-template-columns: 1fr;
            }
            
            .form-content {
                padding: 20px;
            }
        }
    </style>

<div class="crea-header">
    <a href="clienti.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Indietro
    </a>
    <h2><i class="fas fa-user-plus"></i> Crea Nuovo Cliente</h2>
    <p>Inserisci i dati del nuovo cliente nel sistema</p>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="form-container">
    <div class="form-content">
        <form method="POST" id="clienteForm">
            <?php foreach ($sezioni as $nome_sezione => $campi_sezione): ?>
                <div class="section">
                    <div class="section-header" onclick="toggleSection(this)">
                        <span><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($nome_sezione); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="section-content">
                        <?php foreach ($campi_sezione as $campo): ?>
                            <?php if (isset($campi_db[$campo])): ?>
                                <div class="form-group">
                                    <?php
                                    $is_required = in_array($campo, ['Codice fiscale', 'Cognome/Ragione sociale']);
                                    ?>
                                    <label for="<?php echo htmlspecialchars($campo); ?>" <?php echo $is_required ? 'class="required"' : ''; ?>>
                                        <?php echo htmlspecialchars($campo); ?>
                                    </label>
                                    
                                    <?php
                                    $tipo = $campi_db[$campo];
                                    $input_id = htmlspecialchars($campo);
                                    $input_name = htmlspecialchars($campo);
                                    ?>
                                    
                                    <?php if ($tipo === 'textarea'): ?>
                                        <textarea 
                                            id="<?php echo $input_id; ?>" 
                                            name="<?php echo $input_name; ?>" 
                                            rows="3"
                                            <?php echo $is_required ? 'required' : ''; ?>
                                        ></textarea>
                                    <?php elseif ($tipo === 'checkbox'): ?>
                                        <div class="checkbox-group">
                                            <input 
                                                type="checkbox" 
                                                id="<?php echo $input_id; ?>" 
                                                name="<?php echo $input_name; ?>"
                                            >
                                            <label for="<?php echo $input_id; ?>">Attivo</label>
                                        </div>
                                    <?php else: ?>
                                        <input 
                                            type="<?php echo $tipo; ?>" 
                                            id="<?php echo $input_id; ?>" 
                                            name="<?php echo $input_name; ?>"
                                            <?php echo $is_required ? 'required' : ''; ?>
                                            <?php if ($tipo === 'tel'): ?>
                                                oninput="formatPhone(this)"
                                            <?php elseif ($tipo === 'text' && strpos($campo, 'Codice fiscale') !== false): ?>
                                                oninput="formatCodiceFiscale(this)"
                                            <?php elseif ($tipo === 'text' && strpos($campo, 'Partita IVA') !== false): ?>
                                                oninput="formatPartitaIVA(this)"
                                            <?php endif; ?>
                                        >
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="submit-section">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crea Cliente
                    <span class="loader">
                        <div class="spinner"></div>
                    </span>
                </button>
                <a href="clienti.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
</div>

    <script>
        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus sul primo campo
            const firstInput = document.querySelector('input[type="text"], input[type="email"], textarea');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Toggle sezioni
        function toggleSection(header) {
            const section = header.parentElement;
            section.classList.toggle('collapsed');
        }

        // Formattazione telefono
        function formatPhone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 6) {
                    value = value.slice(0, 3) + ' ' + value.slice(3);
                } else if (value.length <= 10) {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
                } else {
                    value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
                }
            }
            input.value = value;
        }

        // Formattazione codice fiscale
        function formatCodiceFiscale(input) {
            let value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (value.length > 16) {
                value = value.slice(0, 16);
            }
            input.value = value;
        }

        // Formattazione partita IVA
        function formatPartitaIVA(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            input.value = value;
        }

        // Gestione submit form
        document.getElementById('clienteForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const loader = submitBtn.querySelector('.loader');
            
            submitBtn.disabled = true;
            loader.style.display = 'inline-block';
        });

        // Shortcuts da tastiera
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter per inviare form
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('clienteForm').submit();
            }
        });

        // Collassa tutte le sezioni tranne la prima
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                if (index > 0) {
                    section.classList.add('collapsed');
                }
            });
        });
    </script>

</main>
</body>
</html>
