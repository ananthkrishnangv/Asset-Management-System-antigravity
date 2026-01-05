<?php
/**
 * Kiosk Login Page
 * Separate authentication for kiosk mode displays
 */
require_once __DIR__ . '/../bootstrap.php';

// If already authenticated via kiosk session, redirect
if (isset($_SESSION['kiosk_authenticated']) && $_SESSION['kiosk_authenticated'] === true) {
    header('Location: ' . url('public/kiosk.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kioskPin = $_POST['kiosk_pin'] ?? '';

    // Kiosk PIN from settings or default
    $validPin = defined('KIOSK_PIN') ? KIOSK_PIN : '1234';

    if ($kioskPin === $validPin) {
        $_SESSION['kiosk_authenticated'] = true;
        $_SESSION['kiosk_login_time'] = time();
        header('Location: ' . url('public/kiosk.php'));
        exit;
    } else {
        $error = 'Invalid Kiosk PIN. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiosk Login - CSIR-SERC AMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .pin-input {
            width: 60px;
            height: 70px;
            font-size: 2rem;
            text-align: center;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
        }

        .pin-input:focus {
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        @media (max-width: 640px) {
            .pin-input {
                width: 50px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="glass-card rounded-3xl p-8 md:p-12 w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="https://ir.serc.res.in/Image/logo-serc.jpg" alt="CSIR-SERC"
                class="w-20 h-20 mx-auto rounded-2xl shadow-lg mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Kiosk Mode</h1>
            <p class="text-gray-500 mt-2">Enter the 4-digit PIN to access the kiosk dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-center">
                <i class="fas fa-exclamation-circle mr-2"></i><?= Security::escape($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="kioskForm">
            <!-- PIN Input -->
            <div class="flex justify-center gap-3 mb-8">
                <input type="password" maxlength="1" class="pin-input" data-index="0" inputmode="numeric"
                    pattern="[0-9]" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" data-index="1" inputmode="numeric"
                    pattern="[0-9]" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" data-index="2" inputmode="numeric"
                    pattern="[0-9]" autocomplete="off">
                <input type="password" maxlength="1" class="pin-input" data-index="3" inputmode="numeric"
                    pattern="[0-9]" autocomplete="off">
            </div>
            <input type="hidden" name="kiosk_pin" id="kioskPin">

            <button type="submit"
                class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 rounded-xl font-semibold text-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                <i class="fas fa-unlock mr-2"></i>Access Kiosk
            </button>
        </form>

        <!-- Numeric Keypad for Touch -->
        <div class="grid grid-cols-3 gap-3 mt-8">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <button type="button"
                    class="keypad-btn bg-gray-100 hover:bg-gray-200 text-gray-800 text-2xl font-semibold py-4 rounded-xl transition-all"
                    data-num="<?= $i ?>">
                    <?= $i ?>
                </button>
            <?php endfor; ?>
            <button type="button"
                class="keypad-btn bg-red-100 hover:bg-red-200 text-red-600 text-xl font-semibold py-4 rounded-xl transition-all"
                data-action="clear">
                <i class="fas fa-eraser"></i>
            </button>
            <button type="button"
                class="keypad-btn bg-gray-100 hover:bg-gray-200 text-gray-800 text-2xl font-semibold py-4 rounded-xl transition-all"
                data-num="0">
                0
            </button>
            <button type="button"
                class="keypad-btn bg-yellow-100 hover:bg-yellow-200 text-yellow-700 text-xl font-semibold py-4 rounded-xl transition-all"
                data-action="backspace">
                <i class="fas fa-backspace"></i>
            </button>
        </div>

        <!-- Back to main login -->
        <div class="mt-8 text-center">
            <a href="<?= url('public/index.php') ?>" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Back to main login
            </a>
        </div>
    </div>

    <script>
        const pinInputs = document.querySelectorAll('.pin-input');
        const kioskPinField = document.getElementById('kioskPin');
        let currentIndex = 0;

        // Focus first input
        pinInputs[0].focus();

        // Handle input
        pinInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                if (value && index < 3) {
                    pinInputs[index + 1].focus();
                    currentIndex = index + 1;
                }
                updateHiddenField();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    pinInputs[index - 1].focus();
                    currentIndex = index - 1;
                }
            });
        });

        // Keypad buttons
        document.querySelectorAll('.keypad-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const num = btn.dataset.num;
                const action = btn.dataset.action;

                if (num !== undefined && currentIndex < 4) {
                    pinInputs[currentIndex].value = num;
                    if (currentIndex < 3) {
                        currentIndex++;
                        pinInputs[currentIndex].focus();
                    }
                } else if (action === 'clear') {
                    pinInputs.forEach(input => input.value = '');
                    currentIndex = 0;
                    pinInputs[0].focus();
                } else if (action === 'backspace' && currentIndex >= 0) {
                    if (pinInputs[currentIndex].value) {
                        pinInputs[currentIndex].value = '';
                    } else if (currentIndex > 0) {
                        currentIndex--;
                        pinInputs[currentIndex].value = '';
                        pinInputs[currentIndex].focus();
                    }
                }
                updateHiddenField();
            });
        });

        function updateHiddenField() {
            kioskPinField.value = Array.from(pinInputs).map(i => i.value).join('');
        }

        // Form submission
        document.getElementById('kioskForm').addEventListener('submit', () => {
            updateHiddenField();
        });
    </script>
</body>

</html>