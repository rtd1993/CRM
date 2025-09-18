<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

include __DIR__ . '/includes/header.php';

// Recupero l'ID del cliente da modificare
$cliente_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cliente_id <= 0) {
    echo "<div class='alert alert-danger'>ID cliente non valido.</div></main></body></html>";
    exit();
}

// Recupero i dati del cliente
$stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo "<div class='alert alert-danger'>Cliente non trovato.</div></main></body></html>";
    exit();
}

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
    'Numero carta d\'identità' => 'text',
    'Rilasciata_dal_Comune_di' => 'text',
    'Data_di_rilascio' => 'date',
    'Valida per l\'espatrio' => 'checkbox',
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
    'Password' => 'text',
    'Scadenza_PEC' => 'date',
    'Rinnovo_Pec' => 'date',
    'SDI' => 'text',
    'note' => 'textarea_large',
    'completo' => 'checkbox'
];

// Gestione dell'aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updates = [];
        $values = [];
        
        foreach ($campi_db as $campo => $tipo) {
            if (isset($_POST[$campo])) {
                $valore = $_POST[$campo];
                
                // Salta il campo note (verrà gestito separatamente)
                if ($campo === 'note') {
                    continue;
                }
                
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
                
                $updates[] = "`$campo` = ?";
                $values[] = $valore;
            }
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE clienti SET " . implode(', ', $updates) . " WHERE id = ?";
            $values[] = $cliente_id;
            
            $stmt = $pdo->prepare($sql);
            
            // Eseguiamo con binding esplicito per gestire meglio NULL
            $result = $stmt->execute($values);
            
            if ($result) {
                $success_message = "Cliente aggiornato con successo!";

                // Gestione file note e creazione/aggiornamento cartella secondo lo standard
                if (isset($_POST['note'])) {
                    $note_content = trim($_POST['note']);

                    // Standard id_Cognome.Nome
                    $cognome_clean = !empty($cliente['Cognome_Ragione_sociale']) ? ucfirst(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Cognome_Ragione_sociale'])) : '';
                    $nome_clean = !empty($cliente['Nome']) ? ucfirst(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Nome'])) : '';
                    $cliente_folder = $cliente['id'] . '_' . $cognome_clean;
                    if (!empty($nome_clean)) {
                        $cliente_folder .= '.' . $nome_clean;
                    }
                    $cartella_path = __DIR__ . '/local_drive/' . $cliente_folder;
                    $note_file = $cartella_path . '/note_' . $cliente['id'] . '.txt';

                    // Rimuovi eventuale vecchia cartella se esiste e diversa
                    if (!empty($cliente['link_cartella']) && $cliente['link_cartella'] !== $cliente_folder) {
                        $old_path = __DIR__ . '/local_drive/' . $cliente['link_cartella'];
                        if (is_dir($old_path)) {
                            // Rimuovi solo se vuota per sicurezza
                            @rmdir($old_path);
                        }
                    }

                    // Crea la cartella se non esiste
                    if (!is_dir($cartella_path)) {
                        mkdir($cartella_path, 0755, true);
                    }

                    // Aggiorna il campo link_cartella nel database
                    $stmt_update = $pdo->prepare("UPDATE clienti SET link_cartella = ? WHERE id = ?");
                    $stmt_update->execute([$cliente_folder, $cliente['id']]);

                    if (!empty($note_content)) {
                        file_put_contents($note_file, $note_content);
                    } else {
                        // Se le note sono vuote, elimina il file se esiste
                        if (file_exists($note_file)) {
                            unlink($note_file);
                        }
                    }
                }
                
                // Redirect dopo 2 secondi per mostrare il messaggio
                header("refresh:2;url=info_cliente.php?id=$cliente_id");
                
                // Ricarico i dati aggiornati
                $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
                $stmt->execute([$cliente_id]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                throw new Exception("Errore nell'esecuzione dell'aggiornamento nel database");
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Errore nell'aggiornamento: " . $e->getMessage();
    }
}

// Raggruppo i campi per sezioni
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
        'Numero carta d\'identità', 'Rilasciata_dal_Comune_di', 'Data_di_rilascio', 
        'Valida per l\'espatrio', 'Stato_civile', 'Data_di_scadenza'
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
.modifica-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    position: relative;
}

.modifica-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.modifica-header p {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

        .changed {
            border-color: #ffc107 !important;
            background: #fff3cd !important;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            border-top: 2px solid #667eea;
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

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            z-index: 1000;
            display: none;
            animation: slideInRight 0.3s ease-out;
        }

        .notification.success {
            background: #28a745;
        }

        .notification.error {
            background: #dc3545;
        }

        .notification.info {
            background: #17a2b8;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            padding: 0 30px;
        }

        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-item i {
            font-size: 2em;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-item .number {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
        }

        .stat-item .label {
            color: #666;
            font-size: 0.9em;
        }

        .submit-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .form-content {
            padding: 30px;
        }

        @media (max-width: 768px) {
            .modifica-header {
                padding: 20px;
            }
            
            .modifica-header h2 {
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

<div class="modifica-header">
    
    <h2><i class="fas fa-user-edit"></i> Modifica Cliente</h2>
    <p>ID: <?php echo htmlspecialchars($cliente['id']); ?> - <?php echo htmlspecialchars($cliente['Cognome_Ragione_sociale'] ?? 'N/A'); ?></p>
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

<div class="stats">
    <div class="stat-item">
        <i class="fas fa-calendar-alt"></i>
        <div class="number"><?php echo $cliente['Inizio_rapporto'] ? date('d/m/Y', strtotime($cliente['Inizio_rapporto'])) : 'N/A'; ?></div>
        <div class="label">Inizio Rapporto</div>
    </div>
    <div class="stat-item">
        <i class="fas fa-envelope"></i>
        <div class="number"><?php echo $cliente['Mail'] ? 'Sì' : 'No'; ?></div>
        <div class="label">Email</div>
    </div>
    <div class="stat-item">
        <i class="fas fa-phone"></i>
        <div class="number"><?php echo $cliente['Telefono'] ? 'Sì' : 'No'; ?></div>
        <div class="label">Telefono</div>
    </div>
    <div class="stat-item">
        <i class="fas fa-certificate"></i>
        <div class="number"><?php echo $cliente['PEC'] ? 'Sì' : 'No'; ?></div>
        <div class="label">PEC</div>
    </div>
</div>

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
                                        <label for="<?php echo htmlspecialchars($campo); ?>">
                                            <?php echo htmlspecialchars($campo); ?>
                                        </label>
                                        
                                        <?php
                                        $tipo = $campi_db[$campo];
                                        
                                        // Gestione speciale per il campo note (caricamento da file con nuovo formato)
                                        if ($campo === 'note') {
                                            // Crea nome cartella con nuovo formato id_cognome.nome
                                            $cliente_folder = $cliente['id'] . '_' . 
                                                            strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Cognome_Ragione_sociale']));
                                            
                                            // Aggiungi il nome se presente
                                            if (!empty($cliente['Nome'])) {
                                                $nome_clean = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $cliente['Nome']));
                                                $cliente_folder .= '.' . $nome_clean;
                                            }
                                            
                                            $note_file = __DIR__ . '/local_drive/' . $cliente_folder . '/note_' . $cliente['id'] . '.txt';
                                            $valore = file_exists($note_file) ? file_get_contents($note_file) : '';
                                        } else {
                                            $valore = $cliente[$campo] ?? '';
                                        }
                                        
                                        $input_id = htmlspecialchars($campo);
                                        $input_name = htmlspecialchars($campo);
                                        ?>
                                        
                                        <?php if ($tipo === 'textarea'): ?>
                                            <textarea 
                                                id="<?php echo $input_id; ?>" 
                                                name="<?php echo $input_name; ?>" 
                                                rows="3"
                                                onchange="markChanged(this)"
                                            ><?php echo htmlspecialchars($valore); ?></textarea>
                                        <?php elseif ($tipo === 'textarea_large'): ?>
                                            <textarea 
                                                id="<?php echo $input_id; ?>" 
                                                name="<?php echo $input_name; ?>" 
                                                rows="8"
                                                style="width: 100%; min-height: 150px; font-family: Arial, sans-serif; font-size: 14px; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; resize: vertical;"
                                                placeholder="Inserisci qui le note relative al cliente..."
                                                onchange="markChanged(this)"
                                            ><?php echo htmlspecialchars($valore); ?></textarea>
                                        <?php elseif ($tipo === 'checkbox'): ?>
                                            <div class="checkbox-group">
                                                <input 
                                                    type="checkbox" 
                                                    id="<?php echo $input_id; ?>" 
                                                    name="<?php echo $input_name; ?>" 
                                                    <?php echo $valore ? 'checked' : ''; ?>
                                                    onchange="markChanged(this)"
                                                >
                                                <label for="<?php echo $input_id; ?>">Attivo</label>
                                            </div>
                                        <?php else: ?>
                                            <input 
                                                type="<?php echo $tipo; ?>" 
                                                id="<?php echo $input_id; ?>" 
                                                name="<?php echo $input_name; ?>" 
                                                value="<?php echo htmlspecialchars($valore); ?>"
                                                onchange="markChanged(this)"
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
                        <i class="fas fa-check"></i> Aggiorna Cliente
                        <span class="loader">
                            <div class="spinner"></div>
                        </span>
                    </button>
                    <a href="info_cliente.php?id=<?php echo $cliente_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                </div>
            </form>

            <!-- Sezione Gestione Cartella -->
            <div class="section" style="margin-top: 30px;">
                <div class="section-header" onclick="toggleSection(this)">
                    <span><i class="fas fa-folder"></i> Gestione Cartella Cliente</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="section-content">
                    <div class="form-group">
                        <label>Cartella Local Drive</label>
                        <?php
                        $link_cartella = $cliente['link_cartella'] ?? '';
                        $cartella_path = !empty($link_cartella) ? __DIR__ . '/local_drive/' . $link_cartella : '';
                        $cartella_esiste = !empty($link_cartella) && is_dir($cartella_path);
                        ?>
                        
                        <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                            <?php if ($cartella_esiste): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.2em;"></i>
                                    <span style="color: #28a745; font-weight: bold;">Cartella trovata</span>
                                    <a href="drive.php?path=<?php echo urlencode($link_cartella); ?>" 
                                       class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9em;">
                                        <i class="fas fa-folder-open"></i> Apri Cartella
                                    </a>
                                </div>
                            <?php elseif (!empty($link_cartella)): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-exclamation-triangle" style="color: #ffc107; font-size: 1.2em;"></i>
                                    <span style="color: #ffc107; font-weight: bold;">Cartella non trovata</span>
                                    <button type="button" 
                                            onclick="creaCartella('<?php echo htmlspecialchars($link_cartella); ?>')" 
                                            class="btn btn-primary" style="padding: 8px 15px; font-size: 0.9em;">
                                        <i class="fas fa-plus"></i> Crea Cartella
                                    </button>
                                </div>
                            <?php else: ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-info-circle" style="color: #17a2b8; font-size: 1.2em;"></i>
                                    <span style="color: #17a2b8; font-weight: bold;">Cartella non presente</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($codice_fiscale)): ?>
                        <?php if (!empty($link_cartella)): ?>
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em; color: #666;">
                                <strong>Percorso:</strong> <?php echo htmlspecialchars(__DIR__ . '/local_drive/' . $link_cartella); ?>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 0.9em; color: #666;">
                                <strong>Percorso:</strong> non presente
                            </div>
                        <?php endif; ?>
            </div>
</div>

<div id="notification" class="notification"></div>

    <script>
        // Variabili globali
        let originalValues = {};

        // Inizializzazione
        document.addEventListener('DOMContentLoaded', function() {
            // Salvo i valori originali
            const form = document.getElementById('clienteForm');
            const formData = new FormData(form);
            for (let [key, value] of formData.entries()) {
                originalValues[key] = value;
            }

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

        // Marca campo come modificato
        function markChanged(element) {
            const originalValue = originalValues[element.name] || '';
            const currentValue = element.type === 'checkbox' ? (element.checked ? 'on' : '') : element.value;
            
            if (currentValue !== originalValue) {
                element.classList.add('changed');
            } else {
                element.classList.remove('changed');
            }
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

        // Notifiche
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Gestione submit form
        document.getElementById('clienteForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const loader = submitBtn.querySelector('.loader');
            
            submitBtn.disabled = true;
            loader.style.display = 'inline-block';
        });

        // Avviso se si sta lasciando la pagina con modifiche non salvate
        window.addEventListener('beforeunload', function(e) {
            const changedElements = document.querySelectorAll('.changed');
            if (changedElements.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
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

        // Funzione per creare la cartella
        function creaCartella(codiceFiscale) {
            if (!codiceFiscale) {
                showNotification('Codice fiscale non valido', 'error');
                return;
            }

            const btn = event.target;
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creazione...';

            fetch('api_crea_cartella.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'codice_fiscale=' + encodeURIComponent(codiceFiscale) + '&cliente_id=' + <?php echo $cliente_id; ?>
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Cartella creata con successo!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Errore: ' + (data.message || 'Impossibile creare la cartella'), 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Errore di connessione', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    </script>

</main>
</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
