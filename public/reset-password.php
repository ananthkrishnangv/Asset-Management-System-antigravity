<?php
/**
 * CSIR-SERC Asset Management System
 * Reset Password Page - Premium Glassmorphism Design
 * Version 2.0
 */

require_once __DIR__ . '/../bootstrap.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $result = Auth::resetPassword($token, $password);

            if ($result['success']) {
                header('Location: index.php?reset=success');
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CSIR-SERC AMS</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --font-primary: 'Noto Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --color-primary: #667eea;
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

        /* LEFT SIDE - HERO */
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

        .hero-bg {
            position: absolute;
            inset: 0;
            background-color: #0f0c29;
            background-image: 
                radial-gradient(at 20% 30%, rgba(17, 153, 142, 0.5) 0px, transparent 50%),
                radial-gradient(at 80% 20%, rgba(56, 239, 125, 0.4) 0px, transparent 50%),
                radial-gradient(at 10% 80%, rgba(102, 126, 234, 0.3) 0px, transparent 50%),
                radial-gradient(at 90% 70%, rgba(118, 75, 162, 0.35) 0px, transparent 50%);
            z-index: 0;
        }

        .hero-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 1;
        }

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
            top: 15%;
            left: 20%;
            background: rgba(17, 153, 142, 0.4);
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            bottom: 25%;
            right: 15%;
            background: rgba(56, 239, 125, 0.35);
            animation-delay: -3s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        .hero-content {
            position: relative;
            z-index: 10;
            text-align: center;
        }

        .hero-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            background: var(--gradient-success);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 40px rgba(56, 239, 125, 0.4);
            animation: fadeInUp 0.8s ease forwards;
        }

        .hero-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #86efac 50%, #38ef7d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            animation: fadeInUp 0.8s ease 0.1s forwards;
            opacity: 0;
        }

        .hero-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.6);
            animation: fadeInUp 0.8s ease 0.2s forwards;
            opacity: 0;
        }

        .hero-footer {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
        }

        /* RIGHT SIDE - FORM */
        .form-section {
            flex: 0 0 40%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: linear-gradient(135deg, #12101f 0%, #1a1830 100%);
            position: relative;
        }

        .form-bg-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(56, 239, 125, 0.06) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: 0;
        }

        .form-card {
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

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 1.5rem;
            background: var(--gradient-success);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
        }

        .form-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Alert */
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

        /* Form Elements */
        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.75rem;
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
            color: white;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .form-input:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .form-input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #38ef7d;
            box-shadow: 0 0 0 4px rgba(56, 239, 125, 0.15);
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

        /* Password Requirements */
        .password-hint {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .password-hint i {
            font-size: 0.65rem;
            color: rgba(56, 239, 125, 0.6);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1.1rem 2rem;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            background: var(--gradient-success);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(56, 239, 125, 0.5);
        }

        .btn-submit i {
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: scale(1.1);
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

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-section {
                display: none;
            }
            .form-section {
                flex: 1;
            }
        }

        @media (max-width: 480px) {
            .form-section {
                padding: 1.5rem;
            }
            .form-card {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- LEFT SIDE - HERO SECTION -->
    <section class="hero-section">
        <div class="hero-bg"></div>
        <div class="hero-grid"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        
        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="hero-title">Set New Password</h1>
            <p class="hero-subtitle">Create a strong password to secure your account</p>
        </div>
        
        <div class="hero-footer">
            CSIR-SERC Chennai • Asset Management System
        </div>
    </section>

    <!-- RIGHT SIDE - FORM SECTION -->
    <section class="form-section">
        <div class="form-bg-pattern"></div>
        
        <div class="form-card">
            <div class="form-header">
                <div class="form-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 class="form-title">Reset Password</h2>
                <p class="form-subtitle">Enter your new password below</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= Security::escape($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <?= Security::csrfField() ?>

                <div class="input-group">
                    <label class="input-label">New Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="form-input" 
                            placeholder="Enter new password"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password', 'eyeIcon1')">
                            <i id="eyeIcon1" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="password-hint">
                        <i class="fas fa-info-circle"></i>
                        Minimum 8 characters required
                    </p>
                </div>

                <div class="input-group">
                    <label class="input-label">Confirm Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password"
                            class="form-input" 
                            placeholder="Confirm new password"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'eyeIcon2')">
                            <i id="eyeIcon2" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-check-circle"></i>
                    <span>Reset Password</span>
                </button>
            </form>
        </div>

        <div class="security-badge">
            <i class="fas fa-lock"></i>
            <span>256-bit SSL Encrypted</span>
        </div>
    </section>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
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