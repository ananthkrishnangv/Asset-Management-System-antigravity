<?php
/**
 * Personal Inventory Register (PIR) Page
 * Elegant Fluent UI Design with Light Gradient Statistics
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && Auth::isSupervisor()) {
        $id = (int) $_POST['item_id'];
        $db->query("UPDATE inventory_items SET is_active = 0 WHERE id = ?", [$id]);
        flash('success', 'Item deleted successfully.');
        redirect(url('public/inventory/pir.php'));
    }
}

// Get PIR items - filter by user if not supervisor
$whereClause = "i.inventory_type = 'pir' AND i.is_active = 1";
$params = [];
if (!Auth::isSupervisor()) {
    $whereClause .= " AND i.current_holder_id = ?";
    $params[] = $user['id'];
}

$items = $db->fetchAll(
    "SELECT i.*, d.name as department_name, d.code as department_code, c.name as category_name,
            u.emp_name as holder_name
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN users u ON i.current_holder_id = u.id
     WHERE $whereClause
     ORDER BY i.id DESC",
    $params
);

// Get stats
$totalValue = $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items i WHERE inventory_type = 'pir' AND is_active = 1" . (!Auth::isSupervisor() ? " AND current_holder_id = {$user['id']}" : "")) ?? 0;
$totalCount = count($items);
$thisMonthCount = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1 AND MONTH(created_at) = MONTH(NOW())" . (!Auth::isSupervisor() ? " AND current_holder_id = {$user['id']}" : "")) ?? 0;
$pendingTransfer = $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor') AND (from_user_id = ? OR to_user_id = ?)", [$user['id'], $user['id']]) ?? 0;

$pageTitle = 'Personal Inventory Register';
$pageSubtitle = Auth::isSupervisor() ? 'All personal inventory items' : 'Your assigned inventory items';

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

    .stat-card-light.green::before {
        background: linear-gradient(180deg, #22c55e, #4ade80);
    }

    .stat-card-light.blue::before {
        background: linear-gradient(180deg, #3b82f6, #60a5fa);
    }

    .stat-card-light.amber::before {
        background: linear-gradient(180deg, #f59e0b, #fbbf24);
    }

    .stat-card-light.teal::before {
        background: linear-gradient(180deg, #14b8a6, #2dd4bf);
    }

    .stat-value {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .table-container {
        background: white;
        border: 1px solid #e5e7eb;
    }

    .data-table th {
        background: #f8fafc;
        font-weight: 600;
        letter-spacing: 0.05em;
    }

    .data-table tr {
        transition: all 0.15s ease;
    }

    .data-table tr:hover {
        background: linear-gradient(90deg, #f0fdf4 0%, #f8fafc 100%);
    }

    .action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .badge-soft {
        font-weight: 500;
        letter-spacing: 0.02em;
    }
</style>

<!-- Analytics Header -->
<div class="flex flex-wrap gap-4 justify-between items-center mb-6">
    <div>
        <p class="text-sm text-gray-500 font-medium">Analytics</p>
    </div>
    <div class="flex gap-3">
        <?php if (Auth::isSupervisor()): ?>
            <a href="<?= url('public/inventory/add.php?type=pir') ?>"
                class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white px-5 py-2.5 rounded-xl font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                <i class="fas fa-plus"></i> Add Item
            </a>
        <?php endif; ?>
        <a href="<?= url('public/reports/export.php?type=pir&format=csv') ?>"
            class="bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl font-medium flex items-center gap-2 hover:bg-gray-50 transition-all">
            <i class="fas fa-download text-emerald-500"></i> Export
        </a>
    </div>
</div>

<!-- Light Gradient Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Total Items -->
    <div class="stat-card-light green rounded-2xl p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">My Items</p>
                <p class="text-3xl font-bold stat-value"><?= number_format($totalCount) ?></p>
                <p class="text-xs text-emerald-500 font-medium mt-2 flex items-center gap-1">
                    <i class="fas fa-user-tag"></i>
                    <span>Personal inventory</span>
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 flex items-center justify-center">
                <i class="fas fa-user-tag text-emerald-500 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Total Value -->
    <div class="stat-card-light blue rounded-2xl p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Value</p>
                <p class="text-2xl font-bold stat-value"><?= formatCurrency($totalValue) ?></p>
                <p class="text-xs text-blue-500 font-medium mt-2 flex items-center gap-1">
                    <i class="fas fa-chart-line"></i>
                    <span>Asset worth</span>
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                <i class="fas fa-rupee-sign text-blue-500 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- This Month -->
    <div class="stat-card-light amber rounded-2xl p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">This Month</p>
                <p class="text-3xl font-bold stat-value"><?= number_format($thisMonthCount) ?></p>
                <p class="text-xs text-amber-500 font-medium mt-2 flex items-center gap-1">
                    <i class="fas fa-calendar"></i>
                    <span>New additions</span>
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 flex items-center justify-center">
                <i class="fas fa-calendar-alt text-amber-500 text-lg"></i>
            </div>
        </div>
    </div>

    <!-- Pending Transfers -->
    <div class="stat-card-light teal rounded-2xl p-5 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Pending</p>
                <p class="text-3xl font-bold stat-value"><?= number_format($pendingTransfer) ?></p>
                <p class="text-xs text-teal-500 font-medium mt-2 flex items-center gap-1">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transfers</span>
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-50 to-teal-100 flex items-center justify-center">
                <i class="fas fa-exchange-alt text-teal-500 text-lg"></i>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="table-container rounded-2xl overflow-hidden shadow-sm">
    <!-- Table Header -->
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap gap-4 justify-between items-center bg-white">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shadow-lg">
                <i class="fas fa-clipboard-list text-white"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">PIR Items</h3>
                <p class="text-xs text-gray-400"><?= number_format($totalCount) ?> total records</p>
            </div>
        </div>
        <div class="relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Search items..."
                class="pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white w-72 text-sm transition-all">
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="p-16 text-center bg-gradient-to-br from-gray-50 to-white">
            <div
                class="w-20 h-20 bg-gradient-to-br from-emerald-100 to-teal-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-inbox text-3xl text-emerald-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No PIR Items Found</h3>
            <p class="text-gray-500 mb-6">You don't have any personal inventory items assigned yet.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full data-table" id="pirTable">
                <thead>
                    <tr>
                        <th class="px-5 py-4 text-left text-xs text-gray-500 uppercase">Serial No.</th>
                        <th class="px-5 py-4 text-left text-xs text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-4 text-left text-xs text-gray-500 uppercase">Category</th>
                        <?php if (Auth::isSupervisor()): ?>
                            <th class="px-5 py-4 text-left text-xs text-gray-500 uppercase">Holder</th>
                        <?php endif; ?>
                        <th class="px-5 py-4 text-right text-xs text-gray-500 uppercase">Value</th>
                        <th class="px-5 py-4 text-center text-xs text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-4 text-center text-xs text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $condition = $item['condition_status'] ?? 'good';
                        $conditionStyles = [
                            'new' => 'bg-emerald-50 text-emerald-600 border-emerald-200',
                            'good' => 'bg-blue-50 text-blue-600 border-blue-200',
                            'fair' => 'bg-amber-50 text-amber-600 border-amber-200',
                            'poor' => 'bg-red-50 text-red-600 border-red-200',
                            'non_serviceable' => 'bg-gray-100 text-gray-600 border-gray-300'
                        ];
                        ?>
                        <tr class="bg-white">
                            <td class="px-5 py-4">
                                <span
                                    class="font-mono text-sm font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                    <?= Security::escape($item['serial_number'] ?? $item['Item_ID'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <a href="<?= url('public/inventory/item-details.php?id=' . ($item['id'] ?? $item['Item_ID'])) ?>"
                                    class="font-medium text-gray-800 hover:text-emerald-600 transition-colors block max-w-xs truncate">
                                    <?= Security::escape(truncate($item['item_description'] ?? $item['Item_desc'] ?? '-', 45)) ?>
                                </a>
                                <?php if (!empty($item['po_number'] ?? $item['po_no'])): ?>
                                    <span class="text-xs text-gray-400 flex items-center gap-1 mt-1">
                                        <i class="fas fa-file-invoice text-gray-300"></i>
                                        PO: <?= Security::escape($item['po_number'] ?? $item['po_no']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600"><?= Security::escape($item['category_name'] ?? '-') ?>
                            </td>
                            <?php if (Auth::isSupervisor()): ?>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white text-xs font-bold">
                                            <?= strtoupper(substr($item['holder_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <span
                                            class="text-sm text-gray-700"><?= Security::escape(truncate($item['holder_name'] ?? '-', 20)) ?></span>
                                    </div>
                                </td>
                            <?php endif; ?>
                            <td class="px-5 py-4 text-right">
                                <span class="font-semibold text-gray-800"><?= formatCurrency($item['unit_price'] ?? 0) ?></span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span
                                    class="badge-soft inline-flex items-center px-2.5 py-1 text-xs rounded-lg border <?= $conditionStyles[$condition] ?? 'bg-gray-50 text-gray-600' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $condition)) ?>
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-center gap-1">
                                    <a href="<?= url('public/inventory/item-details.php?id=' . ($item['id'] ?? $item['Item_ID'])) ?>"
                                        class="action-btn text-blue-500 hover:bg-blue-50" title="View Details">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="<?= url('public/inventory/item-documents.php?id=' . ($item['id'] ?? $item['Item_ID'])) ?>"
                                        class="action-btn text-indigo-500 hover:bg-indigo-50" title="Documents">
                                        <i class="fas fa-images text-sm"></i>
                                    </a>
                                    <a href="<?= url('public/transfers/index.php?item_id=' . ($item['id'] ?? '')) ?>"
                                        class="action-btn text-teal-500 hover:bg-teal-50" title="Transfer">
                                        <i class="fas fa-exchange-alt text-sm"></i>
                                    </a>
                                    <?php if (Auth::isSupervisor()): ?>
                                        <a href="<?= url('public/inventory/add.php?type=pir&edit=' . ($item['id'] ?? '')) ?>"
                                            class="action-btn text-amber-500 hover:bg-amber-50" title="Edit">
                                            <i class="fas fa-pen text-sm"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this item?')">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?? '' ?>">
                                            <button type="submit" class="action-btn text-red-500 hover:bg-red-50" title="Delete">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('searchInput')?.addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#pirTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>