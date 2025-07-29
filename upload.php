<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$base_dir = __DIR__ . '/local_drive/';
$relative_path = $_GET['path'] ?? '';
$current_dir = realpath($base_dir . $relative_path);

// Verifica se è una chiamata modal
$is_modal = isset($_GET['modal']) && $_GET['modal'] == '1';

if (!$current_dir || strpos($current_dir, realpath($base_dir)) !== 0) {
    $current_dir = $base_dir;
    $relative_path = '';
}

$errore = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        // Proteggi da nomi pericolosi
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            $errore = "Nome file non valido.";
        } else {
            $target = $current_dir . '/' . $filename;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                $success = true;
                if ($is_modal) {
                    echo "<script>
                        if (window.parent && window.parent.closeModal) {
                            window.parent.closeModal();
                        } else {
                            window.location.href = 'drive.php?path=" . urlencode(trim($relative_path, '/')) . "';
                        }
                    </script>";
                    exit;
                } else {
                    header("Location: drive.php?path=" . urlencode(trim($relative_path, '/')));
                    exit;
                }
            } else {
                $errore = "Errore nel salvataggio del file.";
            }
        }
    } else {
        $errore = "Errore nell'upload del file.";
    }
}

if (!$is_modal) {
    require_once __DIR__ . '/includes/header.php';
}
?>

<?php if ($is_modal): ?>
<style>
body { 
    margin: 0; 
    padding: 2rem; 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8f9fa;
}
.modal-content {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}
.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.form-group {
    margin-bottom: 1.5rem;
}
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #333;
}
input[type="file"] {
    width: 100%;
    padding: 0.8rem;
    border: 2px dashed #ddd;
    border-radius: 6px;
    font-size: 1rem;
    background: #f8f9fa;
    cursor: pointer;
    transition: border-color 0.2s ease;
}
input[type="file"]:hover {
    border-color: #007bff;
    background: #e3f2fd;
}
.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    margin-right: 1rem;
    transition: all 0.2s ease;
}
.btn-success {
    background: #28a745;
    color: white;
}
.btn-success:hover {
    background: #218838;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.btn-secondary:hover {
    background: #5a6268;
}
h2 {
    margin-top: 0;
    color: #333;
}
.path-info {
    background: #e9ecef;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-family: monospace;
    font-size: 0.9rem;
}
.upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    background: #fafafa;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}
.upload-area:hover {
    border-color: #007bff;
    background: #f0f8ff;
}
.upload-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.6;
}
</style>
<div class="modal-content">
<?php endif; ?>

<h2>⬆️ Upload File</h2>
<div class="path-info">
    <strong>Destinazione:</strong> <?= htmlspecialchars($current_dir) ?>
</div>

<?php if ($errore): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <?php if ($is_modal): ?>
    <div class="upload-area">
        <div class="upload-icon">📁</div>
        <p><strong>Scegli un file da caricare</strong></p>
        <p style="color: #666; font-size: 0.9rem;">Trascina un file qui o clicca per selezionarlo</p>
    </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label>Scegli file da caricare:</label>
        <input type="file" name="file" required <?= $is_modal ? 'style="margin-top: 0.5rem;"' : '' ?>>
    </div>
    
    <button type="submit" class="btn btn-success">⬆️ Carica File</button>
    <?php if ($is_modal): ?>
        <button type="button" class="btn btn-secondary" onclick="window.parent.closeModal()">❌ Annulla</button>
    <?php else: ?>
        <a href="drive.php?path=<?= urlencode(trim($relative_path, '/')) ?>" class="btn btn-secondary">❌ Annulla</a>
    <?php endif; ?>
</form>

<?php if ($is_modal): ?>
<script>
// Migliora l'esperienza di upload con drag & drop
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.querySelector('.upload-area');
    const fileInput = document.querySelector('input[type="file"]');
    
    if (uploadArea && fileInput) {
        // Click su area per aprire file selector
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag & drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#007bff';
            uploadArea.style.background = '#f0f8ff';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.background = '#fafafa';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.background = '#fafafa';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                // Mostra il nome del file selezionato
                const fileName = files[0].name;
                uploadArea.innerHTML = `
                    <div class="upload-icon">📄</div>
                    <p><strong>File selezionato:</strong></p>
                    <p style="color: #007bff;">${fileName}</p>
                `;
            }
        });
        
        // Mostra file selezionato quando si usa il browser
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                uploadArea.innerHTML = `
                    <div class="upload-icon">📄</div>
                    <p><strong>File selezionato:</strong></p>
                    <p style="color: #007bff;">${fileName}</p>
                `;
            }
        });
    }
});
</script>
</div>
<?php else: ?>
</main>
</body>
</html>
<?php endif; ?>