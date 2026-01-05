<?php
/**
 * Admin Settings Page - Fluent UI Design
 * Uses layout template for consistent appearance
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAdmin();

$db = Database::getInstance();

// Helper function to get setting value
function getSetting($key, $default = null)
{
    global $db;
    $result = $db->fetch("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?", [$key]);
    if (!$result)
        return $default;

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
function updateSetting($key, $value, $type = 'string')
{
    global $db;

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
            updateSetting('items_per_page', (int) ($_POST['items_per_page'] ?? 25), 'number');
            flash('success', 'General settings saved successfully!');
            redirect(url('public/admin/settings.php'));
            break;

        case 'smtp':
            updateSetting('smtp_enabled', isset($_POST['smtp_enabled']), 'boolean');
            updateSetting('smtp_host', $_POST['smtp_host'] ?? '');
            updateSetting('smtp_port', (int) ($_POST['smtp_port'] ?? 587), 'number');
            updateSetting('smtp_user', $_POST['smtp_user'] ?? '');
            if (!empty($_POST['smtp_pass'])) {
                updateSetting('smtp_pass', $_POST['smtp_pass']);
            }
            updateSetting('smtp_from_email', $_POST['smtp_from_email'] ?? '');
            updateSetting('smtp_from_name', $_POST['smtp_from_name'] ?? '');
            flash('success', 'SMTP settings saved successfully!');
            redirect(url('public/admin/settings.php'));
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
            flash('success', 'WhatsApp settings saved successfully!');
            redirect(url('public/admin/settings.php'));
            break;

        case 'backup':
            updateSetting('auto_backup_enabled', isset($_POST['backup_enabled']), 'boolean');
            updateSetting('auto_backup_time', $_POST['backup_time'] ?? '00:00');
            updateSetting('backup_retention_days', (int) ($_POST['retention_days'] ?? 30), 'number');
            flash('success', 'Backup settings saved successfully!');
            redirect(url('public/admin/settings.php'));
            break;

        case 'alerts':
            updateSetting('warranty_alert_days', (int) ($_POST['warranty_days'] ?? 30), 'number');
            updateSetting('amc_alert_days', (int) ($_POST['amc_days'] ?? 30), 'number');
            flash('success', 'Alert settings saved successfully!');
            redirect(url('public/admin/settings.php'));
            break;

        case 'test_email':
            try {
                $testEmail = $_POST['test_email'] ?? '';
                if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    $emailSent = Mailer::send(
                        $testEmail,
                        'AMS Test Email',
                        '<h2>Test Email from CSIR-SERC AMS</h2><p>If you received this, your SMTP settings are configured correctly!</p>'
                    );
                    flash($emailSent ? 'success' : 'error', $emailSent ? 'Test email sent successfully!' : 'Failed to send test email.');
                } else {
                    flash('error', 'Invalid email address');
                }
            } catch (Exception $e) {
                flash('error', 'Email error: ' . $e->getMessage());
            }
            redirect(url('public/admin/settings.php'));
            break;

        case 'manual_backup':
            try {
                $backup = new Backup();
                $result = $backup->createBackup();
                flash($result['success'] ? 'success' : 'error', $result['success'] ? 'Backup created: ' . $result['filename'] : 'Backup failed');
            } catch (Exception $e) {
                flash('error', 'Backup error: ' . $e->getMessage());
            }
            redirect(url('public/admin/settings.php'));
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

$pageTitle = 'System Settings';
$pageSubtitle = 'Configure your Asset Management System';

ob_start();
?>

<style>
    .stat-card-light {
        background: white;
        border: 1px solid #e5e7eb;
        position: relative;
        overflow: hidden;
    }

    .stat-card-light::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
    }

    .stat-card-light.blue::before {
        background: linear-gradient(180deg, #3b82f6, #60a5fa);
    }

    .stat-card-light.green::before {
        background: linear-gradient(180deg, #22c55e, #4ade80);
    }

    .stat-card-light.amber::before {
        background: linear-gradient(180deg, #f59e0b, #fbbf24);
    }

    .stat-card-light.purple::before {
        background: linear-gradient(180deg, #8b5cf6, #a78bfa);
    }

    .settings-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
    }

    .settings-card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }

    .settings-card-body {
        padding: 1.5rem;
    }

    .form-input {
        width: 100%;
        padding: 0.625rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background: #d1d5db;
        border-radius: 24px;
        transition: 0.3s;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background: white;
        border-radius: 50%;
        transition: 0.3s;
    }

    input:checked+.toggle-slider {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
    }

    input:checked+.toggle-slider:before {
        transform: translateX(20px);
    }
</style>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card-light blue rounded-xl p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_users']) ?></p>
                <p class="text-sm text-gray-500">Active Users</p>
            </div>
            <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-500"></i>
            </div>
        </div>
    </div>
    <div class="stat-card-light green rounded-xl p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_items']) ?></p>
                <p class="text-sm text-gray-500">Total Items</p>
            </div>
            <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-boxes text-emerald-500"></i>
            </div>
        </div>
    </div>
    <div class="stat-card-light amber rounded-xl p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= number_format($stats['pending_transfers']) ?></p>
                <p class="text-sm text-gray-500">Pending Transfers</p>
            </div>
            <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-exchange-alt text-amber-500"></i>
            </div>
        </div>
    </div>
    <div class="stat-card-light purple rounded-xl p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= $stats['backups_count'] ?></p>
                <p class="text-sm text-gray-500">Backups</p>
            </div>
            <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
                <i class="fas fa-database text-purple-500"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - Main Settings -->
    <div class="lg:col-span-2 space-y-6">
        <!-- General Configuration -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-sliders-h text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">General Configuration</span>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="general">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="form-label">Organization Name</label>
                            <input type="text" name="org_name" class="form-input"
                                value="<?= Security::escape($settings['org_name']) ?>">
                        </div>
                        <div>
                            <label class="form-label">Items Per Page</label>
                            <select name="items_per_page" class="form-input">
                                <?php foreach ([10, 25, 50, 100] as $n): ?>
                                    <option value="<?= $n ?>" <?= $settings['items_per_page'] == $n ? 'selected' : '' ?>>
                                        <?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">Organization Address</label>
                            <input type="text" name="org_address" class="form-input"
                                value="<?= Security::escape($settings['org_address']) ?>">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 py-3 px-4 bg-gray-50 rounded-lg mb-4">
                        <label class="toggle-switch">
                            <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <p class="font-medium text-gray-800">Maintenance Mode</p>
                            <p class="text-xs text-gray-500">Prevent users from logging in during updates</p>
                        </div>
                    </div>

                    <button type="submit"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Save General Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Email Settings -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-envelope text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">Email (SMTP) Configuration</span>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="smtp">

                    <div class="flex items-center gap-3 py-3 px-4 bg-blue-50 rounded-lg mb-4">
                        <label class="toggle-switch">
                            <input type="checkbox" name="smtp_enabled" <?= $settings['smtp_enabled'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <p class="font-medium text-gray-800">Enable SMTP Email</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-3">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-input"
                                value="<?= Security::escape($settings['smtp_host']) ?>">
                        </div>
                        <div>
                            <label class="form-label">Port</label>
                            <input type="number" name="smtp_port" class="form-input"
                                value="<?= $settings['smtp_port'] ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" name="smtp_user" class="form-input"
                                value="<?= Security::escape($settings['smtp_user']) ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" name="smtp_pass" class="form-input"
                                placeholder="Leave empty to keep current">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">From Email</label>
                            <input type="email" name="smtp_from_email" class="form-input"
                                value="<?= Security::escape($settings['smtp_from_email']) ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">From Name</label>
                            <input type="text" name="smtp_from_name" class="form-input"
                                value="<?= Security::escape($settings['smtp_from_name']) ?>">
                        </div>
                    </div>

                    <button type="submit"
                        class="bg-gradient-to-r from-blue-600 to-cyan-600 text-white px-5 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Save SMTP Settings
                    </button>
                </form>

                <hr class="my-5 border-gray-100">

                <form method="POST" class="flex gap-3 items-end">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="test_email">
                    <div class="flex-1">
                        <label class="form-label">Test Email Address</label>
                        <input type="email" name="test_email" class="form-input"
                            value="<?= Security::escape(Auth::user()['email_id'] ?? '') ?>">
                    </div>
                    <button type="submit"
                        class="bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl font-medium hover:bg-gray-50 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i>Send Test
                    </button>
                </form>
            </div>
        </div>

        <!-- WhatsApp Settings -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                    <i class="fab fa-whatsapp text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">WhatsApp Integration</span>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="whatsapp">

                    <div class="flex items-center gap-3 py-3 px-4 bg-green-50 rounded-lg mb-4">
                        <label class="toggle-switch">
                            <input type="checkbox" name="whatsapp_enabled" <?= $settings['whatsapp_enabled'] ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <p class="font-medium text-gray-800">Enable WhatsApp Notifications</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="form-label">Phone Number ID</label>
                            <input type="text" name="wa_phone_id" class="form-input"
                                value="<?= Security::escape($settings['wa_phone_id']) ?>"
                                placeholder="From Meta Business Suite">
                        </div>
                        <div>
                            <label class="form-label">Access Token</label>
                            <input type="password" name="wa_access_token" class="form-input"
                                placeholder="Leave empty to keep current">
                        </div>
                    </div>

                    <p class="text-sm font-medium text-gray-700 mb-3">Notification Triggers</p>
                    <div class="flex flex-wrap gap-4 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="wa_notify_add" <?= $settings['wa_notify_add'] ? 'checked' : '' ?> class="w-4 h-4 text-green-500 rounded">
                            <span class="text-sm text-gray-700">On Asset Add</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="wa_notify_delete" <?= $settings['wa_notify_delete'] ? 'checked' : '' ?> class="w-4 h-4 text-green-500 rounded">
                            <span class="text-sm text-gray-700">On Asset Delete</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="wa_notify_transfer" <?= $settings['wa_notify_transfer'] ? 'checked' : '' ?> class="w-4 h-4 text-green-500 rounded">
                            <span class="text-sm text-gray-700">On Transfer</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 text-white px-5 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Save WhatsApp Settings
                    </button>
                </form>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-database text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">Backup Configuration</span>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="backup">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="flex items-center gap-3 py-3 px-4 bg-purple-50 rounded-lg">
                            <label class="toggle-switch">
                                <input type="checkbox" name="backup_enabled" <?= $settings['backup_enabled'] ? 'checked' : '' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <p class="font-medium text-gray-800 text-sm">Auto Backup</p>
                        </div>
                        <div>
                            <label class="form-label">Backup Time</label>
                            <input type="time" name="backup_time" class="form-input"
                                value="<?= $settings['backup_time'] ?>">
                        </div>
                        <div>
                            <label class="form-label">Retention (Days)</label>
                            <input type="number" name="retention_days" class="form-input"
                                value="<?= $settings['retention_days'] ?>" min="7" max="365">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-5 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                            <i class="fas fa-save mr-2"></i>Save Backup Settings
                        </button>
                    </div>
                </form>

                <hr class="my-5 border-gray-100">

                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="manual_backup">
                    <button type="submit"
                        class="bg-white border border-emerald-200 text-emerald-700 px-4 py-2.5 rounded-xl font-medium hover:bg-emerald-50 transition-all">
                        <i class="fas fa-download mr-2"></i>Create Manual Backup Now
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column - System Info & Alerts -->
    <div class="space-y-6">
        <!-- System Information -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-gray-500 to-slate-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-info-circle text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">System Information</span>
            </div>
            <div class="settings-card-body">
                <div class="space-y-3">
                    <?php
                    $infoItems = [
                        'PHP Version' => $systemInfo['php_version'],
                        'Database' => $systemInfo['db_version'],
                        'App Version' => $systemInfo['app_version'],
                        'Disk Free' => $systemInfo['disk_free'],
                        'Max Upload' => $systemInfo['upload_max'],
                        'Max POST' => $systemInfo['post_max']
                    ];
                    foreach ($infoItems as $label => $value): ?>
                        <div class="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                            <span class="text-sm text-gray-500"><?= $label ?></span>
                            <span class="text-sm font-medium text-gray-800"><?= Security::escape($value) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Alert Settings -->
        <div class="settings-card">
            <div class="settings-card-header flex items-center gap-3">
                <div
                    class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg flex items-center justify-center">
                    <i class="fas fa-bell text-white text-sm"></i>
                </div>
                <span class="font-semibold text-gray-800">Alert Settings</span>
            </div>
            <div class="settings-card-body">
                <form method="POST">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="alerts">

                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="form-label">Warranty Alert (Days Before)</label>
                            <input type="number" name="warranty_days" class="form-input"
                                value="<?= $settings['warranty_days'] ?>" min="7" max="90">
                        </div>
                        <div>
                            <label class="form-label">AMC Alert (Days Before)</label>
                            <input type="number" name="amc_days" class="form-input" value="<?= $settings['amc_days'] ?>"
                                min="7" max="90">
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-amber-500 to-orange-500 text-white px-4 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                        <i class="fas fa-save mr-2"></i>Save Alert Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>