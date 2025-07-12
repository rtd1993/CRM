<?php
// Versione step-by-step di modifica_cliente.php per debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Modifica Cliente - Step by Step Debug</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1000px;margin:0 auto;padding:20px;background:#f8f9fa;}</style>";

// Determina lo step da caricare
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$id = isset($_GET['id']) ? intval($_GET['id']) : 1;

echo "<div style='background:white;padding:20px;border-radius:10px;margin-bottom:20px;'>";
echo "<h2>Step $step - Debug Progressivo</h2>";

// Navigation
echo "<div style='margin-bottom:20px;'>";
for ($i = 1; $i <= 8; $i++) {
    $style = $i == $step ? 'background:#007bff;color:white;' : 'background:#e9ecef;color:#495057;';
    echo "<a href='?step=$i&id=$id' style='display:inline-block;padding:8px 15px;margin:2px;text-decoration:none;border-radius:5px;$style'>Step $i</a>";
}
echo "</div>";

try {
    switch ($step) {
        case 1:
            echo "<h3>Step 1: Includes Base</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            echo "✅ Tutti gli includes caricati correttamente<br>";
            echo "<a href='?step=2&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 2:
            echo "<h3>Step 2: Validazione Parametri</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo "❌ ID non valido<br>";
                break;
            }
            
            $id = intval($_GET['id']);
            echo "✅ ID Cliente: $id<br>";
            echo "<a href='?step=3&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 3:
            echo "<h3>Step 3: Query Database</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                echo "❌ Cliente non trovato<br>";
                break;
            }
            
            echo "✅ Cliente trovato: " . htmlspecialchars($cliente['Cognome/Ragione sociale']) . "<br>";
            echo "<a href='?step=4&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 4:
            echo "<h3>Step 4: Definizione Funzioni</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $errore = '';
            
            function campo_input($nome, $valore, $type = 'text') {
                $nome_escaped = htmlspecialchars($nome);
                $valore_escaped = htmlspecialchars($valore ?? '');
                
                return "<div class=\"form-field\">
                    <label class=\"form-label\">{$nome_escaped}</label>
                    <input type=\"{$type}\" name=\"{$nome}\" value=\"{$valore_escaped}\" class=\"form-control\">
                </div>";
            }
            
            echo "✅ Funzione campo_input definita<br>";
            echo "<a href='?step=5&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 5:
            echo "<h3>Step 5: Definizione Gruppi</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $gruppi = [
                'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA'],
                'Contatti' => ['Telefono', 'Mail', 'PEC']
            ];
            
            echo "✅ Gruppi definiti: " . count($gruppi) . " gruppi<br>";
            echo "<a href='?step=6&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 6:
            echo "<h3>Step 6: CSS Base</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            echo "<style>
            .form-field { margin: 10px 0; }
            .form-label { display: block; font-weight: bold; margin-bottom: 5px; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
            </style>";
            
            echo "✅ CSS base applicato<br>";
            echo "<a href='?step=7&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 7:
            echo "<h3>Step 7: Form HTML</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
            $stmt->execute([$id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            function campo_input($nome, $valore, $type = 'text') {
                $nome_escaped = htmlspecialchars($nome);
                $valore_escaped = htmlspecialchars($valore ?? '');
                
                return "<div class=\"form-field\">
                    <label class=\"form-label\">{$nome_escaped}</label>
                    <input type=\"{$type}\" name=\"{$nome}\" value=\"{$valore_escaped}\" class=\"form-control\">
                </div>";
            }
            
            echo "<style>
            .form-field { margin: 10px 0; }
            .form-label { display: block; font-weight: bold; margin-bottom: 5px; }
            .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
            </style>";
            
            echo "<form method='post' style='background:white;padding:20px;border-radius:10px;'>";
            echo "<h3>Modifica Cliente: " . htmlspecialchars($cliente['Cognome/Ragione sociale']) . "</h3>";
            
            $gruppi = [
                'Anagrafica' => ['Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA'],
                'Contatti' => ['Telefono', 'Mail', 'PEC']
            ];
            
            foreach ($gruppi as $titolo => $campi) {
                echo "<fieldset><legend>$titolo</legend>";
                foreach ($campi as $campo) {
                    echo campo_input($campo, $cliente[$campo] ?? '', 'text');
                }
                echo "</fieldset>";
            }
            
            echo "<button type='submit' class='btn'>Salva Modifiche</button>";
            echo "</form>";
            
            echo "✅ Form HTML generato<br>";
            echo "<a href='?step=8&id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;'>Continua →</a>";
            break;

        case 8:
            echo "<h3>Step 8: JavaScript Base</h3>";
            require_once __DIR__ . '/includes/auth.php';
            require_login();
            require_once __DIR__ . '/includes/db.php';
            require_once __DIR__ . '/includes/header.php';
            
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('✅ JavaScript caricato');
                const form = document.querySelector('form');
                if (form) {
                    console.log('✅ Form trovato');
                }
            });
            </script>";
            
            echo "✅ JavaScript base applicato<br>";
            echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin-top:20px;'>";
            echo "<h4>🎯 Test Completato!</h4>";
            echo "<p>Se tutti gli step sono passati, il problema è nella pagina completa. Possibili cause:</p>";
            echo "<ul>";
            echo "<li><strong>CSS complesso:</strong> Troppi stili o regole conflittuali</li>";
            echo "<li><strong>JavaScript pesante:</strong> Script troppo complessi</li>";
            echo "<li><strong>Memoria PHP:</strong> Limite di memoria superato</li>";
            echo "</ul>";
            echo "</div>";
            break;
    }
} catch (Exception $e) {
    echo "❌ Errore nello Step $step: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

// Footer con link utili
echo "<div style='background:white;padding:20px;border-radius:10px;'>";
echo "<h3>🔗 Link di Test</h3>";
echo "<a href='debug_modifica_avanzato.php?id=$id' style='padding:10px 20px;background:#17a2b8;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Debug Avanzato</a>";
echo "<a href='modifica_cliente_simple.php?id=$id' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Versione Semplice</a>";
echo "<a href='modifica_cliente.php?id=$id' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin:5px;'>Versione Completa</a>";
echo "</div>";
?>
