<?php
/**
 * QR Code Generator and Print Page
 * Displays full item details when scanned
 */

require_once __DIR__ . '/../../bootstrap.php';

// Allow public access for QR scanning (view only)
$serial = $_GET['serial'] ?? null;
$itemId = $_GET['id'] ?? null;
$printMode = isset($_GET['print']);

$db = Database::getInstance();

// Get item by serial or ID
if ($serial) {
    $item = $db->fetch(
        "SELECT i.*, c.name as category_name, d.name as department_name,
                u.emp_name as holder_name, u.ams_id as holder_ams,
                n.emp_name as nodal_name
         FROM inventory_items i
         LEFT JOIN categories c ON i.category_id = c.id
         LEFT JOIN departments d ON i.department_id = d.id
         LEFT JOIN users u ON i.current_holder_id = u.id
         LEFT JOIN users n ON i.nodal_officer_id = n.id
         WHERE i.serial_number = ?",
        [$serial]
    );
} elseif ($itemId) {
    $item = $db->fetch(
        "SELECT i.*, c.name as category_name, d.name as department_name,
                u.emp_name as holder_name, u.ams_id as holder_ams,
                n.emp_name as nodal_name
         FROM inventory_items i
         LEFT JOIN categories c ON i.category_id = c.id
         LEFT JOIN departments d ON i.department_id = d.id
         LEFT JOIN users u ON i.current_holder_id = u.id
         LEFT JOIN users n ON i.nodal_officer_id = n.id
         WHERE i.id = ?",
        [$itemId]
    );
}

if (!$item) {
    http_response_code(404);
    die('Item not found');
}

// Get transfer history
$transferHistory = $db->fetchAll(
    "SELECT * FROM transfer_history WHERE item_id = ? ORDER BY transferred_at DESC",
    [$item['id']]
);

// Generate QR Code URL
$qrUrl = APP_URL . '/public/qr/print.php?serial=' . urlencode($item['serial_number']);
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrUrl);

if ($printMode):
    // Print-friendly QR label view
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>QR Label - <?= Security::escape($item['serial_number']) ?></title>
        <style>
            @page {
                size: 50mm 30mm;
                margin: 2mm;
            }

            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 5mm;
            }

            .label {
                display: flex;
                align-items: center;
                gap: 3mm;
            }

            .qr-code {
                width: 22mm;
                height: 22mm;
            }

            .info {
                flex: 1;
                font-size: 7pt;
            }

            .info h1 {
                font-size: 8pt;
                margin: 0 0 1mm;
            }

            .info p {
                margin: 0.5mm 0;
            }

            .serial {
                font-family: monospace;
                font-weight: bold;
            }

            @media print {
                body {
                    -webkit-print-color-adjust: exact;
                }
            }
        </style>
    </head>

    <body onload="window.print()">
        <div class="label">
            <img src="<?= $qrApiUrl ?>" class="qr-code" alt="QR Code">
            <div class="info">
                <h1>CSIR-SERC</h1>
                <p class="serial"><?= Security::escape($item['serial_number']) ?></p>
                <p><?= Security::escape(substr($item['item_description'], 0, 30)) ?></p>
                <p><?= Security::escape($item['department_name'] ?? '') ?></p>
            </div>
        </div>
    </body>

    </html>
    <?php
    exit;
endif;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Security::escape($item['serial_number']) ?> - CSIR-SERC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-header {
            background: linear-gradient(135deg, #1a365d 0%, #2d5aa0 100%);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-header text-white py-6 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center gap-4">
                <img src="<?= url('Image/logo-serc.jpg') ?>" class="h-14 w-14 rounded-full border-2 border-white/30">
                <div>
                    <h1 class="text-xl font-bold">CSIR-SERC</h1>
                    <p class="text-blue-200 text-sm">Asset Management System</p>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 -mt-6">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Item Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col md:flex-row md:items-start gap-6">
                    <!-- Image/QR -->
                    <div class="flex flex-col items-center gap-4">
                        <?php if ($item['image_path']): ?>
                            <img src="<?= url('uploads/' . $item['image_path']) ?>"
                                class="w-40 h-40 rounded-xl object-cover shadow-lg">
                        <?php else: ?>
                            <div class="w-40 h-40 bg-gray-100 rounded-xl flex items-center justify-center">
                                <i class="fas fa-box text-gray-300 text-5xl"></i>
                            </div>
                        <?php endif; ?>

                        <img src="<?= $qrApiUrl ?>" class="w-24 h-24 rounded-lg shadow" alt="QR Code">

                        <button
                            onclick="window.open('?serial=<?= urlencode($item['serial_number']) ?>&print=1', '_blank')"
                            class="text-sm text-blue-600 hover:underline">
                            <i class="fas fa-print mr-1"></i> Print QR Label
                        </button>
                    </div>

                    <!-- Info -->
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-blue-600 font-mono font-bold">
                                    <?= Security::escape($item['serial_number']) ?></p>
                                <h2 class="text-2xl font-bold text-gray-800 mt-1">
                                    <?= Security::escape($item['item_description']) ?></h2>
                            </div>
                            <span
                                class="px-3 py-1 rounded-full text-sm font-medium 
                                <?= $item['inventory_type'] === 'dir' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                <?= strtoupper($item['inventory_type']) ?>
                            </span>
                        </div>

                        <?php if ($item['detailed_description']): ?>
                            <p class="text-gray-600 mt-3"><?= nl2br(Security::escape($item['detailed_description'])) ?></p>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4 mt-6">
                            <div>
                                <p class="text-xs text-gray-400 uppercase">Category</p>
                                <p class="font-medium text-gray-800">
                                    <?= Security::escape($item['category_name'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase">Condition</p>
                                <span class="inline-block px-2 py-1 rounded text-xs font-medium
                                    <?= getStatusBadge($item['condition_status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $item['condition_status'])) ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase">Quantity</p>
                                <p class="font-medium text-gray-800"><?= $item['quantity'] ?>
                                    <?= $item['quantity_unit'] ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase">Amount</p>
                                <p class="font-medium text-gray-800"><?= formatCurrency($item['amount'] ?? 0) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                <!-- Ownership -->
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-user text-blue-600 mr-2"></i> Ownership Details
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Current Holder</dt>
                            <dd class="font-medium text-gray-800">
                                <?= Security::escape($item['holder_name'] ?? 'Not Assigned') ?></dd>
                        </div>
                        <?php if ($item['holder_ams']): ?>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Holder AMS ID</dt>
                                <dd class="font-mono text-gray-800"><?= Security::escape($item['holder_ams']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Department</dt>
                            <dd class="font-medium text-gray-800">
                                <?= Security::escape($item['department_name'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Nodal Officer</dt>
                            <dd class="font-medium text-gray-800"><?= Security::escape($item['nodal_name'] ?? 'N/A') ?>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Location -->
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-map-marker-alt text-green-600 mr-2"></i> Location Details
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Building</dt>
                            <dd class="font-medium text-gray-800">
                                <?= Security::escape($item['building_location'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Floor</dt>
                            <dd class="font-medium text-gray-800">
                                <?= Security::escape($item['floor_location'] ?? 'N/A') ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Room</dt>
                            <dd class="font-medium text-gray-800">
                                <?= Security::escape($item['room_location'] ?? 'N/A') ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Purchase Info -->
            <div class="p-6 border-t border-gray-100 bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-receipt text-purple-600 mr-2"></i> Purchase Information
                </h3>
                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-400 uppercase">PO Number</p>
                        <p class="font-medium text-gray-800"><?= Security::escape($item['po_number'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">PO Date</p>
                        <p class="font-medium text-gray-800"><?= formatDate($item['po_date']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Purchase Date</p>
                        <p class="font-medium text-gray-800"><?= formatDate($item['purchase_date']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Budget Head</p>
                        <p class="font-medium text-gray-800"><?= Security::escape($item['budget_head'] ?? 'N/A') ?></p>
                    </div>
                </div>

                <?php if ($item['po_file_path']): ?>
                    <div class="mt-4">
                        <a href="<?= url('uploads/' . $item['po_file_path']) ?>" target="_blank"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-colors">
                            <i class="fas fa-file-pdf"></i>
                            View PO Document
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Transfer History -->
            <?php if (!empty($transferHistory)): ?>
                <div class="p-6 border-t border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-exchange-alt text-orange-600 mr-2"></i> Transfer History
                    </h3>

                    <!-- Visual Flow -->
                    <div class="flex items-center flex-wrap gap-2 mb-6 p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center gap-2 px-3 py-2 bg-blue-100 text-blue-800 rounded-lg">
                            <i class="fas fa-plus-circle"></i>
                            <span class="text-sm font-medium">Created</span>
                        </div>
                        <?php foreach (array_reverse($transferHistory) as $i => $transfer): ?>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                            <div class="flex items-center gap-2 px-3 py-2 bg-green-100 text-green-800 rounded-lg">
                                <i class="fas fa-user"></i>
                                <span class="text-sm"><?= Security::escape($transfer['to_user_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Timeline -->
                    <div class="space-y-4">
                        <?php foreach ($transferHistory as $transfer): ?>
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-exchange-alt text-orange-600"></i>
                                    </div>
                                    <div class="w-0.5 h-full bg-gray-200"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <p class="font-medium text-gray-800">
                                        <?= Security::escape($transfer['from_user_name']) ?>
                                        <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                        <?= Security::escape($transfer['to_user_name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= Security::escape($transfer['from_department_name'] ?? '') ?> →
                                        <?= Security::escape($transfer['to_department_name'] ?? '') ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?= formatDateTime($transfer['transferred_at']) ?>
                                        <?php if ($transfer['transfer_slip_number']): ?>
                                            | Slip: <?= Security::escape($transfer['transfer_slip_number']) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="text-center py-6 text-sm text-gray-500">
            <p>CSIR-SERC Asset Management System</p>
            <p class="text-xs mt-1">Scanned on <?= date('d-M-Y H:i:s') ?></p>
        </div>
    </main>
</body>

</html>