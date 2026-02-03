<?php
/**
 * Bulk Import Page
 * Allows users to import inventory items from CSV/Excel files
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();

$message = '';
$messageType = '';
$previewData = [];
$importResult = null;

// Handle template download
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    BulkImport::downloadTemplate();
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } elseif (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt'])) {
            $message = 'Only CSV files are allowed.';
            $messageType = 'error';
        } else {
            $importer = new BulkImport();
            $data = $importer->parseCSV($file['tmp_name']);

            if (empty($data)) {
                $message = 'No data found in the file or file could not be parsed.';
                $messageType = 'error';
            } elseif (isset($_POST['preview'])) {
                // Preview mode
                $previewData = array_slice($data, 0, 10); // Show first 10 rows
                $message = 'Preview of first ' . count($previewData) . ' rows. Total rows: ' . count($data);
                $messageType = 'info';

                // Store file for import
                $tempPath = sys_get_temp_dir() . '/ams_import_' . session_id() . '.csv';
                move_uploaded_file($file['tmp_name'], $tempPath);
                $_SESSION['import_temp_file'] = $tempPath;
                $_SESSION['import_total_rows'] = count($data);
            } elseif (isset($_POST['import'])) {
                // Import mode
                $tempPath = $_SESSION['import_temp_file'] ?? null;
                if ($tempPath && file_exists($tempPath)) {
                    $data = $importer->parseCSV($tempPath);
                    $importResult = $importer->import($data, Auth::id());
                    unlink($tempPath);
                    unset($_SESSION['import_temp_file'], $_SESSION['import_total_rows']);

                    if ($importResult['success'] > 0) {
                        $message = "Successfully imported {$importResult['success']} items.";
                        $messageType = 'success';
                    }
                    if (!empty($importResult['errors'])) {
                        $message .= ' Some errors occurred.';
                        $messageType = $importResult['success'] > 0 ? 'warning' : 'error';
                    }
                } else {
                    $message = 'Session expired. Please upload the file again.';
                    $messageType = 'error';
                }
            }
        }
    } else {
        $message = 'Please select a file to upload.';
        $messageType = 'error';
    }
}

$pageTitle = 'Bulk Import';
$pageSubtitle = 'Import inventory items from CSV';
ob_start();
?>

<style>
    .import-card {
        background: white;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .import-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .import-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .upload-zone {
        border: 2px dashed #E5E7EB;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .upload-zone:hover {
        border-color: #3B82F6;
        background: #F8FAFC;
    }

    .upload-zone.dragover {
        border-color: #3B82F6;
        background: #EFF6FF;
    }

    .preview-table {
        width: 100%;
        font-size: 0.75rem;
    }

    .preview-table th {
        background: #F9FAFB;
        padding: 0.5rem;
        text-align: left;
        font-weight: 600;
        color: #6B7280;
        white-space: nowrap;
    }

    .preview-table td {
        padding: 0.5rem;
        border-top: 1px solid #F3F4F6;
        white-space: nowrap;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?php
    echo match ($messageType) {
        'success' => 'bg-green-50 border border-green-200 text-green-800',
        'error' => 'bg-red-50 border border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border border-yellow-200 text-yellow-800',
        default => 'bg-blue-50 border border-blue-200 text-blue-800'
    };
    ?>">
        <i class="fas <?php
        echo match ($messageType) {
            'success' => 'fa-check-circle text-green-500',
            'error' => 'fa-exclamation-circle text-red-500',
            'warning' => 'fa-exclamation-triangle text-yellow-500',
            default => 'fa-info-circle text-blue-500'
        };
        ?>"></i>
        <span>
            <?= Security::escape($message) ?>
        </span>
    </div>
<?php endif; ?>

<!-- Import Errors -->
<?php if (!empty($importResult['errors'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200">
        <div class="flex items-center gap-2 mb-2 font-semibold text-red-800">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Import Errors (
                <?= count($importResult['errors']) ?>)
            </span>
        </div>
        <div class="max-h-40 overflow-y-auto text-sm text-red-700">
            <?php foreach (array_slice($importResult['errors'], 0, 20) as $err): ?>
                <div class="py-1">
                    <?= Security::escape($err) ?>
                </div>
            <?php endforeach; ?>
            <?php if (count($importResult['errors']) > 20): ?>
                <div class="py-1 font-medium">... and
                    <?= count($importResult['errors']) - 20 ?> more errors
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Upload Section -->
    <div class="lg:col-span-2">
        <div class="import-card">
            <div class="import-card-header">
                <h3 class="import-card-title">
                    <i class="fas fa-file-upload text-blue-500"></i>
                    Upload CSV File
                </h3>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <?= Security::csrfField() ?>

                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('import_file').click()">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 font-medium mb-2">Drop your CSV file here or click to browse</p>
                    <p class="text-sm text-gray-400">Supports: CSV files only</p>
                    <input type="file" name="import_file" id="import_file" accept=".csv,.txt" class="hidden"
                        onchange="updateFileName(this)">
                    <p id="fileName" class="mt-4 text-blue-600 font-medium hidden"></p>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" name="preview" value="1"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-eye"></i>
                        <span>Preview Data</span>
                    </button>
                    <?php if (!empty($_SESSION['import_temp_file'])): ?>
                        <button type="submit" name="import" value="1"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-file-import"></i>
                            <span>Import
                                <?= $_SESSION['import_total_rows'] ?? 0 ?> Rows
                            </span>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Preview Table -->
        <?php if (!empty($previewData)): ?>
            <div class="import-card mt-6">
                <div class="import-card-header">
                    <h3 class="import-card-title">
                        <i class="fas fa-table text-purple-500"></i>
                        Data Preview
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($previewData[0]) as $header): ?>
                                    <?php if ($header !== '_line'): ?>
                                        <th>
                                            <?= Security::escape(ucwords(str_replace('_', ' ', $header))) ?>
                                        </th>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewData as $row): ?>
                                <tr>
                                    <?php foreach ($row as $key => $val): ?>
                                        <?php if ($key !== '_line'): ?>
                                            <td title="<?= Security::escape($val) ?>">
                                                <?= Security::escape($val) ?>
                                            </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Instructions Section -->
    <div class="space-y-6">
        <div class="import-card">
            <div class="import-card-header">
                <h3 class="import-card-title">
                    <i class="fas fa-download text-green-500"></i>
                    Download Template
                </h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Download the CSV template, fill in your data, and upload it.</p>
                <a href="?action=download_template"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all w-full justify-center">
                    <i class="fas fa-file-csv"></i>
                    <span>Download Template</span>
                </a>
            </div>
        </div>

        <div class="import-card">
            <div class="import-card-header">
                <h3 class="import-card-title">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    Instructions
                </h3>
            </div>
            <div class="p-6 text-sm text-gray-600 space-y-3">
                <div class="flex gap-3">
                    <span
                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">1</span>
                    <span>Download the template CSV file</span>
                </div>
                <div class="flex gap-3">
                    <span
                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">2</span>
                    <span>Fill in your inventory data (keep headers)</span>
                </div>
                <div class="flex gap-3">
                    <span
                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">3</span>
                    <span>Upload the file and preview your data</span>
                </div>
                <div class="flex gap-3">
                    <span
                        class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">4</span>
                    <span>Click Import to add items</span>
                </div>
            </div>
        </div>

        <div class="import-card">
            <div class="import-card-header">
                <h3 class="import-card-title">
                    <i class="fas fa-list text-amber-500"></i>
                    Required Fields
                </h3>
            </div>
            <div class="p-4">
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> inventory_type
                        (dir/pir)</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> serial_number</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> item_description
                    </li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> category</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> department</li>
                    <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> unit_price</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('import_file');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => uploadZone.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => uploadZone.classList.remove('dragover'), false);
    });

    uploadZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            updateFileName(fileInput);
        }
    });

    function updateFileName(input) {
        const fileName = document.getElementById('fileName');
        if (input.files.length) {
            fileName.textContent = input.files[0].name;
            fileName.classList.remove('hidden');
        }
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>