<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/header.php';
?>

<style>
.info-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    text-align: center;
}

.info-header h2 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 300;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.info-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.category-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.category-title {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ecf0f1;
}

.category-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-links li {
    margin-bottom: 0.8rem;
}

.category-links a {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    font-weight: 500;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.category-links a:hover {
    background: #e3f2fd;
    color: #1976d2;
    border-left-color: #1976d2;
    transform: translateX(5px);
}

.category-links a::before {
    content: '🔗';
    margin-right: 0.8rem;
    font-size: 1.1rem;
}

.category-links a:hover::before {
    content: '🚀';
}

.stats-bar {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
    color: #6c757d;
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .info-header h2 {
        font-size: 2rem;
    }
    
    .category-card {
        padding: 1rem;
    }
}
</style>

<main class="container mt-4">
    <div class="info-header">
        <h2>🔗 Link Utili</h2>
        <p>Accesso rapido ai servizi web più utilizzati</p>
    </div>

<?php
// Link organizzati per categoria con icone
$links_per_categoria = [
    'Enti Previdenziali' => [
        ['nome'=>'INPS - Istituto Nazionale Previdenza Sociale','link'=>'https://www.inps.it/'],
        ['nome'=>'INAIL - Istituto Nazionale Assicurazione Infortuni','link'=>'https://www.inail.it/'],
    ],
    'Fisco e Agenzie' => [
        ['nome'=>'Agenzia delle Entrate','link'=>'https://www.agenziaentrate.gov.it/'],
        ['nome'=>'FatturaPA - Fatturazione Elettronica','link'=>'https://www.fatturapa.gov.it/'],
    ],
    'Imprese e Commercio' => [
        ['nome'=>'Camera di Commercio','link'=>'https://www.cameradicommercio.it/'],
        ['nome'=>'Comunicazione Unica d\'Impresa','link'=>'https://www.comunicazioneunicadimpresa.gov.it/'],
        ['nome'=>'InfoCamere - Registro Imprese','link'=>'https://www.infocamere.it/'],
    ],
    'Professioni' => [
        ['nome'=>'Ordine dei Commercialisti','link'=>'https://www.odcec.it/'],
        ['nome'=>'Consiglio Nazionale Dottori Commercialisti','link'=>'https://www.commercialisti.it/'],
    ],
    'Territorio' => [
        ['nome'=>'Provincia di Novara','link'=>'https://www.provincia.novara.it/']
    ]
];

$total_links = array_sum(array_map('count', $links_per_categoria));
?>

<div class="stats-bar">
    <strong><?= count($links_per_categoria) ?></strong> categorie • <strong><?= $total_links ?></strong> servizi web disponibili
</div>

<div class="categories-grid">
    <?php foreach ($links_per_categoria as $categoria => $servizi): ?>
        <div class="category-card">
            <h3 class="category-title"><?= htmlspecialchars($categoria) ?></h3>
            <ul class="category-links">
                <?php foreach ($servizi as $s): ?>
                    <li>
                        <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank" rel="noopener noreferrer">
                            <?= htmlspecialchars($s['nome']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>

</main>
</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
