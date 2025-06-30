<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';
?>
<h2>ℹ️ Informazioni Utili</h2>

<?php
// Link organizzati per categoria
$links_per_categoria = [
    'Enti Previdenziali' => [
        ['nome'=>'INPS','link'=>'https://www.inps.it/'],
        ['nome'=>'INAIL','link'=>'https://www.inail.it/'],
    ],
    'Fisco e Agenzie' => [
        ['nome'=>'Agenzia Entrate','link'=>'https://www.agenziaentrate.gov.it/'],
        ['nome'=>'FatturaPA','link'=>'https://www.fatturapa.gov.it/'],
    ],
    'Imprese e Commercio' => [
        ['nome'=>'Camera Commercio','link'=>'https://www.cameradicommercio.it/'],
        ['nome'=>'Comunicazione Unica','link'=>'https://www.comunicazioneunicadimpresa.gov.it/'],
        ['nome'=>'InfoCamere','link'=>'https://www.infocamere.it/'],
    ],
    'Professioni' => [
        ['nome'=>'Ordine Commercialisti','link'=>'https://www.odcec.it/'],
    ],
    'Territorio' => [
        ['nome'=>'Provincia di Novara','link'=>'https://www.provincia.novara.it/']
    ]
];
?>

<?php foreach ($links_per_categoria as $categoria => $servizi): ?>
    <h3><?= htmlspecialchars($categoria) ?></h3>
    <ul>
        <?php foreach ($servizi as $s): ?>
            <li><a href="<?= htmlspecialchars($s['link']) ?>" target="_blank"><?= htmlspecialchars($s['nome']) ?></a></li>
        <?php endforeach; ?>
    </ul>
<?php endforeach; ?>

</main>
</body>
</html>