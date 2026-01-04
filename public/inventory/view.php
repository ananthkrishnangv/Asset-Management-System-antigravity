<?php
/**
 * Detailed Asset View Page (V2)
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'dir';
$table = ($type === 'pir') ? 'pir_details' : 'dir_details';

if (!$id) {
    flash('error', 'Invalid Asset ID');
    redirect(url("public/inventory/{$type}.php"));
}

$db = Database::getInstance();
$item = $db->fetch("SELECT * FROM {$table} WHERE Item_ID = ?", [$id]);

if (!$item) {
    flash('error', 'Asset not found');
    redirect(url("public/inventory/{$type}.php"));
}

$pageTitle = 'Asset Details: #' . $item['Item_ID'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('assets/css/fluent.css') ?>">
    <style>
        .detail-label { font-size: 0.75rem; text-transform: uppercase; color: #605E5C; font-weight: 600; letter-spacing: 0.5px; }
        .detail-value { font-size: 1rem; color: #201F1E; font-weight: 500; }
        .asset-preview { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .doc-preview { background: #f3f3f3; padding: 2rem; text-align: center; border-radius: 8px; border: 1px dashed #ccc; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white" style="width: 260px; min-height: 100vh;">
            <div class="p-4">
                <h4 class="fw-bold mb-4"><i class="fas fa-cube me-2"></i>AMS</h4>
                <a href="<?= url('public/dashboard.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="<?= url('public/inventory/dir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100 <?= $type === 'dir' ? 'fw-bold opacity-100' : '' ?>"><i class="fas fa-list-alt me-2"></i> DIR Details</a>
                <a href="<?= url('public/inventory/pir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100 <?= $type === 'pir' ? 'fw-bold opacity-100' : '' ?>"><i class="fas fa-clipboard-list me-2"></i> PIR Details</a>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0 fw-light">Asset Details</h2>
                    <span class="badge bg-secondary"><?= strtoupper($type) ?> Entry #<?= $item['Item_ID'] ?></span>
                </div>
                <a href="<?= url("public/inventory/{$type}.php") ?>" class="btn btn-outline-primary btn-sm">Back to List</a>
            </div>

            <div class="row g-4">
                <!-- Main Info Card -->
                <div class="col-md-8">
                    <div class="card border-0 animate-fade-in h-100">
                        <div class="card-header border-0 bg-transparent py-3">
                            <h5 class="card-title text-primary"><i class="fas fa-info-circle me-2"></i>Item Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="detail-label">Description</div>
                                <div class="detail-value fs-5"><?= nl2br(htmlspecialchars($item['Item_desc'])) ?></div>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="detail-label">Quantity</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['qty_no'] . ' ' . $item['qty_desc']) ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-label">Stock Ref</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['stock_ref'] ?: 'N/A') ?></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span class="badge <?= $item['trans_status'] == 0 || $item['trans_status'] === 'NO' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= htmlspecialchars($item['trans_status']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="detail-label">Department</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['dept_location']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="detail-label">Location (Building / Floor)</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['b_location'] . ' / ' . $item['fl_location']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media & Documents Side Panel -->
                <div class="col-md-4">
                    <div class="card border-0 animate-fade-in h-100">
                        <div class="card-header border-0 bg-transparent py-3">
                            <h5 class="card-title text-primary"><i class="fas fa-image me-2"></i>Media & Docs</h5>
                        </div>
                        <div class="card-body text-center">
                            <!-- Asset Image -->
                            <?php if (!empty($item['asset_image'])): ?>
                                <img src="<?= asset('uploads/' . $item['asset_image']) ?>" class="asset-preview mb-3" alt="Asset Image">
                            <?php else: ?>
                                <div class="doc-preview mb-3 text-muted">
                                    <i class="fas fa-camera fa-2x mb-2"></i><br>No Image Available
                                </div>
                            <?php endif; ?>

                            <!-- Scanned Copy -->
                            <div class="d-grid mt-3">
                                <?php if (!empty($item['scanned_copy'])): ?>
                                    <a href="<?= asset('uploads/' . $item['scanned_copy']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-alt me-2"></i>View Scanned Copy
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm" disabled>No Scanned Copy</button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- QR Code (Simulated display if data exists) -->
                            <?php if (!empty($item['qr_code_data'])): ?>
                                <div class="mt-4 pt-3 border-top">
                                    <div class="detail-label mb-2">QR Code Data</div>
                                    <div class="bg-light p-2 rounded small text-break border">
                                        <i class="fas fa-qrcode me-1"></i> <?= htmlspecialchars($item['qr_code_data']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Purchase & Warranty Details -->
                <div class="col-12">
                    <div class="card border-0 animate-fade-in">
                        <div class="card-header border-0 bg-transparent py-3">
                            <h5 class="card-title text-primary"><i class="fas fa-file-contract me-2"></i>Purchase & Warranty</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="detail-label">PO Number</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['po_no']) ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="detail-label">PO Date</div>
                                    <div class="detail-value"><?= formatDate($item['po_date']) ?></div>
                                </div>
                                <div class="col-md-3">
                                    <div class="detail-label">Nodal Officer / Budget Head</div>
                                    <div class="detail-value"><?= htmlspecialchars($item['nodal_off'] ?? $item['bud_head'] ?? 'N/A') ?></div>
                                </div>
                                
                                <div class="col-12"></div>

                                <div class="col-md-6">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3"><i class="fas fa-tools me-2 text-secondary"></i>AMC / CAMC Details</h6>
                                            <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($item['amc_details'] ?? 'No AMC details recorded.')) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light border-0 h-100">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2 text-success"></i>Warranty Information</h6>
                                            <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($item['warranty_details'] ?? 'No warranty information available.')) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
