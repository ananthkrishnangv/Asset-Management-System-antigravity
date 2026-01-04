<?php
/**
 * QR Code Generator Page
 * Generate QR codes for assets
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();

// Get selected items
$selectedIds = isset($_GET['ids']) ? explode(',', $_GET['ids']) : [];
$items = [];

if (!empty($selectedIds)) {
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
    $items = $db->fetchAll(
        "SELECT * FROM inventory_items WHERE id IN ($placeholders)",
        $selectedIds
    );
}

// Get all items for selection
$allItems = $db->fetchAll(
    "SELECT id, item_description, serial_number, inventory_type FROM inventory_items WHERE is_active = 1 ORDER BY item_description LIMIT 100"
);

$pageTitle = 'QR Code Generator';
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .qr-card { break-inside: avoid; }
        @media print {
            .no-print { display: none !important; }
            .qr-card { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8 no-print">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?= $pageTitle ?></h1>
                <p class="text-gray-600">Generate QR codes for assets</p>
            </div>
            <div class="flex gap-4">
                <a href="<?= url('public/dashboard.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i> Print QR Codes
                </button>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <!-- Selection Form -->
            <div class="bg-white rounded-2xl shadow-sm p-6 no-print">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Select Assets for QR Codes</h2>
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto p-2">
                        <?php foreach ($allItems as $item): ?>
                            <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-blue-50 transition-colors">
                                <input type="checkbox" name="items[]" value="<?= $item['id'] ?>" 
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-800 truncate"><?= Security::escape($item['item_description']) ?></p>
                                    <p class="text-xs text-gray-500"><?= Security::escape($item['serial_number'] ?? 'N/A') ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" onclick="prepareForm()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                        <i class="fas fa-qrcode mr-2"></i> Generate QR Codes
                    </button>
                </form>
            </div>
            <script>
                function prepareForm() {
                    const form = document.querySelector('form');
                    const checked = form.querySelectorAll('input[name="items[]"]:checked');
                    const ids = Array.from(checked).map(cb => cb.value).join(',');
                    window.location.href = '?ids=' + ids;
                    return false;
                }
            </script>
        <?php else: ?>
            <!-- QR Codes Display -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($items as $item): ?>
                    <div class="qr-card bg-white rounded-2xl shadow-sm p-6 text-center">
                        <div id="qr-<?= $item['id'] ?>" class="mx-auto mb-4"></div>
                        <h3 class="font-bold text-gray-800 text-sm mb-1"><?= Security::escape(truncate($item['item_description'], 30)) ?></h3>
                        <p class="text-xs text-gray-500"><?= Security::escape($item['serial_number'] ?? 'N/A') ?></p>
                        <p class="text-xs text-blue-600 mt-1"><?= strtoupper($item['inventory_type']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <script>
                <?php foreach ($items as $item): ?>
                    new QRCode(document.getElementById("qr-<?= $item['id'] ?>"), {
                        text: "<?= APP_URL ?>/public/inventory/item-details.php?id=<?= $item['id'] ?>",
                        width: 128,
                        height: 128,
                        colorDark: "#1e3a5f",
                        colorLight: "#ffffff"
                    });
                <?php endforeach; ?>
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
