<?php
/**
 * User Profile Page (Self-Service)
 */
require_once __DIR__ . '/../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();
$userId = $user['id']; // Assuming session stores ID now, or query by AMS_ID

// Refresh user data from DB to get latest
$userData = $db->fetch("SELECT * FROM emp_details WHERE AMS_id = ?", [$user['AMS_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile'];
    $address = $_POST['address'];
    $department = $_POST['department'];
    $updates = [
        'mobile' => $mobile,
        'address' => $address,
        'department' => $department
    ];

    // Password Update
    if (!empty($_POST['new_password'])) {
        if (password_verify($_POST['current_password'], $userData['password'])) {
            $updates['password'] = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        } else {
            $error = "Current password incorrect.";
        }
    }

    // Profile Pic Upload
    if (empty($error) && !empty($_FILES['profile_pic']['name'])) {
        $uploadDir = UPLOAD_PATH . 'profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        
        $fileName = $userData['AMS_id'] . '_' . time() . '.jpg'; // Simple rename
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $fileName)) {
            $updates['profile_pic'] = $fileName;
        }
    }

    if (empty($error)) {
        // Construct Update Query
        $setParts = [];
        $params = [];
        foreach ($updates as $key => $val) {
            $setParts[] = "$key = ?";
            $params[] = $val;
        }
        $params[] = $userData['AMS_id']; // For WHERE

        if (!empty($setParts)) {
            $sql = "UPDATE emp_details SET " . implode(', ', $setParts) . " WHERE AMS_id = ?";
            $db->query($sql, $params);
            flash('success', 'Profile updated successfully.');
            redirect(url('public/profile.php'));
        }
    }
}

$pageTitle = 'My Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                
                <?php if(Auth::isAdmin()): ?>
                <a href="<?= url('public/admin/users.php') ?>" class="d-block text-white text-decoration-none mb-3 opacity-75 hover-opacity-100"><i class="fas fa-users me-2"></i> User Management</a>
                <?php endif; ?>
                
                <a href="<?= url('public/profile.php') ?>" class="d-block text-white text-decoration-none mb-3 fw-bold opacity-100"><i class="fas fa-user me-2"></i> My Profile</a>
                <a href="<?= url('public/logout.php') ?>" class="d-block text-danger text-decoration-none mt-5"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4 bg-light">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-light text-primary">My Profile</h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger shadow-sm border-0"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($msg = flash('success')): ?>
                <div class="alert alert-success shadow-sm border-0"><?= $msg ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm animate-fade-in text-center p-4">
                        <div class="mb-3 position-relative d-inline-block">
                            <?php if(!empty($userData['profile_pic'])): ?>
                                <img src="<?= asset('uploads/profiles/' . $userData['profile_pic']) ?>" class="rounded-circle shadow-sm" width="120" height="120" style="object-fit:cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto text-secondary" style="width:120px; height:120px; font-size:3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <label for="picUpload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow-sm" style="cursor:pointer; width:35px; height:35px; font-size:14px; display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <h5 class="mb-1"><?= htmlspecialchars($userData['emp_name']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($userData['user_priv']) ?> | <?= htmlspecialchars($userData['AMS_id']) ?></p>
                        <div class="text-start px-3">
                            <div class="d-flex align-items-center mb-2 text-secondary">
                                <i class="fas fa-envelope w-25px me-2 text-center"></i> <?= htmlspecialchars($userData['email_id']) ?>
                            </div>
                            <div class="d-flex align-items-center mb-2 text-secondary">
                                <i class="fas fa-phone w-25px me-2 text-center"></i> <?= htmlspecialchars($userData['mobile'] ?? 'Not set') ?>
                            </div>
                            <div class="d-flex align-items-center mb-2 text-secondary">
                                <i class="fas fa-building w-25px me-2 text-center"></i> <?= htmlspecialchars($userData['department'] ?? 'Not set') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm animate-fade-in">
                        <div class="card-header bg-white border-0 pt-4 px-4">
                            <h5 class="card-title text-primary mb-0"><i class="fas fa-user-edit me-2"></i>Update Details</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="file" name="profile_pic" id="picUpload" class="d-none" accept="image/*">
                                
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Mobile Number</label>
                                        <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($userData['mobile'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Department</label>
                                        <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($userData['department'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-secondary">Address</label>
                                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <h6 class="text-danger border-bottom pb-2 mb-3"><i class="fas fa-lock me-2"></i>Change Password</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Current Password</label>
                                        <input type="password" name="current_password" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">New Password</label>
                                        <input type="password" name="new_password" class="form-control">
                                        <div class="form-text">Leave blank if you don't want to change it.</div>
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Preview image on selection
        document.getElementById('picUpload').onchange = function (evt) {
            var tgt = evt.target || window.event.srcElement,
                files = tgt.files;
            
            if (FileReader && files && files.length) {
                var fr = new FileReader();
                fr.onload = function () {
                    document.querySelector('.rounded-circle.shadow-sm').src = fr.result;
                }
                fr.readAsDataURL(files[0]);
            }
        }
    </script>
</body>
</html>
