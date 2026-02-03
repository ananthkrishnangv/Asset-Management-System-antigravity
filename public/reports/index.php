<?php
/**
 * Reports & Analytics Dashboard
 * Modern Fluent UI Design with layout template
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();

// Summary Stats
$stats = [
    'total_dir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0,
    'total_pir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0,
    'total_value' => $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items WHERE is_active = 1") ?? 0,
    'pending_transfers' => $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor')") ?? 0,
];

// Department-wise distribution
$deptStats = $db->fetchAll(
    "SELECT d.name, d.code, COUNT(i.id) as count, COALESCE(SUM(i.unit_price), 0) as value
     FROM departments d
     LEFT JOIN inventory_items i ON d.id = i.department_id AND i.is_active = 1
     GROUP BY d.id, d.name, d.code
     ORDER BY count DESC"
) ?: [];

// Category-wise distribution
$catStats = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count, COALESCE(SUM(i.unit_price), 0) as value
     FROM categories c
     LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
     GROUP BY c.id, c.name
     ORDER BY count DESC"
) ?: [];

// Condition-wise distribution
$conditionStats = $db->fetchAll(
    "SELECT condition_status, COUNT(*) as count 
     FROM inventory_items WHERE is_active = 1 
     GROUP BY condition_status"
) ?: [];

// Monthly trend (last 12 months)
$monthlyTrend = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
            inventory_type,
            COUNT(*) as count
     FROM inventory_items 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m'), inventory_type
     ORDER BY month"
) ?: [];

// Old items (> 5 years)
$oldItemsCount = $db->fetchValue(
    "SELECT COUNT(*) FROM inventory_items 
     WHERE purchase_date < DATE_SUB(NOW(), INTERVAL 5 YEAR) AND is_active = 1"
) ?? 0;

// Prepare chart data
$deptChartData = json_encode(array_map(fn($d) => ['name' => $d['name'], 'count' => (int) $d['count'], 'value' => (float) $d['value']], $deptStats));
$catChartData = json_encode(array_map(fn($c) => ['name' => $c['name'], 'count' => (int) $c['count']], $catStats));
$conditionChartData = json_encode($conditionStats);

// Process monthly trend for chart
$months = [];
$dirCounts = [];
$pirCounts = [];
foreach ($monthlyTrend as $row) {
    if (!in_array($row['month'], $months)) {
        $months[] = $row['month'];
    }
}
sort($months);
foreach ($months as $month) {
    $dirCounts[$month] = 0;
    $pirCounts[$month] = 0;
}
foreach ($monthlyTrend as $row) {
    if ($row['inventory_type'] === 'dir') {
        $dirCounts[$row['month']] = (int) $row['count'];
    } else {
        $pirCounts[$row['month']] = (int) $row['count'];
    }
}
$monthlyChartData = json_encode([
    'labels' => array_values($months),
    'dir' => array_values($dirCounts),
    'pir' => array_values($pirCounts)
]);

$pageTitle = 'Reports & Analytics';
$pageSubtitle = 'Asset Management Insights';
ob_start();
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
            <div
                class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg">
                <i class="fas fa-building text-white text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($stats['total_dir']) ?></p>
                <p class="text-sm text-gray-500 font-medium">DIR Items</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
            <div
                class="w-14 h-14 rounded-xl bg-gradient-to-br from-emerald-500 to-green-500 flex items-center justify-center shadow-lg">
                <i class="fas fa-user-tag text-white text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($stats['total_pir']) ?></p>
                <p class="text-sm text-gray-500 font-medium">PIR Items</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
            <div
                class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg">
                <i class="fas fa-rupee-sign text-white text-xl"></i>
            </div>
            <div>
                <p class="text-2xl font-bold text-gray-800"><?= formatCurrency($stats['total_value']) ?></p>
                <p class="text-sm text-gray-500 font-medium">Total Value</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
            <div
                class="w-14 h-14 rounded-xl bg-gradient-to-br from-rose-500 to-red-500 flex items-center justify-center shadow-lg">
                <i class="fas fa-clock text-white text-xl"></i>
            </div>
            <div>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($oldItemsCount) ?></p>
                <p class="text-sm text-gray-500 font-medium">Old Items (5+ yrs)</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 overflow-hidden">
    <!-- Charts Column -->
    <div class="xl:col-span-2 space-y-4 min-w-0">
        <!-- Monthly Trend -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                        <i class="fas fa-chart-line text-indigo-600"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Monthly Trend</span>
                </div>
                <span class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full font-medium">Last 12 Months</span>
            </div>
            <div class="p-4">
                <canvas id="monthlyChart" height="70"></canvas>
            </div>
        </div>

        <!-- Distribution Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-100 to-cyan-100 flex items-center justify-center">
                            <i class="fas fa-building text-blue-600"></i>
                        </div>
                        <span class="font-semibold text-gray-800">By Department</span>
                    </div>
                </div>
                <div class="p-4">
                    <canvas id="deptChart" height="160"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-100 to-emerald-100 flex items-center justify-center">
                            <i class="fas fa-tags text-green-600"></i>
                        </div>
                        <span class="font-semibold text-gray-800">By Category</span>
                    </div>
                </div>
                <div class="p-4">
                    <canvas id="catChart" height="160"></canvas>
                </div>
            </div>
        </div>

        <!-- Condition Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-100 to-yellow-100 flex items-center justify-center">
                        <i class="fas fa-heartbeat text-amber-600"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Asset Condition</span>
                </div>
            </div>
            <div class="p-4">
                <canvas id="conditionChart" height="60"></canvas>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="space-y-6">
        <!-- Export Options -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-100 to-pink-100 flex items-center justify-center">
                        <i class="fas fa-file-export text-purple-600"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Export Reports</span>
                </div>
            </div>
            <div class="p-3 space-y-2">
                <a href="<?= url('public/reports/export.php?type=dir&format=csv') ?>" class=" flex items-center gap-3 p-3 bg-gradient-to-r from-gray-50 to-white border border-gray-100
                    rounded-xl hover:border-green-300 hover:shadow-md transition-all group">
                    <div
                        class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center group-hover:bg-green-500 transition-colors">
                        <i class="fas fa-file-csv text-green-600 group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">DIR Inventory</p>
                        <p class="text-xs text-gray-500">Export as Excel/CSV</p>
                    </div>
                    <i class="fas fa-download ml-auto text-gray-300 group-hover:text-green-500"></i>
                </a>

                <a href="<?= url('public/reports/export.php?type=pir&format=csv') ?>" class=" flex items-center gap-3 p-3 bg-gradient-to-r from-gray-50 to-white border border-gray-100
                    rounded-xl hover:border-blue-300 hover:shadow-md transition-all group">
                    <div
                        class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-500 transition-colors">
                        <i class="fas fa-file-csv text-blue-600 group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">PIR Inventory</p>
                        <p class="text-xs text-gray-500">Export as Excel/CSV</p>
                    </div>
                    <i class="fas fa-download ml-auto text-gray-300 group-hover:text-blue-500"></i>
                </a>

                <a href="<?= url('public/reports/export.php?type=all&format=pdf') ?>" class=" flex items-center gap-3 p-3 bg-gradient-to-r from-gray-50 to-white border border-gray-100
                    rounded-xl hover:border-red-300 hover:shadow-md transition-all group">
                    <div
                        class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center group-hover:bg-red-500 transition-colors">
                        <i class="fas fa-file-pdf text-red-600 group-hover:text-white"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">Print Report</p>
                        <p class="text-xs text-gray-500">PDF with all assets</p>
                    </div>
                    <i class="fas fa-download ml-auto text-gray-300 group-hover:text-red-500"></i>
                </a>
            </div>
        </div>

        <!-- Department Summary Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-100 to-blue-100 flex items-center justify-center">
                        <i class="fas fa-table text-cyan-600"></i>
                    </div>
                    <span class="font-semibold text-gray-800">Department Summary</span>
                </div>
            </div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Dept</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Items</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach (array_slice($deptStats, 0, 8) as $dept): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800"><?= Security::escape($dept['code']) ?></td>
                                <td class="px-4 py-3 text-right text-gray-600"><?= number_format($dept['count']) ?></td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800">
                                    <?= formatCurrency($dept['value']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Monthly Trend Chart
    const monthlyData = <?= $monthlyChartData ?>;
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyGradient1 = monthlyCtx.createLinearGradient(0, 0, 0, 200);
    monthlyGradient1.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
    monthlyGradient1.addColorStop(1, 'rgba(99, 102, 241, 0.01)');
    const monthlyGradient2 = monthlyCtx.createLinearGradient(0, 0, 0, 200);
    monthlyGradient2.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
    monthlyGradient2.addColorStop(1, 'rgba(16, 185, 129, 0.01)');

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.labels.map(m => {
                const [year, month] = m.split('-');
                return new Date(year, month - 1).toLocaleDateString('en', { month: 'short', year: '2-digit' });
            }),
            datasets: [
                {
                    label: 'DIR',
                    data: monthlyData.dir,
                    borderColor: '#6366F1',
                    backgroundColor: monthlyGradient1,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366F1'
                },
                {
                    label: 'PIR',
                    data: monthlyData.pir,
                    borderColor: '#10B981',
                    backgroundColor: monthlyGradient2,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#10B981'
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Department Chart
    const deptData = <?= $deptChartData ?>;
    new Chart(document.getElementById('deptChart'), {
        type: 'doughnut',
        data: {
            labels: deptData.map(d => d.name),
            datasets: [{
                data: deptData.map(d => d.count),
                backgroundColor: ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } } }
        }
    });

    // Category Chart
    const catData = <?= $catChartData ?>;
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: {
            labels: catData.map(c => c.name),
            datasets: [{
                data: catData.map(c => c.count),
                backgroundColor: ['#3B82F6', '#22C55E', '#EAB308', '#EF4444', '#A855F7', '#14B8A6', '#F97316'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } } }
        }
    });

    // Condition Chart
    const conditionColors = {
        'new': '#10B981',
        'good': '#3B82F6',
        'fair': '#F59E0B',
        'poor': '#EF4444',
        'non_serviceable': '#6B7280',
        'scrapped': '#1F2937'
    };
    const conditionData = <?= $conditionChartData ?>;
    new Chart(document.getElementById('conditionChart'), {
        type: 'bar',
        data: {
            labels: conditionData.map(c => c.condition_status.replace('_', ' ').toUpperCase()),
            datasets: [{
                data: conditionData.map(c => c.count),
                backgroundColor: conditionData.map(c => conditionColors[c.condition_status] || '#6B7280'),
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.03)' } },
                y: { grid: { display: false } }
            }
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>