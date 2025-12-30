<?php
/**
 * Stores Returns Management
 * Handle returns for repair, scrapping, non-serviceable items
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    // CREATE RETURN REQUEST
    if (isset($_POST['create_return'])) {
        $itemId = (int) $_POST['item_id'];
        $returnType = in_array($_POST['return_type'], ['repair', 'non_serviceable', 'scrapping', 'obsolete', 'other'])
            ? $_POST['return_type'] : 'other';
        $reason = Security::sanitize($_POST['return_reason'] ?? '');
        $condition = Security::sanitize($_POST['condition_at_return'] ?? '');

        $item = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);
        if (!$item) {
            jsonResponse(['error' => 'Item not found'], 404);
        }

        $returnId = $db->insert('stores_returns', [
            'item_id' => $itemId,
            'returned_by' => Auth::id(),
            'return_type' => $returnType,
            'return_reason' => $reason,
            'condition_at_return' => $condition,
            'status' => 'pending_approval'
        ]);

        // Update item condition
        $db->update('inventory_items', ['condition_status' => 'non_serviceable'], 'id = :id', ['id' => $itemId]);

        ActivityLog::log(
            'create',
            'stores_return',
            $returnId,
            'stores_return',
            'Stores return initiated for: ' . $item['item_description']
        );

        jsonResponse(['success' => true, 'id' => $returnId]);
    }

    // APPROVE/PROCESS RETURN
    if (isset($_POST['process_return'])) {
        Auth::requireSupervisor();

        $returnId = (int) $_POST['return_id'];
        $action = $_POST['action'];
        $comments = Security::sanitize($_POST['comments'] ?? '');

        $return = $db->fetch("SELECT * FROM stores_returns WHERE id = ?", [$returnId]);
        if (!$return) {
            jsonResponse(['error' => 'Return not found'], 404);
        }

        if ($action === 'approve') {
            $db->update('stores_returns', [
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approval_date' => date('Y-m-d H:i:s'),
                'approval_comments' => $comments
            ], 'id = :id', ['id' => $returnId]);

            ActivityLog::log('approve', 'stores_return', $returnId, 'stores_return', 'Approved stores return');
        } elseif ($action === 'receive') {
            $db->update('stores_returns', [
                'status' => 'received',
                'received_by' => Auth::id(),
                'received_date' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $returnId]);

            ActivityLog::log('update', 'stores_return', $returnId, 'stores_return', 'Received item at stores');
        } elseif ($action === 'process') {
            $finalAction = $_POST['final_action'] ?? 'pending';

            $db->update('stores_returns', [
                'status' => 'processed',
                'final_action' => $finalAction,
                'final_action_date' => date('Y-m-d H:i:s'),
                'final_action_notes' => $comments
            ], 'id = :id', ['id' => $returnId]);

            // Update item status
            if ($finalAction === 'scrapped') {
                $db->update(
                    'inventory_items',
                    ['condition_status' => 'scrapped', 'is_active' => 0],
                    'id = :id',
                    ['id' => $return['item_id']]
                );
            } elseif ($finalAction === 'repaired') {
                $db->update(
                    'inventory_items',
                    ['condition_status' => 'good'],
                    'id = :id',
                    ['id' => $return['item_id']]
                );
            }

            ActivityLog::log(
                'update',
                'stores_return',
                $returnId,
                'stores_return',
                'Processed stores return: ' . $finalAction
            );
        } elseif ($action === 'reject') {
            $db->update('stores_returns', [
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approval_comments' => $comments
            ], 'id = :id', ['id' => $returnId]);
        }

        jsonResponse(['success' => true]);
    }

    // FETCH RETURNS
    $status = $_POST['status'] ?? 'all';
    $where = '1=1';
    $params = [];

    if ($status !== 'all') {
        $where .= ' AND sr.status = ?';
        $params[] = $status;
    }

    // Role-based filtering
    if (Auth::isEmployee()) {
        $where .= ' AND sr.returned_by = ?';
        $params[] = Auth::id();
    }

    $returns = $db->fetchAll(
        "SELECT sr.*, i.serial_number, i.item_description, i.image_path,
                u.emp_name as returned_by_name, a.emp_name as approved_by_name
         FROM stores_returns sr
         JOIN inventory_items i ON sr.item_id = i.id
         LEFT JOIN users u ON sr.returned_by = u.id
         LEFT JOIN users a ON sr.approved_by = a.id
         WHERE {$where}
         ORDER BY sr.created_at DESC",
        $params
    );

    jsonResponse(['data' => $returns]);
}

// Check if initiating new return
$itemId = $_GET['item_id'] ?? null;
$item = null;
if ($itemId) {
    $item = $db->fetch(
        "SELECT i.*, u.emp_name as holder_name, d.name as department_name
         FROM inventory_items i
         LEFT JOIN users u ON i.current_holder_id = u.id
         LEFT JOIN departments d ON i.department_id = d.id
         WHERE i.id = ?",
        [$itemId]
    );
}

$pageTitle = $item ? 'Initiate Stores Return' : 'Stores Returns';
$pageSubtitle = 'Manage returns for repair, scrapping, and non-serviceable items';

ob_start();
?>

<?php if ($item): ?>
    <!-- Create Return Form -->
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-warehouse mr-2"></i>
                    Initiate Stores Return
                </h3>
            </div>

            <form id="returnForm" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                <!-- Item Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-4">
                        <?php if ($item['image_path']): ?>
                            <img src="<?= url('uploads/' . $item['image_path']) ?>" class="w-20 h-20 rounded-lg object-cover">
                        <?php endif; ?>
                        <div>
                            <p class="font-bold text-gray-800"><?= Security::escape($item['item_description']) ?></p>
                            <p class="text-sm text-blue-600 font-mono"><?= $item['serial_number'] ?></p>
                            <p class="text-sm text-gray-500 mt-1">Holder:
                                <?= Security::escape($item['holder_name'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Return Type *</label>
                    <select name="return_type" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Type</option>
                        <option value="repair">For Repair</option>
                        <option value="non_serviceable">Non-Serviceable</option>
                        <option value="scrapping">For Scrapping</option>
                        <option value="obsolete">Obsolete</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Reason for Return *</label>
                    <textarea name="return_reason" rows="3" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Describe why this item needs to be returned..."></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Condition at Return</label>
                    <textarea name="condition_at_return" rows="2"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Describe the current condition of the item..."></textarea>
                </div>

                <div class="flex gap-3">
                    <a href="<?= url('public/inventory/dir.php') ?>"
                        class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancel</a>
                    <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Return Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('returnForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('ajax', '1');
            formData.append('create_return', '1');

            fetch('returns.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Return request submitted!', 'success');
                        setTimeout(() => window.location.href = 'returns.php', 1500);
                    } else {
                        showToast(data.error || 'Failed', 'error');
                    }
                });
        });
    </script>

<?php else: ?>
    <!-- Returns List -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <button onclick="filterByStatus('all')" class="status-btn px-4 py-2 rounded-xl bg-gray-100"
                data-status="all">All</button>
            <button onclick="filterByStatus('pending_approval')"
                class="status-btn px-4 py-2 rounded-xl bg-yellow-100 text-yellow-700"
                data-status="pending_approval">Pending</button>
            <button onclick="filterByStatus('approved')" class="status-btn px-4 py-2 rounded-xl bg-blue-100 text-blue-700"
                data-status="approved">Approved</button>
            <button onclick="filterByStatus('received')" class="status-btn px-4 py-2 rounded-xl bg-green-100 text-green-700"
                data-status="received">Received</button>
            <button onclick="filterByStatus('processed')"
                class="status-btn px-4 py-2 rounded-xl bg-purple-100 text-purple-700"
                data-status="processed">Processed</button>
        </div>
    </div>

    <div id="returnsList" class="space-y-4"></div>

    <script>
        let currentStatus = 'all';

        function filterByStatus(status) {
            currentStatus = status;
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-offset-2', 'ring-blue-500');
                if (btn.dataset.status === status) btn.classList.add('ring-2', 'ring-offset-2', 'ring-blue-500');
            });
            loadReturns();
        }

        function loadReturns() {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('status', currentStatus);
            formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => renderReturns(data.data));
        }

        function renderReturns(returns) {
            const container = document.getElementById('returnsList');

            if (!returns.length) {
                container.innerHTML = '<div class="bg-white rounded-2xl p-12 text-center card-shadow"><i class="fas fa-warehouse text-gray-300 text-5xl mb-4"></i><p class="text-gray-500">No returns found</p></div>';
                return;
            }

            const typeColors = { repair: 'bg-blue-100 text-blue-800', non_serviceable: 'bg-red-100 text-red-800', scrapping: 'bg-gray-100 text-gray-800', obsolete: 'bg-orange-100 text-orange-800', other: 'bg-gray-100 text-gray-800' };

            container.innerHTML = returns.map(r => `
        <div class="bg-white rounded-2xl p-6 card-shadow">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    ${r.image_path ? `<img src="../uploads/${r.image_path}" class="w-16 h-16 rounded-xl object-cover">` : '<div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center"><i class="fas fa-box text-gray-400"></i></div>'}
                    <div>
                        <p class="font-bold text-gray-800">${escapeHtml(r.item_description)}</p>
                        <p class="text-sm text-blue-600 font-mono">${r.serial_number}</p>
                        <p class="text-sm text-gray-500 mt-1">Returned by: ${escapeHtml(r.returned_by_name)}</p>
                        <span class="inline-block mt-2 px-2 py-1 rounded-full text-xs font-medium ${typeColors[r.return_type] || ''}">${r.return_type}</span>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(r.status)}">${formatStatus(r.status)}</span>
                    ${getActionButtons(r)}
                </div>
            </div>
            ${r.return_reason ? `<p class="mt-4 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">${escapeHtml(r.return_reason)}</p>` : ''}
        </div>
    `).join('');
        }

        function getStatusClass(s) {
            return { pending_approval: 'bg-yellow-100 text-yellow-800', approved: 'bg-blue-100 text-blue-800', received: 'bg-green-100 text-green-800', processed: 'bg-purple-100 text-purple-800', rejected: 'bg-red-100 text-red-800' }[s] || '';
        }

        function formatStatus(s) {
            return { pending_approval: 'Pending', approved: 'Approved', received: 'Received at Stores', processed: 'Processed', rejected: 'Rejected' }[s] || s;
        }

        function getActionButtons(r) {
            const isSupervisor = <?= Auth::isSupervisor() ? 'true' : 'false' ?>;
            if (!isSupervisor) return '';

            if (r.status === 'pending_approval') {
                return `<div class="mt-3 flex gap-2"><button onclick="processReturn(${r.id}, 'approve')" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm">Approve</button><button onclick="processReturn(${r.id}, 'reject')" class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm">Reject</button></div>`;
            }
            if (r.status === 'approved') {
                return `<div class="mt-3"><button onclick="processReturn(${r.id}, 'receive')" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm">Mark Received</button></div>`;
            }
            if (r.status === 'received') {
                return `<div class="mt-3 flex gap-2"><button onclick="finalProcess(${r.id}, 'repaired')" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm">Repaired</button><button onclick="finalProcess(${r.id}, 'scrapped')" class="px-3 py-1 bg-gray-600 text-white rounded-lg text-sm">Scrapped</button></div>`;
            }
            return '';
        }

        function processReturn(id, action) {
            const comments = prompt('Comments (optional):');

            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('process_return', '1');
            formData.append('return_id', id);
            formData.append('action', action);
            formData.append('comments', comments || '');
            formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { showToast('Action completed', 'success'); loadReturns(); }
                    else showToast(data.error, 'error');
                });
        }

        function finalProcess(id, action) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('process_return', '1');
            formData.append('return_id', id);
            formData.append('action', 'process');
            formData.append('final_action', action);
            formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

            fetch(window.location.href, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { showToast('Item ' + action, 'success'); loadReturns(); }
                });
        }

        function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }

        filterByStatus('all');
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
