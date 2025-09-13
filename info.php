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

.contacts-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 3px solid #e1e5e9;
}

.contacts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 1.5rem;
}

.contact-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e1e5e9;
}

.contact-card h3 {
    color: #2c3e50;
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.contact-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.contact-item {
    display: flex;
    align-items: center;
    padding: 0.8rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.contact-item:hover {
    background: #e3f2fd;
    transform: translateX(5px);
}

.contact-icon {
    font-size: 1.2rem;
    margin-right: 0.8rem;
    width: 24px;
    text-align: center;
}

.contact-info {
    flex: 1;
}

.contact-value {
    font-weight: 600;
    color: #1976d2;
    font-size: 1rem;
}

.contact-description {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.2rem;
}

.email-link {
    text-decoration: none;
    color: inherit;
}

.phone-link {
    text-decoration: none;
    color: inherit;
}

@media (max-width: 768px) {
    .contacts-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

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
    ],
    'Territorio' => [
        ['nome'=>'Provincia di Novara','link'=>'https://www.provincia.novara.it/']
    ]
];

$total_links = array_sum(array_map('count', $links_per_categoria));

// Email e telefoni utili estratti dai servizi
$contatti_email = [
    'INPS' => ['email' => 'inps@inps.it', 'descrizione' => 'Servizio clienti INPS'],
    'INAIL' => ['email' => 'info@inail.it', 'descrizione' => 'Informazioni INAIL'],
    'Agenzia Entrate' => ['email' => 'direzione.piemonte1@agenziaentrate.it', 'descrizione' => 'Direzione Provinciale Piemonte 1'],
    'Camera Commercio Novara' => ['email' => 'info@no.camcom.it', 'descrizione' => 'Camera di Commercio Novara'],
    'Ordine Commercialisti' => ['email' => 'segreteria@odcecnovara.it', 'descrizione' => 'Ordine Commercialisti Novara'],
    'Provincia Novara' => ['email' => 'urp@provincia.novara.it', 'descrizione' => 'URP Provincia di Novara']
];

$contatti_telefono = [
    'INPS' => ['telefono' => '803164', 'descrizione' => 'Contact Center INPS (da fisso gratuito)'],
    'INPS Mobile' => ['telefono' => '06164164', 'descrizione' => 'Contact Center INPS (da cellulare)'],
    'INAIL' => ['telefono' => '06.6001', 'descrizione' => 'Centralino INAIL'],
    'Agenzia Entrate' => ['telefono' => '848.800.444', 'descrizione' => 'Numero Verde Agenzia Entrate'],
    'Camera Commercio Novara' => ['telefono' => '0321.338.111', 'descrizione' => 'Camera di Commercio Novara'],
    'Ordine Commercialisti Novara' => ['telefono' => '0321.35.385', 'descrizione' => 'Ordine Commercialisti Novara'],
    'Provincia Novara' => ['telefono' => '0321.378.111', 'descrizione' => 'Centralino Provincia Novara']
];
?>

<div class="stats-bar">
    <strong><?= count($links_per_categoria) ?></strong> categorie • <strong><?= $total_links ?></strong> servizi web • <strong><?= count($contatti_email) ?></strong> email • <strong><?= count($contatti_telefono) ?></strong> numeri di telefono
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

<!-- Sezione Contatti Utili -->
<div class="contacts-section">
    <div class="info-header" style="margin-bottom: 1.5rem;">
        <h2>📞 Contatti Utili</h2>
        <p>Email e numeri di telefono dei principali enti e servizi</p>
    </div>
    
    <div class="contacts-grid">
        <!-- Sezione Email -->
        <div class="contact-card">
            <h3>
                <span>📧</span>
                Indirizzi Email
            </h3>
            <ul class="contact-list">
                <?php foreach ($contatti_email as $ente => $contatto): ?>
                    <li class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div class="contact-info">
                            <a href="mailto:<?= htmlspecialchars($contatto['email']) ?>" class="email-link">
                                <div class="contact-value"><?= htmlspecialchars($contatto['email']) ?></div>
                                <div class="contact-description"><?= htmlspecialchars($contatto['descrizione']) ?></div>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Sezione Telefoni -->
        <div class="contact-card">
            <h3>
                <span>📞</span>
                Numeri di Telefono
            </h3>
            <ul class="contact-list">
                <?php foreach ($contatti_telefono as $ente => $contatto): ?>
                    <li class="contact-item">
                        <div class="contact-icon">📞</div>
                        <div class="contact-info">
                            <a href="tel:<?= htmlspecialchars(str_replace(['.', ' '], '', $contatto['telefono'])) ?>" class="phone-link">
                                <div class="contact-value"><?= htmlspecialchars($contatto['telefono']) ?></div>
                                <div class="contact-description"><?= htmlspecialchars($contatto['descrizione']) ?></div>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
// Gestione click sui contatti email
document.addEventListener('DOMContentLoaded', function() {
    // Email links
    const emailLinks = document.querySelectorAll('.email-link');
    emailLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Mostra un feedback visivo
            const contactItem = this.closest('.contact-item');
            const originalBg = contactItem.style.backgroundColor;
            contactItem.style.backgroundColor = '#d4edda';
            
            setTimeout(() => {
                contactItem.style.backgroundColor = originalBg;
            }, 300);
            
            // Il mailto: si apre automaticamente
            console.log('Apertura client email per:', this.href);
        });
    });
    
    // Phone links
    const phoneLinks = document.querySelectorAll('.phone-link');
    phoneLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Mostra un feedback visivo
            const contactItem = this.closest('.contact-item');
            const originalBg = contactItem.style.backgroundColor;
            contactItem.style.backgroundColor = '#cce5ff';
            
            setTimeout(() => {
                contactItem.style.backgroundColor = originalBg;
            }, 300);
            
            console.log('Chiamata a:', this.href);
        });
    });
    
    // Copia negli appunti con click destro o doppio click
    const contactItems = document.querySelectorAll('.contact-item');
    contactItems.forEach(item => {
        item.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            const contactValue = this.querySelector('.contact-value').textContent;
            navigator.clipboard.writeText(contactValue).then(() => {
                // Feedback visivo per copia
                const originalBg = this.style.backgroundColor;
                this.style.backgroundColor = '#fff3cd';
                
                // Mostra tooltip temporaneo
                const tooltip = document.createElement('div');
                tooltip.textContent = 'Copiato negli appunti!';
                tooltip.style.cssText = `
                    position: absolute;
                    background: #28a745;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 1000;
                    pointer-events: none;
                `;
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = (rect.left + 10) + 'px';
                tooltip.style.top = (rect.top - 30) + 'px';
                
                document.body.appendChild(tooltip);
                
                setTimeout(() => {
                    this.style.backgroundColor = originalBg;
                    document.body.removeChild(tooltip);
                }, 1500);
            });
        });
    });
});
</script>

</main>
</body>
</html>

<?php require_once __DIR__ . '/includes/footer.php'; ?>