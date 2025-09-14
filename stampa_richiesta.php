<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Verifica se è stato fornito l'ID della richiesta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID richiesta non valido');
}

$richiesta_id = (int)$_GET['id'];

try {
    // Query per recuperare tutti i dati della richiesta
    $sql = "SELECT * FROM richieste WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$richiesta_id]);
    $richiesta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$richiesta) {
        die('Richiesta non trovata');
    }
    
} catch (PDOException $e) {
    die('Errore nel recupero della richiesta: ' . $e->getMessage());
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

// Funzione per formattare lo stato
function formatStato($stato) {
    $stati = [
        'aperta' => 'Aperta',
        'in_lavorazione' => 'In Lavorazione',
        'completata' => 'Completata',
        'chiusa' => 'Chiusa'
    ];
    return $stati[$stato] ?? $stato;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stampa Richiesta #<?= $richiesta['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .container-fluid { max-width: 100%; margin: 0; padding: 0; }
        }
        
        .header-section {
            border-bottom: 2px solid #6f42c1;
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
            color: #6f42c1;
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
        
        .status-aperta { background-color: #f8d7da; color: #721c24; }
        .status-in-lavorazione { background-color: #fff3cd; color: #856404; }
        .status-completata { background-color: #d4edda; color: #155724; }
        .status-chiusa { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="header-section">
            <div class="row">
                <div class="col-md-8">
                    <h2 class="mb-1">Richiesta #<?= $richiesta['id'] ?></h2>
                    <h4 class="text-muted"><?= htmlspecialchars($richiesta['denominazione']) ?></h4>
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

        <!-- Informazioni Generali -->
        <div class="info-section">
            <h5><i class="fas fa-info-circle"></i> Informazioni Generali</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Denominazione:</span>
                        <span class="value"><?= htmlspecialchars($richiesta['denominazione']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Data Richiesta:</span>
                        <span class="value"><?= formatDate($richiesta['data_richiesta']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Stato:</span>
                        <span class="value">
                            <span class="status-badge status-<?= strtolower(str_replace('_', '-', $richiesta['stato'])) ?>">
                                <?= formatStato($richiesta['stato']) ?>
                            </span>
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Telefono:</span>
                        <span class="value"><?= htmlspecialchars($richiesta['telefono'] ?: 'Non specificato') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value"><?= htmlspecialchars($richiesta['email'] ?: 'Non specificata') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Attività a Pagamento:</span>
                        <span class="value"><?= formatBoolean($richiesta['attivita_pagamento']) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($richiesta['attivita_pagamento'] && !empty($richiesta['importo'])): ?>
            <div class="info-row mt-3">
                <span class="label">Importo:</span>
                <span class="value"><strong><?= formatImporto($richiesta['importo']) ?></strong></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Descrizione Richiesta -->
        <div class="info-section">
            <h5><i class="fas fa-question-circle"></i> Descrizione Richiesta</h5>
            <div class="info-row">
                <div class="value"><?= nl2br(htmlspecialchars($richiesta['richiesta'])) ?></div>
            </div>
        </div>

        <!-- Soluzione -->
        <?php if (!empty($richiesta['soluzione'])): ?>
        <div class="info-section">
            <h5><i class="fas fa-lightbulb"></i> Soluzione Proposta/Implementata</h5>
            <div class="info-row">
                <div class="value"><?= nl2br(htmlspecialchars($richiesta['soluzione'])) ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timestamp -->
        <div class="info-section">
            <h5><i class="fas fa-clock"></i> Cronologia</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Creata il:</span>
                        <span class="value"><?= date('d/m/Y H:i:s', strtotime($richiesta['created_at'])) ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row">
                        <span class="label">Ultima modifica:</span>
                        <span class="value"><?= date('d/m/Y H:i:s', strtotime($richiesta['updated_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-muted">
            <hr>
            <small>
                Sistema di Gestione Richieste CRM - Generato automaticamente il <?= date('d/m/Y \a\l\l\e H:i') ?>
            </small>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/YOUR_FONT_AWESOME_KIT.js"></script>
</body>
</html>
