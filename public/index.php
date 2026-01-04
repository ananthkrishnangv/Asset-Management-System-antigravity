<?php
/**
 * CSIR-SERC Asset Management System
 * Login Page - Premium Glassmorphism Design
 * Version 2.0
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
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">
    
    <style>
        /* ═══════════════════════════════════════════════════════════════
           BASE STYLES
           ═══════════════════════════════════════════════════════════════ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --font-primary: 'Noto Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --color-primary: #667eea;
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        html, body {
            height: 100%;
            font-family: var(--font-primary);
            overflow: hidden;
        }

        body {
            display: flex;
            background: #0a0a1a;
        }

        /* ═══════════════════════════════════════════════════════════════
           LEFT SIDE - HERO SECTION (60%)
           ═══════════════════════════════════════════════════════════════ */
        .hero-section {
            flex: 0 0 60%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            overflow: hidden;
        }

        /* Background Image with Overlay */
        .hero-bg {
            position: absolute;
            inset: 0;
            background-color: #0f0c29;
            background-image: 
                linear-gradient(rgba(15, 12, 41, 0.7), rgba(15, 12, 41, 0.85)),
                url('<?= url('public/assets/img/login-bg.jpg') ?>');
            background-size: cover;
            background-position: center;
            z-index: 0;
        }

        /* Animated Grid Pattern */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
        }

        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 2;
            animation: float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: 15%;
            background: rgba(102, 126, 234, 0.3);
            animation-delay: 0s;
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            bottom: 20%;
            right: 10%;
            background: rgba(240, 147, 251, 0.25);
            animation-delay: -2s;
        }

        .orb-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(79, 172, 254, 0.2);
            animation-delay: -4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        /* Hero Content */
        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 500px;
        }

        .hero-logos {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2.5rem;
        }

        .hero-logo-combined {
            max-width: 320px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 0.8s ease forwards;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
        }

        .hero-logo-combined:hover {
            transform: scale(1.05);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 50%, #f0abfc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            animation: fadeInUp 0.8s ease 0.2s forwards;
            opacity: 0;
            text-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 0.3em;
            text-transform: uppercase;
            margin-bottom: 3rem;
            animation: fadeInUp 0.8s ease 0.3s forwards;
            opacity: 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .hero-features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            animation: fadeInUp 0.8s ease 0.4s forwards;
            opacity: 0;
        }

        .feature-tag {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .feature-tag i {
            color: var(--color-primary);
        }

        .hero-footer {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            text-align: center;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
        }

        /* ═══════════════════════════════════════════════════════════════
           RIGHT SIDE - LOGIN SECTION (40%)
           ═══════════════════════════════════════════════════════════════ */
        .login-section {
            flex: 0 0 40%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: linear-gradient(135deg, #12101f 0%, #1a1830 100%);
            position: relative;
            overflow: hidden;
        }

        /* Subtle Background Pattern */
        .login-bg-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(102, 126, 234, 0.08) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: 0;
        }

        /* Login Card */
        .login-card {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 3rem;
            background: rgba(30, 28, 50, 0.7);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: scaleIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            background: var(--gradient-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .login-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Alert Messages */
        .alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
            margin-left: 0.25rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            padding-right: 3rem;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            color: #ffffff; /* Explicit white */
            background: rgba(255, 255, 255, 0.15); /* More visible background */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Stronger border */
            border-radius: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.7); /* Lighter placeholder */
        }

        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .input-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
            font-size: 1rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            padding: 0.5rem;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: white;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1.1rem 2rem;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            background: var(--gradient-primary);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: translateX(4px);
        }

        /* Footer Link */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-link:hover {
            color: white;
        }

        .forgot-link:hover i {
            transform: translateX(4px);
        }

        .forgot-link i {
            font-size: 0.75rem;
            transition: transform 0.3s ease;
        }

        /* Security Badge */
        .security-badge {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.25);
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        .security-badge i {
            color: #38ef7d;
            font-size: 0.6rem;
        }

        /* ═══════════════════════════════════════════════════════════════
           ANIMATIONS
           ═══════════════════════════════════════════════════════════════ */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ═══════════════════════════════════════════════════════════════
           RESPONSIVE DESIGN
           ═══════════════════════════════════════════════════════════════ */
        @media (max-width: 1024px) {
            .hero-section {
                display: none;
            }
            
            .login-section {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .login-section {
                padding: 1.5rem;
            }
            
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- ═══════════════════════════════════════════════════════════════
         LEFT SIDE - HERO SECTION
         ═══════════════════════════════════════════════════════════════ -->
    <section class="hero-section">
        <!-- Background Elements -->
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>
        
        <!-- Floating Orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        
        <!-- Content -->
        <div class="hero-content">
            <div class="hero-logos">
                <img src="<?= url('public/assets/img/login-logo.jpg') ?>" alt="CSIR-SERC Logo" class="hero-logo-combined">
            </div>
            
            <h1 class="hero-title">CSIR-SERC</h1>
            <p class="hero-subtitle">Asset Management System</p>
            
            <div class="hero-features">
                <div class="feature-tag">
                    <i class="fas fa-qrcode"></i>
                    <span>QR Tracking</span>
                </div>
                <div class="feature-tag">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </div>
                <div class="feature-tag">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transfers</span>
                </div>
                <div class="feature-tag">
                    <i class="fas fa-file-export"></i>
                    <span>Reports</span>
                </div>
            </div>

            <div class="hero-stats" style="margin-top: 3rem; display: flex; gap: 40px; animation: fadeInUp 0.8s ease 0.5s forwards; opacity: 0;">
                <div class="stat-item" style="text-align: center;">
                    <span style="display: block; font-size: 2.5rem; font-weight: 800; color: #fff; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">15k+</span>
                    <span style="font-size: 0.85rem; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Assets Tracked</span>
                </div>
                <div class="stat-item" style="text-align: center;">
                    <span style="display: block; font-size: 2.5rem; font-weight: 800; color: #fff; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">₹45Cr+</span>
                    <span style="font-size: 0.85rem; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Asset Value</span>
                </div>
                <div class="stat-item" style="text-align: center;">
                    <span style="display: block; font-size: 2.5rem; font-weight: 800; color: #fff; text-shadow: 0 4px 10px rgba(0,0,0,0.3);">12</span>
                    <span style="font-size: 0.85rem; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Departments</span>
                </div>
            </div>
        </div>
        
        <div class="hero-footer">
            Powered by CSIR-SERC Chennai
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════
         RIGHT SIDE - LOGIN SECTION
         ═══════════════════════════════════════════════════════════════ -->
    <section class="login-section">
        <div class="login-bg-pattern"></div>
        
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Sign in to your account</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= Security::escape($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= Security::escape($success) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="">
                <?= Security::csrfField() ?>

                <div class="form-group">
                    <label class="form-label">AMS ID</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            name="ams_id" 
                            class="form-input" 
                            placeholder="Enter your AMS ID"
                            value="<?= Security::escape($_POST['ams_id'] ?? '') ?>"
                            required
                            autocomplete="username"
                        >
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i id="eyeIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Sign In to Dashboard</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-footer">
                <a href="<?= url('public/forgot-password.php') ?>" class="forgot-link">
                    <span>Forgot your password?</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="security-badge">
            <i class="fas fa-lock"></i>
            <span>256-bit SSL Encrypted</span>
        </div>
    </section>

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

        // Add subtle parallax effect to orbs
        document.addEventListener('mousemove', (e) => {
            const orbs = document.querySelectorAll('.orb');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            orbs.forEach((orb, index) => {
                const speed = (index + 1) * 10;
                const xOffset = (x - 0.5) * speed;
                const yOffset = (y - 0.5) * speed;
                orb.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
            });
        });
    </script>
</body>

</html>