<?php
/**
 * Kiosk Dashboard - Light Fluent UI
 * Touch-optimized, mobile-responsive interface
 */
require_once __DIR__ . '/../bootstrap.php';

// Check for kiosk session authentication or standard auth
$isKioskAuth = isset($_SESSION['kiosk_authenticated']) && $_SESSION['kiosk_authenticated'] === true;
$isUserAuth = Auth::check();

if (!$isKioskAuth && !$isUserAuth) {
    header('Location: ' . url('public/kiosk-login.php'));
    exit;
}

if ($isUserAuth && !$isKioskAuth) {
    if (!Auth::isAdmin() && !Auth::isSupervisor()) {
        redirect(url('public/dashboard.php'));
    }
}

$db = Database::getInstance();

// Get statistics
$stats = [
    'total_dir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0,
    'total_pir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0,
    'total_value' => $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items WHERE is_active = 1") ?? 0,
    'pending_transfers' => $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor')") ?? 0,
    'pending_returns' => $db->fetchValue("SELECT COUNT(*) FROM stores_returns WHERE status = 'pending_approval'") ?? 0,
    'total_users' => $db->fetchValue("SELECT COUNT(*) FROM users WHERE is_active = 1") ?? 0,
];

// Weekly stats
$thisWeek = [
    'added' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0,
    'returns' => $db->fetchValue("SELECT COUNT(*) FROM stores_returns WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0,
    'transfers' => $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0,
];

// Monthly data for chart
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
     FROM inventory_items 
     WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month"
) ?: [];

// High value items
$highValueItems = $db->fetchAll(
    "SELECT i.serial_number, i.item_description, i.unit_price, d.code as dept_code
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     WHERE i.is_active = 1 AND i.unit_price >= 100000
     ORDER BY i.unit_price DESC LIMIT 6"
) ?: [];

// Recent transfers
$recentTransfers = $db->fetchAll(
    "SELECT tr.*, i.serial_number, i.item_description, 
            fu.emp_name as from_name, tu.emp_name as to_name,
            fd.code as from_dept, td.code as to_dept
     FROM transfer_requests tr
     LEFT JOIN inventory_items i ON tr.item_id = i.id
     LEFT JOIN users fu ON tr.from_user_id = fu.id
     LEFT JOIN users tu ON tr.to_user_id = tu.id
     LEFT JOIN departments fd ON tr.from_department_id = fd.id
     LEFT JOIN departments td ON tr.to_department_id = td.id
     ORDER BY tr.created_at DESC LIMIT 5"
) ?: [];

// Today's items
$todayItems = $db->fetchAll(
    "SELECT i.serial_number, i.item_description, i.unit_price, d.code as dept_code
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     WHERE i.is_active = 1 AND DATE(i.created_at) = CURDATE()
     ORDER BY i.created_at DESC LIMIT 5"
) ?: [];

// Active ticker items
$tickerItems = $db->fetchAll(
    "SELECT * FROM news_ticker WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 10"
) ?: [];

$monthlyJson = json_encode($monthlyData);
$badgeColors = [
    'important' => '#ef4444',
    'new' => '#22c55e',
    'alert' => '#f97316',
    'warning' => '#eab308',
    'info' => '#3b82f6'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Kiosk Dashboard - CSIR-SERC AMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card.blue {
            background: linear-gradient(135deg, #EBF4FF 0%, #F0F7FF 100%);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .stat-card.green {
            background: linear-gradient(135deg, #ECFDF5 0%, #F0FDF4 100%);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .stat-card.amber {
            background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
            border-color: rgba(245, 158, 11, 0.2);
        }

        .stat-card.purple {
            background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
            border-color: rgba(139, 92, 246, 0.2);
        }

        .stat-card.pink {
            background: linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 100%);
            border-color: rgba(236, 72, 153, 0.2);
        }

        .data-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 0.5rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .ticker-container {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }

        .ticker-content {
            display: flex;
            animation: scroll-left 30s linear infinite;
            white-space: nowrap;
        }

        .ticker-content:hover {
            animation-play-state: paused;
        }

        @keyframes scroll-left {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @media (max-width: 768px) {
            .kiosk-grid {
                grid-template-columns: 1fr !important;
            }

            .stat-value {
                font-size: 1.5rem !important;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 via-white to-gray-100 min-h-screen">
    <!-- Ticker -->
    <?php if (!empty($tickerItems)): ?>
        <div class="ticker-container py-2">
            <div class="ticker-content">
                <?php for ($i = 0; $i < 2; $i++): ?>
                    <?php foreach ($tickerItems as $item): ?>
                        <span class="inline-flex items-center gap-2 px-6 text-white text-sm">
                            <span class="px-2 py-0.5 rounded text-xs font-bold uppercase"
                                style="background: <?= $badgeColors[$item['badge_type']] ?? '#3b82f6' ?>">
                                <?= ucfirst($item['badge_type']) ?>
                            </span>
                            <span class="font-medium"><?= Security::escape($item['title']) ?></span>
                            <span class="mx-8 opacity-50">•</span>
                        </span>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="p-4 md:p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <header
            class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6 p-5 bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4">
                <img src="<?= url('Image/portal-logo.png') ?>" alt="CSIR-SERC AMS" class="h-16 md:h-20 object-contain">
            </div>
            <div class="text-center md:text-right">
                <div id="clock" class="text-3xl md:text-4xl font-light text-gray-800"></div>
                <div id="date" class="text-gray-500 text-sm"></div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <div class="stat-card blue">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <i class="fas fa-boxes text-blue-600"></i>
                    </div>
                </div>
                <p class="stat-value text-2xl md:text-3xl font-bold text-gray-800">
                    <?= number_format($stats['total_dir']) ?>
                </p>
                <p class="text-gray-500 text-sm mt-1">DIR Items</p>
            </div>

            <div class="stat-card green">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                        <i class="fas fa-user-tag text-green-600"></i>
                    </div>
                </div>
                <p class="stat-value text-2xl md:text-3xl font-bold text-gray-800">
                    <?= number_format($stats['total_pir']) ?>
                </p>
                <p class="text-gray-500 text-sm mt-1">PIR Items</p>
            </div>

            <div class="stat-card amber">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <i class="fas fa-exchange-alt text-amber-600"></i>
                    </div>
                </div>
                <p class="stat-value text-2xl md:text-3xl font-bold text-gray-800">
                    <?= number_format($stats['pending_transfers']) ?>
                </p>
                <p class="text-gray-500 text-sm mt-1">Transfers</p>
            </div>

            <div class="stat-card purple">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                        <i class="fas fa-undo text-purple-600"></i>
                    </div>
                </div>
                <p class="stat-value text-2xl md:text-3xl font-bold text-gray-800">
                    <?= number_format($stats['pending_returns']) ?>
                </p>
                <p class="text-gray-500 text-sm mt-1">Returns</p>
            </div>

            <div class="stat-card pink col-span-2 md:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center">
                        <i class="fas fa-rupee-sign text-pink-600"></i>
                    </div>
                </div>
                <p class="stat-value text-xl md:text-2xl font-bold text-gray-800">
                    <?= formatCurrency($stats['total_value']) ?>
                </p>
                <p class="text-gray-500 text-sm mt-1">Total Value</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 kiosk-grid">
            <!-- Left: Chart + Weekly Stats -->
            <div class="space-y-6">
                <div class="data-card p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-line text-blue-500"></i>
                        Monthly Trend
                    </h3>
                    <div class="h-48">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <div class="data-card p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-week text-green-500"></i>
                        This Week
                    </h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-green-50 rounded-xl border border-green-100">
                            <p class="text-2xl font-bold text-green-600"><?= $thisWeek['added'] ?></p>
                            <p class="text-xs text-gray-500 mt-1">Added</p>
                        </div>
                        <div class="text-center p-3 bg-amber-50 rounded-xl border border-amber-100">
                            <p class="text-2xl font-bold text-amber-600"><?= $thisWeek['returns'] ?></p>
                            <p class="text-xs text-gray-500 mt-1">Returns</p>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-xl border border-blue-100">
                            <p class="text-2xl font-bold text-blue-600"><?= $thisWeek['transfers'] ?></p>
                            <p class="text-xs text-gray-500 mt-1">Transfers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle: Transfers + Today's Items -->
            <div class="space-y-6">
                <div class="data-card p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-exchange-alt text-purple-500"></i>
                        Recent Transfers
                    </h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <?php if (empty($recentTransfers)): ?>
                            <div class="text-center py-6 text-gray-400">
                                <i class="fas fa-exchange-alt text-2xl mb-2"></i>
                                <p class="text-sm">No recent transfers</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentTransfers as $tr): ?>
                                <div class="activity-item">
                                    <div class="avatar bg-gradient-to-br from-indigo-500 to-purple-500 text-white">
                                        <?= strtoupper(substr($tr['from_name'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">
                                            <?= Security::escape(truncate($tr['item_description'] ?? '-', 25)) ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?= Security::escape($tr['from_dept'] ?? '-') ?> →
                                            <?= Security::escape($tr['to_dept'] ?? '-') ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="data-card p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-day text-cyan-500"></i>
                        Added Today
                    </h3>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        <?php if (empty($todayItems)): ?>
                            <div class="text-center py-6 text-gray-400">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p class="text-sm">No items added today</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($todayItems as $item): ?>
                                <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg border border-gray-100">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800 truncate">
                                            <?= Security::escape(truncate($item['item_description'] ?? '-', 25)) ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?= Security::escape($item['serial_number'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-lg font-medium">
                                        <?= Security::escape($item['dept_code'] ?? '-') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: High Value Items -->
            <div class="data-card p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-gem text-amber-500"></i>
                    High Value Assets (₹1L+)
                </h3>
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    <?php if (empty($highValueItems)): ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fas fa-gem text-3xl mb-3"></i>
                            <p>No high-value items</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($highValueItems as $item): ?>
                            <div class="p-3 bg-gradient-to-r from-amber-50 to-yellow-50 rounded-xl border border-amber-100">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-gray-800 truncate">
                                            <?= Security::escape(truncate($item['item_description'] ?? '-', 30)) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?= Security::escape($item['serial_number'] ?? '-') ?> •
                                            <?= Security::escape($item['dept_code'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <p class="text-amber-600 font-bold whitespace-nowrap ml-3">
                                        <?= formatCurrency($item['unit_price'] ?? 0) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Indicator -->
    <div
        class="fixed bottom-4 right-4 flex items-center gap-2 px-4 py-2 bg-white shadow-lg rounded-full text-sm text-gray-600 border border-gray-200">
        <span class="pulse w-2 h-2 bg-green-500 rounded-full"></span>
        Auto-refresh 60s
    </div>

    <script>
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-IN', {
                hour: '2-digit', minute: '2-digit', hour12: true
            });
            document.getElementById('date').textContent = now.toLocaleDateString('en-IN', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Monthly Chart
        const monthlyData = <?= $monthlyJson ?>;
        const ctx = document.getElementById('monthlyChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 180);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => {
                    const [year, month] = d.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('en', { month: 'short' });
                }),
                datasets: [{
                    data: monthlyData.map(d => d.count),
                    borderColor: '#6366F1',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366F1',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.03)' },
                        ticks: { color: '#9CA3AF' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#9CA3AF' }
                    }
                }
            }
        });

        // Auto refresh every 60 seconds
        setTimeout(() => location.reload(), 60000);
    </script>
</body>

</html>