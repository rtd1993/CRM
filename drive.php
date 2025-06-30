<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_login();

$baseDir = __DIR__ . '/files';
$currentPath = isset($_GET['path']) ? realpath($baseDir . '/' . $_GET['path']) : $baseDir;
$search = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';
$sortBy = $_GET['sort_by'] ?? 'az';

if (strpos($currentPath, realpath($baseDir)) !== 0) {
    die('Accesso non autorizzato');
}

$currentRelPath = str_replace($baseDir, '', $currentPath);
$parentPath = dirname($currentRelPath);
if ($parentPath === DIRECTORY_SEPARATOR) $parentPath = '';

$items = scandir($currentPath);
$folders = [];
$files = [];

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    if ($search && stripos($item, $search) === false) continue;

    $fullPath = $currentPath . '/' . $item;
    if (is_dir($fullPath)) $folders[] = $item;
    else $files[] = $item;
}

$sort_func = function($a, $b) use ($currentPath, $sortBy) {
    $pa = $currentPath . '/' . $a;
    $pb = $currentPath . '/' . $b;
    if ($sortBy === 'za') return strcasecmp($b, $a);
    if ($sortBy === 'date_asc') return filemtime($pa) <=> filemtime($pb);
    if ($sortBy === 'date_desc') return filemtime($pb) <=> filemtime($pa);
    return strcasecmp($a, $b);
};

usort($folders, $sort_func);
usort($files, $sort_func);

$stmtClienti = $pdo->query("SELECT id, nome, 'Codice fiscale' FROM clienti WHERE 'Link cartella' IS NULL OR 'Link cartella' = ''");
$clientiDisponibili = $stmtClienti->fetchAll(PDO::FETCH_ASSOC);

$rootPath = realpath(__DIR__ . '/files');
$cartelleRoot = array_filter(scandir($rootPath), function ($item) use ($rootPath) {
    return $item !== '.' && $item !== '..' && is_dir($rootPath . DIRECTORY_SEPARATOR . $item);
});
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione File</title>
    <link rel="stylesheet" href="/style.css">
    <script>
        function mostraForm(id) {
            document.querySelectorAll('.form-section').forEach(f => f.classList.add('hidden'));
            if (id !== '') document.getElementById(id).classList.remove('hidden');
        }
    </script>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f9f9f9; }
        .container { background: #fff; padding: 20px; border-radius: 10px; }
        .entry { padding: 8px; border-bottom: 1px solid #ccc; }
        .folder { font-weight: bold; }
        .hidden { display: none; }
        .inline { display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <div class="path">
        <a href="drive.php">🏠 Home</a> |
        Percorso: <strong><?= htmlspecialchars($currentRelPath ?: '/') ?></strong>
        <form method="get" action="drive.php" class="inline">
            <input type="hidden" name="path" value="<?= htmlspecialchars($currentRelPath) ?>">
        </form>
		<form method="get" action="drive.php" class="inline">
		🔍 <input type="text" name="q" placeholder="Cerca" value="<?= htmlspecialchars($search) ?>">
            <select name="sort_by">
                <option value="az" <?= $sortBy === 'az' ? 'selected' : '' ?>>A-Z</option>
                <option value="za" <?= $sortBy === 'za' ? 'selected' : '' ?>>Z-A</option>
                <option value="date_desc" <?= $sortBy === 'date_desc' ? 'selected' : '' ?>>Più recenti</option>
                <option value="date_asc" <?= $sortBy === 'date_asc' ? 'selected' : '' ?>>Più vecchi</option>
            </select>
            <button type="submit">Applica</button>
			</form>
    </div>

    <?php if ($currentRelPath): ?>
        <div class="back">
            <a href="drive.php?path=<?= urlencode($parentPath) ?>">🔙 Su</a>
        </div>
    <?php endif; ?>

    <div class="gestione">
        <label>Gestione:</label>
        <select onchange="mostraForm(this.value)">
            <option value="">-- Azione --</option>
            <option value="form-cartella">📁 Crea</option>
            <option value="form-upload">📤 Upload</option>
            <option value="form-rinomina">✏️ Rinomina</option>
        </select>
    </div>

    <div id="form-cartella" class="form-section hidden">
        <form method="post" action="includes/drive_actions.php">
            <input type="hidden" name="azione" value="crea_cartella">
            <input type="hidden" name="percorso" value="<?= htmlspecialchars($currentRelPath) ?>">
            <input type="text" name="nome_cartella" placeholder="Nome nuova cartella" required>
            <button type="submit">Crea</button>
        </form>
    </div>

    <div id="form-upload" class="form-section hidden">
        <form method="post" action="includes/drive_actions.php" enctype="multipart/form-data">
            <input type="hidden" name="azione" value="upload">
            <input type="hidden" name="percorso" value="<?= htmlspecialchars($currentRelPath) ?>">
            <input type="file" name="file" required>
            <button type="submit">Upload</button>
        </form>
    </div>

    <div id="form-rinomina" class="form-section hidden">
        <form method="post" action="includes/drive_actions.php">
            <input type="hidden" name="azione" value="rinomina">
            <input type="hidden" name="percorso" value="<?= htmlspecialchars($currentRelPath) ?>">
            <select name="vecchio_nome" required>
                <option value="">-- Seleziona --</option>
                <?php foreach ($folders as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="nuovo_nome" placeholder="Nuovo nome" required>
            <button type="submit">Rinomina</button>
        </form>
    </div>

    <form method="post" style="margin: 20px 0;">
        <h4>Associa cartella a cliente</h4>
        <select name="cliente_id" required>
            <option value="">-- Cliente --</option>
            <?php foreach ($clientiDisponibili as $cliente): ?>
                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="nome_cartella" required>
            <option value="">-- Cartella --</option>
            <?php foreach ($cartelleRoot as $cartella): ?>
                <option value="<?= htmlspecialchars($cartella) ?>"><?= htmlspecialchars($cartella) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="associa_cartella">Associa</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associa_cartella'])) {
        $clienteId = $_POST['cliente_id'];
        $cartella = $_POST['nome_cartella'];
        if ($clienteId && $cartella) {
            $stmtUpdate = $pdo->prepare("UPDATE clienti SET link_cartella = ? WHERE id = ?");
            $stmtUpdate->execute([$cartella, $clienteId]);
            echo "<p style='color: green;'>Cartella $cartella associata a cliente ID $clienteId</p>";
            echo "<script>setTimeout(() => location.reload(), 1000);</script>";
        }
    }
    ?>

    <h3>📁 Cartelle</h3>
    <?php foreach ($folders as $folder): ?>
        <div class="entry folder">
            <a href="drive.php?path=<?= urlencode(trim($currentRelPath . '/' . $folder, '/')) ?>">
                📁 <?= htmlspecialchars($folder) ?>
            </a>
        </div>
    <?php endforeach; ?>

    <h3>📄 File</h3>
    <?php foreach ($files as $file): ?>
        <div class="entry">
            📄 <?= htmlspecialchars($file) ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
