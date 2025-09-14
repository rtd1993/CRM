<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$error_message = '';
$procedure_data = null;

// Recupera ID dalla query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $error_message = 'ID procedura non valido.';
} else {
    try {
        require_once __DIR__ . '/includes/config.php';
        
        // Recupera i dati della procedura
        $stmt = $pdo->prepare("SELECT * FROM procedure_crm WHERE id = ?");
        $stmt->execute([$id]);
        $procedure_data = $stmt->fetch();
        
        if (!$procedure_data) {
            $error_message = 'Procedura non trovata.';
        }
    } catch (Exception $e) {
        $error_message = 'Errore di connessione al database: ' . $e->getMessage();
    }
}

// Se c'è un errore, mostra la pagina di errore
if ($error_message) {
    echo '<!DOCTYPE html>
          <html>
          <head>
              <title>Errore - Stampa Procedura</title>
              <meta charset="utf-8">
              <style>
                  body { font-family: Arial, sans-serif; margin: 2rem; text-align: center; }
                  .error { color: #dc3545; font-size: 1.2rem; }
              </style>
          </head>
          <body>
              <h1>Errore</h1>
              <p class="error">' . htmlspecialchars($error_message) . '</p>
              <button onclick="window.close()">Chiudi</button>
          </body>
          </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stampa Procedura: <?= htmlspecialchars($procedure_data['denominazione']) ?></title>
    
    <style>
        /* Reset CSS per stampa */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: white;
            margin: 0;
            padding: 2cm;
        }
        
        /* Header aziendale */
        .header {
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 1cm;
            margin-bottom: 1.5cm;
            text-align: center;
        }
        
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.3cm;
        }
        
        .document-title {
            font-size: 16pt;
            font-weight: bold;
            color: #34495e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Informazioni procedura */
        .procedure-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1cm;
            margin-bottom: 1.5cm;
        }
        
        .procedure-title {
            font-size: 16pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5cm;
            text-align: center;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 0.3cm;
        }
        
        .procedure-meta {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 1cm;
        }
        
        .meta-item {
            flex: 1;
            min-width: 200px;
            margin: 0.2cm 0;
        }
        
        .meta-label {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 10pt;
        }
        
        .meta-value {
            font-size: 11pt;
            color: #2c3e50;
        }
        
        /* Contenuto procedura */
        .procedure-content {
            margin-top: 1.5cm;
        }
        
        .content-header {
            font-size: 14pt;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.8cm;
            text-align: center;
            padding: 0.5cm;
            background: #ecf0f1;
            border-radius: 5px;
        }
        
        .content-text {
            font-size: 11pt;
            line-height: 1.8;
            text-align: justify;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        /* Footer */
        .footer {
            position: fixed;
            bottom: 1cm;
            left: 2cm;
            right: 2cm;
            border-top: 1px solid #bdc3c7;
            padding-top: 0.5cm;
            font-size: 9pt;
            color: #7f8c8d;
            display: flex;
            justify-content: space-between;
        }
        
        /* Controlli stampa (solo a schermo) */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        .close-btn {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .close-btn:hover {
            background: #7f8c8d;
        }
        
        /* Stili per la stampa */
        @media print {
            body {
                margin: 0;
                padding: 1.5cm;
                font-size: 10pt;
            }
            
            .print-controls {
                display: none !important;
            }
            
            .header {
                margin-bottom: 1cm;
                padding-bottom: 0.5cm;
            }
            
            .procedure-info {
                margin-bottom: 1cm;
                padding: 0.8cm;
            }
            
            .procedure-content {
                margin-top: 1cm;
            }
            
            .footer {
                position: fixed;
                bottom: 1cm;
            }
            
            /* Evita interruzioni di pagina inopportune */
            .procedure-info {
                page-break-inside: avoid;
            }
            
            .content-header {
                page-break-after: avoid;
            }
        }
        
        @page {
            size: A4;
            margin: 2cm;
        }
    </style>
</head>
<body>
    <!-- Controlli stampa (visibili solo a schermo) -->
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> Stampa
        </button>
        <button class="close-btn" onclick="window.close()">
            <i class="fas fa-times"></i> Chiudi
        </button>
    </div>
    
    <!-- Header -->
    <div class="header">
        <div class="company-name">SISTEMA CRM</div>
        <div class="document-title">Documento di Procedura</div>
    </div>
    
    <!-- Informazioni procedura -->
    <div class="procedure-info">
        <h1 class="procedure-title"><?= htmlspecialchars($procedure_data['denominazione']) ?></h1>
        
        <div class="procedure-meta">
            <div class="meta-item">
                <div class="meta-label">ID PROCEDURA</div>
                <div class="meta-value"><?= $procedure_data['id'] ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">VALIDA DAL</div>
                <div class="meta-value"><?= date('d/m/Y', strtotime($procedure_data['valida_dal'])) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-label">DATA CREAZIONE</div>
                <div class="meta-value"><?= date('d/m/Y H:i', strtotime($procedure_data['data_creazione'])) ?></div>
            </div>
            <?php if ($procedure_data['data_modifica'] != $procedure_data['data_creazione']): ?>
            <div class="meta-item">
                <div class="meta-label">ULTIMA MODIFICA</div>
                <div class="meta-value"><?= date('d/m/Y H:i', strtotime($procedure_data['data_modifica'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contenuto procedura -->
    <div class="procedure-content">
        <div class="content-header">
            DESCRIZIONE PROCEDURA
        </div>
        
        <div class="content-text">
            <?= htmlspecialchars($procedure_data['procedura']) ?>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div>
            Documento generato automaticamente dal Sistema CRM
        </div>
        <div>
            Stampa del <?= date('d/m/Y H:i') ?>
        </div>
    </div>
    
    <script>
        // Auto-print se richiesto
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('auto_print') === '1') {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        }
        
        // Gestione tastiera
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });
        
        // Focus per permettere l'uso immediato dei tasti
        window.focus();
        
        // Messaggio di conferma alla chiusura se ci sono modifiche pendenti
        window.onbeforeunload = function() {
            // Solo se la finestra è stata aperta da un popup
            if (window.opener) {
                return;
            }
        };
    </script>
</body>
</html>
