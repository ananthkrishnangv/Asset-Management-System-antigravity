<?php
require_once '../bootstrap.php';

// Auth check
Auth::requireAdmin();

$pageTitle = 'User Management';
require_once '../templates/header.php';
require_once '../templates/sidebar.php';

// Pagination & Filters
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filters = [
    'search' => $_GET['search'] ?? '',
    'role' => $_GET['role'] ?? '',
    'department_id' => $_GET['department_id'] ?? ''
];

$users = User::getAll($filters, $limit, $offset);
$totalUsers = User::count($filters);
$totalPages = ceil($totalUsers / $limit);

// Get departments for filter
$db = Database::getInstance();
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name ASC");
?>

<div class="main-content">
    <div class="page-header flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
            <p class="text-gray-600">Manage system users, roles, and permissions</p>
        </div>
        <div class="flex gap-2">
            <a href="import_users.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Import Users
            </a>
            <a href="user_create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New User
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                       placeholder="Search by Name, AMS ID, or Email..." 
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="w-48">
                <select name="role" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Roles</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="supervisor" <?= $filters['role'] === 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
                    <option value="employee" <?= $filters['role'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                </select>
            </div>
            <div class="w-48">
                <select name="department_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $filters['department_id'] == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-700">Filter</button>
            <?php if (!empty($filters['search']) || !empty($filters['role']) || !empty($filters['department_id'])): ?>
                <a href="users.php" class="text-gray-600 px-4 py-2 hover:underline">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Info</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role & Dept</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                No users found matching your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                            <?= strtoupper(substr($user['emp_name'], 0, 1)) ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['emp_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email_id']) ?></div>
                                            <div class="text-xs text-gray-400">AMS ID: <?= htmlspecialchars($user['ams_id']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : ($user['role'] === 'supervisor' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                    <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($user['department_name'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($user['is_active']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <a href="user_edit.php?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <?php if ($user['ams_id'] !== '1410146'): // Prevent deleting default admin ?>
                                        <a href="user_delete.php?id=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-between items-center relative z-0">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                        <span class="font-medium"><?= min($offset + $limit, $totalUsers) ?></span> of 
                        <span class="font-medium"><?= $totalUsers ?></span> results
                    </p>
                </div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($filters, ['page' => ''])) ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                           <?= $i == $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
