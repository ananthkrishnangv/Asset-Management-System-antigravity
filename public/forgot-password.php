<?php
/**
 * CSIR-SERC Asset Management System
 * Forgot Password Page - Premium Glassmorphism Design
 * Version 2.0
 */

require_once __DIR__ . '/../bootstrap.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = Security::sanitize($_POST['email'] ?? '');

        if (empty($email) || !Security::validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = Auth::generatePasswordResetToken($email);

            if ($result) {
                // Send reset email
                $mailResult = Mailer::sendPasswordReset($email, $result['token'], $result['user']['emp_name']);

                if ($mailResult['success']) {
                    $success = 'Password reset link has been sent to your email address.';
                } else {
                    $error = 'Failed to send email. Please try again or contact admin.';
                }
            } else {
                // Don't reveal if email exists or not
                $success = 'If the email exists, a password reset link will be sent.';
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
    <title>Forgot Password - CSIR-SERC AMS</title>
    
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
                radial-gradient(at 20% 30%, rgba(102, 126, 234, 0.5) 0px, transparent 50%),
                radial-gradient(at 80% 20%, rgba(118, 75, 162, 0.4) 0px, transparent 50%),
                radial-gradient(at 10% 80%, rgba(240, 147, 251, 0.3) 0px, transparent 50%),
                radial-gradient(at 90% 70%, rgba(79, 172, 254, 0.35) 0px, transparent 50%);
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
            background: rgba(102, 126, 234, 0.4);
        }

        .orb-2 {
            width: 250px;
            height: 250px;
            bottom: 25%;
            right: 15%;
            background: rgba(240, 147, 251, 0.35);
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
            background: var(--gradient-primary);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            animation: fadeInUp 0.8s ease forwards;
        }

        .hero-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #a5b4fc 50%, #f0abfc 100%);
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
            background-image: radial-gradient(rgba(102, 126, 234, 0.08) 1px, transparent 1px);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
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
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .input-icon {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.3);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 1.1rem 2rem;
            font-family: var(--font-primary);
            font-size: 0.95rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(240, 147, 251, 0.5);
        }

        .btn-submit i {
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: translateX(4px);
        }

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s ease;
        }

        .back-link a:hover {
            color: white;
        }

        .back-link a i {
            transition: transform 0.3s ease;
        }

        .back-link a:hover i {
            transform: translateX(-4px);
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
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h1 class="hero-title">Password Recovery</h1>
            <p class="hero-subtitle">We'll send you a secure link to reset your password</p>
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
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="form-title">Forgot Password?</h2>
                <p class="form-subtitle">Enter your email to receive reset link</p>
            </div>

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

            <form method="POST">
                <?= Security::csrfField() ?>

                <div class="input-group">
                    <label class="input-label">Email Address</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-input" 
                            placeholder="Enter your registered email"
                            required
                            autocomplete="email"
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Send Reset Link</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <div class="back-link">
                <a href="<?= url('public/index.php') ?>">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Login</span>
                </a>
            </div>
        </div>

        <div class="security-badge">
            <i class="fas fa-lock"></i>
            <span>256-bit SSL Encrypted</span>
        </div>
    </section>
</body>

</html>