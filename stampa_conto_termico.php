<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Verifica se è stato fornito l'ID della pratica Conto Termico
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID pratica Conto Termico non valido');
}

$conto_termico_id = (int)$_GET['id'];

try {
    // Query per recuperare tutti i dati della pratica Conto Termico con informazioni cliente
    $sql = "SELECT ct.*, 
                   c.id as cliente_id,
                   c.Cognome_Ragione_sociale,
                   c.Nome,
                   c.Codice_fiscale,
                   c.Partita_iva,
                   c.Indirizzo,
                   c.CAP,
                   c.Citta,
                   c.Provincia,
                   c.Telefono,
                   c.Email
            FROM conto_termico ct 
            LEFT JOIN clienti c ON ct.cliente_id = c.id 
            WHERE ct.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$conto_termico_id]);
    $pratica = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pratica) {
        die('Pratica Conto Termico non trovata');
    }
    
} catch (PDOException $e) {
    die('Errore nel recupero della pratica: ' . $e->getMessage());
}

// Funzione per formattare i valori booleani
function formatBoolean($value) {
    if ($value === '1' || $value === 1 || $value === true) {
        return 'Sì';
    } elseif ($value === '0' || $value === 0 || $value === false) {
        return 'No';
    } else {
        return 'Non specificato';
    }
}

// Funzione per formattare le date
function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return 'Non specificata';
    }
    return date('d/m/Y', strtotime($date));
}

// Funzione per formattare gli importi
function formatImporto($importo) {
    if (empty($importo) || $importo == 0) {
        return 'Non specificato';
    }
    return '€ ' . number_format($importo, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa Pratica Conto Termico #<?= $pratica['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container-fluid { max-width: 100%; margin: 0; padding: 0; }
        }
        
        .header-section {
            border-bottom: 2px solid #28a745;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        
        .info-section {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .info-section h5 {
            color: #28a745;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .label {
            font-weight: bold;
            color: #495057;
        }
        
        .value {
            margin-left: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .status-completata { background-color: #d4edda; color: #155724; }
        .status-in-lavorazione { background-color: #fff3cd; color: #856404; }
        .status-sospesa { background-color: #f8d7da; color: #721c24; }
        .status-da-iniziare { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-section">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="mb-1">Pratica Conto Termico #<?= $pratica['id'] ?></h2>
                    <p class="text-muted mb-0">Data stampa: <?= date('d/m/Y H:i') ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <button onclick="window.print()" class="btn btn-primary no-print">
                        <i class="fas fa-print"></i> Stampa
                    </button>
                    <button onclick="window.close()" class="btn btn-secondary no-print">
                        <i class="fas fa-times"></i> Chiudi
                    </button>
                </div>
            </div>
        </div>

        <!-- Informazioni Cliente -->
        <div class="info-section">
            <h5><i class="fas fa-user"></i> Informazioni Cliente</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Nome/Ragione Sociale:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Cognome_Ragione_sociale'] . ($pratica['Nome'] ? ' ' . $pratica['Nome'] : '')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Codice Fiscale:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Codice_fiscale'] ?: 'Non specificato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Partita IVA:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Partita_iva'] ?: 'Non specificata') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Indirizzo:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Indirizzo'] ?: 'Non specificato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Città:</span>
                        <span class="value"><?= htmlspecialchars(($pratica['CAP'] ? $pratica['CAP'] . ' ' : '') . ($pratica['Citta'] ?: 'Non specificata') . ($pratica['Provincia'] ? ' (' . $pratica['Provincia'] . ')' : '')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Contatti:</span>
                        <span class="value">
                            <?= htmlspecialchars($pratica['Telefono'] ?: '') ?>
                            <?= $pratica['Telefono'] && $pratica['Email'] ? ' - ' : '' ?>
                            <?= htmlspecialchars($pratica['Email'] ?: '') ?>
                            <?= !$pratica['Telefono'] && !$pratica['Email'] ? 'Non specificati' : '' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dettagli Pratica Conto Termico -->
        <div class="info-section">
            <h5><i class="fas fa-fire"></i> Dettagli Pratica Conto Termico</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Codice Pratica:</span>
                        <span class="value"><?= htmlspecialchars($pratica['codice_pratica'] ?: 'Non assegnato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tipo Intervento:</span>
                        <span class="value"><?= htmlspecialchars($pratica['tipo_intervento'] ?: 'Non specificato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Stato:</span>
                        <span class="value">
                            <span class="status-badge status-<?= strtolower(str_replace([' ', '_'], '-', $pratica['stato'])) ?>">
                                <?= htmlspecialchars($pratica['stato']) ?>
                            </span>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Importo Ammissibile:</span>
                        <span class="value"><?= formatImporto($pratica['importo_ammissibile']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Incentivo:</span>
                        <span class="value"><?= formatImporto($pratica['incentivo']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Data Presentazione:</span>
                        <span class="value"><?= formatDate($pratica['data_presentazione']) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($pratica['descrizione'])): ?>
            <div class="info-row mt-3">
                <span class="label">Descrizione:</span>
                <div class="value mt-1"><?= nl2br(htmlspecialchars($pratica['descrizione'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Documenti -->
        <div class="info-section">
            <h5><i class="fas fa-folder"></i> Documenti</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Scheda Descrittiva:</span>
                        <span class="value"><?= formatBoolean($pratica['scheda_descrittiva']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Diagnosi Energetica:</span>
                        <span class="value"><?= formatBoolean($pratica['diagnosi_energetica']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Asseverazione:</span>
                        <span class="value"><?= formatBoolean($pratica['asseverazione']) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Fatture:</span>
                        <span class="value"><?= formatBoolean($pratica['fatture']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Certificazioni:</span>
                        <span class="value"><?= formatBoolean($pratica['certificazioni']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Altri Documenti:</span>
                        <span class="value"><?= formatBoolean($pratica['altri_documenti']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Note -->
        <?php if (!empty($pratica['note'])): ?>
        <div class="info-section">
            <h5><i class="fas fa-sticky-note"></i> Note</h5>
            <div class="info-row">
                <div class="value"><?= nl2br(htmlspecialchars($pratica['note'])) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://kit.fontawesome.com/YOUR_FONT_AWESOME_KIT.js"></script>
</body>
</html>
