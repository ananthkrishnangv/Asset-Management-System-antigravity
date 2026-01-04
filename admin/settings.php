<?php
/**
 * System Admin Settings Page
 * WhatsApp Integration Configuration
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'save_whatsapp') {
            // Save WhatsApp settings
            $settings = [
                'whatsapp_enabled' => isset($_POST['whatsapp_enabled']) ? 'true' : 'false',
                'whatsapp_access_token' => Security::sanitize($_POST['whatsapp_access_token'] ?? ''),
                'whatsapp_phone_number_id' => Security::sanitize($_POST['whatsapp_phone_number_id'] ?? ''),
                'whatsapp_notify_add' => isset($_POST['whatsapp_notify_add']) ? 'true' : 'false',
                'whatsapp_notify_delete' => isset($_POST['whatsapp_notify_delete']) ? 'true' : 'false',
                'whatsapp_notify_transfer' => isset($_POST['whatsapp_notify_transfer']) ? 'true' : 'false',
            ];
            
            foreach ($settings as $key => $value) {
                $existing = $db->fetchValue("SELECT id FROM settings WHERE setting_key = ?", [$key]);
                if ($existing) {
                    $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
                } else {
                    $db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value,
                        'setting_type' => 'string',
                        'description' => 'WhatsApp Integration Setting'
                    ]);
                }
            }
            
            $success = 'WhatsApp settings saved successfully!';
        }
        
        if ($action === 'test_whatsapp') {
            $result = WhatsApp::testConnection();
            if ($result['success']) {
                $success = 'WhatsApp connection test successful!';
            } else {
                $error = 'WhatsApp connection failed: ' . $result['error'];
            }
        }
    }
}

// Get current settings
$whatsappEnabled = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_enabled'") === 'true';
$whatsappToken = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_access_token'") ?? '';
$whatsappPhoneId = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_phone_number_id'") ?? '';
$whatsappNotifyAdd = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_add'") === 'true';
$whatsappNotifyDelete = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_delete'") === 'true';
$whatsappNotifyTransfer = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_transfer'") === 'true';

$pageTitle = 'System Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - CSIR-SERC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 min-h-screen p-6 fixed">
            <div class="flex items-center gap-3 mb-8">
                <img src="<?= url('Image/logo-serc.jpg') ?>" alt="SERC" class="w-10 h-10 rounded-full">
                <div>
                    <h1 class="text-white font-bold">CSIR-SERC</h1>
                    <p class="text-slate-400 text-xs">Asset Management</p>
                </div>
            </div>
            
            <nav class="space-y-2">
                <a href="<?= url('admin/dashboard.php') ?>" class="flex items-center gap-3 text-slate-300 hover:bg-slate-800 px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="<?= url('admin/users.php') ?>" class="flex items-center gap-3 text-slate-300 hover:bg-slate-800 px-4 py-3 rounded-lg transition-colors">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="#" class="flex items-center gap-3 text-white bg-blue-600 px-4 py-3 rounded-lg">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="<?= url('public/logout.php') ?>" class="flex items-center gap-3 text-slate-300 hover:bg-slate-800 px-4 py-3 rounded-lg transition-colors mt-8">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= $pageTitle ?></h1>
                <p class="text-gray-600 mb-8">Configure system integrations and notifications</p>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                        <i class="fas fa-check-circle"></i> <?= Security::escape($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                        <i class="fas fa-exclamation-circle"></i> <?= Security::escape($error) ?>
                    </div>
                <?php endif; ?>
                
                <!-- WhatsApp Integration Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="fab fa-whatsapp text-white text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white">WhatsApp Integration</h2>
                            <p class="text-green-100 text-sm">Send notifications via WhatsApp Business API</p>
                        </div>
                    </div>
                    
                    <form method="POST" class="p-6 space-y-6">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="save_whatsapp">
                        
                        <!-- Enable Toggle -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <h3 class="font-semibold text-gray-800">Enable WhatsApp Notifications</h3>
                                <p class="text-sm text-gray-500">Send automatic notifications via WhatsApp</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="whatsapp_enabled" class="sr-only peer" <?= $whatsappEnabled ? 'checked' : '' ?>>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                        
                        <!-- API Credentials -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-2"></i>Access Token
                                </label>
                                <input type="password" name="whatsapp_access_token" 
                                       value="<?= Security::escape($whatsappToken) ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter your WhatsApp Business API token">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-2"></i>Phone Number ID
                                </label>
                                <input type="text" name="whatsapp_phone_number_id"
                                       value="<?= Security::escape($whatsappPhoneId) ?>"
                                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter your Phone Number ID">
                            </div>
                        </div>
                        
                        <!-- Notification Events -->
                        <div class="border-t pt-6">
                            <h3 class="font-semibold text-gray-800 mb-4">Notification Events</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input type="checkbox" name="whatsapp_notify_add" 
                                           class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                           <?= $whatsappNotifyAdd ? 'checked' : '' ?>>
                                    <div>
                                        <span class="font-medium text-gray-800">Asset Added</span>
                                        <p class="text-xs text-gray-500">Notify on new items</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input type="checkbox" name="whatsapp_notify_delete"
                                           class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                           <?= $whatsappNotifyDelete ? 'checked' : '' ?>>
                                    <div>
                                        <span class="font-medium text-gray-800">Asset Deleted</span>
                                        <p class="text-xs text-gray-500">Notify on removal</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input type="checkbox" name="whatsapp_notify_transfer"
                                           class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                           <?= $whatsappNotifyTransfer ? 'checked' : '' ?>>
                                    <div>
                                        <span class="font-medium text-gray-800">Transfers</span>
                                        <p class="text-xs text-gray-500">Notify on transfers</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-4 pt-4 border-t">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors flex items-center gap-2">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <button type="submit" name="action" value="test_whatsapp" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold transition-colors flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> Test Connection
                            </button>
                        </div>
                    </form>
                    
                    <!-- Help Section -->
                    <div class="bg-blue-50 p-6 border-t">
                        <h4 class="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i> How to Get WhatsApp API Credentials
                        </h4>
                        <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                            <li>Go to <a href="https://developers.facebook.com" target="_blank" class="underline">Facebook Developers</a> and create an app</li>
                            <li>Add the WhatsApp product to your app</li>
                            <li>Navigate to WhatsApp > API Setup</li>
                            <li>Copy the <strong>Access Token</strong> and <strong>Phone Number ID</strong></li>
                            <li>Save the settings and test the connection</li>
                        </ol>
                    </div>
                </div>
                
            </div>
        </main>
    </div>
</body>
</html>
