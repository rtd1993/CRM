<?php
// File di test per verificare i campi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Dati POST ricevuti:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>Controllo specifico del codice fiscale:</h2>";
    echo "Campo 'Codice fiscale': ";
    if (isset($_POST['Codice fiscale'])) {
        echo "'" . $_POST['Codice fiscale'] . "' (lunghezza: " . strlen($_POST['Codice fiscale']) . ")";
    } else {
        echo "NON TROVATO";
    }
    
    echo "<br>Campo esiste: " . (isset($_POST['Codice fiscale']) ? 'SI' : 'NO');
    echo "<br>Campo vuoto: " . (empty($_POST['Codice fiscale']) ? 'SI' : 'NO');
    echo "<br>Campo trim vuoto: " . (trim($_POST['Codice fiscale'] ?? '') === '' ? 'SI' : 'NO');
} else {
?>
<form method="POST">
    <label for="codice_fiscale">Codice fiscale:</label>
    <input type="text" id="codice_fiscale" name="Codice fiscale" value="RSSMRA85M01H501Z">
    <button type="submit">Invia Test</button>
</form>
<?php } ?>
