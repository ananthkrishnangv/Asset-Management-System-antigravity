<?php
/**
 * Item Documents Management
 * Upload/view/delete images and documents for inventory items
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();

$itemId = (int) ($_GET['id'] ?? $_POST['item_id'] ?? 0);

if (!$itemId) {
    flash('error', 'Item not specified');
    redirect(url('public/dashboard.php'));
}

// Fetch item details
$item = $db->fetch("SELECT i.*, c.name as category_name, d.name as dept_name 
    FROM inventory_items i 
    LEFT JOIN categories c ON i.category_id = c.id 
    LEFT JOIN departments d ON i.department_id = d.id 
    WHERE i.id = ? AND i.is_active = 1", [$itemId]);

if (!$item) {
    flash('error', 'Item not found');
    redirect(url('public/dashboard.php'));
}

// Handle uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request');
        redirect(url("public/inventory/item-documents.php?id=$itemId"));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $docType = $_POST['document_type'] ?? 'image';
        $uploadDir = UPLOAD_PATH . 'documents/' . $itemId . '/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $uploaded = 0;
        $files = $_FILES['files'] ?? [];

        if (!empty($files['name'][0])) {
            $fileCount = count($files['name']);

            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $originalName = $files['name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    // Validate extension
                    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
                    if (!in_array($ext, $allowedExt))
                        continue;

                    $newName = $docType . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $filePath = $uploadDir . $newName;

                    if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                        $relativePath = 'uploads/documents/' . $itemId . '/' . $newName;

                        $db->query(
                            "INSERT INTO item_documents (item_id, document_type, file_path, original_name, uploaded_by) VALUES (?, ?, ?, ?, ?)",
                            [$itemId, $docType, $relativePath, $originalName, $user['id']]
                        );
                        $uploaded++;
                    }
                }
            }

            if ($uploaded > 0) {
                flash('success', "$uploaded file(s) uploaded successfully.");
            } else {
                flash('error', 'Failed to upload files.');
            }
        }

        redirect(url("public/inventory/item-documents.php?id=$itemId"));
    }

    if ($action === 'delete') {
        $docId = (int) ($_POST['doc_id'] ?? 0);
        $doc = $db->fetch("SELECT * FROM item_documents WHERE id = ? AND item_id = ?", [$docId, $itemId]);

        if ($doc) {
            // Delete file
            $fullPath = dirname(UPLOAD_PATH) . '/' . $doc['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $db->query("DELETE FROM item_documents WHERE id = ?", [$docId]);
            flash('success', 'Document deleted.');
        }

        redirect(url("public/inventory/item-documents.php?id=$itemId"));
    }
}

// Fetch existing documents
$documents = $db->fetchAll("SELECT * FROM item_documents WHERE item_id = ? ORDER BY document_type, created_at DESC", [$itemId]) ?: [];

// Group by type
$docsByType = [];
foreach ($documents as $doc) {
    $docsByType[$doc['document_type']][] = $doc;
}

$pageTitle = 'Item Documents';
$pageSubtitle = 'Manage images and documents for ' . truncate($item['item_description'], 40);
ob_start();
?>

<style>
    .doc-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }

    .doc-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .doc-preview {
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
    }

    .doc-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        transition: all 0.2s;
        background: #f9fafb;
    }

    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: #667eea;
        background: #f0f4ff;
    }
</style>

<div class="mb-6">
    <a href="<?= url('public/inventory/' . $item['inventory_type'] . '.php') ?>"
        class="text-purple-600 hover:text-purple-800">
        <i class="fas fa-arrow-left mr-2"></i>Back to <?= strtoupper($item['inventory_type']) ?> List
    </a>
</div>

<!-- Item Info Card -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-800"><?= Security::escape($item['item_description']) ?></h2>
            <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-500">
                <span><i class="fas fa-barcode mr-1"></i><?= Security::escape($item['serial_number']) ?></span>
                <span><i class="fas fa-folder mr-1"></i><?= Security::escape($item['category_name'] ?? 'N/A') ?></span>
                <span><i class="fas fa-building mr-1"></i><?= Security::escape($item['dept_name'] ?? 'N/A') ?></span>
                <span><i class="fas fa-rupee-sign mr-1"></i><?= formatCurrency($item['unit_price'] ?? 0) ?></span>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= url('public/inventory/add.php?id=' . $itemId) ?>"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 font-medium">
                <i class="fas fa-edit mr-2"></i>Edit Item
            </a>
        </div>
    </div>
</div>

<!-- Upload Section -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-cloud-upload-alt text-purple-500 mr-2"></i>Upload Documents
    </h3>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
        <input type="hidden" name="action" value="upload">
        <input type="hidden" name="item_id" value="<?= $itemId ?>">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="md:col-span-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                <select name="document_type"
                    class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="image">📷 Item Image</option>
                    <option value="dir_book">📕 DIR Book Scan</option>
                    <option value="pir_book">📗 PIR Book Scan</option>
                    <option value="purchase_order">📄 Purchase Order</option>
                    <option value="other">📎 Other Document</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Files</label>
                <div class="upload-zone" id="dropZone">
                    <input type="file" name="files[]" multiple accept="image/*,.pdf" class="hidden" id="fileInput">
                    <label for="fileInput" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600 font-medium">Click to select or drag & drop files here</p>
                        <p class="text-gray-400 text-sm mt-1">Images (JPG, PNG, GIF, WebP) or PDF documents</p>
                    </label>
                    <div id="fileList" class="mt-4 text-left hidden"></div>
                </div>
            </div>
        </div>

        <button type="submit"
            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium hover:from-purple-700 hover:to-indigo-700 transition-all">
            <i class="fas fa-upload mr-2"></i>Upload Files
        </button>
    </form>
</div>

<!-- Document Gallery -->
<?php
$typeLabels = [
    'image' => ['Item Images', 'fa-images', 'purple'],
    'dir_book' => ['DIR Book Scans', 'fa-book', 'blue'],
    'pir_book' => ['PIR Book Scans', 'fa-book', 'green'],
    'purchase_order' => ['Purchase Orders', 'fa-file-invoice', 'amber'],
    'other' => ['Other Documents', 'fa-file-alt', 'gray'],
];
?>

<?php foreach ($typeLabels as $type => $info): ?>
    <?php $docs = $docsByType[$type] ?? []; ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas <?= $info[1] ?> text-<?= $info[2] ?>-500 mr-2"></i><?= $info[0] ?>
            <span class="text-sm font-normal text-gray-400 ml-2">(<?= count($docs) ?>)</span>
        </h3>

        <?php if (empty($docs)): ?>
            <div class="text-center py-8 text-gray-400">
                <i class="fas <?= $info[1] ?> text-3xl mb-2"></i>
                <p>No <?= strtolower($info[0]) ?> uploaded yet</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($docs as $doc): ?>
                    <?php
                    $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    ?>
                    <div class="doc-card group">
                        <div class="doc-preview">
                            <?php if ($isImage): ?>
                                <a href="<?= url($doc['file_path']) ?>" target="_blank">
                                    <img src="<?= url($doc['file_path']) ?>" alt="Document">
                                </a>
                            <?php else: ?>
                                <a href="<?= url($doc['file_path']) ?>" target="_blank" class="text-red-500">
                                    <i class="fas fa-file-pdf text-5xl"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="text-xs text-gray-500 truncate" title="<?= Security::escape($doc['original_name']) ?>">
                                <?= Security::escape(truncate($doc['original_name'], 20)) ?>
                            </p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-400"><?= date('M j', strtotime($doc['created_at'])) ?></span>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this document?')">
                                    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                    <button type="submit"
                                        class="text-red-400 hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const fileList = document.getElementById('fileList');

    // Drag and drop
    ['dragenter', 'dragover'].forEach(event => {
        dropZone.addEventListener(event, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        dropZone.addEventListener(event, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        fileInput.files = files;
        showFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        showFiles(e.target.files);
    });

    function showFiles(files) {
        if (files.length > 0) {
            fileList.classList.remove('hidden');
            fileList.innerHTML = '<p class="font-medium text-gray-700 mb-2">Selected files:</p>' +
                Array.from(files).map(f => `<div class="text-sm text-gray-500"><i class="fas fa-file mr-2"></i>${f.name}</div>`).join('');
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>