<?php
/**
 * CSIR-SERC Asset Management System
 * Login Page - Split Screen Modern Design
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
            --primary-gradient: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
        }

        body {
            font-family: 'Outfit', sans-serif;
            overflow-x: hidden;
        }

        /* Ambient Background for Right Side */
        .right-section {
            background-color: #0f172a;
            position: relative;
            overflow: hidden;
        }

        .ambient-light {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(0, 114, 255, 0.15) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            animation: pulse 8s infinite alternate;
        }

        @keyframes pulse {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
            100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
        }

        /* Glass Input Fields */
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

        .btn-primary {
            background: var(--primary-gradient);
            background-size: 200% auto;
            transition: 0.5s;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 114, 255, 0.3);
        }

        /* Left Side Styles */
        .left-section {
            background-image: url('<?= url('Branding/CSIR-SERC Main Building.png') ?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .left-overlay {
            background: linear-gradient(to right, rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.7));
            position: absolute;
            inset: 0;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .animate-up {
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
    </style>
</head>

<body class="min-h-screen grid grid-cols-1 lg:grid-cols-2">

    <!-- Left Section (Info & Stats) -->
    <div class="left-section hidden lg:flex flex-col justify-between p-12 lg:p-16 text-white relative">
        <div class="left-overlay"></div>
        
        <!-- Header -->
        <div class="relative z-10 flex items-center gap-4 animate-up">
            <img src="<?= url('Branding/csirlogo.jpg') ?>" alt="CSIR" class="w-12 h-12 rounded-full border border-white/20">
            <div>
                <h2 class="font-bold text-lg tracking-wide">CSIR-SERC</h2>
                <p class="text-xs text-blue-200 uppercase tracking-widest">Chennai</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 max-w-lg mt-12 animate-up delay-100">
            <h1 class="text-5xl font-bold leading-tight mb-6">
                Advanced <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">Asset Management</span>
            </h1>
            <p class="text-lg text-blue-200/80 mb-10 leading-relaxed">
                Streamlining inventory tracking, transfers, and maintenance across all departments with precision and security.
            </p>

            <!-- Glimpse / Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="stat-card p-4 rounded-xl">
                    <div class="text-3xl font-bold text-white mb-1">10k+</div>
                    <div class="text-xs text-blue-200 uppercase tracking-wider">Assets Tracked</div>
                </div>
                <div class="stat-card p-4 rounded-xl">
                    <div class="text-3xl font-bold text-white mb-1">100%</div>
                    <div class="text-xs text-blue-200 uppercase tracking-wider">Digital Records</div>
                </div>
                <div class="stat-card p-4 rounded-xl">
                    <div class="text-3xl font-bold text-white mb-1">24/7</div>
                    <div class="text-xs text-blue-200 uppercase tracking-wider">Availability</div>
                </div>
                <div class="stat-card p-4 rounded-xl">
                    <div class="text-3xl font-bold text-white mb-1">Secure</div>
                    <div class="text-xs text-blue-200 uppercase tracking-wider">Role Access</div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 text-xs text-blue-200/40 animate-up delay-200">
            &copy; <?= date('Y') ?> CSIR-Structural Engineering Research Centre. All rights reserved.
        </div>
    </div>

    <!-- Right Section (Login Form) -->
    <div class="right-section flex items-center justify-center p-6 lg:p-12 relative">
        <div class="ambient-light"></div>

        <div class="w-full max-w-md relative z-10 animate-up delay-300">
            
            <!-- Mobile Branding (Visible only on small screens) -->
            <div class="lg:hidden text-center mb-10">
                <div class="flex justify-center items-center gap-4 mb-4">
                    <img src="<?= url('Branding/csirlogo.jpg') ?>" class="w-12 h-12 rounded-full border border-white/20">
                    <img src="<?= url('Image/logo-serc.jpg') ?>" class="w-12 h-12 rounded-full border border-white/20">
                </div>
                <h2 class="text-2xl font-bold text-white">CSIR-SERC</h2>
                <p class="text-sm text-blue-200">Asset Management System</p>
            </div>

            <div class="bg-slate-900/60 backdrop-blur-xl border border-white/10 p-8 rounded-3xl shadow-2xl">
                
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-white mb-2">Welcome Back</h2>
                    <p class="text-blue-200/60 text-sm">Please sign in to your dashboard</p>
                </div>

                <!-- Messages -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm flex items-center gap-3 animate-pulse">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                        <?= Security::escape($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-200 text-sm flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-400"></i>
                        <?= Security::escape($success) ?>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" action="" class="space-y-5">
                    <?= Security::csrfField() ?>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-blue-200/80 uppercase tracking-wider ml-1">AMS ID</label>
                        <div class="relative group">
                            <input type="text" name="ams_id" required
                                class="input-field w-full px-5 py-3.5 rounded-xl text-sm pl-11" 
                                placeholder="Enter AMS ID"
                                value="<?= Security::escape($_POST['ams_id'] ?? '') ?>">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-300/40 group-focus-within:text-blue-400 transition-colors">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-blue-200/80 uppercase tracking-wider ml-1">Password</label>
                        <div class="relative group">
                            <input type="password" name="password" id="password" required
                                class="input-field w-full px-5 py-3.5 rounded-xl text-sm pl-11" 
                                placeholder="••••••••">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-300/40 group-focus-within:text-blue-400 transition-colors">
                                <i class="fas fa-lock"></i>
                            </div>
                            <button type="button" onclick="togglePassword()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-blue-300/40 hover:text-white transition-colors p-1">
                                <i id="eyeIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                            class="btn-primary w-full py-4 rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-blue-900/20 text-white flex items-center justify-center gap-2 group">
                            <span>SIGN IN</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>

                    <div class="text-center mt-6">
                        <a href="<?= url('public/forgot-password.php') ?>" class="text-sm text-blue-300/60 hover:text-white transition-colors">
                            Forgot Password?
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="mt-8 text-center">
                 <p class="text-xs text-blue-200/30 font-mono">SECURE SYSTEM v<?= APP_VERSION ?></p>
            </div>
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