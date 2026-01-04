<?php
/**
 * Item Details Page - Full item view with transfer history
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();
$itemId = (int) ($_GET['id'] ?? 0);

if (!$itemId) {
    header('Location: dir.php');
    exit;
}

$item = $db->fetch(
    "SELECT i.*, c.name as category_name, d.name as department_name,
            u.emp_name as holder_name, u.ams_id as holder_ams, u.email_id as holder_email,
            n.emp_name as nodal_name, cr.emp_name as created_by_name
     FROM inventory_items i
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN departments d ON i.department_id = d.id
     LEFT JOIN users u ON i.current_holder_id = u.id
     LEFT JOIN users n ON i.nodal_officer_id = n.id
     LEFT JOIN users cr ON i.created_by = cr.id
     WHERE i.id = ?",
    [$itemId]
);

if (!$item) {
    flash('error', 'Item not found');
    header('Location: dir.php');
    exit;
}

// Get transfer history
$transferHistory = $db->fetchAll(
    "SELECT * FROM transfer_history WHERE item_id = ? ORDER BY transferred_at DESC",
    [$itemId]
);

// Get pending transfers
$pendingTransfers = $db->fetchAll(
    "SELECT tr.*, fu.emp_name as from_user, tu.emp_name as to_user
     FROM transfer_requests tr
     LEFT JOIN users fu ON tr.from_user_id = fu.id
     LEFT JOIN users tu ON tr.to_user_id = tu.id
     WHERE tr.item_id = ? AND tr.status NOT IN ('completed', 'rejected')
     ORDER BY tr.created_at DESC",
    [$itemId]
);

// Generate QR URL
$qrUrl = APP_URL . '/public/qr/print.php?serial=' . urlencode($item['serial_number']);
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrUrl);

$pageTitle = 'Item Details';
$pageSubtitle = $item['serial_number'];

ob_start();
?>

<div class="mb-6">
    <a href="<?= $item['inventory_type'] === 'dir' ? 'dir.php' : 'pir.php' ?>"
        class="inline-flex items-center text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i> Back to <?= strtoupper($item['inventory_type']) ?>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Details -->
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-blue-200 text-sm font-mono"><?= Security::escape($item['serial_number']) ?></p>
                    <h2 class="text-xl font-bold text-white mt-1"><?= Security::escape($item['item_description']) ?>
                    </h2>
                </div>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white">
                    <?= strtoupper($item['inventory_type']) ?>
                </span>
            </div>

            <div class="p-6">
                <?php if ($item['image_path']): ?>
                    <div class="mb-6">
                        <img src="<?= url('uploads/' . $item['image_path']) ?>"
                            class="max-w-md rounded-xl shadow-lg cursor-pointer" onclick="window.open(this.src)">
                    </div>
                <?php endif; ?>

                <?php if ($item['detailed_description']): ?>
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase mb-2">Detailed Description</h3>
                        <p class="text-gray-700"><?= nl2br(Security::escape($item['detailed_description'])) ?></p>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Category</p>
                        <p class="font-medium text-gray-800"><?= Security::escape($item['category_name'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Quantity</p>
                        <p class="font-medium text-gray-800"><?= $item['quantity'] ?> <?= $item['quantity_unit'] ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Amount</p>
                        <p class="font-medium text-gray-800"><?= formatCurrency($item['amount'] ?? 0) ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Condition</p>
                        <span
                            class="inline-block px-2 py-1 rounded text-xs font-medium <?= getStatusBadge($item['condition_status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', $item['condition_status'])) ?>
                        </span>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Purchase Date</p>
                        <p class="font-medium text-gray-800"><?= formatDate($item['purchase_date']) ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-400 uppercase">Created</p>
                        <p class="font-medium text-gray-800"><?= formatDate($item['created_at']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location & Purchase -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl p-6 card-shadow">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-map-marker-alt text-green-600 mr-2"></i>Location
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Department</dt>
                        <dd class="font-medium"><?= Security::escape($item['department_name'] ?? 'N/A') ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Building</dt>
                        <dd class="font-medium"><?= Security::escape($item['building_location'] ?? 'N/A') ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Floor</dt>
                        <dd class="font-medium"><?= Security::escape($item['floor_location'] ?? 'N/A') ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Room</dt>
                        <dd class="font-medium"><?= Security::escape($item['room_location'] ?? 'N/A') ?></dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl p-6 card-shadow">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-file-invoice text-purple-600 mr-2"></i>Purchase Info
                </h3>
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">PO Number</dt>
                        <dd class="font-medium"><?= Security::escape($item['po_number'] ?? 'N/A') ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">PO Date</dt>
                        <dd class="font-medium"><?= formatDate($item['po_date']) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Budget Head</dt>
                        <dd class="font-medium"><?= Security::escape($item['budget_head'] ?? 'N/A') ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Stock Ref</dt>
                        <dd class="font-medium"><?= Security::escape($item['stock_reference'] ?? 'N/A') ?></dd>
                    </div>
                </dl>
                <?php if ($item['po_file_path']): ?>
                    <a href="<?= url('uploads/' . $item['po_file_path']) ?>" target="_blank"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">
                        <i class="fas fa-file-pdf"></i> View PO Document
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Transfer History -->
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-exchange-alt text-orange-600 mr-2"></i>Transfer History
                </h3>
            </div>

            <?php if (!empty($transferHistory)): ?>
                <!-- Visual Flow -->
                <div class="p-6 bg-gray-50 border-b border-gray-100">
                    <p class="text-sm text-gray-500 mb-3">Transfer Flow:</p>
                    <div class="flex items-center flex-wrap gap-2">
                        <div class="flex items-center gap-2 px-3 py-2 bg-blue-100 text-blue-800 rounded-lg">
                            <i class="fas fa-asterisk"></i>
                            <span class="text-sm font-medium">Created</span>
                        </div>
                        <?php foreach (array_reverse($transferHistory) as $transfer): ?>
                            <i class="fas fa-long-arrow-alt-right text-gray-400"></i>
                            <div class="flex items-center gap-2 px-3 py-2 bg-green-100 text-green-800 rounded-lg">
                                <i class="fas fa-user"></i>
                                <div class="text-sm">
                                    <span class="font-medium"><?= Security::escape($transfer['to_user_name']) ?></span>
                                    <span
                                        class="text-xs block text-green-600"><?= Security::escape($transfer['to_department_name'] ?? '') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($transferHistory as $transfer): ?>
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-exchange-alt text-orange-600"></i>
                                    </div>
                                    <div class="w-0.5 flex-1 bg-gray-200"></div>
                                </div>
                                <div class="flex-1 pb-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium text-gray-800">
                                                <?= Security::escape($transfer['from_user_name']) ?>
                                                <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                                <?= Security::escape($transfer['to_user_name']) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?= Security::escape($transfer['from_department_name'] ?? '') ?> →
                                                <?= Security::escape($transfer['to_department_name'] ?? '') ?>
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs text-gray-400"><?= formatDateTime($transfer['transferred_at']) ?></span>
                                    </div>
                                    <?php if ($transfer['transfer_slip_number']): ?>
                                        <p class="text-xs text-gray-500 mt-1">Slip:
                                            <?= Security::escape($transfer['transfer_slip_number']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-history text-4xl mb-3"></i>
                    <p>No transfer history yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- QR Code -->
        <div class="bg-white rounded-2xl p-6 card-shadow text-center">
            <img src="<?= $qrApiUrl ?>" class="mx-auto rounded-lg shadow mb-4" alt="QR Code">
            <p class="text-sm text-gray-500 mb-4">Scan to view details</p>
            <a href="<?= url('public/qr/print.php?serial=' . urlencode($item['serial_number']) . '&print=1') ?>"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-print"></i> Print QR Label
            </a>
        </div>

        <!-- Current Holder -->
        <div class="bg-white rounded-2xl p-6 card-shadow">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-user text-blue-600 mr-2"></i>Current Holder
            </h3>
            <?php if ($item['holder_name']): ?>
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                        <?= strtoupper(substr($item['holder_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?= Security::escape($item['holder_name']) ?></p>
                        <p class="text-sm text-gray-500"><?= Security::escape($item['holder_ams']) ?></p>
                        <p class="text-xs text-gray-400"><?= Security::escape($item['holder_email']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-gray-500">Not assigned</p>
            <?php endif; ?>
        </div>

        <!-- Pending Transfers -->
        <?php if (!empty($pendingTransfers)): ?>
            <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-200">
                <h3 class="text-lg font-bold text-yellow-800 mb-4">
                    <i class="fas fa-clock mr-2"></i>Pending Transfer
                </h3>
                <?php foreach ($pendingTransfers as $pt): ?>
                    <div class="bg-white rounded-lg p-3 mb-2">
                        <p class="text-sm">
                            To: <span class="font-medium"><?= Security::escape($pt['to_user']) ?></span>
                        </p>
                        <p class="text-xs text-yellow-600"><?= ucfirst(str_replace('_', ' ', $pt['status'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="bg-white rounded-2xl p-6 card-shadow">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Actions</h3>
            <div class="space-y-2">
                <?php if (Auth::isSupervisor()): ?>
                    <a href="<?= $item['inventory_type'] === 'dir' ? 'dir.php' : 'pir.php' ?>?action=edit&id=<?= $item['id'] ?>"
                        class="w-full flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200">
                        <i class="fas fa-edit"></i> Edit Item
                    </a>
                <?php endif; ?>

                <a href="../transfers/index.php?item_id=<?= $item['id'] ?>"
                    class="w-full flex items-center gap-2 px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200">
                    <i class="fas fa-exchange-alt"></i> Initiate Transfer
                </a>

                <a href="../stores/returns.php?item_id=<?= $item['id'] ?>"
                    class="w-full flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">
                    <i class="fas fa-warehouse"></i> Stores Return
                </a>
            </div>
        </div>

        <!-- Metadata -->
        <div class="bg-gray-50 rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase mb-3">Metadata</h3>
            <dl class="text-sm space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-400">Created By</dt>
                    <dd><?= Security::escape($item['created_by_name'] ?? 'System') ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Created At</dt>
                    <dd><?= formatDateTime($item['created_at']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Updated At</dt>
                    <dd><?= formatDateTime($item['updated_at']) ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
