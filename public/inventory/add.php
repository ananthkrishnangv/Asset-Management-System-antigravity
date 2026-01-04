<?php
/**
 * Add/Edit Asset Page - Enhanced V3
 * Uses inventory_items table with full file upload support
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();
Auth::requireSupervisor(); // Only supervisors and admins can add

$db = Database::getInstance();

// Get form data
$type = $_GET['type'] ?? $_POST['type'] ?? 'dir';
$editId = $_GET['id'] ?? $_POST['id'] ?? null;
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

// Load departments and categories for dropdowns
$departments = $db->fetchAll("SELECT id, name, code FROM departments ORDER BY name");
$categories = $db->fetchAll("SELECT id, name, code FROM categories ORDER BY name");
$users = $db->fetchAll("SELECT id, ams_id, emp_name FROM users WHERE is_active = 1 ORDER BY emp_name");

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // File Upload Handler
        $uploadDir = UPLOAD_PATH;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        
        $scannedCopy = $item['scanned_copy'] ?? '';
        $assetImage = $item['asset_image'] ?? '';
        
        // Handle scanned copy upload
        if (!empty($_FILES['scanned_copy']['name']) && $_FILES['scanned_copy']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['scanned_copy']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_EXTENSIONS)) {
                $fileName = 'scan_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['scanned_copy']['tmp_name'], $uploadDir . $fileName)) {
                    // Delete old file if exists
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
                    // Delete old file if exists
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
            'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
            'department_id' => (int)($_POST['department_id'] ?? 0) ?: null,
            'current_holder_id' => (int)($_POST['holder_id'] ?? 0) ?: null,
            'quantity' => (int)($_POST['quantity'] ?? 1),
            'quantity_unit' => Security::sanitize($_POST['unit'] ?? 'No.'),
            'unit_price' => (float)($_POST['amount'] ?? 0),
            'amount' => (float)($_POST['amount'] ?? 0),
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
                // Update
                $db->update('inventory_items', $data, 'id = :id', ['id' => $editId]);
                ActivityLog::log('update', 'inventory_items', $editId, 'inventory', 
                    'Updated ' . strtoupper($type) . ' item: ' . $data['serial_number']);
                flash('success', 'Asset updated successfully!');
            } else {
                // Insert
                $data['created_by'] = Auth::id();
                $db->insert('inventory_items', $data);
                $newId = $db->lastInsertId();
                
                // Generate QR code path
                $qrPath = 'uploads/qr/qr_' . $newId . '_' . time() . '.png';
                $db->query("UPDATE inventory_items SET qr_code_path = ? WHERE id = ?", [$qrPath, $newId]);
                
                ActivityLog::log('create', 'inventory_items', $newId, 'inventory', 
                    'Added new ' . strtoupper($type) . ' item: ' . $data['serial_number']);
                flash('success', 'Asset added successfully!');
            }
            
            redirect(url("public/inventory/{$type}.php"));
        } catch (Exception $e) {
            $error = "Error saving asset: " . $e->getMessage();
        }
    }
}

$pageTitle = ($editId ? 'Edit' : 'Add New') . ' Asset (' . strtoupper($type) . ')';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --dark: #1E293B;
            --light: #F8FAFC;
        }
        
        body { 
            font-family: 'Noto Sans', sans-serif; 
            background: var(--light); 
            color: #334155; 
        }
        
        .sidebar { 
            width: 260px; 
            background: var(--dark); 
            min-height: 100vh; 
            position: fixed; 
        }
        
        .content { 
            margin-left: 260px; 
            padding: 2rem; 
        }
        
        .nav-link { 
            color: #cbd5e1; 
            padding: 0.8rem 1.5rem; 
            display: block; 
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active { 
            background: #0f172a; 
            color: white;
            border-left-color: var(--primary);
        }
        
        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border: none;
        }
        
        .section-header {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-primary:hover { background: var(--primary-dark); }
        
        .preview-box {
            width: 100%;
            height: 150px;
            border: 2px dashed #e2e8f0;
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
            background: #e2e8f0;
            border-radius: 10px;
            padding: 4px;
        }
        
        .type-toggle .btn {
            flex: 1;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        
        .type-toggle .btn.active {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar py-4">
        <div class="px-4 mb-4">
            <h4 class="text-white fw-bold"><i class="fas fa-cube me-2"></i>AMS</h4>
        </div>
        <a href="<?= url('public/dashboard.php') ?>" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="<?= url('public/inventory/dir.php') ?>" class="nav-link <?= $type === 'dir' ? 'active' : '' ?>"><i class="fas fa-list-alt me-2"></i> DIR Inventory</a>
        <a href="<?= url('public/inventory/pir.php') ?>" class="nav-link <?= $type === 'pir' ? 'active' : '' ?>"><i class="fas fa-clipboard-list me-2"></i> PIR Inventory</a>
        <a href="<?= url('public/reports/index.php') ?>" class="nav-link"><i class="fas fa-chart-pie me-2"></i> Reports</a>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/logout.php') ?>" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><?= $editId ? 'Edit' : 'Add New' ?> Asset</h2>
                <p class="text-muted mb-0"><?= strtoupper($type) ?> Inventory</p>
            </div>
            <a href="<?= url("public/inventory/{$type}.php") ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="fas fa-exclamation-triangle me-2"></i><?= Security::escape($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?= Security::csrfField() ?>
            <input type="hidden" name="type" value="<?= $type ?>">
            <?php if ($editId): ?>
                <input type="hidden" name="id" value="<?= $editId ?>">
            <?php endif; ?>
            
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <div class="form-card p-4 mb-4">
                        <div class="section-header">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </div>
                        
                        <?php if (!$editId): ?>
                        <div class="mb-4">
                            <label class="form-label">Inventory Type</label>
                            <div class="type-toggle">
                                <a href="?type=dir" class="btn <?= $type === 'dir' ? 'active' : '' ?>">
                                    <i class="fas fa-building me-2"></i>DIR (Departmental)
                                </a>
                                <a href="?type=pir" class="btn <?= $type === 'pir' ? 'active' : '' ?>">
                                    <i class="fas fa-user me-2"></i>PIR (Personal)
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Serial Number / Asset ID</label>
                                <input type="text" name="serial_number" class="form-control" 
                                       value="<?= Security::escape($item['serial_number'] ?? '') ?>"
                                       placeholder="Auto-generated if empty">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-select select2" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($item['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= Security::escape($cat['name']) ?> (<?= $cat['code'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Item Description *</label>
                                <textarea name="description" class="form-control" rows="3" required><?= Security::escape($item['item_description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" 
                                       value="<?= $item['quantity'] ?? 1 ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit</label>
                                <input type="text" name="unit" class="form-control" 
                                       value="<?= Security::escape($item['quantity_unit'] ?? 'No.') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount (₹)</label>
                                <input type="number" name="amount" class="form-control" step="0.01" min="0"
                                       value="<?= $item['amount'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Condition</label>
                                <select name="condition" class="form-select">
                                    <?php foreach (['new', 'good', 'fair', 'poor', 'non_serviceable', 'scrapped'] as $cond): ?>
                                        <option value="<?= $cond ?>" <?= ($item['condition_status'] ?? 'good') == $cond ? 'selected' : '' ?>>
                                            <?= ucfirst(str_replace('_', ' ', $cond)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-card p-4 mb-4">
                        <div class="section-header">
                            <i class="fas fa-map-marker-alt"></i> Location & Assignment
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select name="department_id" class="form-select select2" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= ($item['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                            <?= Security::escape($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Current Holder</label>
                                <select name="holder_id" class="form-select select2">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= ($item['current_holder_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                            <?= Security::escape($user['emp_name']) ?> (<?= $user['ams_id'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Building</label>
                                <input type="text" name="building" class="form-control" 
                                       value="<?= Security::escape($item['building_location'] ?? '') ?>" placeholder="e.g., Main Block">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Floor</label>
                                <input type="text" name="floor" class="form-control" 
                                       value="<?= Security::escape($item['floor_location'] ?? '') ?>" placeholder="e.g., 2nd Floor">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room</label>
                                <input type="text" name="room" class="form-control" 
                                       value="<?= Security::escape($item['room_location'] ?? '') ?>" placeholder="e.g., Room 201">
                            </div>
                        </div>
                    </div>

                    <div class="form-card p-4 mb-4">
                        <div class="section-header">
                            <i class="fas fa-file-invoice"></i> Purchase Details
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">PO Number</label>
                                <input type="text" name="po_number" class="form-control" 
                                       value="<?= Security::escape($item['po_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PO Date</label>
                                <input type="date" name="po_date" class="form-control" 
                                       value="<?= $item['po_date'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control" 
                                       value="<?= $item['purchase_date'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" name="supplier_name" class="form-control" 
                                       value="<?= Security::escape($item['supplier_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Invoice Number</label>
                                <input type="text" name="invoice_number" class="form-control" 
                                       value="<?= Security::escape($item['invoice_number'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Budget Head</label>
                                <input type="text" name="budget_head" class="form-control" 
                                       value="<?= Security::escape($item['budget_head'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Stock Reference</label>
                                <input type="text" name="stock_reference" class="form-control" 
                                       value="<?= Security::escape($item['stock_reference'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">QR / Barcode Data</label>
                                <input type="text" name="qr_code_data" class="form-control" 
                                       value="<?= Security::escape($item['qr_code_data'] ?? '') ?>" placeholder="Auto-generated if empty">
                            </div>
                        </div>
                    </div>

                    <div class="form-card p-4">
                        <div class="section-header">
                            <i class="fas fa-shield-alt"></i> Warranty & AMC
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Warranty Expiry</label>
                                <input type="date" name="warranty_expiry" class="form-control" 
                                       value="<?= $item['warranty_expiry'] ?? '' ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Warranty Details</label>
                                <input type="text" name="warranty_details" class="form-control" 
                                       value="<?= Security::escape($item['warranty_details'] ?? '') ?>" placeholder="Terms, conditions...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">AMC Expiry</label>
                                <input type="date" name="amc_expiry" class="form-control" 
                                       value="<?= $item['amc_expiry'] ?? '' ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">AMC Details</label>
                                <input type="text" name="amc_details" class="form-control" 
                                       value="<?= Security::escape($item['amc_details'] ?? '') ?>" placeholder="Contractor, amount...">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"><?= Security::escape($item['remarks'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <div class="form-card p-4 mb-4">
                        <div class="section-header">
                            <i class="fas fa-camera"></i> Asset Image
                        </div>
                        
                        <div class="preview-box mb-3" id="imagePreview">
                            <?php if (!empty($item['asset_image'])): ?>
                                <img src="<?= url('uploads/' . $item['asset_image']) ?>" alt="Asset">
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-image fa-2x mb-2"></i><br>No image</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="asset_image" class="form-control" accept="image/*" id="imageInput">
                        <small class="text-muted">JPG, PNG, GIF up to 10MB</small>
                    </div>

                    <div class="form-card p-4 mb-4">
                        <div class="section-header">
                            <i class="fas fa-file-pdf"></i> Scanned Document
                        </div>
                        
                        <?php if (!empty($item['scanned_copy'])): ?>
                            <div class="alert alert-info py-2 mb-3">
                                <i class="fas fa-file me-2"></i>
                                <a href="<?= url('uploads/' . $item['scanned_copy']) ?>" target="_blank">
                                    View Current Document
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <input type="file" name="scanned_copy" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Upload scanned copy from DIR/PIR register</small>
                    </div>

                    <div class="form-card p-4">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i><?= $editId ? 'Update' : 'Save' ?> Asset
                            </button>
                            <a href="<?= url("public/inventory/{$type}.php") ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        });

        // Image preview
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = 
                        '<img src="' + e.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
