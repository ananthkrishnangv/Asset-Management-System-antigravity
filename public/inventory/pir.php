<?php
/**
 * Personal Inventory Register (PIR) Management
 */

require_once __PIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    // ADD
    if (isset($_POST['add'])) {
        Auth::requireSupervisor();

        try {
            $serialNumber = SerialNumber::generateForInventory('PIR');

            // Handle image upload
            $imagePath = null;
            if (!empty($_FILES['item_image']['name'])) {
                $imagePath = handleImageUpload($_FILES['item_image'], 'items');
            }

            // Handle PO file upload
            $poFilePath = null;
            if (!empty($_FILES['po_file']['name'])) {
                $poFilePath = handleFileUpload($_FILES['po_file'], 'po');
            }

            $data = [
                'serial_number' => $serialNumber,
                'item_description' => Security::sanitize($_POST['item_description']),
                'detailed_description' => Security::sanitize($_POST['detailed_description'] ?? ''),
                'category_id' => (int) $_POST['category_id'] ?: null,
                'quantity' => (int) $_POST['quantity'],
                'quantity_unit' => Security::sanitize($_POST['quantity_unit']),
                'amount' => (float) $_POST['amount'],
                'purchase_date' => $_POST['purchase_date'] ?: null,
                'po_number' => Security::sanitize($_POST['po_number'] ?? ''),
                'po_date' => $_POST['po_date'] ?: null,
                'po_file_path' => $poFilePath,
                'budget_head' => Security::sanitize($_POST['budget_head'] ?? ''),
                'stock_reference' => Security::sanitize($_POST['stock_reference'] ?? ''),
                'issue_number' => (int) $_POST['issue_number'] ?: null,
                'issue_date' => $_POST['issue_date'] ?: null,
                'building_location' => Security::sanitize($_POST['building_location'] ?? ''),
                'floor_location' => Security::sanitize($_POST['floor_location'] ?? ''),
                'department_id' => (int) $_POST['department_id'] ?: null,
                'room_location' => Security::sanitize($_POST['room_location'] ?? ''),
                'current_holder_id' => (int) $_POST['current_holder_id'] ?: null,
                'nodal_officer_id' => (int) $_POST['nodal_officer_id'] ?: null,
                'condition_status' => $_POST['condition_status'] ?? 'good',
                'inventory_type' => 'pir',
                'created_by' => Auth::id()
            ];

            if ($imagePath) {
                $data['image_path'] = $imagePath;
            }

            $itemId = $db->insert('inventory_items', $data);

            // Log activity
            ActivityLog::log(
                'create',
                'inventory',
                $itemId,
                'pir',
                'Created PIR item: ' . $data['item_description'] . ' (' . $serialNumber . ')'
            );

            // Send notification
            Mailer::sendDeletionNotification($data['item_description'], Auth::user()['emp_name'], $serialNumber);

            jsonResponse(['success' => true, 'id' => $itemId, 'serial_number' => $serialNumber]);

        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    // UPDATE
    if (isset($_POST['update'])) {
        Auth::requireSupervisor();

        $itemId = (int) $_POST['item_id'];
        $oldData = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);

        if (!$oldData) {
            jsonResponse(['error' => 'Item not found'], 404);
        }

        $data = [
            'item_description' => Security::sanitize($_POST['item_description']),
            'detailed_description' => Security::sanitize($_POST['detailed_description'] ?? ''),
            'category_id' => (int) $_POST['category_id'] ?: null,
            'quantity' => (int) $_POST['quantity'],
            'quantity_unit' => Security::sanitize($_POST['quantity_unit']),
            'amount' => (float) $_POST['amount'],
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'po_number' => Security::sanitize($_POST['po_number'] ?? ''),
            'po_date' => $_POST['po_date'] ?: null,
            'budget_head' => Security::sanitize($_POST['budget_head'] ?? ''),
            'stock_reference' => Security::sanitize($_POST['stock_reference'] ?? ''),
            'building_location' => Security::sanitize($_POST['building_location'] ?? ''),
            'floor_location' => Security::sanitize($_POST['floor_location'] ?? ''),
            'department_id' => (int) $_POST['department_id'] ?: null,
            'room_location' => Security::sanitize($_POST['room_location'] ?? ''),
            'current_holder_id' => (int) $_POST['current_holder_id'] ?: null,
            'nodal_officer_id' => (int) $_POST['nodal_officer_id'] ?: null,
            'condition_status' => $_POST['condition_status'] ?? 'good'
        ];

        // Handle new image upload
        if (!empty($_FILES['item_image']['name'])) {
            $data['image_path'] = handleImageUpload($_FILES['item_image'], 'items');
        }

        // Handle new PO file
        if (!empty($_FILES['po_file']['name'])) {
            $data['po_file_path'] = handleFileUpload($_FILES['po_file'], 'po');
        }

        $db->update('inventory_items', $data, 'id = :id', ['id' => $itemId]);

        ActivityLog::log(
            'update',
            'inventory',
            $itemId,
            'pir',
            'Updated PIR item: ' . $data['item_description'],
            $oldData,
            $data
        );

        jsonResponse(['success' => true]);
    }

    // DELETE
    if (isset($_POST['delete_id'])) {
        Auth::requireSupervisor();

        $itemId = (int) $_POST['delete_id'];
        $item = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);

        if ($item) {
            $db->update('inventory_items', ['is_active' => 0], 'id = :id', ['id' => $itemId]);

            ActivityLog::log(
                'delete',
                'inventory',
                $itemId,
                'pir',
                'Deleted PIR item: ' . $item['item_description'] . ' (' . $item['serial_number'] . ')'
            );

            // Send deletion notification email
            Mailer::sendDeletionNotification($item['item_description'], Auth::user()['emp_name'], $item['serial_number']);

            jsonResponse(['success' => true]);
        } else {
            jsonResponse(['error' => 'Item not found'], 404);
        }
    }

    // FETCH / SEARCH
    $search = '%' . Security::sanitize($_POST['search'] ?? '') . '%';
    $sort = in_array($_POST['sort'] ?? '', ['serial_number', 'item_description', 'amount', 'created_at'])
        ? $_POST['sort'] : 'created_at';
    $order = ($_POST['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT i.*, c.name as category_name, d.name as department_name,
                   u.emp_name as holder_name, n.emp_name as nodal_name
            FROM inventory_items i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN departments d ON i.department_id = d.id
            LEFT JOIN users u ON i.current_holder_id = u.id
            LEFT JOIN users n ON i.nodal_officer_id = n.id
            WHERE i.inventory_type = 'pir' AND i.is_active = 1
            AND (i.serial_number LIKE ? OR i.item_description LIKE ? OR i.po_number LIKE ?)
            ORDER BY {$sort} {$order}
            LIMIT {$limit} OFFSET {$offset}";

    $items = $db->fetchAll($sql, [$search, $search, $search]);

    $total = $db->fetchValue(
        "SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1
         AND (serial_number LIKE ? OR item_description LIKE ? OR po_number LIKE ?)",
        [$search, $search, $search]
    );

    jsonResponse([
        'data' => $items,
        'total' => $total,
        'pages' => ceil($total / $limit),
        'page' => $page
    ]);
}

// Helper functions
function handleImageUpload($file, $folder)
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid image type. Allowed: JPG, PNG');
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('File too large. Maximum: 10MB');
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_pir($uploadDir)) {
        mkpir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload file');
    }

    return $folder . '/' . $filename;
}

function handleFileUpload($file, $folder)
{
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Allowed: PDF, JPG, PNG');
    }

    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('File too large. Maximum: 10MB');
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_pir($uploadDir)) {
        mkpir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload file');
    }

    return $folder . '/' . $filename;
}

// Get categories and departments for dropdowns
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");
$users = $db->fetchAll("SELECT id, ams_id, emp_name FROM users WHERE is_active = 1 ORDER BY emp_name");

$pageTitle = 'Personal Inventory Register (PIR)';
$pageSubtitle = 'Manage personal inventory items';

ob_start();
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <!-- Global Search -->
        <div class="relative">
            <input type="text" id="searchInput" placeholder="Search items..."
                class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl w-80 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>

    <?php if (Auth::isSupervisor()): ?>
        <div class="flex items-center gap-3">
            <button onclick="exportToExcel()"
                class="flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors">
                <i class="fas fa-file-excel"></i>
                <span>Export Excel</span>
            </button>
            <button onclick="exportToPDF()"
                class="flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors">
                <i class="fas fa-file-pdf"></i>
                <span>Export PDF</span>
            </button>
            <button onclick="openAddModal()"
                class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
                <i class="fas fa-plus"></i>
                <span>Add New Item</span>
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl p-4 mb-6 card-shadow">
    <div class="flex flex-wrap items-center gap-4">
        <select id="filterCategory"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= Security::escape($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filterDepartment"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>"><?= Security::escape($dept['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="filterCondition"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Conditions</option>
            <option value="new">New</option>
            <option value="good">Good</option>
            <option value="fair">Fair</option>
            <option value="poor">Poor</option>
            <option value="non_serviceable">Non-Serviceable</option>
        </select>

        <input type="date" id="filterDateFrom"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="From Date">
        <input type="date" id="filterDateTo"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="To Date">

        <button onclick="clearFilters()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
            <i class="fas fa-times mr-1"></i> Clear
        </button>
    </div>
</div>

<!-- Data Table -->
<div class="bg-white rounded-2xl card-shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-slate-800 to-slate-900 text-white">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-semibold cursor-pointer"
                        onclick="sortBy('serial_number')">
                        Serial No <i class="fas fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Image</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold cursor-pointer"
                        onclick="sortBy('item_description')">
                        Description <i class="fas fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Category</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Qty</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold cursor-pointer" onclick="sortBy('amount')">
                        Amount <i class="fas fa-sort ml-1"></i>
                    </th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Location</th>
                    <th class="px-6 py-4 text-left text-sm font-semibold">Condition</th>
                    <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100">
                <!-- Data loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div id="pagination" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-sm text-gray-500">Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of
            <span id="totalItems">0</span> items</p>
        <div id="paginationButtons" class="flex gap-2"></div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="itemModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div
            class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10">
            <div class="sticky top-0 bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 rounded-t-2xl">
                <h3 id="modalTitle" class="text-xl font-bold text-white">Add New Item</h3>
            </div>

            <form id="itemForm" class="p-6" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="item_id" id="itemId">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Basic Info -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Item Description *</label>
                        <input type="text" name="item_description" id="itemDescription" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                        <select name="category_id" id="categoryId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= Security::escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Detailed Description</label>
                        <textarea name="detailed_description" id="detailedDescription" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-camera text-blue-600 mr-1"></i> Item Image
                        </label>
                        <div class="flex items-center gap-4">
                            <div id="imagePreview"
                                class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center bg-gray-50">
                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <input type="file" name="item_image" id="itemImage" accept="image/*" class="hidden"
                                    onchange="previewImage(this)">
                                <button type="button" onclick="document.getElementById('itemImage').click()"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-upload mr-2"></i> Upload Image
                                </button>
                                <p class="text-xs text-gray-500 mt-1">JPG, PNG. Max 10MB</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity & Amount -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" id="quantity" required min="1" value="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Unit</label>
                        <select name="quantity_unit" id="quantityUnit"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Nos">Nos</option>
                            <option value="Sets">Sets</option>
                            <option value="Pairs">Pairs</option>
                            <option value="Boxes">Boxes</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (₹)</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Purchase Info -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PO Number</label>
                        <input type="text" name="po_number" id="poNumber"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">PO Date</label>
                        <input type="date" name="po_date" id="poDate"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Purchase Date</label>
                        <input type="date" name="purchase_date" id="purchaseDate"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- PO File Upload -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-file-pdf text-red-600 mr-1"></i> PO Document
                        </label>
                        <input type="file" name="po_file" id="poFile" accept=".pdf,.jpg,.jpeg,.png"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG. Max 10MB</p>
                    </div>

                    <!-- Location Info -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Department</label>
                        <select name="department_id" id="departmentId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= Security::escape($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Building</label>
                        <input type="text" name="building_location" id="buildingLocation"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Floor</label>
                        <input type="text" name="floor_location" id="floorLocation"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Room</label>
                        <input type="text" name="room_location" id="roomLocation"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Holder Info -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Current Holder</label>
                        <select name="current_holder_id" id="currentHolderId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Holder</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= Security::escape($u['emp_name']) ?>
                                    (<?= $u['ams_id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Condition</label>
                        <select name="condition_status" id="conditionStatus"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="new">New</option>
                            <option value="good" selected>Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="non_serviceable">Non-Serviceable</option>
                        </select>
                    </div>

                    <!-- Additional -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Budget Head</label>
                        <input type="text" name="budget_head" id="budgetHead"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Stock Reference</label>
                        <input type="text" name="stock_reference" id="stockReference"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-100">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all">
                        <i class="fas fa-save mr-2"></i> Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalScripts = <<<'SCRIPT'
<script>
let currentPage = 1;
let sortColumn = 'created_at';
let sortOrder = 'DESC';
let editMode = false;

// Load table data
function loadTable() {
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('search', document.getElementById('searchInput').value);
    formData.append('sort', sortColumn);
    formData.append('order', sortOrder);
    formData.append('page', currentPage);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        renderTable(data.data);
        renderPagination(data);
    });
}

function renderTable(items) {
    const tbody = document.getElementById('tableBody');
    
    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>No items found</p>
                </td>
            </tr>`;
        return;
    }
    
    tbody.innerHTML = items.map(item => `
        <tr class="table-row-hover">
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <button onclick="printQR('${item.serial_number}')" class="text-gray-400 hover:text-blue-600" title="Print QR">
                        <i class="fas fa-qrcode"></i>
                    </button>
                    <span class="font-mono text-sm text-blue-600">${item.serial_number}</span>
                </div>
            </td>
            <td class="px-6 py-4">
                ${item.image_path ? 
                    `<img src="${item.image_path}" class="w-12 h-12 rounded-lg object-cover cursor-pointer" onclick="viewImage('${item.image_path}')">` : 
                    `<div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>`
                }
            </td>
            <td class="px-6 py-4">
                <p class="font-medium text-gray-800">${escapeHtml(item.item_description)}</p>
                ${item.po_number ? `<p class="text-xs text-gray-500">PO: ${escapeHtml(item.po_number)}</p>` : ''}
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">${item.category_name || '-'}</td>
            <td class="px-6 py-4 text-sm">${item.quantity} ${item.quantity_unit}</td>
            <td class="px-6 py-4 font-medium">₹${parseFloat(item.amount || 0).toLocaleString('en-IN')}</td>
            <td class="px-6 py-4 text-sm text-gray-600">
                ${item.department_name || '-'}
                ${item.floor_location ? `<br><span class="text-xs">${item.floor_location}</span>` : ''}
            </td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs font-medium ${getConditionClass(item.condition_status)}">
                    ${item.condition_status}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-2">
                    <button onclick="viewItem(${item.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editItem(${JSON.stringify(item).replace(/"/g, '&quot;')})" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="initiateTransfer(${item.id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="Transfer">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button onclick="deleteItem(${item.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getConditionClass(status) {
    const classes = {
        'new': 'bg-blue-100 text-blue-800',
        'good': 'bg-green-100 text-green-800',
        'fair': 'bg-yellow-100 text-yellow-800',
        'poor': 'bg-orange-100 text-orange-800',
        'non_serviceable': 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function renderPagination(data) {
    document.getElementById('showingFrom').textContent = ((data.page - 1) * 20) + 1;
    document.getElementById('showingTo').textContent = Math.min(data.page * 20, data.total);
    document.getElementById('totalItems').textContent = data.total;
    
    let buttons = '';
    for (let i = 1; i <= data.pages; i++) {
        buttons += `<button onclick="goToPage(${i})" class="px-3 py-1 rounded ${i === data.page ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200'}">${i}</button>`;
    }
    document.getElementById('paginationButtons').innerHTML = buttons;
}

function goToPage(page) {
    currentPage = page;
    loadTable();
}

function sortBy(column) {
    if (sortColumn === column) {
        sortOrder = sortOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        sortColumn = column;
        sortOrder = 'DESC';
    }
    loadTable();
}

function openAddModal() {
    editMode = false;
    document.getElementById('modalTitle').textContent = 'Add New Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('imagePreview').innerHTML = '<i class="fas fa-image text-gray-400 text-2xl"></i>';
    document.getElementById('itemModal').classList.remove('hidden');
}

function editItem(item) {
    editMode = true;
    document.getElementById('modalTitle').textContent = 'Edit Item';
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemDescription').value = item.item_description;
    document.getElementById('detailedDescription').value = item.detailed_description || '';
    document.getElementById('categoryId').value = item.category_id || '';
    document.getElementById('quantity').value = item.quantity;
    document.getElementById('quantityUnit').value = item.quantity_unit;
    document.getElementById('amount').value = item.amount;
    document.getElementById('poNumber').value = item.po_number || '';
    document.getElementById('poDate').value = item.po_date || '';
    document.getElementById('purchaseDate').value = item.purchase_date || '';
    document.getElementById('departmentId').value = item.department_id || '';
    document.getElementById('buildingLocation').value = item.building_location || '';
    document.getElementById('floorLocation').value = item.floor_location || '';
    document.getElementById('roomLocation').value = item.room_location || '';
    document.getElementById('currentHolderId').value = item.current_holder_id || '';
    document.getElementById('conditionStatus').value = item.condition_status;
    document.getElementById('budgetHead').value = item.budget_head || '';
    document.getElementById('stockReference').value = item.stock_reference || '';
    
    if (item.image_path) {
        document.getElementById('imagePreview').innerHTML = `<img src="${item.image_path}" class="w-full h-full object-cover rounded-xl">`;
    }
    
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover rounded-xl">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('itemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('ajax', '1');
    formData.append(editMode ? 'update' : 'add', '1');
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadTable();
            showToast(editMode ? 'Item updated successfully' : 'Item added successfully', 'success');
        } else {
            showToast(data.error || 'An error occurred', 'error');
        }
    });
});

function deleteItem(id) {
    if (!confirm('Are you sure you want to delete this item?')) return;
    
    const formData = new FormData();
    formData.append('ajax', '1');
    formData.append('delete_id', id);
    formData.append('csrf_token', document.querySelector('[name="csrf_token"]').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadTable();
            showToast('Item deleted successfully', 'success');
        } else {
            showToast(data.error || 'Failed to delete', 'error');
        }
    });
}

function printQR(serialNumber) {
    window.open(`../qr/print.php?serial=${serialNumber}`, '_blank', 'width=400,height=500');
}

function viewItem(id) {
    window.location.href = `item-details.php?id=${id}`;
}

function initiateTransfer(id) {
    window.location.href = `../transfers/request.php?item_id=${id}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Search with debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(loadTable, 300);
});

// Load on start
loadTable();
</script>
SCRIPT;

include __PIR__ . '/../../templates/layout.php';
