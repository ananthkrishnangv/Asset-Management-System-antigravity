<?php
/**
 * Admin User Management Page
 * Uses layout template and correct users table
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireRole('admin');

$db = Database::getInstance();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    $action = $_POST['action'] ?? '';

    // Add Single User
    if ($action === 'add_user') {
        $amsId = Security::sanitize($_POST['ams_id'] ?? '');
        $empName = Security::sanitize($_POST['emp_name'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $password = !empty($_POST['password']) ? Security::hashPassword($_POST['password']) : Security::hashPassword('serc@123');
        $role = $_POST['role'] ?? 'employee';
        $deptId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;

        // Check duplicate
        $exists = $db->fetch("SELECT id FROM users WHERE ams_id = ?", [$amsId]);
        if ($exists) {
            flash('error', 'User with AMS ID ' . $amsId . ' already exists.');
        } else {
            $db->insert('users', [
                'ams_id' => $amsId,
                'emp_name' => $empName,
                'email_id' => $email,
                'password' => $password,
                'role' => $role,
                'department_id' => $deptId,
                'is_active' => 1
            ]);
            flash('success', 'User added successfully.');
            ActivityLog::log('create', 'users', null, 'user', "Created user: $amsId");
        }
        redirect(url('public/admin/users.php'));
    }

    // Update User
    if ($action === 'update_user') {
        $id = (int) $_POST['user_id'];
        $updates = [
            'emp_name' => Security::sanitize($_POST['emp_name'] ?? ''),
            'email_id' => Security::sanitize($_POST['email'] ?? ''),
            'role' => $_POST['role'] ?? 'employee',
            'department_id' => !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        if (!empty($_POST['password'])) {
            $updates['password'] = Security::hashPassword($_POST['password']);
        }
        $db->update('users', $updates, 'id = :id', ['id' => $id]);
        flash('success', 'User updated successfully.');
        redirect(url('public/admin/users.php'));
    }

    // Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM users WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' users deleted successfully.');
        }
        redirect(url('public/admin/users.php'));
    }

    // CSV Import
    if ($action === 'import_csv' && !empty($_FILES['csv_file']['tmp_name'])) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $count = 0;
        fgetcsv($file); // Skip header

        while (($row = fgetcsv($file)) !== FALSE) {
            if (count($row) >= 4) {
                $amsId = $row[0];
                if ($db->fetch("SELECT id FROM users WHERE ams_id = ?", [$amsId]))
                    continue;

                $db->insert('users', [
                    'ams_id' => $row[0],
                    'emp_name' => $row[1],
                    'email_id' => $row[2],
                    'password' => Security::hashPassword($row[3] ?? 'serc@123'),
                    'role' => $row[4] ?? 'employee',
                    'department_id' => !empty($row[5]) ? (int) $row[5] : null,
                    'is_active' => 1
                ]);
                $count++;
            }
        }
        fclose($file);
        flash('success', "$count users imported successfully.");
        redirect(url('public/admin/users.php'));
    }
}

// Fetch Users with department names
$users = $db->fetchAll(
    "SELECT u.*, d.name as department_name, d.code as department_code 
     FROM users u 
     LEFT JOIN departments d ON u.department_id = d.id 
     ORDER BY u.id DESC"
);

// Fetch departments for dropdown
$departments = $db->fetchAll("SELECT id, name, code FROM departments ORDER BY name");

$pageTitle = 'User Management';
$pageSubtitle = 'Manage system users and access roles';

ob_start();
?>

<!-- Header Actions -->
<div class="flex flex-wrap gap-3 mb-6">
    <button onclick="document.getElementById('addModal').classList.remove('hidden')"
        class="btn-primary px-5 py-2.5 rounded-xl font-semibold flex items-center gap-2 shadow-lg">
        <i class="fas fa-user-plus"></i> Add User
    </button>
    <button onclick="document.getElementById('importModal').classList.remove('hidden')"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold flex items-center gap-2 shadow-lg transition-colors">
        <i class="fas fa-file-csv"></i> Import CSV
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="text-2xl font-bold text-gray-800"><?= count($users) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Admins</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-shield text-purple-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Supervisors</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?= count(array_filter($users, fn($u) => $u['role'] === 'supervisor')) ?></p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-tie text-amber-600"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Active</p>
                <p class="text-2xl font-bold text-gray-800">
                    <?= count(array_filter($users, fn($u) => $u['is_active'])) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
        <h3 class="font-semibold text-gray-800">All Users</h3>
        <input type="text" id="searchInput" placeholder="Search users..."
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>
    <div class="overflow-x-auto">
        <table class="w-full" id="usersTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">AMS ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                    <?= strtoupper(substr($u['emp_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?= Security::escape($u['emp_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= Security::escape($u['email_id']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm"><?= Security::escape($u['ams_id']) ?></td>
                        <td class="px-4 py-3">
                            <span
                                class="px-2 py-1 text-xs font-semibold rounded-lg 
                            <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' :
                                ($u['role'] === 'supervisor' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-700') ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= Security::escape($u['department_code'] ?? '-') ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($u['is_active']): ?>
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-lg bg-green-100 text-green-700">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-lg bg-red-100 text-red-700">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <button onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
                                    <?= Security::csrfField() ?>
                                    <input type="hidden" name="action" value="bulk_delete">
                                    <input type="hidden" name="selected_ids[]" value="<?= $u['id'] ?>">
                                    <button type="submit"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Add New User</h3>
            <button onclick="document.getElementById('addModal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="add_user">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">AMS ID *</label>
                    <input type="text" name="ams_id" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select name="role"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="employee">Employee</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="emp_name" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <select name="department_id"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Select Department --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= Security::escape($d['code'] . ' - ' . $d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" placeholder="Default: serc@123"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="w-full btn-primary py-3 rounded-xl font-semibold">
                <i class="fas fa-user-plus mr-2"></i> Create User
            </button>
        </form>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="importModal"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Import Users from CSV</h3>
            <button onclick="document.getElementById('importModal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" enctype="multipart/form-data" class="p-6">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="import_csv">

            <div class="text-center mb-6">
                <i class="fas fa-file-csv text-5xl text-emerald-500 mb-4"></i>
                <p class="text-sm text-gray-500">CSV Format: AMS_ID, Name, Email, Password, Role, Department_ID</p>
            </div>

            <input type="file" name="csv_file" accept=".csv" required
                class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl mb-4 cursor-pointer hover:border-blue-500 transition-colors">

            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-xl font-semibold transition-colors">
                <i class="fas fa-upload mr-2"></i> Upload & Import
            </button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-xl font-bold text-gray-800">Edit User</h3>
            <button onclick="document.getElementById('editModal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="emp_name" id="edit_emp_name" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" id="edit_email" required
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="edit_role"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="employee">Employee</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" id="edit_department_id"
                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- None --</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= Security::escape($d['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" name="password" placeholder="Leave blank to keep current"
                    class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="edit_is_active" class="w-4 h-4 text-blue-600 rounded">
                <label for="edit_is_active" class="text-sm text-gray-700">Active User</label>
            </div>

            <button type="submit" class="w-full btn-primary py-3 rounded-xl font-semibold">
                <i class="fas fa-save mr-2"></i> Save Changes
            </button>
        </form>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#usersTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    // Edit user
    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_emp_name').value = user.emp_name;
        document.getElementById('edit_email').value = user.email_id;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_department_id').value = user.department_id || '';
        document.getElementById('edit_is_active').checked = user.is_active == 1;
        document.getElementById('editModal').classList.remove('hidden');
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
