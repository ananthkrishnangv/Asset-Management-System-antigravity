<?php
/**
 * Barcode/QR Scanner Page
 * Scan QR codes or barcodes to quickly find assets
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$pageTitle = 'Asset Scanner';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - CSIR-SERC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        #reader { width: 100%; max-width: 500px; margin: 0 auto; }
        #reader video { border-radius: 16px; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?= $pageTitle ?></h1>
                <p class="text-gray-600">Scan QR code or barcode to find asset</p>
            </div>
            <a href="<?= url('public/dashboard.php') ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>

        <div class="max-w-2xl mx-auto">
            <!-- Scanner Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-camera text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Camera Scanner</h2>
                        <p class="text-sm text-gray-500">Point camera at QR code or barcode</p>
                    </div>
                </div>
                
                <div id="reader" class="mb-4"></div>
                
                <div class="text-center mt-4">
                    <button id="startBtn" onclick="startScanner()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                        <i class="fas fa-play mr-2"></i> Start Scanner
                    </button>
                    <button id="stopBtn" onclick="stopScanner()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors hidden">
                        <i class="fas fa-stop mr-2"></i> Stop Scanner
                    </button>
                </div>
            </div>

            <!-- Manual Entry -->
            <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
                <h3 class="font-bold text-gray-800 mb-4">Or Enter Manually</h3>
                <form action="<?= url('public/inventory/item-details.php') ?>" method="GET" class="flex gap-3">
                    <input type="text" name="serial" placeholder="Enter Serial Number or Asset ID" 
                           class="flex-1 px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-colors">
                        <i class="fas fa-search mr-2"></i> Find
                    </button>
                </form>
            </div>

            <!-- Result Display -->
            <div id="result" class="bg-green-50 border border-green-200 rounded-2xl p-6 hidden">
                <div class="flex items-center gap-3 mb-4">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    <h3 class="font-bold text-green-800">Scan Successful!</h3>
                </div>
                <p class="text-gray-700 mb-4">Detected: <span id="scannedText" class="font-mono bg-white px-2 py-1 rounded"></span></p>
                <a id="viewLink" href="#" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold inline-block transition-colors">
                    <i class="fas fa-eye mr-2"></i> View Asset Details
                </a>
            </div>
        </div>
    </div>

    <script>
        let html5QrCode = null;
        
        function startScanner() {
            html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess,
                onScanFailure
            ).then(() => {
                document.getElementById('startBtn').classList.add('hidden');
                document.getElementById('stopBtn').classList.remove('hidden');
            }).catch(err => {
                console.error('Scanner error:', err);
                alert('Could not start camera. Please ensure camera permissions are granted.');
            });
        }

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    document.getElementById('startBtn').classList.remove('hidden');
                    document.getElementById('stopBtn').classList.add('hidden');
                });
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            stopScanner();
            
            document.getElementById('result').classList.remove('hidden');
            document.getElementById('scannedText').textContent = decodedText;
            
            // Try to extract asset ID from URL or use as-is
            let url = decodedText;
            if (!url.includes('http')) {
                url = '<?= url('public/inventory/item-details.php') ?>?serial=' + encodeURIComponent(decodedText);
            }
            document.getElementById('viewLink').href = url;
            
            // Play success sound
            const audio = new Audio('data:audio/wav;base64,UklGRjIAAABXQVZFZm10IBIAAAABAAEAQB8AAEAfAAABAAgAAABmYWN0BAAAAAAAAABkYXRhAAAAAA==');
            audio.play().catch(() => {});
        }

        function onScanFailure(error) {
            // Silent fail for continuous scanning
        }
    </script>
</body>
</html>
