<?php
/**
 * Forgot Password Page
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="glass-card rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-8 text-center">
                <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h1 class="text-xl font-bold text-white">Forgot Password</h1>
                <p class="text-blue-200 text-sm mt-1">Enter your email to reset password</p>
            </div>

            <div class="p-8">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
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

                <form method="POST">
                    <?= Security::csrfField() ?>

                    <div class="mb-6">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope text-blue-600 mr-2"></i>Email Address
                        </label>
                        <input type="email" name="email" id="email" required placeholder="Enter your registered email"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send Reset Link
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>