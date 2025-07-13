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

// Gestione dell'aggiornamento
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
    ],
    'Altro' => [
        'Link cartella'
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
    <a href="info_cliente.php?id=<?php echo $cliente_id; ?>" class="back-btn">
        <i class="fas fa-arrow-left"></i> Indietro
    </a>
    <h2><i class="fas fa-user-edit"></i> Modifica Cliente</h2>
    <p>ID: <?php echo htmlspecialchars($cliente['id']); ?> - <?php echo htmlspecialchars($cliente['Cognome/Ragione sociale'] ?? 'N/A'); ?></p>
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
        <div class="number"><?php echo $cliente['Inizio rapporto'] ? date('d/m/Y', strtotime($cliente['Inizio rapporto'])) : 'N/A'; ?></div>
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
                                        $valore = $cliente[$campo] ?? '';
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
    </script>

</main>
</body>
</html>
