<?php
/**
 * Analytics Dashboard
 * Advanced statistics and insights
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance();

// Get analytics data
$totalAssets = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1") ?? 0;
$totalDIR = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0;
$totalPIR = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0;
$totalValue = $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items WHERE is_active = 1") ?? 0;

// Assets by condition
$byCondition = $db->fetchAll("SELECT condition_status, COUNT(*) as count FROM inventory_items WHERE is_active = 1 GROUP BY condition_status");

// Assets by category
$byCategory = $db->fetchAll("SELECT c.name, COUNT(i.id) as count FROM categories c LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1 GROUP BY c.id, c.name ORDER BY count DESC LIMIT 10");

// Recent additions (last 30 days)
$recentAdded = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)") ?? 0;

// Old assets (5+ years)
$oldAssets = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE purchase_date < DATE_SUB(NOW(), INTERVAL 5 YEAR) AND is_active = 1") ?? 0;

// Transfer statistics
$transferStats = $db->fetch("SELECT 
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
    COUNT(CASE WHEN status IN ('pending_supervisor', 'pending_hod') THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
    FROM transfer_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");

// Assets by department
$byDepartment = $db->fetchAll("SELECT d.name, COUNT(i.id) as count FROM departments d LEFT JOIN inventory_items i ON d.id = i.department_id AND i.is_active = 1 GROUP BY d.id, d.name ORDER BY count DESC LIMIT 8");

// Monthly trend
$monthlyTrend = $db->fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count FROM inventory_items WHERE created_at > DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");

$pageTitle = 'Analytics Dashboard';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        .stat-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .chart-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="min-h-screen text-white">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold"><?= $pageTitle ?></h1>
                <p class="text-slate-400">Real-time insights and statistics</p>
            </div>
            <a href="<?= url('admin/dashboard.php') ?>" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>

        <!-- Key Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="stat-card rounded-2xl p-6">
                <div class="text-slate-400 text-sm mb-2">Total Assets</div>
                <div class="text-3xl font-bold text-white"><?= number_format($totalAssets) ?></div>
                <div class="text-green-400 text-xs mt-2"><i class="fas fa-arrow-up mr-1"></i><?= $recentAdded ?> this month</div>
            </div>
            <div class="stat-card rounded-2xl p-6">
                <div class="text-slate-400 text-sm mb-2">DIR Items</div>
                <div class="text-3xl font-bold text-blue-400"><?= number_format($totalDIR) ?></div>
            </div>
            <div class="stat-card rounded-2xl p-6">
                <div class="text-slate-400 text-sm mb-2">PIR Items</div>
                <div class="text-3xl font-bold text-green-400"><?= number_format($totalPIR) ?></div>
            </div>
            <div class="stat-card rounded-2xl p-6">
                <div class="text-slate-400 text-sm mb-2">Total Value</div>
                <div class="text-3xl font-bold text-yellow-400">₹<?= number_format($totalValue / 100000, 1) ?>L</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="chart-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">Assets by Condition</h3>
                <canvas id="conditionChart" height="250"></canvas>
            </div>
            <div class="chart-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">Monthly Trend</h3>
                <canvas id="trendChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="chart-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">Top Categories</h3>
                <canvas id="categoryChart" height="200"></canvas>
            </div>
            <div class="chart-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">By Department</h3>
                <canvas id="deptChart" height="200"></canvas>
            </div>
            <div class="chart-card rounded-2xl p-6">
                <h3 class="text-lg font-bold mb-4">Transfer Activity</h3>
                <div class="space-y-4 mt-6">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">Completed</span>
                        <span class="text-green-400 font-bold"><?= $transferStats['completed'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">Pending</span>
                        <span class="text-yellow-400 font-bold"><?= $transferStats['pending'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400">Rejected</span>
                        <span class="text-red-400 font-bold"><?= $transferStats['rejected'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <div class="chart-card rounded-2xl p-6">
            <h3 class="text-lg font-bold mb-4"><i class="fas fa-exclamation-triangle text-yellow-400 mr-2"></i>Attention Required</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4">
                    <div class="text-2xl font-bold text-red-400"><?= $oldAssets ?></div>
                    <div class="text-sm text-red-300">Assets over 5 years old</div>
                </div>
                <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4">
                    <div class="text-2xl font-bold text-yellow-400"><?= $transferStats['pending'] ?? 0 ?></div>
                    <div class="text-sm text-yellow-300">Pending transfers</div>
                </div>
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                    <div class="text-2xl font-bold text-blue-400"><?= $recentAdded ?></div>
                    <div class="text-sm text-blue-300">New items (30 days)</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const conditionData = <?= json_encode($byCondition) ?>;
        const categoryData = <?= json_encode($byCategory) ?>;
        const deptData = <?= json_encode($byDepartment) ?>;
        const trendData = <?= json_encode($monthlyTrend) ?>;

        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.1)';

        new Chart(document.getElementById('conditionChart'), {
            type: 'doughnut',
            data: {
                labels: conditionData.map(c => c.condition_status),
                datasets: [{
                    data: conditionData.map(c => c.count),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#6b7280']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trendData.map(t => t.month),
                datasets: [{
                    label: 'Assets Added',
                    data: trendData.map(t => t.count),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true }
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryData.map(c => c.name),
                datasets: [{
                    data: categoryData.map(c => c.count),
                    backgroundColor: '#3b82f6'
                }]
            },
            options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('deptChart'), {
            type: 'polarArea',
            data: {
                labels: deptData.map(d => d.name),
                datasets: [{
                    data: deptData.map(d => d.count),
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316']
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
