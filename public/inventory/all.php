<?php
/**
 * All Inventory Page
 * Shows all DIR and PIR items across all departments
 * Access: Admin and Purchase Officer (E Maheshkumar) only
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

// Check access - Admin or Purchase Officer (E Maheshkumar)
$user = Auth::user();
$isPurchaseOfficer = stripos($user['emp_name'] ?? '', 'maheshkumar') !== false ||
    stripos($user['email_id'] ?? '', 'emaheshkumar') !== false;

if (!Auth::isAdmin() && !$isPurchaseOfficer) {
    flash('error', 'Access denied. This page is only for admins and purchase officers.');
    redirect(url('public/dashboard.php'));
}

$db = Database::getInstance();

// Filters
$type = $_GET['type'] ?? 'all';
$department = $_GET['department'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['i.is_active = 1'];
$params = [];

if ($type && $type !== 'all') {
    $where[] = 'i.inventory_type = ?';
    $params[] = $type;
}

if ($department) {
    $where[] = 'i.department_id = ?';
    $params[] = $department;
}

if ($category) {
    $where[] = 'i.category_id = ?';
    $params[] = $category;
}

if ($search) {
    $where[] = '(i.serial_number LIKE ? OR i.item_description LIKE ? OR i.custodian LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Get total count
$totalItems = $db->fetchValue(
    "SELECT COUNT(*) FROM inventory_items i WHERE $whereClause",
    $params
);
$totalPages = ceil($totalItems / $perPage);

// Get items
$items = $db->fetchAll(
    "SELECT i.*, d.name as department_name, d.code as department_code, 
            c.name as category_name, u.emp_name as created_by_name
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN users u ON i.created_by = u.id
     WHERE $whereClause
     ORDER BY i.created_at DESC
     LIMIT $perPage OFFSET $offset",
    $params
);

// Get filter options
$departments = $db->fetchAll("SELECT id, code, name FROM departments ORDER BY name");
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");

// Summary stats
$totalValue = $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items i WHERE $whereClause", $params) ?? 0;
$dirCount = $db->fetchValue("SELECT COUNT(*) FROM inventory_items i WHERE $whereClause AND i.inventory_type = 'dir'", $params) ?? 0;
$pirCount = $db->fetchValue("SELECT COUNT(*) FROM inventory_items i WHERE $whereClause AND i.inventory_type = 'pir'", $params) ?? 0;

$pageTitle = 'All Inventory';
$pageSubtitle = 'View all assets across departments';
ob_start();
?>

<style>
    .filter-card {
        background: white;
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem;
    }

    .data-table {
        width: 100%;
    }

    .data-table th {
        text-align: left;
        padding: 0.75rem 1rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: #6B7280;
        text-transform: uppercase;
        background: #F9FAFB;
        white-space: nowrap;
    }

    .data-table td {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
        color: #374151;
        border-top: 1px solid #F3F4F6;
    }

    .data-table tr:hover td {
        background: #F9FAFB;
    }

    .badge {
        display: inline-flex;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .badge-dir {
        background: #DBEAFE;
        color: #1D4ED8;
    }

    .badge-pir {
        background: #D1FAE5;
        color: #047857;
    }
</style>

<!-- Stats Row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <p class="text-2xl font-bold text-gray-800">
            <?= number_format($totalItems) ?>
        </p>
        <p class="text-sm text-gray-500">Total Items</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <p class="text-2xl font-bold text-blue-600">
            <?= number_format($dirCount) ?>
        </p>
        <p class="text-sm text-gray-500">DIR Items</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <p class="text-2xl font-bold text-green-600">
            <?= number_format($pirCount) ?>
        </p>
        <p class="text-sm text-gray-500">PIR Items</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <p class="text-xl font-bold text-purple-600">
            <?= formatCurrency($totalValue) ?>
        </p>
        <p class="text-sm text-gray-500">Total Value</p>
    </div>
</div>

<!-- Filters -->
<div class="filter-card mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
            <select name="type"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>All Types</option>
                <option value="dir" <?= $type === 'dir' ? 'selected' : '' ?>>DIR Only</option>
                <option value="pir" <?= $type === 'pir' ? 'selected' : '' ?>>PIR Only</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Department</label>
            <select name="department"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                <option value="">All Departments</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $department == $dept['id'] ? 'selected' : '' ?>>
                        <?= Security::escape($dept['code']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
            <select name="category"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                        <?= Security::escape($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="<?= Security::escape($search) ?>"
                placeholder="Serial, description..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-medium hover:bg-blue-600">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
            <a href="?" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Serial No.</th>
                    <th>Description</th>
                    <th>Department</th>
                    <th>Category</th>
                    <th>Custodian</th>
                    <th>Value</th>
                    <th>Condition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No items found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <span class="badge badge-<?= $item['inventory_type'] ?>">
                                    <?= strtoupper($item['inventory_type']) ?>
                                </span>
                            </td>
                            <td class="font-medium">
                                <?= Security::escape($item['serial_number']) ?>
                            </td>
                            <td class="max-w-xs truncate" title="<?= Security::escape($item['item_description']) ?>">
                                <?= Security::escape(truncate($item['item_description'], 40)) ?>
                            </td>
                            <td>
                                <?= Security::escape($item['department_code'] ?? '-') ?>
                            </td>
                            <td>
                                <?= Security::escape($item['category_name'] ?? '-') ?>
                            </td>
                            <td>
                                <?= Security::escape($item['custodian'] ?? '-') ?>
                            </td>
                            <td class="font-medium">
                                <?= formatCurrency($item['unit_price'] ?? 0) ?>
                            </td>
                            <td>
                                <span class="capitalize text-xs">
                                    <?= str_replace('_', ' ', $item['condition_status'] ?? 'good') ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= url('public/inventory/view.php?id=' . $item['id']) ?>"
                                    class="text-blue-500 hover:text-blue-700 text-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Showing
                <?= $offset + 1 ?> to
                <?= min($offset + $perPage, $totalItems) ?> of
                <?= number_format($totalItems) ?> items
            </p>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                        class="px-3 py-1 bg-gray-100 text-gray-600 rounded text-sm hover:bg-gray-200">Prev</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                        class="px-3 py-1 <?= $i === $page ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> rounded text-sm">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                        class="px-3 py-1 bg-gray-100 text-gray-600 rounded text-sm hover:bg-gray-200">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>