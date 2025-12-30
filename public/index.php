<?php
/**
 * CSIR-SERC Asset Management System
 * Login Page - Stunning Modern Design
 */

require_once __DIR__ . '/bootstrap.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(url('public/dashboard.php'));
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $amsId = Security::sanitize($_POST['ams_id'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($amsId) || empty($password)) {
            $error = 'Please enter both AMS ID and Password.';
        } else {
            $result = Auth::attempt($amsId, $password);

            if ($result['success']) {
                redirect(url('public/dashboard.php'));
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Check for URL params
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'auth':
            $error = 'Please login to continue.';
            break;
        case 'timeout':
            $error = 'Session expired. Please login again.';
            break;
    }
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset successfully. Please login with your new password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CSIR-SERC Asset Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .bg-pattern {
            background-image: url('<?= url('Branding/CSIR-SERC Main Building.png') ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gradient-text {
            background: linear-gradient(135deg, #1a365d 0%, #2d5aa0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #1a365d 0%, #2d5aa0 50%, #1a365d 100%);
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(45, 90, 160, 0.2);
            border-color: #2d5aa0;
        }

        .logo-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .overlay {
            background: linear-gradient(135deg, rgba(26, 54, 93, 0.85) 0%, rgba(45, 90, 160, 0.75) 100%);
        }
    </style>
</head>

<body class="min-h-screen bg-pattern">
    <div class="min-h-screen overlay flex items-center justify-center p-4">
        <div class="w-full max-w-md slide-in">
            <!-- Login Card -->
            <div class="glass-card rounded-3xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-10 text-center">
                    <div class="flex justify-center items-center gap-4 mb-4">
                        <img src="<?= url('Branding/csirlogo.jpg') ?>" alt="CSIR Logo"
                            class="h-16 w-16 rounded-full border-4 border-white/30 shadow-lg float-animation">
                        <img src="<?= url('Image/logo-serc.jpg') ?>" alt="SERC Logo"
                            class="h-16 w-16 rounded-full border-4 border-white/30 shadow-lg float-animation"
                            style="animation-delay: 0.5s;">
                    </div>
                    <h1 class="text-2xl font-bold text-white mb-1">CSIR-SERC</h1>
                    <p class="text-blue-200 text-sm">Structural Engineering Research Centre</p>
                    <div class="mt-4 inline-block bg-white/10 backdrop-blur-sm rounded-full px-4 py-2">
                        <span class="text-white font-semibold text-lg">Asset Management System</span>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="px-8 py-10">
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg shake">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                                <span class="text-red-700 text-sm"><?= Security::escape($error) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-r-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                <span class="text-green-700 text-sm"><?= Security::escape($success) ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <?= Security::csrfField() ?>

                        <!-- AMS ID Field -->
                        <div>
                            <label for="ams_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-id-card text-blue-600 mr-2"></i>AMS ID
                            </label>
                            <div class="relative">
                                <input type="text" name="ams_id" id="ams_id" required autocomplete="username"
                                    placeholder="Enter your AMS ID"
                                    class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none transition-all duration-300"
                                    value="<?= Security::escape($_POST['ams_id'] ?? '') ?>">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock text-blue-600 mr-2"></i>Password
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required
                                    autocomplete="current-password" placeholder="Enter your password"
                                    class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none transition-all duration-300">
                                <button type="button" onclick="togglePassword()"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <i id="eyeIcon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Login Button -->
                        <button type="submit"
                            class="btn-gradient w-full py-4 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Sign In</span>
                        </button>
                    </form>

                    <!-- Forgot Password Link -->
                    <div class="mt-6 text-center">
                        <a href="<?= url('public/forgot-password.php') ?>"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                            <i class="fas fa-key mr-1"></i>
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-8 py-4 text-center border-t border-gray-100">
                    <p class="text-xs text-gray-500">
                        © <?= date('Y') ?> CSIR-SERC. All rights reserved.
                    </p>
                </div>
            </div>

            <!-- Security Badge -->
            <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2">
                    <i class="fas fa-shield-alt text-green-400"></i>
                    <span class="text-white text-sm">Secured with SSL & Advanced Encryption</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Focus on AMS ID field on load
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('ams_id').focus();
        });
    </script>
</body>

</html>