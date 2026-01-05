<?php
/**
 * Add/Edit Asset Page - Consistent UI
 * Uses layout template for consistent sidebar
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();
Auth::requireSupervisor();

$db = Database::getInstance();

// Get form data
$type = $_GET['type'] ?? $_POST['type'] ?? 'dir';
$editId = $_GET['edit'] ?? $_GET['id'] ?? $_POST['id'] ?? null;
$item = null;

// If editing, load the item
if ($editId) {
    $item = $db->fetch("SELECT * FROM inventory_items WHERE id = ? AND is_active = 1", [$editId]);
    if (!$item) {
        flash('error', 'Item not found');
        redirect(url("public/inventory/{$type}.php"));
    }
    $type = $item['inventory_type'];
}

// Load dropdowns
$departments = $db->fetchAll("SELECT id, name, code FROM departments ORDER BY name");
$categories = $db->fetchAll("SELECT id, name, code FROM categories ORDER BY name");
$users = $db->fetchAll("SELECT id, ams_id, emp_name FROM users WHERE is_active = 1 ORDER BY emp_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        flash('error', 'Invalid request. Please try again.');
        redirect(url("public/inventory/add.php?type={$type}" . ($editId ? "&edit={$editId}" : "")));
    }

    // File Upload Handler
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0775, true);

    $scannedCopy = $item['scanned_copy'] ?? '';
    $assetImage = $item['asset_image'] ?? '';

    // Handle scanned copy upload
    if (!empty($_FILES['scanned_copy']['name']) && $_FILES['scanned_copy']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['scanned_copy']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ALLOWED_EXTENSIONS)) {
            $fileName = 'scan_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['scanned_copy']['tmp_name'], $uploadDir . $fileName)) {
                if ($scannedCopy && file_exists($uploadDir . $scannedCopy)) {
                    unlink($uploadDir . $scannedCopy);
                }
                $scannedCopy = $fileName;
            }
        }
    }

    // Handle asset image upload
    if (!empty($_FILES['asset_image']['name']) && $_FILES['asset_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['asset_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $fileName = 'img_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['asset_image']['tmp_name'], $uploadDir . $fileName)) {
                if ($assetImage && file_exists($uploadDir . $assetImage)) {
                    unlink($uploadDir . $assetImage);
                }
                $assetImage = $fileName;
            }
        }
    }

    // Generate QR code data
    $qrCodeData = $_POST['qr_code_data'] ?? '';
    if (empty($qrCodeData)) {
        $qrCodeData = 'AMS-' . strtoupper($type) . '-' . date('Ymd') . '-' . substr(uniqid(), -6);
    }

    // Prepare data
    $data = [
        'item_description' => Security::sanitize($_POST['description']),
        'serial_number' => Security::sanitize($_POST['serial_number'] ?? $qrCodeData),
        'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
        'department_id' => (int) ($_POST['department_id'] ?? 0) ?: null,
        'current_holder_id' => (int) ($_POST['holder_id'] ?? 0) ?: null,
        'quantity' => (int) ($_POST['quantity'] ?? 1),
        'quantity_unit' => Security::sanitize($_POST['unit'] ?? 'No.'),
        'unit_price' => (float) ($_POST['amount'] ?? 0),
        'amount' => (float) ($_POST['amount'] ?? 0),
        'po_number' => Security::sanitize($_POST['po_number'] ?? ''),
        'po_date' => $_POST['po_date'] ?: null,
        'purchase_date' => $_POST['purchase_date'] ?: null,
        'budget_head' => Security::sanitize($_POST['budget_head'] ?? ''),
        'stock_reference' => Security::sanitize($_POST['stock_reference'] ?? ''),
        'supplier_name' => Security::sanitize($_POST['supplier_name'] ?? ''),
        'invoice_number' => Security::sanitize($_POST['invoice_number'] ?? ''),
        'building_location' => Security::sanitize($_POST['building'] ?? ''),
        'floor_location' => Security::sanitize($_POST['floor'] ?? ''),
        'room_location' => Security::sanitize($_POST['room'] ?? ''),
        'location' => Security::sanitize($_POST['location'] ?? ''),
        'condition_status' => $_POST['condition'] ?? 'good',
        'inventory_type' => $type,
        'remarks' => Security::sanitize($_POST['remarks'] ?? ''),
        'scanned_copy' => $scannedCopy,
        'asset_image' => $assetImage,
        'amc_details' => Security::sanitize($_POST['amc_details'] ?? ''),
        'amc_expiry' => $_POST['amc_expiry'] ?: null,
        'warranty_details' => Security::sanitize($_POST['warranty_details'] ?? ''),
        'warranty_expiry' => $_POST['warranty_expiry'] ?: null,
        'qr_code_data' => $qrCodeData,
    ];

    try {
        if ($editId) {
            $db->update('inventory_items', $data, 'id = :id', ['id' => $editId]);
            ActivityLog::log(
                'update',
                'inventory_items',
                $editId,
                'inventory',
                'Updated ' . strtoupper($type) . ' item: ' . $data['serial_number']
            );
            flash('success', 'Asset updated successfully!');
        } else {
            $data['created_by'] = Auth::id();
            $db->insert('inventory_items', $data);
            $newId = $db->lastInsertId();

            $qrPath = 'uploads/qr/qr_' . $newId . '_' . time() . '.png';
            $db->query("UPDATE inventory_items SET qr_code_path = ? WHERE id = ?", [$qrPath, $newId]);

            ActivityLog::log(
                'create',
                'inventory_items',
                $newId,
                'inventory',
                'Added new ' . strtoupper($type) . ' item: ' . $data['serial_number']
            );
            flash('success', 'Asset added successfully!');
        }

        redirect(url("public/inventory/{$type}.php"));
    } catch (Exception $e) {
        flash('error', "Error saving asset: " . $e->getMessage());
        redirect(url("public/inventory/add.php?type={$type}" . ($editId ? "&edit={$editId}" : "")));
    }
}

$pageTitle = ($editId ? 'Edit' : 'Add New') . ' ' . strtoupper($type) . ' Item';
$pageSubtitle = strtoupper($type) . ' Inventory';

ob_start();
?>

<style>
    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
    }

    .section-header {
        font-size: 0.9rem;
        font-weight: 600;
        color: #6366f1;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.625rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        margin-bottom: 0.5rem;
    }

    .preview-box {
        width: 100%;
        height: 140px;
        border: 2px dashed #e5e7eb;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        overflow: hidden;
    }

    .preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .type-toggle {
        display: flex;
        background: #f1f5f9;
        border-radius: 10px;
        padding: 4px;
    }

    .type-toggle a {
        flex: 1;
        text-align: center;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        color: #64748b;
        transition: all 0.2s;
    }

    .type-toggle a.active {
        background: white;
        color: #1e293b;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<!-- Header Actions -->
<div class="flex flex-wrap gap-3 justify-between items-center mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= url("public/inventory/{$type}.php") ?>" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-arrow-left"></i>
        </a>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-500"><?= $editId ? 'Editing item #' . $editId : 'Creating new item' ?></span>
    </div>
    <a href="<?= url("public/inventory/{$type}.php") ?>"
        class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 transition-all">
        Cancel
    </a>
</div>

<form method="POST" enctype="multipart/form-data">
    <?= Security::csrfField() ?>
    <input type="hidden" name="type" value="<?= $type ?>">
    <?php if ($editId): ?>
        <input type="hidden" name="id" value="<?= $editId ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-info-circle"></i> Basic Information
                </div>

                <?php if (!$editId): ?>
                    <div class="mb-5">
                        <label class="form-label">Inventory Type</label>
                        <div class="type-toggle">
                            <a href="?type=dir" class="<?= $type === 'dir' ? 'active' : '' ?>">
                                <i class="fas fa-building mr-2"></i>DIR (Departmental)
                            </a>
                            <a href="?type=pir" class="<?= $type === 'pir' ? 'active' : '' ?>">
                                <i class="fas fa-user mr-2"></i>PIR (Personal)
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Serial Number / Asset ID</label>
                        <input type="text" name="serial_number" class="form-input"
                            value="<?= Security::escape($item['serial_number'] ?? '') ?>"
                            placeholder="Auto-generated if empty">
                    </div>
                    <div>
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-input" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= Security::escape($cat['name']) ?> (<?= $cat['code'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Item Description *</label>
                        <textarea name="description" class="form-input" rows="3"
                            required><?= Security::escape($item['item_description'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" class="form-input" min="1"
                            value="<?= $item['quantity'] ?? 1 ?>">
                    </div>
                    <div>
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" class="form-input"
                            value="<?= Security::escape($item['quantity_unit'] ?? 'No.') ?>">
                    </div>
                    <div>
                        <label class="form-label">Amount (₹)</label>
                        <input type="number" name="amount" class="form-input" step="0.01" min="0"
                            value="<?= $item['amount'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">Condition</label>
                        <select name="condition" class="form-input">
                            <?php foreach (['new', 'good', 'fair', 'poor', 'non_serviceable', 'scrapped'] as $cond): ?>
                                <option value="<?= $cond ?>" <?= ($item['condition_status'] ?? 'good') == $cond ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $cond)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Location & Assignment -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-map-marker-alt"></i> Location & Assignment
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Department *</label>
                        <select name="department_id" class="form-input" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($item['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= Security::escape($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Current Holder</label>
                        <select name="holder_id" class="form-input">
                            <option value="">Select Employee</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= ($item['current_holder_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= Security::escape($user['emp_name']) ?> (<?= $user['ams_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Building</label>
                        <input type="text" name="building" class="form-input"
                            value="<?= Security::escape($item['building_location'] ?? '') ?>"
                            placeholder="e.g., Main Block">
                    </div>
                    <div>
                        <label class="form-label">Floor</label>
                        <input type="text" name="floor" class="form-input"
                            value="<?= Security::escape($item['floor_location'] ?? '') ?>"
                            placeholder="e.g., 2nd Floor">
                    </div>
                    <div>
                        <label class="form-label">Room</label>
                        <input type="text" name="room" class="form-input"
                            value="<?= Security::escape($item['room_location'] ?? '') ?>" placeholder="e.g., Room 201">
                    </div>
                </div>
            </div>

            <!-- Purchase Details -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-file-invoice"></i> Purchase Details
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">PO Number</label>
                        <input type="text" name="po_number" class="form-input"
                            value="<?= Security::escape($item['po_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">PO Date</label>
                        <input type="date" name="po_date" class="form-input" value="<?= $item['po_date'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-input"
                            value="<?= $item['purchase_date'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">Supplier</label>
                        <input type="text" name="supplier_name" class="form-input"
                            value="<?= Security::escape($item['supplier_name'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Invoice No.</label>
                        <input type="text" name="invoice_number" class="form-input"
                            value="<?= Security::escape($item['invoice_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">Budget Head</label>
                        <input type="text" name="budget_head" class="form-input"
                            value="<?= Security::escape($item['budget_head'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Warranty & AMC -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-shield-alt"></i> Warranty & AMC
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" class="form-input"
                            value="<?= $item['warranty_expiry'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">Warranty Details</label>
                        <input type="text" name="warranty_details" class="form-input"
                            value="<?= Security::escape($item['warranty_details'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="form-label">AMC Expiry</label>
                        <input type="date" name="amc_expiry" class="form-input"
                            value="<?= $item['amc_expiry'] ?? '' ?>">
                    </div>
                    <div>
                        <label class="form-label">AMC Details</label>
                        <input type="text" name="amc_details" class="form-input"
                            value="<?= Security::escape($item['amc_details'] ?? '') ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-input"
                            rows="2"><?= Security::escape($item['remarks'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Asset Image -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-camera"></i> Asset Image
                </div>

                <div class="preview-box mb-3" id="imagePreview">
                    <?php if (!empty($item['asset_image'])): ?>
                        <img src="<?= url('uploads/' . $item['asset_image']) ?>" alt="Asset">
                    <?php else: ?>
                        <span class="text-gray-400 text-center">
                            <i class="fas fa-image text-2xl mb-2"></i><br>
                            <span class="text-xs">No image</span>
                        </span>
                    <?php endif; ?>
                </div>
                <input type="file" name="asset_image" class="form-input text-sm" accept="image/*" id="imageInput">
                <p class="text-xs text-gray-400 mt-2">JPG, PNG, GIF up to 10MB</p>
            </div>

            <!-- Scanned Document -->
            <div class="form-card p-5">
                <div class="section-header">
                    <i class="fas fa-file-pdf"></i> Scanned Document
                </div>

                <?php if (!empty($item['scanned_copy'])): ?>
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-3">
                        <i class="fas fa-file text-blue-500 mr-2"></i>
                        <a href="<?= url('uploads/' . $item['scanned_copy']) ?>" target="_blank"
                            class="text-blue-600 text-sm hover:underline">
                            View Current Document
                        </a>
                    </div>
                <?php endif; ?>

                <input type="file" name="scanned_copy" class="form-input text-sm" accept=".pdf,.jpg,.jpeg,.png">
                <p class="text-xs text-gray-400 mt-2">Upload scanned copy from DIR/PIR register</p>
            </div>

            <!-- Save Button -->
            <div class="form-card p-5">
                <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i><?= $editId ? 'Update' : 'Save' ?> Asset
                </button>
                <a href="<?= url("public/inventory/{$type}.php") ?>"
                    class="block w-full text-center mt-3 py-2.5 text-gray-500 hover:text-gray-700">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    document.getElementById('imageInput')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('imagePreview').innerHTML =
                    '<img src="' + e.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>