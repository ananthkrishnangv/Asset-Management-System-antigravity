<?php
/**
 * Department Management Page (Admin Only)
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

    // ADD DEPARTMENT
    if (isset($_POST['add'])) {
        $name = Security::sanitize($_POST['name']);
        $code = Security::sanitize($_POST['code']);

        if (empty($name) || empty($code)) {
            jsonResponse(['error' => 'Name and Code are required'], 400);
        }

        // Check duplicate
        $exists = $db->fetchValue("SELECT COUNT(*) FROM departments WHERE code = ?", [$code]);
        if ($exists) {
            jsonResponse(['error' => 'Department code already exists'], 400);
        }

        $id = $db->insert('departments', [
            'name' => $name,
            'code' => strtoupper($code)
        ]);

        ActivityLog::log('create', 'departments', $id, 'department', "Created department: $name");

        jsonResponse(['success' => true, 'id' => $id]);
    }

    // UPDATE DEPARTMENT
    if (isset($_POST['update'])) {
        $id = (int) $_POST['id'];
        $name = Security::sanitize($_POST['name']);
        $code = Security::sanitize($_POST['code']);

        $db->update('departments', [
            'name' => $name,
            'code' => strtoupper($code)
        ], 'id = :id', ['id' => $id]);

        ActivityLog::log('update', 'departments', $id, 'department', "Updated department: $name");

        jsonResponse(['success' => true]);
    }

    // DELETE DEPARTMENT
    if (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];

        // Check usage
        $usage = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE department_id = ?", [$id]);
        if ($usage > 0) {
            jsonResponse(['error' => 'Cannot delete: Department has associated items'], 400);
        }

        $userUsage = $db->fetchValue("SELECT COUNT(*) FROM users WHERE department_id = ?", [$id]);
        if ($userUsage > 0) {
            jsonResponse(['error' => 'Cannot delete: Department has associated users'], 400);
        }

        $dept = $db->fetch("SELECT name FROM departments WHERE id = ?", [$id]);
        $db->query("DELETE FROM departments WHERE id = ?", [$id]);

        ActivityLog::log('delete', 'departments', $id, 'department', "Deleted department: " . $dept['name']);

        jsonResponse(['success' => true]);
    }

    // FETCH DEPARTMENTS
    $search = '%' . Security::sanitize($_POST['search'] ?? '') . '%';
    $depts = $db->fetchAll(
        "SELECT * FROM departments 
         WHERE name LIKE ? OR code LIKE ? 
         ORDER BY name", 
        [$search, $search]
    );

    jsonResponse(['data' => $depts]);
}

$pageTitle = 'Department Management';
$pageSubtitle = 'Manage organization departments';

ob_start();
?>

<div class="flex items-center justify-between mb-6">
    <div class="relative">
        <input type="text" id="searchInput" placeholder="Search departments..."
            class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl w-80 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>

    <button onclick="openAddModal()"
        class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
        <i class="fas fa-plus"></i>
        <span>Add Department</span>
    </button>
</div>

<!-- Departments Table -->
<div class="bg-white rounded-2xl card-shadow overflow-hidden">
    <table class="w-full">
        <thead class="bg-gradient-to-r from-slate-800 to-slate-900 text-white">
            <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold">Code</th>
                <th class="px-6 py-4 text-left text-sm font-semibold">Department Name</th>
                <th class="px-6 py-4 text-center text-sm font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody id="tableBody" class="divide-y divide-gray-100"></tbody>
    </table>
</div>

<!-- Modal -->
<div id="deptModal" class="fixed inset-0 z-50 hidden">
    <div class="modal-backdrop absolute inset-0 bg-black/50" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-6 py-4 rounded-t-2xl">
                <h3 id="modalTitle" class="text-xl font-bold text-white">Add Department</h3>
            </div>

            <form id="deptForm" class="p-6">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="id" id="deptId">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Department Name *</label>
                    <input type="text" name="name" id="deptName" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Department Code *</label>
                    <input type="text" name="code" id="deptCode" required uppercase
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-3 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200">Cancel</button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                        <i class="fas fa-save mr-2"></i>Save
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

    function renderTable(depts) {
        if (depts.length === 0) {
            document.getElementById('tableBody').innerHTML = `<tr><td colspan="3" class="text-center py-6 text-gray-500">No departments found</td></tr>`;
            return;
        }

        document.getElementById('tableBody').innerHTML = depts.map(d => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 font-mono text-sm font-medium text-blue-600">${escapeHtml(d.code)}</td>
            <td class="px-6 py-4 font-medium text-gray-800">${escapeHtml(d.name)}</td>
            <td class="px-6 py-4 text-center">
                <button onclick='editDept(${JSON.stringify(d).replace(/'/g, "\\'")})' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteDept(${d.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    }

    function openAddModal() {
        editMode = false;
        document.getElementById('modalTitle').textContent = 'Add Department';
        document.getElementById('deptForm').reset();
        document.getElementById('deptId').value = '';
        document.getElementById('deptModal').classList.remove('hidden');
    }

    function editDept(dept) {
        editMode = true;
        document.getElementById('modalTitle').textContent = 'Edit Department';
        document.getElementById('deptId').value = dept.id;
        document.getElementById('deptName').value = dept.name;
        document.getElementById('deptCode').value = dept.code;
        document.getElementById('deptModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deptModal').classList.add('hidden');
    }

    document.getElementById('deptForm').addEventListener('submit', function (e) {
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
                    showToast('Department saved successfully', 'success');
                } else {
                    showToast(data.error || 'Error saving department', 'error');
                }
            });
    });

    function deleteDept(id) {
        if (!confirm('Delete this department?')) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('delete_id', id);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) { loadTable(); showToast('Department deleted', 'success'); }
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
