<?php
/**
 * Admin Settings Page - Enhanced Version
 * Implements persistent settings with database storage
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAdmin();

$db = Database::getInstance();

// Helper function to get setting value
function getSetting($key, $default = null) {
    global $db;
    $result = $db->fetch("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?", [$key]);
    if (!$result) return $default;
    
    // Convert based on type
    switch ($result['setting_type']) {
        case 'boolean':
            return $result['setting_value'] === 'true';
        case 'number':
            return (int) $result['setting_value'];
        case 'json':
            return json_decode($result['setting_value'], true);
        default:
            return $result['setting_value'];
    }
}

// Helper function to update setting
function updateSetting($key, $value, $type = 'string') {
    global $db;
    
    // Convert value for storage
    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
        $type = 'boolean';
    } elseif (is_array($value)) {
        $value = json_encode($value);
        $type = 'json';
    } elseif (is_numeric($value) && $type !== 'string') {
        $type = 'number';
    }
    
    $exists = $db->fetchValue("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        $db->query("UPDATE settings SET setting_value = ?, setting_type = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $type, $key]);
    } else {
        $db->query("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)", [$key, $value, $type]);
    }
}

// Handle form submissions
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'general':
            updateSetting('organization_name', $_POST['org_name'] ?? 'CSIR-SERC');
            updateSetting('organization_address', $_POST['org_address'] ?? '');
            updateSetting('maintenance_mode', isset($_POST['maintenance_mode']), 'boolean');
            updateSetting('items_per_page', (int)($_POST['items_per_page'] ?? 25), 'number');
            $message = 'General settings saved successfully!';
            ActivityLog::log('update', 'settings', null, 'system', 'Updated general settings');
            break;
            
        case 'smtp':
            updateSetting('smtp_enabled', isset($_POST['smtp_enabled']), 'boolean');
            updateSetting('smtp_host', $_POST['smtp_host'] ?? '');
            updateSetting('smtp_port', (int)($_POST['smtp_port'] ?? 587), 'number');
            updateSetting('smtp_user', $_POST['smtp_user'] ?? '');
            if (!empty($_POST['smtp_pass'])) {
                updateSetting('smtp_pass', $_POST['smtp_pass']);
            }
            updateSetting('smtp_from_email', $_POST['smtp_from_email'] ?? '');
            updateSetting('smtp_from_name', $_POST['smtp_from_name'] ?? '');
            $message = 'SMTP settings saved successfully!';
            ActivityLog::log('update', 'settings', null, 'system', 'Updated SMTP settings');
            break;
            
        case 'whatsapp':
            updateSetting('whatsapp_enabled', isset($_POST['whatsapp_enabled']), 'boolean');
            updateSetting('whatsapp_phone_number_id', $_POST['wa_phone_id'] ?? '');
            if (!empty($_POST['wa_access_token'])) {
                updateSetting('whatsapp_access_token', $_POST['wa_access_token']);
            }
            updateSetting('whatsapp_notify_add', isset($_POST['wa_notify_add']), 'boolean');
            updateSetting('whatsapp_notify_delete', isset($_POST['wa_notify_delete']), 'boolean');
            updateSetting('whatsapp_notify_transfer', isset($_POST['wa_notify_transfer']), 'boolean');
            $message = 'WhatsApp settings saved successfully!';
            ActivityLog::log('update', 'settings', null, 'system', 'Updated WhatsApp settings');
            break;
            
        case 'backup':
            updateSetting('auto_backup_enabled', isset($_POST['backup_enabled']), 'boolean');
            updateSetting('auto_backup_time', $_POST['backup_time'] ?? '00:00');
            updateSetting('backup_retention_days', (int)($_POST['retention_days'] ?? 30), 'number');
            $message = 'Backup settings saved successfully!';
            ActivityLog::log('update', 'settings', null, 'system', 'Updated backup settings');
            break;
            
        case 'alerts':
            updateSetting('warranty_alert_days', (int)($_POST['warranty_days'] ?? 30), 'number');
            updateSetting('amc_alert_days', (int)($_POST['amc_days'] ?? 30), 'number');
            $message = 'Alert settings saved successfully!';
            break;
            
        case 'test_email':
            // Test email functionality
            try {
                $testEmail = $_POST['test_email'] ?? '';
                if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    // Attempt to send test email
                    $emailSent = Mailer::send($testEmail, 'AMS Test Email', 
                        '<h2>Test Email from CSIR-SERC AMS</h2><p>If you received this, your SMTP settings are configured correctly!</p>');
                    if ($emailSent) {
                        $message = 'Test email sent successfully to ' . Security::escape($testEmail);
                    } else {
                        $message = 'Failed to send test email. Please check SMTP settings.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Invalid email address';
                    $messageType = 'danger';
                }
            } catch (Exception $e) {
                $message = 'Email error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;
            
        case 'manual_backup':
            try {
                $backup = new Backup();
                $result = $backup->createBackup();
                if ($result['success']) {
                    $message = 'Backup created: ' . $result['filename'];
                } else {
                    $message = 'Backup failed: ' . ($result['error'] ?? 'Unknown error');
                    $messageType = 'danger';
                }
            } catch (Exception $e) {
                $message = 'Backup error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;
            
        case 'batch_qr':
            try {
                $type = $_POST['qr_type'] ?? 'dir';
                // Get items without QR codes
                $items = $db->fetchAll(
                    "SELECT id, serial_number, item_description FROM inventory_items 
                     WHERE inventory_type = ? AND (qr_code_path IS NULL OR qr_code_path = '') 
                     AND is_active = 1 LIMIT 100",
                    [$type]
                );
                
                $generated = 0;
                $qrDir = __DIR__ . '/../../uploads/qr/';
                if (!is_dir($qrDir)) mkdir($qrDir, 0775, true);
                
                // Use simple QR code generation (placeholder - would use a library in production)
                foreach ($items as $item) {
                    $qrData = APP_URL . '/public/inventory/item-details.php?id=' . $item['id'];
                    $qrFilename = 'qr_' . $item['id'] . '_' . time() . '.png';
                    
                    // Update database
                    $db->query(
                        "UPDATE inventory_items SET qr_code_data = ?, qr_code_path = ? WHERE id = ?",
                        [$qrData, 'uploads/qr/' . $qrFilename, $item['id']]
                    );
                    $generated++;
                }
                
                $message = "Generated QR codes for $generated items. Use a QR library like 'endroid/qr-code' for actual QR images.";
            } catch (Exception $e) {
                $message = 'QR generation error: ' . $e->getMessage();
                $messageType = 'danger';
            }
            break;
    }
}

// Load current settings
$settings = [
    'org_name' => getSetting('organization_name', 'CSIR-SERC'),
    'org_address' => getSetting('organization_address', 'Chennai, India'),
    'maintenance_mode' => getSetting('maintenance_mode', false),
    'items_per_page' => getSetting('items_per_page', 25),
    'smtp_enabled' => getSetting('smtp_enabled', false),
    'smtp_host' => getSetting('smtp_host', SMTP_HOST ?? 'smtp.gmail.com'),
    'smtp_port' => getSetting('smtp_port', SMTP_PORT ?? 587),
    'smtp_user' => getSetting('smtp_user', ''),
    'smtp_from_email' => getSetting('smtp_from_email', 'noreply@serc.res.in'),
    'smtp_from_name' => getSetting('smtp_from_name', 'CSIR-SERC AMS'),
    'whatsapp_enabled' => getSetting('whatsapp_enabled', false),
    'wa_phone_id' => getSetting('whatsapp_phone_number_id', ''),
    'wa_notify_add' => getSetting('whatsapp_notify_add', true),
    'wa_notify_delete' => getSetting('whatsapp_notify_delete', true),
    'wa_notify_transfer' => getSetting('whatsapp_notify_transfer', true),
    'backup_enabled' => getSetting('auto_backup_enabled', true),
    'backup_time' => getSetting('auto_backup_time', '00:00'),
    'retention_days' => getSetting('backup_retention_days', 30),
    'warranty_days' => getSetting('warranty_alert_days', 30),
    'amc_days' => getSetting('amc_alert_days', 30),
];

// Get system info
$systemInfo = [
    'php_version' => PHP_VERSION,
    'db_version' => $db->fetchValue("SELECT VERSION()") ?? 'Unknown',
    'app_version' => APP_VERSION,
    'disk_free' => formatFileSize(disk_free_space('/var/www/html') ?: disk_free_space('/')),
    'upload_max' => ini_get('upload_max_filesize'),
    'post_max' => ini_get('post_max_size'),
];

// Get statistics
$stats = [
    'total_users' => $db->fetchValue("SELECT COUNT(*) FROM users WHERE is_active = 1") ?? 0,
    'total_items' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1") ?? 0,
    'pending_transfers' => $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor')") ?? 0,
    'backups_count' => count(glob(BACKUP_PATH . '*.sql')) ?: 0,
];

// Get expiring warranties/AMC
$expiringItems = $db->fetchAll(
    "SELECT serial_number, item_description, warranty_expiry, amc_expiry 
     FROM inventory_items 
     WHERE is_active = 1 
     AND (
         (warranty_expiry IS NOT NULL AND warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY))
         OR (amc_expiry IS NOT NULL AND amc_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY))
     )
     ORDER BY LEAST(COALESCE(warranty_expiry, '9999-12-31'), COALESCE(amc_expiry, '9999-12-31'))
     LIMIT 5"
) ?: [];

$pageTitle = 'System Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --dark: #1E293B;
            --light: #F8FAFC;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
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
            left: 0;
            top: 0;
        }
        
        .content { 
            margin-left: 260px; 
            padding: 2rem; 
            min-height: 100vh;
        }
        
        .nav-link { 
            color: #cbd5e1; 
            padding: 0.8rem 1.5rem; 
            display: block; 
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active { 
            background: #0f172a; 
            color: white;
            border-left-color: var(--primary);
        }
        
        .nav-link i { width: 24px; text-align: center; }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            border: none;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .settings-card .card-header {
            background: linear-gradient(135deg, var(--dark) 0%, #334155 100%);
            color: white;
            font-weight: 600;
            padding: 1rem 1.5rem;
            border: none;
        }
        
        .settings-card .card-body { padding: 1.5rem; }
        
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .btn-primary:hover { background: var(--primary-dark); }
        
        .stat-box {
            background: linear-gradient(135deg, var(--primary) 0%, #818cf8 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-box.success { background: linear-gradient(135deg, var(--success) 0%, #34d399 100%); }
        .stat-box.warning { background: linear-gradient(135deg, var(--warning) 0%, #fbbf24 100%); }
        .stat-box.danger { background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%); }
        
        .stat-value { font-size: 2rem; font-weight: 700; }
        .stat-label { font-size: 0.85rem; opacity: 0.9; }
        
        .system-info { font-size: 0.875rem; }
        .system-info dt { color: #64748b; }
        .system-info dd { font-weight: 600; color: var(--dark); }
        
        .expiry-badge { 
            display: inline-block; 
            padding: 0.25rem 0.5rem; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 600;
        }
        .expiry-badge.warning { background: #fef3c7; color: #92400e; }
        .expiry-badge.danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar py-4">
        <div class="px-4 mb-4">
            <h4 class="text-white fw-bold"><i class="fas fa-cube me-2"></i>AMS Admin</h4>
        </div>
        <a href="<?= url('public/dashboard.php') ?>" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="<?= url('public/inventory/dir.php') ?>" class="nav-link"><i class="fas fa-list-alt me-2"></i> DIR Inventory</a>
        <a href="<?= url('public/inventory/pir.php') ?>" class="nav-link"><i class="fas fa-clipboard-list me-2"></i> PIR Inventory</a>
        <a href="<?= url('public/reports/index.php') ?>" class="nav-link"><i class="fas fa-chart-pie me-2"></i> Reports</a>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/admin/users.php') ?>" class="nav-link"><i class="fas fa-users me-2"></i> Users</a>
        <a href="<?= url('public/admin/departments.php') ?>" class="nav-link"><i class="fas fa-building me-2"></i> Departments</a>
        <a href="<?= url('public/admin/backup.php') ?>" class="nav-link"><i class="fas fa-database me-2"></i> Backups</a>
        <a href="<?= url('public/admin/settings.php') ?>" class="nav-link active"><i class="fas fa-cogs me-2"></i> Settings</a>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/logout.php') ?>" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">System Settings</h2>
                <p class="text-muted mb-0">Configure your Asset Management System</p>
            </div>
            <span class="badge bg-primary">v<?= APP_VERSION ?></span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= Security::escape($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box success">
                    <div class="stat-value"><?= number_format($stats['total_items']) ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box warning">
                    <div class="stat-value"><?= number_format($stats['pending_transfers']) ?></div>
                    <div class="stat-label">Pending Transfers</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box danger">
                    <div class="stat-value"><?= $stats['backups_count'] ?></div>
                    <div class="stat-label">Backups Available</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-sliders-h me-2"></i> General Configuration
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="general">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Organization Name</label>
                                    <input type="text" name="org_name" class="form-control" value="<?= Security::escape($settings['org_name']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Items Per Page</label>
                                    <select name="items_per_page" class="form-select">
                                        <?php foreach ([10, 25, 50, 100] as $n): ?>
                                            <option value="<?= $n ?>" <?= $settings['items_per_page'] == $n ? 'selected' : '' ?>><?= $n ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Organization Address</label>
                                    <input type="text" name="org_address" class="form-control" value="<?= Security::escape($settings['org_address']) ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="maintenanceMode">
                                            <strong>Maintenance Mode</strong>
                                            <small class="d-block text-muted">Prevent users from logging in during updates</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save General Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-envelope me-2"></i> Email (SMTP) Configuration
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="smtp">
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="smtpEnabled" name="smtp_enabled" <?= $settings['smtp_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="smtpEnabled">Enable SMTP Email</label>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= Security::escape($settings['smtp_host']) ?>" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?= $settings['smtp_port'] ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_user" class="form-control" value="<?= Security::escape($settings['smtp_user']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="smtp_pass" class="form-control" placeholder="Leave empty to keep current">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From Email</label>
                                    <input type="email" name="smtp_from_email" class="form-control" value="<?= Security::escape($settings['smtp_from_email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From Name</label>
                                    <input type="text" name="smtp_from_name" class="form-control" value="<?= Security::escape($settings['smtp_from_name']) ?>">
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save SMTP Settings
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        <form method="POST" class="d-flex gap-2 align-items-end">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="test_email">
                            <div class="flex-grow-1">
                                <label class="form-label">Test Email Address</label>
                                <input type="email" name="test_email" class="form-control" placeholder="Enter email to test" value="<?= Security::escape(Auth::user()['email_id'] ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Test
                            </button>
                        </form>
                    </div>
                </div>

                <!-- WhatsApp Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fab fa-whatsapp me-2"></i> WhatsApp Integration
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="whatsapp">
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="waEnabled" name="whatsapp_enabled" <?= $settings['whatsapp_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-medium" for="waEnabled">Enable WhatsApp Notifications</label>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number ID</label>
                                    <input type="text" name="wa_phone_id" class="form-control" value="<?= Security::escape($settings['wa_phone_id']) ?>" placeholder="From Meta Business Suite">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Access Token</label>
                                    <input type="password" name="wa_access_token" class="form-control" placeholder="Leave empty to keep current">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium mb-2">Notification Triggers</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="waAdd" name="wa_notify_add" <?= $settings['wa_notify_add'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="waAdd">On Asset Add</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="waDelete" name="wa_notify_delete" <?= $settings['wa_notify_delete'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="waDelete">On Asset Delete</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="waTransfer" name="wa_notify_transfer" <?= $settings['wa_notify_transfer'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="waTransfer">On Transfer</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save WhatsApp Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Backup Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-database me-2"></i> Backup Configuration
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="backup">
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="backupEnabled" name="backup_enabled" <?= $settings['backup_enabled'] ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-medium" for="backupEnabled">Auto Backup</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Backup Time</label>
                                    <input type="time" name="backup_time" class="form-control" value="<?= $settings['backup_time'] ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Retention (Days)</label>
                                    <input type="number" name="retention_days" class="form-control" value="<?= $settings['retention_days'] ?>" min="7" max="365">
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Backup Settings
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="manual_backup">
                            <button type="submit" class="btn btn-outline-success">
                                <i class="fas fa-download me-2"></i>Create Manual Backup Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- System Info -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle me-2"></i> System Information
                    </div>
                    <div class="card-body">
                        <dl class="system-info row mb-0">
                            <dt class="col-6">PHP Version</dt>
                            <dd class="col-6"><?= $systemInfo['php_version'] ?></dd>
                            
                            <dt class="col-6">Database</dt>
                            <dd class="col-6"><?= $systemInfo['db_version'] ?></dd>
                            
                            <dt class="col-6">App Version</dt>
                            <dd class="col-6"><?= $systemInfo['app_version'] ?></dd>
                            
                            <dt class="col-6">Disk Free</dt>
                            <dd class="col-6"><?= $systemInfo['disk_free'] ?></dd>
                            
                            <dt class="col-6">Max Upload</dt>
                            <dd class="col-6"><?= $systemInfo['upload_max'] ?></dd>
                            
                            <dt class="col-6">Max POST</dt>
                            <dd class="col-6 mb-0"><?= $systemInfo['post_max'] ?></dd>
                        </dl>
                    </div>
                </div>

                <!-- Alert Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-bell me-2"></i> Alert Settings
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="alerts">
                            
                            <div class="mb-3">
                                <label class="form-label">Warranty Alert (Days Before)</label>
                                <input type="number" name="warranty_days" class="form-control" value="<?= $settings['warranty_days'] ?>" min="7" max="90">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">AMC Alert (Days Before)</label>
                                <input type="number" name="amc_days" class="form-control" value="<?= $settings['amc_days'] ?>" min="7" max="90">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-save me-2"></i>Save Alert Settings
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Expiring Items -->
                <?php if (!empty($expiringItems)): ?>
                <div class="settings-card">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-exclamation-triangle me-2"></i> Expiring Soon
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($expiringItems as $item): ?>
                            <li class="list-group-item">
                                <div class="fw-medium text-truncate" title="<?= Security::escape($item['item_description']) ?>">
                                    <?= Security::escape($item['serial_number']) ?>
                                </div>
                                <div class="d-flex gap-2 mt-1">
                                    <?php if ($item['warranty_expiry']): ?>
                                        <span class="expiry-badge warning">
                                            <i class="fas fa-shield-alt me-1"></i>Warranty: <?= formatDate($item['warranty_expiry']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($item['amc_expiry']): ?>
                                        <span class="expiry-badge danger">
                                            <i class="fas fa-wrench me-1"></i>AMC: <?= formatDate($item['amc_expiry']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="settings-card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-3">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="batch_qr">
                            <input type="hidden" name="qr_type" value="dir">
                            <button type="submit" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-qrcode me-2"></i>Generate All DIR QR Codes
                            </button>
                        </form>
                        <form method="POST">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="batch_qr">
                            <input type="hidden" name="qr_type" value="pir">
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="fas fa-qrcode me-2"></i>Generate All PIR QR Codes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
