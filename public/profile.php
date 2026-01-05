<?php
/**
 * User Profile Page - Simple & Elegant
 * Features: View profile, update contact info, change password, profile picture
 */
require_once __DIR__ . '/../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();
$userId = $user['id'];

// Refresh user data from DB
$userData = $db->fetch("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ?", [$userId]);

if (!$userData) {
    flash('error', 'User not found.');
    redirect(url('public/logout.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        flash('error', 'Invalid request');
        redirect(url('public/profile.php'));
    }

    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $updates = [];
        $params = [];

        // AMS ID update (only if currently empty)
        if (empty($userData['ams_id']) && !empty($_POST['ams_id'])) {
            $newAmsId = trim($_POST['ams_id']);
            $existing = $db->fetchValue("SELECT id FROM users WHERE ams_id = ? AND id != ?", [$newAmsId, $userId]);
            if ($existing) {
                flash('error', 'This AMS ID is already in use.');
                redirect(url('public/profile.php'));
            }
            $updates[] = "ams_id = ?";
            $params[] = $newAmsId;
        }

        // Contact info
        if (isset($_POST['mobile'])) {
            $updates[] = "mobile = ?";
            $params[] = trim($_POST['mobile']);
        }
        if (isset($_POST['landline'])) {
            $updates[] = "landline = ?";
            $params[] = trim($_POST['landline']);
        }
        if (isset($_POST['phone'])) {
            $updates[] = "phone = ?";
            $params[] = trim($_POST['phone']);
        }
        if (isset($_POST['address'])) {
            $updates[] = "address = ?";
            $params[] = trim($_POST['address']);
        }

        // Email update
        if (!empty($_POST['email_id']) && $_POST['email_id'] !== $userData['email_id']) {
            $updates[] = "email_id = ?";
            $params[] = trim($_POST['email_id']);
        }

        // Profile picture upload
        if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_PATH . 'profiles/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0775, true);

            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $fileName = ($userData['ams_id'] ?? $userId) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $fileName)) {
                    $updates[] = "profile_pic = ?";
                    $params[] = $fileName;
                }
            }
        }

        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $db->query($sql, $params);
            flash('success', 'Profile updated successfully!');
        }
        redirect(url('public/profile.php'));
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            flash('error', 'Please fill in all password fields.');
        } elseif ($newPassword !== $confirmPassword) {
            flash('error', 'New passwords do not match.');
        } elseif (strlen($newPassword) < 6) {
            flash('error', 'Password must be at least 6 characters.');
        } elseif (!password_verify($currentPassword, $userData['password'])) {
            flash('error', 'Current password is incorrect.');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
            flash('success', 'Password changed successfully!');
        }
        redirect(url('public/profile.php'));
    }
}

// Re-fetch after potential updates
$userData = $db->fetch("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ?", [$userId]);

$pageTitle = 'My Profile';
$pageSubtitle = 'Manage your account settings';

ob_start();
?>

<style>
    .profile-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .avatar-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2.5rem;
        border: 4px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .upload-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        border: 3px solid white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .upload-btn:hover {
        transform: scale(1.1);
    }

    .info-row {
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .form-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
    }

    .form-input {
        width: 100%;
        padding: 0.625rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: white;
    }

    .form-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }
</style>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="lg:col-span-1">
        <div class="profile-card p-6 text-center">
            <div class="relative inline-block mb-4">
                <?php if (!empty($userData['profile_pic'])): ?>
                    <img src="<?= url('uploads/profiles/' . $userData['profile_pic']) ?>" class="profile-avatar"
                        id="avatarPreview" alt="Profile">
                <?php else: ?>
                    <div class="avatar-placeholder" id="avatarPlaceholder">
                        <?= strtoupper(substr($userData['emp_name'] ?? 'U', 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <label for="profile_pic_input" class="upload-btn">
                    <i class="fas fa-camera text-sm"></i>
                </label>
            </div>

            <h3 class="text-xl font-bold text-gray-800 mb-1"><?= Security::escape($userData['emp_name']) ?></h3>
            <p class="text-sm text-gray-500 mb-4"><?= Security::escape($userData['designation'] ?? 'Employee') ?></p>

            <div class="text-left px-2">
                <div class="info-row flex justify-between items-center">
                    <span class="text-sm text-gray-500">AMS ID</span>
                    <span class="font-semibold text-gray-800">
                        <?= Security::escape($userData['ams_id'] ?? 'Not Set') ?>
                        <?php if (empty($userData['ams_id'])): ?>
                            <span class="ml-1 text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Required</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row flex justify-between items-center">
                    <span class="text-sm text-gray-500">Email</span>
                    <span
                        class="text-sm text-gray-700"><?= Security::escape($userData['email_id'] ?? 'Not Set') ?></span>
                </div>
                <div class="info-row flex justify-between items-center">
                    <span class="text-sm text-gray-500">Department</span>
                    <span
                        class="text-sm text-gray-700"><?= Security::escape($userData['department_name'] ?? 'Not Assigned') ?></span>
                </div>
                <div class="info-row flex justify-between items-center">
                    <span class="text-sm text-gray-500">Role</span>
                    <span
                        class="text-xs bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded-full font-medium"><?= ucfirst($userData['role'] ?? 'employee') ?></span>
                </div>
                <?php if (!empty($userData['mobile'])): ?>
                    <div class="info-row flex justify-between items-center">
                        <span class="text-sm text-gray-500">Mobile</span>
                        <span class="text-sm text-gray-700"><?= Security::escape($userData['mobile']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Forms -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Update Details -->
        <div class="form-card p-6">
            <div class="flex items-center gap-3 mb-5">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-edit text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Update Details</h3>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="update_profile">
                <input type="file" name="profile_pic" id="profile_pic_input" class="hidden" accept="image/*">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <?php if (empty($userData['ams_id'])): ?>
                        <div>
                            <label class="form-label">AMS ID <span class="text-red-500">*</span></label>
                            <input type="text" name="ams_id" class="form-input" placeholder="Enter your AMS ID"
                                pattern="[0-9]{7}" title="7-digit AMS ID" required>
                            <p class="text-xs text-gray-400 mt-1">Your 7-digit employee AMS ID</p>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email_id" class="form-input"
                            value="<?= Security::escape($userData['email_id'] ?? '') ?>"
                            placeholder="your.email@csir.res.in">
                    </div>

                    <div>
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-input"
                            value="<?= Security::escape($userData['mobile'] ?? '') ?>" placeholder="+91 98765 43210">
                    </div>

                    <div>
                        <label class="form-label">Landline Number</label>
                        <input type="tel" name="landline" class="form-input"
                            value="<?= Security::escape($userData['landline'] ?? '') ?>" placeholder="044-2254 2xxx">
                    </div>

                    <div>
                        <label class="form-label">Extension/Intercom</label>
                        <input type="text" name="phone" class="form-input"
                            value="<?= Security::escape($userData['phone'] ?? '') ?>" placeholder="Extension number">
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-input" rows="2"
                            placeholder="Office/Residence address"><?= Security::escape($userData['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <button type="submit"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="form-card p-6">
            <div class="flex items-center gap-3 mb-5">
                <div
                    class="w-10 h-10 bg-gradient-to-br from-rose-500 to-red-500 rounded-xl flex items-center justify-center">
                    <i class="fas fa-lock text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Change Password</h3>
            </div>

            <form method="POST">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required
                            placeholder="••••••••">
                    </div>
                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" minlength="6" required
                            placeholder="••••••••">
                    </div>
                    <div>
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-input" required
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="bg-gradient-to-r from-rose-500 to-red-500 text-white px-6 py-2.5 rounded-xl font-medium hover:shadow-lg transition-all">
                    <i class="fas fa-key mr-2"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('profile_pic_input')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                const preview = document.getElementById('avatarPreview');
                const placeholder = document.getElementById('avatarPlaceholder');

                if (preview) {
                    preview.src = event.target.result;
                } else if (placeholder) {
                    placeholder.outerHTML = '<img src="' + event.target.result + '" class="profile-avatar" id="avatarPreview" alt="Profile">';
                }

                // Auto-submit after selecting image
                document.querySelector('form[enctype="multipart/form-data"]').submit();
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../templates/layout.php';
?>