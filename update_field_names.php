<?php
// Script per aggiornare tutti i nomi dei campi nei file PHP
echo "Aggiornamento nomi campi nei file PHP...\n";

// Mappatura vecchi -> nuovi nomi
$mappatura = [
    'Inizio rapporto' => 'Inizio_rapporto',
    'Fine rapporto' => 'Fine_rapporto',
    'Inserito gestionale' => 'Inserito_gestionale',
    'Codice ditta' => 'Codice_ditta',
    'Cognome/Ragione sociale' => 'Cognome_Ragione_sociale',
    'cognome/ragione sociale' => 'cognome_ragione_sociale',  // versione lowercase
    'Cognome/Ragione Sociale' => 'Cognome_Ragione_sociale',
    'Codice fiscale' => 'Codice_fiscale',
    'Partita IVA' => 'Partita_IVA',
    'Soci Amministratori' => 'Soci_Amministratori',
    'Sede Legale' => 'Sede_Legale',
    'Sede Operativa' => 'Sede_Operativa',
    'Data di nascita/costituzione' => 'Data_di_nascita_costituzione',
    'Luogo di nascita' => 'Luogo_di_nascita',
    'Numero carta d\'identità' => 'Numero_carta_d_identità',
    "Numero carta d'identità" => 'Numero_carta_d_identità',
    'Rilasciata dal Comune di' => 'Rilasciata_dal_Comune_di',
    'Data di rilascio' => 'Data_di_rilascio',
    'Valida per l\'espatrio' => 'Valida_per_espatrio',
    "Valida per l'espatrio" => 'Valida_per_espatrio',
    'Stato civile' => 'Stato_civile',
    'Data di scadenza' => 'Data_di_scadenza',
    'Descrizione attivita' => 'Descrizione_attivita',
    'Codice ATECO' => 'Codice_ATECO',
    'Camera di commercio' => 'Camera_di_commercio',
    'Codice inps' => 'Codice_inps',
    'Codice inps_2' => 'Codice_inps_2',
    'Codice inail' => 'Codice_inail',
    'Cod.PIN Inail' => 'Cod_PIN_Inail',
    'Cassa Edile' => 'Cassa_Edile',
    'Numero Cassa Professionisti' => 'Numero_Cassa_Professionisti',
    'Liquidazione IVA' => 'Liquidazione_IVA',
    'User Aruba' => 'User_Aruba', 
    'Scadenza PEC' => 'Scadenza_PEC',
    'Rinnovo Pec' => 'Rinnovo_Pec',
    'Link cartella' => 'Link_cartella'
];

// File da aggiornare
$files = [
    'modifica_cliente.php',
    'info_cliente.php',
    'elimina_cliente.php',
    'email.php',
    'email_invio.php',
    'drive.php',
    'cronologia_email.php',
    'crea_task_clienti.php',
    'chat.php',
    'task_clienti.php',
    'dashboard.php',
    'api/bulk_delete_clients.php',
    'includes/chat_pratiche_widget.php'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File $file non trovato, skipping...\n";
        continue;
    }
    
    echo "Aggiornando $file...\n";
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Sostituisci tutti i riferimenti ai campi
    foreach ($mappatura as $vecchio => $nuovo) {
        // Pattern per catturare i backtick intorno ai nomi dei campi
        $patterns = [
            "`$vecchio`",           // Con backtick
            "'$vecchio'",           // Con singole virgolette  
            "\"$vecchio\"",         // Con doppie virgolette
            "$vecchio AS ",         // Nel SELECT AS
            "$vecchio LIKE",        // Nel WHERE LIKE
            "$vecchio ORDER",       // Nel ORDER BY
            "$vecchio,",            // Nelle liste separate da virgola
            "[$vecchio]",           // Negli array PHP
            "name=\"$vecchio\"",    // Negli attributi HTML
            "name='$vecchio'",      // Negli attributi HTML
        ];
        
        $replacements = [
            "`$nuovo`",
            "'$nuovo'", 
            "\"$nuovo\"",
            "$nuovo AS ",
            "$nuovo LIKE", 
            "$nuovo ORDER",
            "$nuovo,",
            "[$nuovo]",
            "name=\"$nuovo\"",
            "name='$nuovo'",
        ];
        
        $content = str_replace($patterns, $replacements, $content);
    }
    
    // Se il contenuto è cambiato, salva il file
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "File $file aggiornato!\n";
    } else {
        echo "File $file non necessita aggiornamenti.\n";
    }
}

echo "Aggiornamento completato!\n";
?>
