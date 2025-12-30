<?php
/**
 * Reports Dashboard
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();

// Get report data
$dateFrom = $_GET['from'] ?? date('Y-m-01');
$dateTo = $_GET['to'] ?? date('Y-m-d');

// Monthly statistics
$newEntries = $db->fetchValue(
    "SELECT COUNT(*) FROM inventory_items WHERE DATE(created_at) BETWEEN ? AND ?",
    [$dateFrom, $dateTo]
) ?? 0;

$transfers = $db->fetchValue(
    "SELECT COUNT(*) FROM transfer_requests WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'",
    [$dateFrom, $dateTo]
) ?? 0;

$returns = $db->fetchValue(
    "SELECT COUNT(*) FROM stores_returns WHERE DATE(created_at) BETWEEN ? AND ?",
    [$dateFrom, $dateTo]
) ?? 0;

// Items by category
$byCategory = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count, SUM(i.amount) as total_value
     FROM categories c
     LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
     GROUP BY c.id, c.name"
);

// Items by age
$byAge = $db->fetchAll(
    "SELECT 
        CASE 
            WHEN YEAR(NOW()) - YEAR(purchase_date) = 0 THEN 'Current Year'
            WHEN YEAR(NOW()) - YEAR(purchase_date) BETWEEN 1 AND 2 THEN '1-2 Years'
            WHEN YEAR(NOW()) - YEAR(purchase_date) BETWEEN 3 AND 5 THEN '3-5 Years'
            WHEN YEAR(NOW()) - YEAR(purchase_date) > 5 THEN '5+ Years'
            ELSE 'Unknown'
        END as age_group,
        COUNT(*) as count,
        SUM(amount) as total_value
     FROM inventory_items 
     WHERE is_active = 1 AND purchase_date IS NOT NULL
     GROUP BY age_group"
);

// Items by department
$byDepartment = $db->fetchAll(
    "SELECT d.name, COUNT(i.id) as count, SUM(i.amount) as total_value
     FROM departments d
     LEFT JOIN inventory_items i ON d.id = i.department_id AND i.is_active = 1
     GROUP BY d.id, d.name"
);

// Items by condition
$byCondition = $db->fetchAll(
    "SELECT condition_status, COUNT(*) as count 
     FROM inventory_items WHERE is_active = 1 
     GROUP BY condition_status"
);

$pageTitle = 'Reports';
$pageSubtitle = 'Analytics and reporting dashboard';

ob_start();
?>

<!-- Date Filter -->
<div class="bg-white rounded-2xl p-4 mb-6 card-shadow">
    <form method="GET" class="flex items-center gap-4">
        <label class="text-sm font-medium text-gray-700">Date Range:</label>
        <input type="date" name="from" value="<?= $dateFrom ?>"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span>to</span>
        <input type="date" name="to" value="<?= $dateTo ?>"
            class="px-4 py-2 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
            <i class="fas fa-filter mr-1"></i> Apply
        </button>

        <?php if (Auth::isSupervisor()): ?>
            <div class="ml-auto flex gap-2">
                <a href="export.php?type=dir" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700">
                    <i class="fas fa-file-excel mr-1"></i> Export DIR
                </a>
                <a href="export.php?type=pir" class="px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700">
                    <i class="fas fa-file-excel mr-1"></i> Export PIR
                </a>
                <a href="export.php?type=pdf" class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- Summary Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">New Entries</p>
                <p class="text-4xl font-bold mt-1"><?= number_format($newEntries) ?></p>
            </div>
            <i class="fas fa-plus-circle text-4xl text-blue-300"></i>
        </div>
        <p class="text-blue-100 text-xs mt-4"><?= formatDate($dateFrom) ?> - <?= formatDate($dateTo) ?></p>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Completed Transfers</p>
                <p class="text-4xl font-bold mt-1"><?= number_format($transfers) ?></p>
            </div>
            <i class="fas fa-exchange-alt text-4xl text-green-300"></i>
        </div>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">Stores Returns</p>
                <p class="text-4xl font-bold mt-1"><?= number_format($returns) ?></p>
            </div>
            <i class="fas fa-warehouse text-4xl text-purple-300"></i>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm">Total Inventory</p>
                <p class="text-4xl font-bold mt-1">
                    <?= number_format($db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1")) ?>
                </p>
            </div>
            <i class="fas fa-boxes text-4xl text-orange-300"></i>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Category</h3>
        <canvas id="categoryChart" height="250"></canvas>
    </div>

    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Age</h3>
        <canvas id="ageChart" height="250"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Department</h3>
        <canvas id="deptChart" height="250"></canvas>
    </div>

    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Condition</h3>
        <canvas id="conditionChart" height="250"></canvas>
    </div>
</div>

<!-- Tables -->
<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl card-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Category-wise Value</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Count</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Total Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($byCategory as $cat): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3"><?= Security::escape($cat['name']) ?></td>
                        <td class="px-6 py-3 text-right"><?= number_format($cat['count']) ?></td>
                        <td class="px-6 py-3 text-right font-medium"><?= formatCurrency($cat['total_value'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl card-shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-800">Age-wise Distribution</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">Age Group</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Count</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">Total Value</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($byAge as $age): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3"><?= Security::escape($age['age_group']) ?></td>
                        <td class="px-6 py-3 text-right"><?= number_format($age['count']) ?></td>
                        <td class="px-6 py-3 text-right font-medium"><?= formatCurrency($age['total_value'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280', '#ec4899', '#14b8a6'];

    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($byCategory, 'name')) ?>,
            datasets: [{ data: <?= json_encode(array_column($byCategory, 'count')) ?>, backgroundColor: colors, borderWidth: 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'right' } } }
    });

    new Chart(document.getElementById('ageChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($byAge, 'age_group')) ?>,
            datasets: [{ data: <?= json_encode(array_column($byAge, 'count')) ?>, backgroundColor: colors, borderWidth: 0 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'right' } } }
    });

    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($byDepartment, 'name')) ?>,
            datasets: [{ label: 'Items', data: <?= json_encode(array_column($byDepartment, 'count')) ?>, backgroundColor: '#3b82f6', borderRadius: 8 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    const conditionColors = { 'new': '#10b981', 'good': '#3b82f6', 'fair': '#f59e0b', 'poor': '#ef4444', 'non_serviceable': '#6b7280', 'scrapped': '#1f2937' };
    const conditionData = <?= json_encode($byCondition) ?>;
    new Chart(document.getElementById('conditionChart'), {
        type: 'bar',
        data: {
            labels: conditionData.map(c => c.condition_status),
            datasets: [{ data: conditionData.map(c => c.count), backgroundColor: conditionData.map(c => conditionColors[c.condition_status] || '#6b7280'), borderRadius: 8 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
