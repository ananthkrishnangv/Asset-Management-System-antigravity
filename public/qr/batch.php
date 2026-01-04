<?php
/**
 * QR Code Batch Generation Page
 * Generate QR codes for all inventory items
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireSupervisor();

$db = Database::getInstance();

// Handle generation request
$message = '';
$messageType = 'success';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $type = $_POST['type'] ?? 'dir';
    $regenerate = isset($_POST['regenerate']);
    
    // Get items needing QR codes
    $where = $regenerate 
        ? "inventory_type = ? AND is_active = 1" 
        : "inventory_type = ? AND is_active = 1 AND (qr_code_path IS NULL OR qr_code_path = '')";
    
    $items = $db->fetchAll(
        "SELECT id, serial_number, item_description, qr_code_data FROM inventory_items WHERE $where LIMIT 100",
        [$type]
    );
    
    if (empty($items)) {
        $message = 'No items found that need QR codes generated.';
        $messageType = 'info';
    } else {
        require_once __DIR__ . '/../../includes/QRCodeGenerator.php';
        $qrGen = new QRCodeGenerator(200, 10);
        
        $qrDir = __DIR__ . '/../../uploads/qr/';
        if (!is_dir($qrDir)) mkdir($qrDir, 0775, true);
        
        $generated = 0;
        $failed = 0;
        
        foreach ($items as $item) {
            $qrData = $item['qr_code_data'] ?: (APP_URL . '/public/inventory/view.php?id=' . $item['id']);
            $filename = 'qr_' . $item['id'] . '_' . time() . '.png';
            $outputPath = $qrDir . $filename;
            
            try {
                if ($qrGen->generate($qrData, $outputPath)) {
                    $db->query(
                        "UPDATE inventory_items SET qr_code_data = ?, qr_code_path = ? WHERE id = ?",
                        [$qrData, 'uploads/qr/' . $filename, $item['id']]
                    );
                    $generated++;
                    $results[] = [
                        'id' => $item['id'],
                        'serial' => $item['serial_number'],
                        'path' => 'uploads/qr/' . $filename,
                        'success' => true
                    ];
                } else {
                    $failed++;
                    $results[] = [
                        'id' => $item['id'],
                        'serial' => $item['serial_number'],
                        'success' => false
                    ];
                }
            } catch (Exception $e) {
                $failed++;
            }
        }
        
        $message = "Generated $generated QR codes" . ($failed ? ", $failed failed" : "");
        $messageType = $failed ? 'warning' : 'success';
        
        ActivityLog::log('create', 'qr_codes', null, 'system', 
            "Batch generated $generated QR codes for " . strtoupper($type) . " items");
    }
}

// Get stats
$stats = [
    'dir_total' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0,
    'dir_with_qr' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1 AND qr_code_path IS NOT NULL AND qr_code_path != ''") ?? 0,
    'pir_total' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0,
    'pir_with_qr' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1 AND qr_code_path IS NOT NULL AND qr_code_path != ''") ?? 0,
];

$pageTitle = 'QR Code Batch Generation';
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
    
    <style>
        :root { --primary: #4F46E5; --dark: #1E293B; --light: #F8FAFC; }
        body { font-family: 'Noto Sans', sans-serif; background: var(--light); color: #334155; }
        .sidebar { width: 260px; background: var(--dark); min-height: 100vh; position: fixed; }
        .content { margin-left: 260px; padding: 2rem; }
        .nav-link { color: #cbd5e1; padding: 0.8rem 1.5rem; display: block; text-decoration: none; border-left: 3px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #0f172a; color: white; border-left-color: var(--primary); }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-icon { font-size: 3rem; margin-bottom: 1rem; }
        .stat-value { font-size: 2.5rem; font-weight: 700; }
        .stat-label { color: #64748b; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.1em; }
        .progress-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: conic-gradient(var(--primary) var(--percent), #e2e8f0 0);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .progress-circle::before {
            content: attr(data-percent);
            width: 60px; height: 60px;
            background: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .action-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .btn-generate {
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
            color: white;
        }
        .result-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .result-item.success { border-left: 4px solid #10B981; }
        .result-item.failed { border-left: 4px solid #EF4444; }
        .qr-preview { width: 50px; height: 50px; background: #e2e8f0; border-radius: 4px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar py-4">
        <div class="px-4 mb-4">
            <h4 class="text-white fw-bold"><i class="fas fa-cube me-2"></i>AMS</h4>
        </div>
        <a href="<?= url('public/dashboard.php') ?>" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="<?= url('public/inventory/dir.php') ?>" class="nav-link"><i class="fas fa-list-alt me-2"></i> DIR Inventory</a>
        <a href="<?= url('public/inventory/pir.php') ?>" class="nav-link"><i class="fas fa-clipboard-list me-2"></i> PIR Inventory</a>
        <a href="<?= url('public/reports/index.php') ?>" class="nav-link"><i class="fas fa-chart-pie me-2"></i> Reports</a>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/qr/batch.php') ?>" class="nav-link active"><i class="fas fa-qrcode me-2"></i> QR Codes</a>
        <a href="<?= url('public/admin/settings.php') ?>" class="nav-link"><i class="fas fa-cogs me-2"></i> Settings</a>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/logout.php') ?>" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">QR Code Batch Generation</h2>
                <p class="text-muted mb-0">Generate QR codes for all inventory items</p>
            </div>
            <a href="<?= url('public/qr/labels.php') ?>" class="btn btn-outline-primary">
                <i class="fas fa-print me-2"></i>Print Labels
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm border-0">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'info-circle' ?> me-2"></i>
                <?= Security::escape($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <?php $dirPercent = $stats['dir_total'] ? round(($stats['dir_with_qr'] / $stats['dir_total']) * 100) : 0; ?>
                    <div class="progress-circle" style="--percent: <?= $dirPercent ?>%;" data-percent="<?= $dirPercent ?>%"></div>
                    <div class="stat-label">DIR Items with QR</div>
                    <div class="mt-2">
                        <span class="text-success fw-bold"><?= $stats['dir_with_qr'] ?></span> / 
                        <span class="text-muted"><?= $stats['dir_total'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <?php $pirPercent = $stats['pir_total'] ? round(($stats['pir_with_qr'] / $stats['pir_total']) * 100) : 0; ?>
                    <div class="progress-circle" style="--percent: <?= $pirPercent ?>%;" data-percent="<?= $pirPercent ?>%"></div>
                    <div class="stat-label">PIR Items with QR</div>
                    <div class="mt-2">
                        <span class="text-success fw-bold"><?= $stats['pir_with_qr'] ?></span> / 
                        <span class="text-muted"><?= $stats['pir_total'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generation Forms -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="action-card">
                    <h5 class="fw-bold mb-3"><i class="fas fa-building text-primary me-2"></i>DIR Inventory</h5>
                    <p class="text-muted small mb-4">Generate QR codes for departmental inventory items</p>
                    
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="type" value="dir">
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="dirRegenerate" name="regenerate">
                            <label class="form-check-label" for="dirRegenerate">
                                Regenerate existing QR codes
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-generate">
                            <i class="fas fa-qrcode me-2"></i>Generate DIR QR Codes
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="action-card">
                    <h5 class="fw-bold mb-3"><i class="fas fa-user text-success me-2"></i>PIR Inventory</h5>
                    <p class="text-muted small mb-4">Generate QR codes for personal issue register items</p>
                    
                    <form method="POST">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="type" value="pir">
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="pirRegenerate" name="regenerate">
                            <label class="form-check-label" for="pirRegenerate">
                                Regenerate existing QR codes
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-generate" style="background: linear-gradient(135deg, #10B981, #34d399);">
                            <i class="fas fa-qrcode me-2"></i>Generate PIR QR Codes
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results -->
        <?php if (!empty($results)): ?>
        <div class="action-card mt-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-list-check text-primary me-2"></i>Generation Results</h5>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($results as $r): ?>
                <div class="result-item <?= $r['success'] ? 'success' : 'failed' ?>">
                    <?php if ($r['success']): ?>
                    <img src="<?= url($r['path']) ?>" alt="QR" class="qr-preview">
                    <?php else: ?>
                    <div class="qr-preview d-flex align-items-center justify-content-center">
                        <i class="fas fa-times text-danger"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                        <strong><?= Security::escape($r['serial']) ?></strong>
                        <small class="d-block text-muted">ID: <?= $r['id'] ?></small>
                    </div>
                    <div class="ms-auto">
                        <?php if ($r['success']): ?>
                        <span class="badge bg-success">Generated</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Failed</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
