<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Verifica se è stato fornito l'ID della pratica ENEA
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID pratica ENEA non valido');
}

$enea_id = (int)$_GET['id'];

try {
    // Query per recuperare tutti i dati della pratica ENEA con informazioni cliente
    $sql = "SELECT e.*, 
                   c.id as cliente_id,
                   c.Cognome_Ragione_sociale,
                   c.Nome,
                   c.Codice_fiscale,
                   c.Partita_IVA,
                   c.Indirizzo,
                   c.Sede_Legale,
                   c.Sede_Operativa,
                   c.Residenza,
                   c.Telefono,
                   c.Mail
            FROM enea e 
            LEFT JOIN clienti c ON e.cliente_id = c.id 
            WHERE e.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$enea_id]);
    $pratica = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pratica) {
        die('Pratica ENEA non trovata');
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
    <title>Stampa Pratica ENEA #<?= $pratica['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container-fluid { max-width: 100%; margin: 0; padding: 0; }
        }
        
        .header-section {
            border-bottom: 2px solid #007bff;
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
            color: #007bff;
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
                    <h2 class="mb-1">Pratica ENEA #<?= $pratica['id'] ?></h2>
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
                        <span class="value"><?= htmlspecialchars($pratica['Partita_IVA'] ?: 'Non specificata') ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Indirizzo:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Indirizzo'] ?: 'Non specificato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Sede Legale:</span>
                        <span class="value"><?= htmlspecialchars($pratica['Sede_Legale'] ?: 'Non specificata') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Contatti:</span>
                        <span class="value">
                            <?= htmlspecialchars($pratica['Telefono'] ?: '') ?>
                            <?= $pratica['Telefono'] && $pratica['Mail'] ? ' - ' : '' ?>
                            <?= htmlspecialchars($pratica['Mail'] ?: '') ?>
                            <?= !$pratica['Telefono'] && !$pratica['Mail'] ? 'Non specificati' : '' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dettagli Pratica ENEA -->
        <div class="info-section">
            <h5><i class="fas fa-file-alt"></i> Dettagli Pratica ENEA</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Codice ENEA:</span>
                        <span class="value"><?= htmlspecialchars($pratica['codice_enea'] ?: 'Non assegnato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Anno Fiscale:</span>
                        <span class="value"><?= htmlspecialchars($pratica['anno_fiscale']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tipo Detrazione:</span>
                        <span class="value"><?= htmlspecialchars($pratica['tipo_detrazione']) ?></span>
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
                        <span class="label">Importo Spesa:</span>
                        <span class="value"><?= formatImporto($pratica['importo_spesa']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Importo Detrazione:</span>
                        <span class="value"><?= formatImporto($pratica['importo_detrazione']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Data Trasmissione:</span>
                        <span class="value"><?= formatDate($pratica['data_trasmissione']) ?></span>
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

        <!-- Documenti e Comunicazioni -->
        <div class="info-section">
            <h5><i class="fas fa-folder"></i> Documenti e Comunicazioni</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Prima Telefonata:</span>
                        <span class="value"><?= formatBoolean($pratica['prima_tel']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Richiesta Documenti:</span>
                        <span class="value"><?= formatBoolean($pratica['richiesta_doc']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Ns Prev. N° Del.:</span>
                        <span class="value"><?= formatBoolean($pratica['ns_prev_n_del']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Ns Ord. N° Del.:</span>
                        <span class="value"><?= formatBoolean($pratica['ns_ord_n_del']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Ns Fatt. N° Del.:</span>
                        <span class="value"><?= formatBoolean($pratica['ns_fatt_n_del']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Bonifico NS:</span>
                        <span class="value"><?= formatBoolean($pratica['bonifico_ns']) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Copia Fatt. Fornitore:</span>
                        <span class="value"><?= formatBoolean($pratica['copia_fatt_fornitore']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Schede Tecniche:</span>
                        <span class="value"><?= formatBoolean($pratica['schede_tecniche']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Visura Catastale:</span>
                        <span class="value"><?= formatBoolean($pratica['visura_catastale']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Firma Notorio:</span>
                        <span class="value"><?= formatBoolean($pratica['firma_notorio']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Firma Delega Ag. Entr.:</span>
                        <span class="value"><?= formatBoolean($pratica['firma_delega_ag_entr']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Firma Delega ENEA:</span>
                        <span class="value"><?= formatBoolean($pratica['firma_delega_enea']) ?></span>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Consenso:</span>
                        <span class="value"><?= formatBoolean($pratica['consenso']) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">EV Atto Notorio:</span>
                        <span class="value"><?= formatBoolean($pratica['ev_atto_notorio']) ?></span>
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
