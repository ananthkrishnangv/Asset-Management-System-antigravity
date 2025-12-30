<?php
/**
 * User Management Page (Admin Only)
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    // ADD USER
    if (isset($_POST['add'])) {
        $amsId = Security::sanitize($_POST['ams_id']);
        $empName = Security::sanitize($_POST['emp_name']);
        $email = Security::sanitize($_POST['email_id']);
        $role = in_array($_POST['role'], ['admin', 'supervisor', 'employee']) ? $_POST['role'] : 'employee';
        $deptId = (int) $_POST['department_id'] ?: null;
        $hodId = (int) $_POST['hod_id'] ?: null;
        $supervisorId = (int) $_POST['supervisor_id'] ?: null;

        // Check for duplicate
        $exists = $db->fetchValue("SELECT COUNT(*) FROM users WHERE ams_id = ? OR email_id = ?", [$amsId, $email]);
        if ($exists) {
            jsonResponse(['error' => 'AMS ID or Email already exists'], 400);
        }

        // Default password
        $password = Security::hashPassword('Welcome@123');

        $userId = $db->insert('users', [
            'ams_id' => $amsId,
            'emp_name' => $empName,
            'email_id' => $email,
            'password' => $password,
            'role' => $role,
            'department_id' => $deptId,
            'hod_id' => $hodId,
            'supervisor_id' => $supervisorId,
            'phone' => Security::sanitize($_POST['phone'] ?? ''),
            'designation' => Security::sanitize($_POST['designation'] ?? '')
        ]);

        ActivityLog::log('create', 'users', $userId, 'user', 'Created user: ' . $empName);

        jsonResponse(['success' => true, 'id' => $userId, 'message' => 'User created. Default password: Welcome@123']);
    }

    // UPDATE USER
    if (isset($_POST['update'])) {
        $userId = (int) $_POST['user_id'];

        $data = [
            'emp_name' => Security::sanitize($_POST['emp_name']),
            'email_id' => Security::sanitize($_POST['email_id']),
            'role' => in_array($_POST['role'], ['admin', 'supervisor', 'employee']) ? $_POST['role'] : 'employee',
            'department_id' => (int) $_POST['department_id'] ?: null,
            'hod_id' => (int) $_POST['hod_id'] ?: null,
            'supervisor_id' => (int) $_POST['supervisor_id'] ?: null,
            'phone' => Security::sanitize($_POST['phone'] ?? ''),
            'designation' => Security::sanitize($_POST['designation'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Reset password if requested
        if (!empty($_POST['reset_password'])) {
            $data['password'] = Security::hashPassword('Welcome@123');
        }

        $db->update('users', $data, 'id = :id', ['id' => $userId]);

        ActivityLog::log('update', 'users', $userId, 'user', 'Updated user: ' . $data['emp_name']);

        jsonResponse(['success' => true]);
    }

    // DELETE USER
    if (isset($_POST['delete_id'])) {
        $userId = (int) $_POST['delete_id'];

        if ($userId == Auth::id()) {
            jsonResponse(['error' => 'Cannot delete yourself'], 400);
        }

        $user = $db->fetch("SELECT emp_name FROM users WHERE id = ?", [$userId]);
        $db->update('users', ['is_active' => 0], 'id = :id', ['id' => $userId]);

        ActivityLog::log('delete', 'users', $userId, 'user', 'Deactivated user: ' . $user['emp_name']);

        jsonResponse(['success' => true]);
    }

    // FETCH USERS
    $search = '%' . Security::sanitize($_POST['search'] ?? '') . '%';

    $users = $db->fetchAll(
        "SELECT u.*, d.name as department_name, h.emp_name as hod_name, s.emp_name as supervisor_name
         FROM users u
         LEFT JOIN departments d ON u.department_id = d.id
         LEFT JOIN users h ON u.hod_id = h.id
         LEFT JOIN users s ON u.supervisor_id = s.id
         WHERE (u.ams_id LIKE ? OR u.emp_name LIKE ? OR u.email_id LIKE ?)
         ORDER BY u.emp_name",
        [$search, $search, $search]
    );

    jsonResponse(['data' => $users]);
}

$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name");
$allUsers = $db->fetchAll("SELECT id, ams_id, emp_name FROM users WHERE is_active = 1 ORDER BY emp_name");

$pageTitle = 'User Management';
$pageSubtitle = 'Manage users, roles, and HoD mappings';

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div class="relative">
        <input type="text" id="searchInput" placeholder="Search users..."
            class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl w-80 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>

    <button onclick="openAddModal()"
        class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
        <i class="fas fa-user-plus"></i>
        <span>Add User</span>
    </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 card-shadow">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-red-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">
                    <?= $db->fetchValue("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1") ?></p>
                <p class="text-sm text-gray-500">Admins</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 card-shadow">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-tie text-blue-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">
                    <?= $db->fetchValue("SELECT COUNT(*) FROM users WHERE role='supervisor' AND is_active=1") ?></p>
                <p class="text-sm text-gray-500">Supervisors</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 card-shadow">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-green-600"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800">
                    <?= $db->fetchValue("SELECT COUNT(*) FROM users WHERE role='employee' AND is_active=1") ?></p>
                <p class="text-sm text-gray-500">Employees</p>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl card-shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gradient-to-r from-slate-800 to-slate-900 text-white">
            <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold">AMS ID</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Name</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Role</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Department</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">HoD</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100"></tbody>
    </table>
</div>

<!-- User Modal -->
<div id="userModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0 bg-black/50" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 rounded-t-2xl">
                <h3 id="modalTitle" class="text-xl font-bold text-white">Add User</h3>
            </div>

            <form id="userForm" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="user_id" id="userId">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">AMS ID *</label>
                        <input type="text" name="ams_id" id="amsId" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Employee Name *</label>
                        <input type="text" name="emp_name" id="empName" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email_id" id="emailId" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Role *</label>
                        <select name="role" id="role" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="employee">Employee</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
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
                        <label class="block text-sm font-semibold text-gray-700 mb-2">HoD (Head of Department)</label>
                        <select name="hod_id" id="hodId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select HoD</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= Security::escape($u['emp_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Supervisor</label>
                        <select name="supervisor_id" id="supervisorId"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Supervisor</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= Security::escape($u['emp_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                        <input type="text" name="phone" id="phone"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Designation</label>
                        <input type="text" name="designation" id="designation"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div id="activeField" class="hidden">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="isActive" checked class="w-4 h-4 rounded">
                            <span class="text-sm font-medium text-gray-700">Active User</span>
                        </label>
                    </div>
                    <div id="resetPwdField" class="hidden">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="reset_password" id="resetPassword" class="w-4 h-4 rounded">
                            <span class="text-sm font-medium text-gray-700">Reset Password to Default</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let editMode = false;

    function loadTable() {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('search', document.getElementById('searchInput').value);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => renderTable(data.data));
    }

    function renderTable(users) {
        document.getElementById('tableBody').innerHTML = users.map(u => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm">${u.ams_id}</td>
            <td class="px-6 py-4 font-medium">${escapeHtml(u.emp_name)}</td>
            <td class="px-6 py-4 text-sm text-gray-600">${u.email_id}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-full text-xs font-medium ${getRoleClass(u.role)}">${u.role}</span>
            </td>
            <td class="px-6 py-4 text-sm">${u.department_name || '-'}</td>
            <td class="px-6 py-4 text-sm">${u.hod_name || '-'}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-full text-xs ${u.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                    ${u.is_active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="px-6 py-4 text-center">
                <button onclick='editUser(${JSON.stringify(u).replace(/'/g, "\\'")})' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser(${u.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    }

    function getRoleClass(role) {
        return { admin: 'bg-red-100 text-red-800', supervisor: 'bg-blue-100 text-blue-800', employee: 'bg-green-100 text-green-800' }[role];
    }

    function openAddModal() {
        editMode = false;
        document.getElementById('modalTitle').textContent = 'Add User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('amsId').disabled = false;
        document.getElementById('activeField').classList.add('hidden');
        document.getElementById('resetPwdField').classList.add('hidden');
        document.getElementById('userModal').classList.remove('hidden');
    }

    function editUser(user) {
        editMode = true;
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('amsId').value = user.ams_id;
        document.getElementById('amsId').disabled = true;
        document.getElementById('empName').value = user.emp_name;
        document.getElementById('emailId').value = user.email_id;
        document.getElementById('role').value = user.role;
        document.getElementById('departmentId').value = user.department_id || '';
        document.getElementById('hodId').value = user.hod_id || '';
        document.getElementById('supervisorId').value = user.supervisor_id || '';
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('designation').value = user.designation || '';
        document.getElementById('isActive').checked = user.is_active == 1;
        document.getElementById('activeField').classList.remove('hidden');
        document.getElementById('resetPwdField').classList.remove('hidden');
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    document.getElementById('userForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('ajax', '1');
        formData.append(editMode ? 'update' : 'add', '1');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadTable();
                    showToast(data.message || 'User saved successfully', 'success');
                } else {
                    showToast(data.error || 'Error saving user', 'error');
                }
            });
    });

    function deleteUser(id) {
        if (!confirm('Deactivate this user?')) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('delete_id', id);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) { loadTable(); showToast('User deactivated', 'success'); }
                else { showToast(data.error, 'error'); }
            });
    }

    function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    document.getElementById('searchInput').addEventListener('input', () => setTimeout(loadTable, 300));
    loadTable();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
