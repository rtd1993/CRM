<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';

// Definisco i campi della tabella clienti in base alla struttura del DB
$campi_db = [
    'Inizio_rapporto' => 'date',
    'Fine_rapporto' => 'date',
    'Inserito_gestionale' => 'checkbox',
    'Codice_ditta' => 'text',
    'Colore' => 'color',
    'Cognome_Ragione_sociale' => 'text',
    'Nome' => 'text',
    'Codice_fiscale' => 'text',
    'Partita_IVA' => 'text',
    'Qualifica' => 'text',
    'Soci_Amministratori' => 'text',
    'Sede_Legale' => 'textarea',
    'Sede_Operativa' => 'textarea',
    'Data_di_nascita_costituzione' => 'date',
    'Luogo_di_nascita' => 'text',
    'Cittadinanza' => 'text',
    'Residenza' => 'textarea',
    'Numero_carta_d_identità' => 'text',
    'Rilasciata_dal_Comune_di' => 'text',
    'Data_di_rilascio' => 'date',
    'Valida_per_espatrio' => 'checkbox',
    'Stato_civile' => 'text',
    'Data_di_scadenza' => 'date',
    'Descrizione_attivita' => 'textarea',
    'Codice_ATECO' => 'text',
    'Camera_di_commercio' => 'text',
    'Dipendenti' => 'number',
    'Codice_inps' => 'text',
    'Titolare' => 'text',
    'Codice_inps_2' => 'text',
    'Codice_inail' => 'text',
    'PAT' => 'text',
    'Cod_PIN_Inail' => 'text',
    'Cassa_Edile' => 'text',
    'Numero_Cassa_Professionisti' => 'text',
    'Contabilita' => 'text',
    'Liquidazione_IVA' => 'text',
    'Telefono' => 'tel',
    'Mail' => 'email',
    'PEC' => 'email',
    'User_Aruba' => 'text',
    'Password' => 'password',
    'Scadenza_PEC' => 'date',
    'Rinnovo_Pec' => 'date',
    'SDI' => 'text',
    'Link_cartella' => 'url',
    'note' => 'textarea_large',
    'completo' => 'checkbox'
];

// Gestione dell'inserimento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [];
        $values = [];
        
        // Processo tutti i campi definiti nell'array $campi_db
        foreach ($campi_db as $campo => $tipo) {
            if (!isset($_POST[$campo])) {
                continue; // Salta se il campo non è presente nel POST
            }
            
            $valore = $_POST[$campo];
            
            // Salta il campo note e Link_cartella (verranno gestiti separatamente)
            if ($campo === 'note' || $campo === 'Link_cartella') {
                continue;
            }
            
            // Gestione dei tipi di dato
            if ($tipo === 'checkbox') {
                $valore = $valore === 'on' ? 1 : 0;
            } elseif ($tipo === 'number') {
                if ($valore === '' || $valore === null) {
                    $valore = null;
                } else {
                    $valore = is_numeric($valore) ? intval($valore) : null;
                }
            } elseif ($tipo === 'date') {
                if ($valore === '' || $valore === null) {
                    $valore = null;
                } else {
                    $date = DateTime::createFromFormat('Y-m-d', $valore);
                    if (!$date) {
                        $valore = null;
                    }
                }
            } elseif ($tipo === 'email' && !empty($valore) && !filter_var($valore, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Email non valida per il campo: $campo");
            }
            
            // Aggiungi solo se non è vuoto (eccetto per campi obbligatori)
            if ($valore !== '' && $valore !== null || $campo === 'Codice fiscale') {
                $updates[] = "`$campo`";
                $values[] = $valore;
            }
        }
        
                // Validazione base: almeno Cognome/Ragione sociale è necessario
        $codice_fiscale = $_POST['Codice_fiscale'] ?? '';
        $cognome = $_POST['Cognome_Ragione_sociale'] ?? '';
        $nome = $_POST['Nome'] ?? '';
        
        if (empty($cognome) || trim($cognome) === '') {
            throw new Exception("Il campo Cognome/Ragione sociale è obbligatorio.");
        }
        
        if (!empty($updates)) {
            // Prima inserisce il record per ottenere l'ID
            $sql = "INSERT INTO clienti (" . implode(', ', $updates) . ") VALUES (" . implode(', ', array_fill(0, count($values), '?')) . ")";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                $nuovo_cliente_id = $pdo->lastInsertId();
                
                // Creazione cartella con formato ID_COGNOME.NOME
                $cognome_clean = preg_replace('/[^A-Za-z0-9]/', '', $cognome);
                $nome_clean = !empty($nome) ? preg_replace('/[^A-Za-z0-9]/', '', $nome) : '';
                
                // Formato cartella: ID_COGNOME.NOME (se nome è vuoto, solo ID_COGNOME)
                if (!empty($nome_clean)) {
                    $folder_name = $nuovo_cliente_id . '_' . $cognome_clean . '.' . $nome_clean;
                } else {
                    $folder_name = $nuovo_cliente_id . '_' . $cognome_clean;
                }
                
                $cartella_path = __DIR__ . '/local_drive/' . $folder_name;
                $link_cartella = 'drive.php?path=' . urlencode($folder_name);
                
                // Crea la cartella se non esiste
                if (!is_dir($cartella_path)) {
                    if (!mkdir($cartella_path, 0755, true)) {
                        throw new Exception("Impossibile creare la cartella per il cliente");
                    }
                    
                    // Crea file di benvenuto nella cartella
                    $welcome_file = $cartella_path . '/README.txt';
                    $welcome_content = "Cartella cliente ID: " . $nuovo_cliente_id . "\n";
                    $welcome_content .= "Cognome/Ragione sociale: " . $cognome . "\n";
                    if (!empty($nome)) {
                        $welcome_content .= "Nome: " . $nome . "\n";
                    }
                    if (!empty($codice_fiscale)) {
                        $welcome_content .= "Codice fiscale: " . $codice_fiscale . "\n";
                    }
                    $welcome_content .= "Creata il: " . date('d/m/Y H:i:s') . "\n\n";
                    $welcome_content .= "Questa cartella contiene i file relativi al cliente.\n";
                    
                    file_put_contents($welcome_file, $welcome_content);
                }
                
                // Gestione file note se presente
                if (isset($_POST['note']) && !empty(trim($_POST['note']))) {
                    $note_content = trim($_POST['note']);
                    $note_file = $cartella_path . '/note_' . $folder_name . '.txt';
                    file_put_contents($note_file, $note_content);
                }
                
                // Aggiorna il record con il link alla cartella
                $update_sql = "UPDATE clienti SET Link_cartella = ? WHERE id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute([$link_cartella, $nuovo_cliente_id]);
                
                $success_message = "Cliente creato con successo!";
                
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

// Raggruppo i campi per sezioni (nomi aggiornati)
$sezioni = [
    'Dati Generali' => [
        'Inizio_rapporto', 'Fine_rapporto', 'Inserito_gestionale', 'Codice_ditta', 'Colore',
        'Cognome_Ragione_sociale', 'Nome', 'Codice_fiscale', 'Partita_IVA', 'Qualifica', 'completo'
    ],
    'Soci e Sedi' => [
        'Soci_Amministratori', 'Sede_Legale', 'Sede_Operativa'
    ],
    'Dati Anagrafici' => [
        'Data_di_nascita_costituzione', 'Luogo_di_nascita', 'Cittadinanza', 'Residenza'
    ],
    'Documento di Identità' => [
        'Numero_carta_d_identità', 'Rilasciata_dal_Comune_di', 'Data_di_rilascio', 
        'Valida_per_espatrio', 'Stato_civile', 'Data_di_scadenza'
    ],
    'Attività' => [
        'Descrizione_attivita', 'Codice_ATECO', 'Camera_di_commercio', 'Dipendenti'
    ],
    'Codici Fiscali' => [
        'Codice_inps', 'Titolare', 'Codice_inps_2', 'Codice_inail', 'PAT', 'Cod_PIN_Inail',
        'Cassa_Edile', 'Numero_Cassa_Professionisti'
    ],
    'Contabilità' => [
        'Contabilita', 'Liquidazione_IVA'
    ],
    'Contatti' => [
        'Telefono', 'Mail', 'PEC', 'User_Aruba', 'Password', 'Scadenza_PEC', 'Rinnovo_Pec', 'SDI'
    ],
    'Note' => [
        'note'
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
                                    $is_required = in_array($campo, []); // Nessun campo obbligatorio
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
                                    <?php elseif ($tipo === 'textarea_large'): ?>
                                        <textarea 
                                            id="<?php echo $input_id; ?>" 
                                            name="<?php echo $input_name; ?>" 
                                            rows="8"
                                            style="width: 100%; min-height: 150px; font-family: Arial, sans-serif; font-size: 14px; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; resize: vertical;"
                                            placeholder="Inserisci qui le note relative al cliente..."
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
                                            type="<?php echo $tipo === 'password' ? 'text' : $tipo; ?>" 
                                            id="<?php echo $input_id; ?>" 
                                            name="<?php echo $input_name; ?>"
                                            <?php echo $is_required ? 'required' : ''; ?>
                                            <?php if ($tipo === 'tel'): ?>
                                                oninput="formatPhone(this)"
                                            <?php elseif ($tipo === 'text' && strpos($campo, 'Codice_fiscale') !== false): ?>
                                                oninput="formatCodiceFiscale(this)"
                                            <?php elseif ($tipo === 'text' && strpos($campo, 'Partita_IVA') !== false): ?>
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
            // Validazione lato client del codice fiscale
            const codiceFiscale = document.querySelector('input[name="Codice_fiscale"]');
            if (!codiceFiscale || !codiceFiscale.value.trim()) {
                e.preventDefault();
                alert('Il Codice Fiscale è obbligatorio!');
                if (codiceFiscale) {
                    codiceFiscale.focus();
                    codiceFiscale.style.borderColor = '#dc3545';
                }
                return;
            }
            
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
