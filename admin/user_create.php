<?php
require_once '../bootstrap.php';

// Auth check
Auth::requireAdmin();

$pageTitle = 'Add New User';
require_once '../templates/header.php';
require_once '../templates/sidebar.php';

$error = '';
$success = '';

// Get Departments
$db = Database::getInstance();
$departments = $db->fetchAll("SELECT * FROM departments ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!Security::verifyCSRFToken($_POST['ams_csrf_token'])) {
        $error = 'Invalid request token.';
    } else {
        $amsId = trim($_POST['ams_id']);
        $empName = trim($_POST['emp_name']);
        $emailId = trim($_POST['email_id']);
        $role = $_POST['role'];
        $departmentId = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        // Basic Validation
        if (empty($amsId) || empty($empName) || empty($emailId) || empty($password)) {
            $error = 'All fields are required.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (User::findByAmsId($amsId)) {
            $error = 'User with this AMS ID already exists.';
        } elseif (User::findByEmail($emailId)) {
            $error = 'User with this Email ID already exists.';
        } else {
            // Create User
            $data = [
                'ams_id' => $amsId,
                'emp_name' => $empName,
                'email_id' => $emailId,
                'role' => $role,
                'department_id' => $departmentId,
                'password' => $password, // Model handles hashing
                'is_active' => 1
            ];

            try {
                if (User::create($data)) {
                    $success = 'User created successfully!';
                } else {
                    $error = 'Failed to create user. Database error.';
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="main-content">
    <div class="page-header mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Add New User</h2>
        <p class="text-gray-600">Create a new user account</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form action="" method="POST">
            <?= Security::csrfField() ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">AMS ID / Emp ID</label>
                    <input type="text" name="ams_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Employee Name</label>
                    <input type="text" name="emp_name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                    <input type="email" name="email_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Department</label>
                    <select name="department_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select name="role" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="employee">Employee</option>
                        <option value="supervisor">Supervisor</option>
                        <option value="admin">System Admin</option>
                    </select>
                </div>

                <div class="md:col-span-2 border-t pt-4 mt-2">
                    <h4 class="text-sm font-bold text-gray-700 mb-4">Initial Password</h4>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                    <input type="password" name="confirm_password" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <a href="users.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg mr-2 hover:bg-gray-300">Cancel</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Create User</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
