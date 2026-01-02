<?php
/**
 * CSIR-SERC Asset Management System
 * Login Page - Stunning Modern Design
 */

require_once __DIR__ . '/../bootstrap.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">
    <style>
        :root {
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --primary-gradient: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0f172a;
            overflow: hidden;
        }

        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background-image: url('<?= url('Branding/CSIR-SERC Main Building.png') ?>');
            background-size: cover;
            background-position: center;
            filter: brightness(0.4);
        }

        .ambient-light {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120vw;
            height: 120vh;
            background: radial-gradient(circle at center, rgba(0, 114, 255, 0.15) 0%, transparent 70%);
            z-index: -1;
            animation: pulseLight 8s ease-in-out infinite alternate;
        }

        @keyframes pulseLight {
            0% {
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(0.9);
            }

            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.1);
            }
        }

        .glass-card {
            background: rgba(20, 30, 48, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        .input-group {
            position: relative;
            transition: all 0.3s ease;
        }

        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #00C6FF;
            box-shadow: 0 0 0 4px rgba(0, 198, 255, 0.1);
            outline: none;
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .btn-primary {
            background: var(--primary-gradient);
            background-size: 200% auto;
            transition: 0.5s;
            border: none;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 114, 255, 0.3);
        }

        .logo-container img {
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.2));
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .logo-container:hover img {
            transform: scale(1.05);
        }

        .animate-up {
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status Messages */
        .status-message {
            animation: slideIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideIn {
            from {
                transform: translateX(-10px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen text-white">
    <!-- Background Elements -->
    <div class="bg-pattern"></div>
    <div class="ambient-light"></div>

    <!-- Login Container -->
    <div class="w-full max-w-[440px] p-6 relative z-10">
        <div class="glass-card rounded-3xl p-8 md:p-10 animate-up">

            <!-- Branding -->
            <div class="text-center mb-10 logo-container">
                <div class="flex justify-center items-center gap-6 mb-6">
                    <img src="<?= url('Branding/csirlogo.jpg') ?>" alt="CSIR"
                        class="w-16 h-16 rounded-full border-2 border-white/20">
                    <div class="h-10 w-px bg-white/20"></div>
                    <img src="<?= url('Image/logo-serc.jpg') ?>" alt="SERC"
                        class="w-16 h-16 rounded-full border-2 border-white/20">
                </div>
                <h1
                    class="text-2xl font-bold tracking-tight mb-2 bg-clip-text text-transparent bg-gradient-to-r from-white to-blue-200">
                    CSIR-SERC
                </h1>
                <p
                    class="text-sm text-blue-200/80 font-medium tracking-wide border-t border-white/10 inline-block pt-2">
                    ASSET MANAGEMENT SYSTEM
                </p>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div
                    class="status-message mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                    <?= Security::escape($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div
                    class="status-message mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-200 text-sm flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-400"></i>
                    <?= Security::escape($success) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" class="space-y-6">
                <?= Security::csrfField() ?>

                <div class="space-y-2 animate-up delay-100">
                    <label class="text-xs font-semibold text-blue-200/80 uppercase tracking-wider ml-1">AMS ID</label>
                    <div class="input-group">
                        <input type="text" name="ams_id" required
                            class="input-field w-full px-5 py-3.5 rounded-xl text-sm" placeholder="Enter your AMS ID"
                            value="<?= Security::escape($_POST['ams_id'] ?? '') ?>">
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-300/50">
                            <i class="fas fa-user-circle text-lg"></i>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 animate-up delay-200">
                    <label class="text-xs font-semibold text-blue-200/80 uppercase tracking-wider ml-1">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" required
                            class="input-field w-full px-5 py-3.5 rounded-xl text-sm" placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-300/50 hover:text-white transition-colors p-1">
                            <i id="eyeIcon" class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-4 animate-up delay-300">
                    <button type="submit"
                        class="btn-primary w-full py-4 rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-blue-900/20 text-white flex items-center justify-center gap-2 group">
                        <span>SIGN IN TO DASHBOARD</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center animate-up delay-300">
                <a href="<?= url('public/forgot-password.php') ?>"
                    class="text-sm text-blue-300/60 hover:text-white transition-colors inline-flex items-center gap-2 hover:gap-3">
                    <span>Forgot your password?</span>
                    <i class="fas fa-long-arrow-alt-right opacity-0 hover:opacity-100 transition-opacity"></i>
                </a>
            </div>
        </div>

        <div class="mt-8 text-center text-xs text-blue-200/20 font-light tracking-widest animate-up delay-300">
            SECURED CONNECTION &bull; SSL ENCRYPTED
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
 if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>

</html>