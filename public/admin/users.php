<?php
/**
 * Admin User Management Page
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireRole('admin');

$db = Database::getInstance();

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add Single User
    if ($action === 'add_user') {
        $amsId = $_POST['ams_id'];
        $empName = $_POST['emp_name'];
        $email = $_POST['email'];
        // Default password is 'serc@123' if not provided
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : password_hash('serc@123', PASSWORD_BCRYPT);
        $role = $_POST['role'];
        $dept = $_POST['department'];

        // Check duplicate
        $exists = $db->fetch("SELECT id FROM emp_details WHERE AMS_id = ?", [$amsId]);
        if ($exists) {
            flash('error', 'User with AMS ID ' . $amsId . ' already exists.');
        } else {
            $db->insert('emp_details', [
                'AMS_id' => $amsId,
                'emp_name' => $empName,
                'email_id' => $email,
                'password' => $password,
                'user_priv' => $role,
                'department' => $dept
            ]);
            flash('success', 'User added successfully.');
        }
        redirect(url('public/admin/users.php'));
    }

    // Bulk Delete
    if ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? [];
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->query("DELETE FROM emp_details WHERE id IN ($placeholders)", $ids);
            flash('success', count($ids) . ' users deleted successfully.');
        } else {
            flash('error', 'No users selected for deletion.');
        }
        redirect(url('public/admin/users.php'));
    }

    // CSV Import
    if ($action === 'import_csv') {
        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $count = 0;
            // Skip header
            fgetcsv($file);
            
            while (($row = fgetcsv($file)) !== FALSE) {
                // Expected: AMS_ID, Name, Email, Password, Role, Department
                if (count($row) >= 6) {
                    $amsId = $row[0];
                    // Skip if exists
                    if ($db->fetch("SELECT id FROM emp_details WHERE AMS_id = ?", [$amsId])) continue;

                    $db->insert('emp_details', [
                        'AMS_id' => $row[0],
                        'emp_name' => $row[1],
                        'email_id' => $row[2],
                        'password' => password_hash($row[3], PASSWORD_BCRYPT),
                        'user_priv' => $row[4],
                        'department' => $row[5]
                    ]);
                    $count++;
                }
            }
            fclose($file);
            flash('success', "$count users imported successfully.");
        }
        redirect(url('public/admin/users.php'));
    }
}

// Fetch Users
$users = $db->fetchAll("SELECT * FROM emp_details ORDER BY id DESC");
$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= asset('assets/css/fluent.css') ?>">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white" style="width: 260px; min-height: 100vh;">
            <div class="p-4">
                <h4 class="fw-bold mb-4"><i class="fas fa-cube me-2"></i>AMS</h4>
                <a href="<?= url('public/dashboard.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-home me-2"></i> Dashboard</a>
                <a href="<?= url('public/inventory/dir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-list-alt me-2"></i> DIR Details</a>
                <a href="<?= url('public/inventory/pir.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-clipboard-list me-2"></i> PIR Details</a>
                
                <hr class="border-secondary my-4">
                
                <a href="<?= url('public/admin/users.php') ?>" class="d-block text-white text-decoration-none mb-3 fw-bold opacity-100"><i class="fas fa-users me-2"></i> User Management</a>
                <a href="<?= url('public/admin/settings.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-cogs me-2"></i> Settings</a>
                <a href="<?= url('public/logout.php') ?>" class="d-block text-danger text-decoration-none mt-5"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-light text-primary">User Management</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal"><i class="fas fa-file-csv me-2"></i>Import CSV</button>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-user-plus me-2"></i>Add User</button>
                </div>
            </div>

            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($msg = flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm animate-fade-in">
                <div class="card-body">
                    <form method="POST" id="bulkForm">
                        <input type="hidden" name="action" value="bulk_delete">
                        
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Manage system access and roles</span>
                            <button type="submit" class="btn btn-danger btn-sm d-none" id="deleteBtn" onclick="return confirm('Delete selected users?')">
                                <i class="fas fa-trash me-2"></i>Delete Selected
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="userTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                                        <th>AMS ID</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_ids[]" value="<?= $u['id'] ?>" class="form-check-input user-check"></td>
                                        <td class="fw-bold"><?= htmlspecialchars($u['AMS_id']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if(!empty($u['profile_pic'])): ?>
                                                    <img src="<?= asset('uploads/profiles/' . $u['profile_pic']) ?>" class="rounded-circle me-2" width="32" height="32" style="object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:32px; height:32px; font-size:12px;">
                                                        <?= strtoupper(substr($u['emp_name'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($u['emp_name']) ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-label-primary"><?= ucfirst($u['user_priv']) ?></span></td>
                                        <td><?= htmlspecialchars($u['department'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($u['email_id']) ?></td>
                                        <td><?= htmlspecialchars($u['mobile'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_user">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">AMS ID</label>
                            <input type="text" name="ams_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Full Name</label>
                            <input type="text" name="emp_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-secondary">Role</label>
                                <select name="role" class="form-select">
                                    <option value="user">Staff User</option>
                                    <option value="admin">Admin</option>
                                    <option value="supervisor">Supervisor</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-secondary">Department</label>
                                <input type="text" name="department" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Password (Default: serc@123)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank for default">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Import Users CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-file-csv fa-3x text-success mb-3"></i>
                        <p class="text-muted small">Upload a CSV file with the following columns:<br>
                        <strong>AMS_ID, Name, Email, Password, Role, Department</strong></p>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_csv">
                        <input type="file" name="csv_file" class="form-control mb-3" accept=".csv" required>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Upload & Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // DataTables
            $('#userTable').DataTable({
                pageLength: 25,
                language: { searchPlaceholder: "Search users..." }
            });

            // Bulk Select
            $('#selectAll').change(function() {
                $('.user-check').prop('checked', $(this).prop('checked'));
                toggleDeleteBtn();
            });

            $(document).on('change', '.user-check', function() {
                toggleDeleteBtn();
            });

            function toggleDeleteBtn() {
                if ($('.user-check:checked').length > 0) {
                    $('#deleteBtn').removeClass('d-none');
                } else {
                    $('#deleteBtn').addClass('d-none');
                }
            }
        });
    </script>
</body>
</html>
