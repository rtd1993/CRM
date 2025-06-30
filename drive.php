<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/header.php';

$base_dir = '/var/www/CRM/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_dir = realpath($base_dir . $relative_path);

if (!$current_dir || strpos($current_dir, realpath($base_dir)) !== 0) {
    $current_dir = $base_dir;
    $relative_path = '';
}

// Percorso attuale (breadcrumb)
$breadcrumbs = [];
$tmp = '';
foreach (explode('/', trim($relative_path, '/')) as $part) {
    if ($part === '') continue;
    $tmp .= '/' . $part;
    $breadcrumbs[] = [
        'name' => $part,
        'path' => ltrim($tmp, '/')
    ];
}

// Ricerca e ordinamento
$search = trim($_GET['search'] ?? '');
$order = $_GET['order'] ?? 'name';

// Leggi contenuto directory
$contents = [];
if ($handle = opendir($current_dir)) {
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if ($search && stripos($entry, $search) === false) continue;
        $full_path = $current_dir . '/' . $entry;
        $is_dir = is_dir($full_path);
        $size = $is_dir ? '' : filesize($full_path);

        // Associa cliente se il nome cartella corrisponde al codice fiscale
        $cliente = '';
        if ($is_dir) {
            $stmt = $pdo->prepare("SELECT `Cognome/Ragione sociale`, `Nome` FROM clienti WHERE `Codice fiscale` = ?");
            $stmt->execute([$entry]);
            if ($row = $stmt->fetch()) {
                $cliente = $row['Cognome/Ragione sociale'] . ' ' . $row['Nome'];
            }
        }

        $contents[] = [
            'name' => $entry,
            'is_dir' => $is_dir,
            'size' => $size,
            'cliente' => $cliente
        ];
    }
    closedir($handle);
}

// Ordinamento
usort($contents, function($a, $b) use ($order) {
    if ($a['is_dir'] !== $b['is_dir']) return $a['is_dir'] ? -1 : 1; // Cartelle prima
    if ($order === 'size') return ($a['size'] ?? 0) <=> ($b['size'] ?? 0);
    if ($order === 'cliente') return strcmp($a['cliente'], $b['cliente']);
    return strcasecmp($a['name'], $b['name']); // Default: nome
});
?>

<h2>📂 Drive Documentale</h2>

<!-- Breadcrumb -->
<nav style="margin-bottom:20px;">
    <span>Percorso: </span>
    <a href="drive.php">Root</a>
    <?php foreach ($breadcrumbs as $b): ?>
        / <a href="drive.php?path=<?= urlencode($b['path']) ?>"><?= htmlspecialchars($b['name']) ?></a>
    <?php endforeach; ?>
</nav>

<!-- Ricerca e ordinamento -->
<form method="get" style="display:flex;align-items:center;gap:15px;margin-bottom:20px;">
    <input type="hidden" name="path" value="<?= htmlspecialchars($relative_path) ?>">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cerca file o cartella..." style="padding:7px;width:230px;">
    <select name="order" style="padding:7px;">
        <option value="name"<?= $order==='name'?' selected':'' ?>>Ordina per nome</option>
        <option value="cliente"<?= $order==='cliente'?' selected':'' ?>>Ordina per cliente</option>
        <option value="size"<?= $order==='size'?' selected':'' ?>>Ordina per dimensione</option>
    </select>
    <button type="submit" style="padding:7px 20px;">Cerca/Ordina</button>
</form>

<!-- Tabella file/cartelle -->
<table style="width:100%;border-collapse:collapse;background:#fff;">
    <thead>
        <tr style="background:#f8f9fa;">
            <th style="text-align:left;padding:10px;">Nome</th>
            <th style="text-align:left;padding:10px;">Cliente</th>
            <th style="text-align:left;padding:10px;">Dimensione</th>
            <th style="text-align:right;padding:10px;">Azioni</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($contents)): ?>
        <tr><td colspan="4" style="padding:20px;text-align:center;">Nessun file o cartella</td></tr>
    <?php else: foreach ($contents as $c):
        $icon = $c['is_dir'] ? '📁' : '📄';
        $size_str = $c['is_dir'] ? '-' : number_format($c['size']/1024, 2) . ' KB';
    ?>
        <tr>
            <td style="padding:10px;">
                <?= $icon ?>
                <?php if ($c['is_dir']): ?>
                    <a href="drive.php?path=<?= urlencode(trim($relative_path . '/' . $c['name'], '/')) ?>" style="font-weight:bold;">
                        <?= htmlspecialchars($c['name']) ?>
                    </a>
                <?php else: ?>
                    <?= htmlspecialchars($c['name']) ?>
                <?php endif; ?>
            </td>
            <td style="padding:10px;"><?= htmlspecialchars($c['cliente']) ?></td>
            <td style="padding:10px;"><?= $size_str ?></td>
            <td style="padding:10px;text-align:right;">
                <!-- Burger menu -->
                <div style="display:inline-block;position:relative;">
                    <button type="button" onclick="toggleMenu(this)" style="background:none;border:none;font-size:18px;cursor:pointer;">☰</button>
                    <div class="burger-menu" style="display:none;position:absolute;right:0;background:#f8f9fa;border:1px solid #ccc;border-radius:6px;box-shadow:0 2px 8px #eee;min-width:150px;z-index:10;">
                        <?php if ($c['is_dir']): ?>
                            <a href="crea_cartella.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">➕ Crea Cartella</a>
                            <a href="upload.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">⏫ Upload File</a>
                            <a href="rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">✏️ Rinomina</a>
                            <a href="elimina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#d9534f;">🗑️ Elimina</a>
                            <a href="sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">📂 Sposta</a>
                        <?php else: ?>
                            <a href="rinomina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">✏️ Rinomina</a>
                            <a href="download.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">⬇️ Download</a>
                            <a href="elimina.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#d9534f;">🗑️ Elimina</a>
                            <a href="sposta.php?path=<?= urlencode($relative_path . '/' . $c['name']) ?>" style="display:block;padding:8px;text-decoration:none;color:#333;">📂 Sposta</a>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>

<script>
function toggleMenu(btn) {
    // Chiudi altri menu aperti
    document.querySelectorAll('.burger-menu').forEach(menu => menu.style.display = 'none');
    // Apri questo
    var menu = btn.nextElementSibling;
    menu.style.display = 'block';
    // Chiudi al click fuori
    document.addEventListener('click', function handler(e) {
        if (!menu.contains(e.target) && e.target !== btn) {
            menu.style.display = 'none';
            document.removeEventListener('click', handler);
        }
    });
}
</script>

</main>
</body>
</html>