<?php
/**
 * Transfer Request Handler
 * Workflow: Employee → HoD Approval → Supervisor Final Approval → Update DIR/PIR → Notify
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    // CREATE TRANSFER REQUEST
    if (isset($_POST['create_request'])) {
        $itemId = (int) $_POST['item_id'];
        $toUserId = (int) $_POST['to_user_id'];
        $reason = Security::sanitize($_POST['transfer_reason'] ?? '');

        // Get item details
        $item = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$itemId]);
        if (!$item) {
            jsonResponse(['error' => 'Item not found'], 404);
        }

        // Get from user (current holder)
        $fromUserId = $item['current_holder_id'] ?: Auth::id();

        // Get to user details
        $toUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$toUserId]);
        if (!$toUser) {
            jsonResponse(['error' => 'Recipient user not found'], 404);
        }

        // Get HoD of the initiating employee
        $initiator = $db->fetch("SELECT * FROM users WHERE id = ?", [Auth::id()]);
        $hodId = $initiator['hod_id'];

        // Get a supervisor for final approval
        $supervisor = $db->fetch("SELECT id FROM users WHERE role = 'supervisor' AND is_active = 1 LIMIT 1");
        $supervisorId = $supervisor['id'] ?? null;

        // Create transfer request - first goes to HoD
        $requestId = $db->insert('transfer_requests', [
            'item_id' => $itemId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'from_department_id' => $item['department_id'],
            'to_department_id' => $toUser['department_id'],
            'transfer_reason' => $reason,
            'status' => 'pending_hod', // First step: HoD approval
            'hod_id' => $hodId,
            'supervisor_id' => $supervisorId,
            'requested_by' => Auth::id(),
            'transfer_slip_number' => SerialNumber::generateTransferSlip()
        ]);

        // Log activity
        ActivityLog::log(
            'create',
            'transfer',
            $requestId,
            'transfer_request',
            'Transfer request created for: ' . $item['item_description']
        );

        // Notify HoD
        if ($hodId) {
            Mailer::sendTransferNotification(
                $hodId,
                $item['item_description'],
                Auth::user()['emp_name'],
                'pending_hod'
            );
        }

        jsonResponse(['success' => true, 'request_id' => $requestId]);
    }

    // HoD APPROVAL
    if (isset($_POST['hod_approve'])) {
        $requestId = (int) $_POST['request_id'];
        $action = $_POST['action']; // 'approve' or 'reject'
        $comments = Security::sanitize($_POST['comments'] ?? '');

        $request = $db->fetch("SELECT * FROM transfer_requests WHERE id = ?", [$requestId]);
        if (!$request || $request['status'] !== 'pending_hod') {
            jsonResponse(['error' => 'Invalid request or already processed'], 400);
        }

        // Verify current user is the HoD
        if ($request['hod_id'] != Auth::id() && !Auth::isAdmin()) {
            jsonResponse(['error' => 'Not authorized'], 403);
        }

        if ($action === 'approve') {
            // Move to supervisor approval
            $db->update('transfer_requests', [
                'status' => 'pending_supervisor',
                'hod_action' => 'approved',
                'hod_comments' => $comments,
                'hod_action_date' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $requestId]);

            // Notify supervisor
            if ($request['supervisor_id']) {
                $item = $db->fetch("SELECT item_description FROM inventory_items WHERE id = ?", [$request['item_id']]);
                Mailer::sendTransferNotification(
                    $request['supervisor_id'],
                    $item['item_description'],
                    'HoD Approved',
                    'pending_supervisor'
                );
            }

            ActivityLog::log(
                'approve',
                'transfer',
                $requestId,
                'transfer_request',
                'HoD approved transfer request'
            );
        } else {
            // Reject
            $db->update('transfer_requests', [
                'status' => 'rejected',
                'hod_action' => 'rejected',
                'hod_comments' => $comments,
                'hod_action_date' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $requestId]);

            // Notify requester
            Mailer::sendNotification(
                $request['requested_by'],
                'transfer_rejected',
                'Transfer Request Rejected',
                'Your transfer request has been rejected by HoD. Reason: ' . $comments
            );

            ActivityLog::log(
                'reject',
                'transfer',
                $requestId,
                'transfer_request',
                'HoD rejected transfer request'
            );
        }

        jsonResponse(['success' => true]);
    }

    // SUPERVISOR FINAL APPROVAL
    if (isset($_POST['supervisor_approve'])) {
        Auth::requireSupervisor();

        $requestId = (int) $_POST['request_id'];
        $action = $_POST['action'];
        $comments = Security::sanitize($_POST['comments'] ?? '');

        $request = $db->fetch("SELECT * FROM transfer_requests WHERE id = ?", [$requestId]);
        if (!$request || $request['status'] !== 'pending_supervisor') {
            jsonResponse(['error' => 'Invalid request or already processed'], 400);
        }

        if ($action === 'approve') {
            $db->beginTransaction();

            try {
                // Update transfer request
                $db->update('transfer_requests', [
                    'status' => 'completed',
                    'supervisor_action' => 'approved',
                    'supervisor_comments' => $comments,
                    'supervisor_action_date' => date('Y-m-d H:i:s'),
                    'completed_at' => date('Y-m-d H:i:s')
                ], 'id = :id', ['id' => $requestId]);

                // Update inventory item ownership
                $db->update('inventory_items', [
                    'current_holder_id' => $request['to_user_id'],
                    'department_id' => $request['to_department_id']
                ], 'id = :id', ['id' => $request['item_id']]);

                // Get user names for history
                $fromUser = $db->fetch("SELECT emp_name FROM users WHERE id = ?", [$request['from_user_id']]);
                $toUser = $db->fetch("SELECT * FROM users WHERE id = ?", [$request['to_user_id']]);
                $fromDept = $db->fetch("SELECT name FROM departments WHERE id = ?", [$request['from_department_id']]);
                $toDept = $db->fetch("SELECT name FROM departments WHERE id = ?", [$request['to_department_id']]);

                // Record in transfer history
                $db->insert('transfer_history', [
                    'item_id' => $request['item_id'],
                    'transfer_request_id' => $requestId,
                    'from_user_id' => $request['from_user_id'],
                    'to_user_id' => $request['to_user_id'],
                    'from_department_id' => $request['from_department_id'],
                    'to_department_id' => $request['to_department_id'],
                    'from_user_name' => $fromUser['emp_name'] ?? 'Unknown',
                    'to_user_name' => $toUser['emp_name'] ?? 'Unknown',
                    'from_department_name' => $fromDept['name'] ?? 'Unknown',
                    'to_department_name' => $toDept['name'] ?? 'Unknown',
                    'transfer_type' => $request['from_department_id'] != $request['to_department_id']
                        ? 'inter_department' : 'internal',
                    'transfer_slip_number' => $request['transfer_slip_number'],
                    'remarks' => $comments
                ]);

                $db->commit();

                // Get item details for notifications
                $item = $db->fetch("SELECT * FROM inventory_items WHERE id = ?", [$request['item_id']]);

                // Notify new holder
                Mailer::sendNotification(
                    $request['to_user_id'],
                    'transfer_completed',
                    'Item Transferred to You',
                    "The item '{$item['item_description']}' (Serial: {$item['serial_number']}) has been transferred to you."
                );

                // Notify HoD of new holder
                if ($toUser['hod_id']) {
                    Mailer::sendNotification(
                        $toUser['hod_id'],
                        'transfer_notification',
                        'Incoming Transfer to Your Team',
                        "Item '{$item['item_description']}' has been transferred to {$toUser['emp_name']} in your department."
                    );
                }

                // Notify original requester
                Mailer::sendNotification(
                    $request['requested_by'],
                    'transfer_completed',
                    'Transfer Completed',
                    'Your transfer request has been approved and completed.'
                );

                ActivityLog::log(
                    'approve',
                    'transfer',
                    $requestId,
                    'transfer_request',
                    'Supervisor approved and completed transfer'
                );

                jsonResponse(['success' => true]);

            } catch (Exception $e) {
                $db->rollback();
                jsonResponse(['error' => $e->getMessage()], 500);
            }
        } else {
            // Reject
            $db->update('transfer_requests', [
                'status' => 'rejected',
                'supervisor_action' => 'rejected',
                'supervisor_comments' => $comments,
                'supervisor_action_date' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $requestId]);

            Mailer::sendNotification(
                $request['requested_by'],
                'transfer_rejected',
                'Transfer Request Rejected',
                'Your transfer request was rejected by Supervisor. Reason: ' . $comments
            );

            ActivityLog::log(
                'reject',
                'transfer',
                $requestId,
                'transfer_request',
                'Supervisor rejected transfer request'
            );

            jsonResponse(['success' => true]);
        }
    }

    // FETCH REQUESTS
    $status = $_POST['status'] ?? 'all';
    $page = max(1, (int) ($_POST['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $where = '1=1';
    $params = [];

    // Role-based filtering
    if (Auth::isEmployee()) {
        $where .= ' AND tr.requested_by = ?';
        $params[] = Auth::id();
    } elseif (!Auth::isAdmin()) {
        // Supervisor sees requests pending their approval or their own
        $where .= ' AND (tr.supervisor_id = ? OR tr.requested_by = ? OR tr.hod_id = ?)';
        $params[] = Auth::id();
        $params[] = Auth::id();
        $params[] = Auth::id();
    }

    if ($status !== 'all') {
        $where .= ' AND tr.status = ?';
        $params[] = $status;
    }

    $sql = "SELECT tr.*, 
                   i.serial_number, i.item_description, i.image_path,
                   fu.emp_name as from_user_name, tu.emp_name as to_user_name,
                   fd.name as from_dept_name, td.name as to_dept_name,
                   ru.emp_name as requester_name
            FROM transfer_requests tr
            JOIN inventory_items i ON tr.item_id = i.id
            LEFT JOIN users fu ON tr.from_user_id = fu.id
            LEFT JOIN users tu ON tr.to_user_id = tu.id
            LEFT JOIN departments fd ON tr.from_department_id = fd.id
            LEFT JOIN departments td ON tr.to_department_id = td.id
            LEFT JOIN users ru ON tr.requested_by = ru.id
            WHERE {$where}
            ORDER BY tr.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";

    $requests = $db->fetchAll($sql, $params);

    jsonResponse(['data' => $requests]);
}

// Check if creating new request
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

$users = $db->fetchAll("SELECT id, ams_id, emp_name, department_id FROM users WHERE is_active = 1 ORDER BY emp_name");
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");

$pageTitle = $item ? 'Create Transfer Request' : 'Transfer Management';
$pageSubtitle = 'Handle asset transfers between departments and employees';

ob_start();
?>

<?php if ($item): ?>
    <!-- Create Transfer Request Form -->
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-2xl card-shadow overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                <h3 class="text-xl font-bold text-white">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Create Transfer Request
                </h3>
            </div>

            <form id="transferForm" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                <!-- Item Info -->
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3">Item Details</h4>
                    <div class="flex items-start gap-4">
                        <?php if ($item['image_path']): ?>
                            <img src="<?= url('uploads/' . $item['image_path']) ?>" class="w-20 h-20 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                <i class="fas fa-box text-gray-400 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-bold text-gray-800"><?= Security::escape($item['item_description']) ?></p>
                            <p class="text-sm text-blue-600 font-mono"><?= $item['serial_number'] ?></p>
                            <p class="text-sm text-gray-500 mt-1">
                                Current Holder: <?= Security::escape($item['holder_name'] ?? 'Not assigned') ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                Department: <?= Security::escape($item['department_name'] ?? 'Not assigned') ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Transfer To -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user text-green-600 mr-1"></i> Transfer To *
                    </label>
                    <select name="to_user_id" id="toUserId" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="">Select Recipient</option>
                        <?php foreach ($users as $u): ?>
                            <?php if ($u['id'] != $item['current_holder_id']): ?>
                                <option value="<?= $u['id'] ?>"><?= Security::escape($u['emp_name']) ?> (<?= $u['ams_id'] ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reason -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-comment text-gray-400 mr-1"></i> Reason for Transfer
                    </label>
                    <textarea name="transfer_reason" rows="4"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Explain why this item needs to be transferred..."></textarea>
                </div>

                <!-- Workflow Info -->
                <div class="bg-blue-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-1"></i> Approval Workflow
                    </h4>
                    <div class="flex items-center gap-2 text-sm text-blue-700">
                        <span class="bg-blue-200 px-2 py-1 rounded">1. You</span>
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span class="bg-blue-200 px-2 py-1 rounded">2. HoD Approval</span>
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span class="bg-blue-200 px-2 py-1 rounded">3. Supervisor Approval</span>
                        <i class="fas fa-arrow-right text-blue-400"></i>
                        <span class="bg-green-200 text-green-800 px-2 py-1 rounded">4. Completed</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <a href="<?= url('public/inventory/dir.php') ?>"
                        class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Transfer Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('transferForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('ajax', '1');
            formData.append('create_request', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Transfer request submitted successfully!', 'success');
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1500);
                    } else {
                        showToast(data.error || 'Failed to submit request', 'error');
                    }
                });
        });
    </script>

<?php else: ?>
    <!-- Transfer List View -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <button onclick="filterByStatus('all')"
                class="status-btn px-4 py-2 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200" data-status="all">
                All Requests
            </button>
            <button onclick="filterByStatus('pending_hod')"
                class="status-btn px-4 py-2 rounded-xl bg-orange-100 text-orange-700 hover:bg-orange-200"
                data-status="pending_hod">
                Pending HoD
            </button>
            <button onclick="filterByStatus('pending_supervisor')"
                class="status-btn px-4 py-2 rounded-xl bg-blue-100 text-blue-700 hover:bg-blue-200"
                data-status="pending_supervisor">
                Pending Supervisor
            </button>
            <button onclick="filterByStatus('completed')"
                class="status-btn px-4 py-2 rounded-xl bg-green-100 text-green-700 hover:bg-green-200"
                data-status="completed">
                Completed
            </button>
            <button onclick="filterByStatus('rejected')"
                class="status-btn px-4 py-2 rounded-xl bg-red-100 text-red-700 hover:bg-red-200" data-status="rejected">
                Rejected
            </button>
        </div>
    </div>

    <div id="requestsList" class="space-y-4">
        <!-- Loaded via AJAX -->
    </div>

    <script>
        let currentStatus = 'all';

        function filterByStatus(status) {
            currentStatus = status;
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-offset-2');
                if (btn.dataset.status === status) {
                    btn.classList.add('ring-2', 'ring-offset-2', 'ring-blue-500');
                }
            });
            loadRequests();
        }

        function loadRequests() {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('status', currentStatus);
            formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    renderRequests(data.data);
                });
        }

        function renderRequests(requests) {
            const container = document.getElementById('requestsList');

            if (requests.length === 0) {
                container.innerHTML = `
            <div class="bg-white rounded-2xl p-12 text-center card-shadow">
                <i class="fas fa-exchange-alt text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">No transfer requests found</p>
            </div>`;
                return;
            }

            container.innerHTML = requests.map(req => `
        <div class="bg-white rounded-2xl p-6 card-shadow">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    ${req.image_path ?
                `<img src="../uploads/${req.image_path}" class="w-16 h-16 rounded-xl object-cover">` :
                `<div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center"><i class="fas fa-box text-gray-400"></i></div>`
            }
                    <div>
                        <p class="font-bold text-gray-800">${escapeHtml(req.item_description)}</p>
                        <p class="text-sm text-blue-600 font-mono">${req.serial_number}</p>
                        <p class="text-sm text-gray-500 mt-2">
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-user"></i> ${escapeHtml(req.from_user_name || 'N/A')}
                            </span>
                            <i class="fas fa-arrow-right mx-2 text-green-500"></i>
                            <span class="inline-flex items-center gap-1">
                                <i class="fas fa-user"></i> ${escapeHtml(req.to_user_name || 'N/A')}
                            </span>
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Requested by ${escapeHtml(req.requester_name)} on ${new Date(req.created_at).toLocaleDateString()}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${getStatusClass(req.status)}">
                        ${formatStatus(req.status)}
                    </span>
                    ${getActionButtons(req)}
                </div>
            </div>
            ${req.transfer_reason ? `<p class="mt-4 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">${escapeHtml(req.transfer_reason)}</p>` : ''}
        </div>
    `).join('');
        }

        function getStatusClass(status) {
            const classes = {
                'pending_hod': 'bg-orange-100 text-orange-800',
                'pending_supervisor': 'bg-blue-100 text-blue-800',
                'approved': 'bg-green-100 text-green-800',
                'completed': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        }

        function formatStatus(status) {
            const names = {
                'pending_hod': 'Awaiting HoD',
                'pending_supervisor': 'Awaiting Supervisor',
                'approved': 'Approved',
                'completed': 'Completed',
                'rejected': 'Rejected'
            };
            return names[status] || status;
        }

        function getActionButtons(req) {
            const userId = <?= Auth::id() ?>;
            const isAdmin = <?= Auth::isAdmin() ? 'true' : 'false' ?>;
            const isSupervisor = <?= Auth::isSupervisor() ? 'true' : 'false' ?>;

            if (req.status === 'pending_hod' && (req.hod_id == userId || isAdmin)) {
                return `
            <div class="mt-3 flex gap-2">
                <button onclick="approveHoD(${req.id})" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                    <i class="fas fa-check mr-1"></i> Approve
                </button>
                <button onclick="rejectHoD(${req.id})" class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                    <i class="fas fa-times mr-1"></i> Reject
                </button>
            </div>`;
            }

            if (req.status === 'pending_supervisor' && isSupervisor) {
                return `
            <div class="mt-3 flex gap-2">
                <button onclick="approveSupervisor(${req.id})" class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                    <i class="fas fa-check mr-1"></i> Final Approve
                </button>
                <button onclick="rejectSupervisor(${req.id})" class="px-3 py-1 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                    <i class="fas fa-times mr-1"></i> Reject
                </button>
            </div>`;
            }

            return '';
        }

        function approveHoD(id) {
            const comments = prompt('Add approval comments (optional):');
            submitAction('hod_approve', id, 'approve', comments);
        }

        function rejectHoD(id) {
            const comments = prompt('Reason for rejection:');
            if (comments) {
                submitAction('hod_approve', id, 'reject', comments);
            }
        }

        function approveSupervisor(id) {
            const comments = prompt('Add approval comments (optional):');
            submitAction('supervisor_approve', id, 'approve', comments);
        }

        function rejectSupervisor(id) {
            const comments = prompt('Reason for rejection:');
            if (comments) {
                submitAction('supervisor_approve', id, 'reject', comments);
            }
        }

        function submitAction(actionType, requestId, action, comments) {
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append(actionType, '1');
            formData.append('request_id', requestId);
            formData.append('action', action);
            formData.append('comments', comments || '');
            formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('Action completed successfully', 'success');
                        loadRequests();
                    } else {
                        showToast(data.error || 'Action failed', 'error');
                    }
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        // Initial load
        filterByStatus('all');
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
