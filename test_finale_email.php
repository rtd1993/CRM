<?php
require_once 'includes/email_config.php';

echo "=== TEST FINALE INVIO EMAIL ===\n";

// Test con la funzione ottimizzata
$destinatario = 'gestione.ascontabilmente@gmail.com';
$nome = 'Test Destinatario';
$oggetto = 'Test Email CRM - Versione Finale - ' . date('Y-m-d H:i:s');
$messaggio = '
<h2>🎉 Test Email Sistema CRM</h2>
<p>Caro/a <strong>' . $nome . '</strong>,</p>
<p>Questa è una email di test dal sistema CRM con la configurazione finale ottimizzata.</p>
<hr>
<p><strong>Dettagli invio:</strong></p>
<ul>
    <li>Data e ora: ' . date('d/m/Y H:i:s') . '</li>
    <li>Configurazione: Ottimizzata per deliverability</li>
    <li>Formato: HTML con versione testo alternativa</li>
    <li>Encoding: UTF-8 con Base64</li>
</ul>
<p><strong>Se ricevi questa email, il sistema funziona perfettamente! ✅</strong></p>
<p>Cordiali saluti,<br><strong>AS Contabilmente CRM</strong></p>
';

$risultato = inviaEmailSMTP($destinatario, $nome, $oggetto, $messaggio, true);

if ($risultato['success']) {
    echo "✅ SUCCESS: " . $risultato['message'] . "\n";
    echo "📧 Email inviata a: $destinatario\n";
    echo "📝 Oggetto: $oggetto\n";
    echo "\n🔍 CONTROLLA QUESTE CARTELLE:\n";
    echo "1. 📥 Posta in arrivo\n";
    echo "2. 🗑️  Spam/Junk\n";
    echo "3. 📂 Promozioni (Gmail)\n";
    echo "4. 📂 Social (Gmail)\n";
    echo "5. 📂 Aggiornamenti (Gmail)\n";
} else {
    echo "❌ ERROR: " . $risultato['message'] . "\n";
}
?>
