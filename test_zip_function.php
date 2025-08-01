<?php
// Script di test per la funzione di archiviazione cartelle clienti

echo "<h2>Test Funzione Archiviazione Cartelle Clienti</h2>\n";

// Simuliamo i dati di un cliente di test
$codice_fiscale = "TESTCLIENT001";
$codice_fiscale_clean = preg_replace('/[^A-Za-z0-9]/', '', $codice_fiscale);
$cartella_test = '/var/www/CRM/local_drive/' . $codice_fiscale_clean;
$cartella_ex_clienti = '/var/www/CRM/local_drive/ASContabilmente/Ex_clienti';

echo "Codice fiscale pulito: $codice_fiscale_clean<br>\n";
echo "Cartella test: $cartella_test<br>\n";
echo "Cartella destinazione: $cartella_ex_clienti<br>\n";

// Crea una cartella di test con alcuni file
if (!is_dir($cartella_test)) {
    mkdir($cartella_test, 0755, true);
    echo "✅ Cartella test creata<br>\n";
} else {
    echo "ℹ️ Cartella test già esistente<br>\n";
}

// Crea alcuni file di test
file_put_contents($cartella_test . '/documento1.txt', "Questo è un documento di test 1\nCreato il: " . date('Y-m-d H:i:s'));
file_put_contents($cartella_test . '/documento2.txt', "Questo è un documento di test 2\nCliente: $codice_fiscale");

// Crea una sottocartella con file
mkdir($cartella_test . '/sottocartella', 0755, true);
file_put_contents($cartella_test . '/sottocartella/file_sottocartella.txt', "File nella sottocartella");

echo "✅ File di test creati<br>\n";

// Verifica che ZipArchive sia disponibile
if (!class_exists('ZipArchive')) {
    echo "❌ Errore: ZipArchive non è disponibile su questo server<br>\n";
    exit;
}

// Testa la funzione di archiviazione
try {
    $nome_zip = $codice_fiscale_clean . '_' . date('Y-m-d_H-i-s') . '.zip';
    $percorso_zip = $cartella_ex_clienti . '/' . $nome_zip;
    
    echo "Nome ZIP: $nome_zip<br>\n";
    echo "Percorso ZIP: $percorso_zip<br>\n";
    
    // Crea lo ZIP
    $zip = new ZipArchive();
    if ($zip->open($percorso_zip, ZipArchive::CREATE) === TRUE) {
        echo "✅ File ZIP aperto per scrittura<br>\n";
        
        // Funzione ricorsiva per aggiungere tutti i file e sottocartelle
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cartella_test, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $files_added = 0;
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($cartella_test) + 1);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
                echo "📁 Aggiunta cartella: $relativePath<br>\n";
            } else {
                $zip->addFile($filePath, $relativePath);
                echo "📄 Aggiunto file: $relativePath<br>\n";
                $files_added++;
            }
        }
        
        $zip->close();
        echo "✅ ZIP creato con successo! File aggiunti: $files_added<br>\n";
        
        // Verifica che il file ZIP esista e abbia una dimensione ragionevole
        if (file_exists($percorso_zip)) {
            $zip_size = filesize($percorso_zip);
            echo "✅ File ZIP esiste. Dimensione: " . number_format($zip_size) . " bytes<br>\n";
            
            // Lista il contenuto del ZIP per verifica
            $zip_verify = new ZipArchive();
            if ($zip_verify->open($percorso_zip) === TRUE) {
                echo "<h3>Contenuto dell'archivio:</h3>\n";
                for ($i = 0; $i < $zip_verify->numFiles; $i++) {
                    $file_info = $zip_verify->statIndex($i);
                    echo "- " . $file_info['name'] . " (" . number_format($file_info['size']) . " bytes)<br>\n";
                }
                $zip_verify->close();
            }
            
        } else {
            echo "❌ Errore: File ZIP non trovato dopo la creazione<br>\n";
        }
        
        // Test della rimozione della cartella originale
        function rimuoviCartellaTest($dir) {
            if (!is_dir($dir)) return false;
            
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? rimuoviCartellaTest($path) : unlink($path);
            }
            return rmdir($dir);
        }
        
        if (rimuoviCartellaTest($cartella_test)) {
            echo "✅ Cartella originale rimossa con successo<br>\n";
        } else {
            echo "❌ Errore nella rimozione della cartella originale<br>\n";
        }
        
    } else {
        echo "❌ Errore nell'apertura del file ZIP<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore durante il test: " . $e->getMessage() . "<br>\n";
}

echo "<br><h3>Test completato!</h3>\n";
echo "<p>Se tutto è andato bene, dovresti trovare l'archivio ZIP in: <code>$cartella_ex_clienti</code></p>\n";

// Visualizza i file nella cartella Ex_clienti
echo "<h3>File nella cartella Ex_clienti:</h3>\n";
$files = scandir($cartella_ex_clienti);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $full_path = $cartella_ex_clienti . '/' . $file;
        $size = is_file($full_path) ? filesize($full_path) : 0;
        echo "- $file (" . number_format($size) . " bytes)<br>\n";
    }
}
?>
