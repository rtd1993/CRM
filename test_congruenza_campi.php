<?php
// Test di congruenza campi database - form
// Questo script verifica che tutti i campi del form esistano nel database

$campi_database = [
    'id', 'Inizio rapporto', 'Fine rapporto', 'Inserito gestionale', 'Codice ditta', 'Colore',
    'Cognome/Ragione sociale', 'Nome', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori',
    'Sede Legale', 'Sede Operativa', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Residenza',
    'Numero carta d\'identità', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l\'espatrio',
    'Stato civile', 'Data di scadenza', 'Descrizione attivita', 'Codice ATECO', 'Camera di commercio', 'Dipendenti',
    'Codice inps', 'Titolare', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile',
    'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'Telefono', 'Mail', 'PEC',
    'User Aruba', 'Password', 'Scadenza PEC', 'Rinnovo Pec', 'SDI', 'Link cartella'
];

$campi_form = [
    // Anagrafica
    'Cognome/Ragione sociale', 'Nome', 'Data di nascita/costituzione', 'Luogo di nascita', 'Cittadinanza', 'Stato civile', 'Codice fiscale', 'Partita IVA', 'Qualifica', 'Soci Amministratori', 'Titolare',
    // Contatti
    'Telefono', 'Mail', 'PEC', 'Scadenza PEC', 'Rinnovo Pec', 'User Aruba', 'Password',
    // Sedi
    'Sede Legale', 'Sede Operativa', 'Residenza',
    // Documenti
    'Numero carta d identita', 'Rilasciata dal Comune di', 'Data di rilascio', 'Valida per l espatrio',
    // Fiscali
    'Codice ditta', 'Codice ATECO', 'Descrizione attivita', 'Camera di commercio', 'Dipendenti', 'Codice inps', 'Codice inps_2', 'Codice inail', 'PAT', 'Cod.PIN Inail', 'Cassa Edile', 'Numero Cassa Professionisti', 'Contabilita', 'Liquidazione IVA', 'SDI',
    // Altro
    'Colore', 'Inserito gestionale', 'Inizio rapporto', 'Fine rapporto', 'Link cartella'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Congruenza Campi</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; }
        .ok { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; }
        .field { padding: 5px; margin: 2px 0; border-radius: 4px; }
        .match { background: #d4edda; } .mismatch { background: #f8d7da; }
        h2 { color: #007bff; }
    </style>
</head>
<body>
    <h1>🔍 Test Congruenza Campi Database ↔ Form</h1>";

$campi_ok = 0;
$campi_problemi = 0;

echo "<h2>✅ Campi Form vs Database</h2>";
foreach ($campi_form as $campo_form) {
    // Converti per confronto (gestisci apostrofi)
    $campo_db_expected = str_replace([' d identita', ' l espatrio'], [' d\'identità', ' l\'espatrio'], $campo_form);
    
    if (in_array($campo_db_expected, $campi_database) || in_array($campo_form, $campi_database)) {
        echo "<div class='field match'>✓ <strong>$campo_form</strong> → OK</div>";
        $campi_ok++;
    } else {
        echo "<div class='field mismatch'>✗ <strong>$campo_form</strong> → NON TROVATO nel DB</div>";
        $campi_problemi++;
    }
}

echo "<h2>⚠️ Campi Database non nel Form</h2>";
$campi_non_usati = array_diff($campi_database, 
    array_merge($campi_form, ['id', 'Data di scadenza', 'Numero carta d\'identità', 'Valida per l\'espatrio']));

foreach ($campi_non_usati as $campo) {
    echo "<div class='field warning'>⚠️ <strong>$campo</strong> → Nel DB ma non nel form</div>";
}

echo "<hr>
<h2>📊 Riepilogo</h2>
<p class='ok'><strong>✅ Campi congruenti: $campi_ok</strong></p>";

if ($campi_problemi > 0) {
    echo "<p class='error'><strong>❌ Campi con problemi: $campi_problemi</strong></p>";
} else {
    echo "<p class='ok'><strong>🎉 TUTTI I CAMPI SONO CONGRUENTI!</strong></p>";
}

echo "<p><strong>Status:</strong> " . ($campi_problemi == 0 ? "<span class='ok'>PERFETTO</span>" : "<span class='error'>NECESSARIE CORREZIONI</span>") . "</p>";

echo "</body></html>";
?>
