<?php
/**
 * Dashboard Page
 * Statistics, charts, and quick actions
 */

require_once __DIR__ . '/../bootstrap.php';
Auth::requireAuth();

$db = Database::getInstance();
$user = Auth::user();

// Get statistics
$totalDIR = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0;
$totalPIR = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0;
$pendingTransfers = $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor')") ?? 0;
$pendingReturns = $db->fetchValue("SELECT COUNT(*) FROM stores_returns WHERE status = 'pending_approval'") ?? 0;

// Get recent activity
$recentActivity = $db->fetchAll(
    "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5"
);

// Get items by category
$itemsByCategory = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count 
     FROM categories c 
     LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
     GROUP BY c.id, c.name"
);

// Get items by condition
$itemsByCondition = $db->fetchAll(
    "SELECT condition_status, COUNT(*) as count 
     FROM inventory_items 
     WHERE is_active = 1 
     GROUP BY condition_status"
);

// Get monthly entries (last 6 months)
$monthlyEntries = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
     FROM inventory_items 
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month"
);

// Get old items (purchase date > 5 years)
$oldItems = $db->fetchValue(
    "SELECT COUNT(*) FROM inventory_items 
     WHERE purchase_date < DATE_SUB(NOW(), INTERVAL 5 YEAR) AND is_active = 1"
) ?? 0;

// Get pending approvals for supervisor/HoD
$pendingForMe = 0;
if (Auth::isSupervisor()) {
    $pendingForMe = $db->fetchValue(
        "SELECT COUNT(*) FROM transfer_requests WHERE status = 'pending_supervisor'"
    ) ?? 0;
}

// Page data
$pageTitle = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . Security::escape($user['emp_name']);

// Start output buffering
ob_start();
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total DIR -->
    <div class="stat-card bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total DIR Items</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($totalDIR) ?></p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-building text-blue-600 text-2xl"></i>
            </div>
        </div>
        <a href="<?= url('public/inventory/dir.php') ?>"
            class="mt-4 inline-flex items-center text-blue-600 text-sm font-medium hover:underline">
            View All <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <!-- Total PIR -->
    <div class="stat-card bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total PIR Items</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($totalPIR) ?></p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-tag text-green-600 text-2xl"></i>
            </div>
        </div>
        <a href="<?= url('public/inventory/pir.php') ?>"
            class="mt-4 inline-flex items-center text-green-600 text-sm font-medium hover:underline">
            View All <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <!-- Pending Transfers -->
    <div class="stat-card bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Transfers</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($pendingTransfers) ?></p>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exchange-alt text-yellow-600 text-2xl"></i>
            </div>
        </div>
        <a href="<?= url('public/transfers/index.php') ?>"
            class="mt-4 inline-flex items-center text-yellow-600 text-sm font-medium hover:underline">
            View All <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <!-- Stores Returns -->
    <div class="stat-card bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Pending Returns</p>
                <p class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($pendingReturns) ?></p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-warehouse text-purple-600 text-2xl"></i>
            </div>
        </div>
        <a href="<?= url('public/stores/returns.php') ?>"
            class="mt-4 inline-flex items-center text-purple-600 text-sm font-medium hover:underline">
            View All <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
</div>

<?php if ($pendingForMe > 0): ?>
    <!-- Pending Approvals Alert -->
    <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-2xl p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold">Pending Approvals</h3>
                    <p class="text-white/80">You have <?= $pendingForMe ?> transfer request(s) waiting for your approval</p>
                </div>
            </div>
            <a href="<?= url('public/transfers/index.php?status=pending') ?>"
                class="bg-white text-orange-600 px-6 py-2 rounded-xl font-semibold hover:bg-orange-50 transition-colors">
                Review Now
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Category Distribution -->
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Category</h3>
        <canvas id="categoryChart" height="250"></canvas>
    </div>

    <!-- Monthly Trend -->
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Monthly Entries</h3>
        <canvas id="monthlyChart" height="250"></canvas>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Condition Status -->
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Items by Condition</h3>
        <canvas id="conditionChart" height="200"></canvas>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Stats</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-clock text-gray-400"></i>
                    <span class="text-gray-600">Old Items (5+ years)</span>
                </div>
                <span class="font-bold text-gray-800"><?= number_format($oldItems) ?></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-boxes text-gray-400"></i>
                    <span class="text-gray-600">Total Items</span>
                </div>
                <span class="font-bold text-gray-800"><?= number_format($totalDIR + $totalPIR) ?></span>
            </div>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div class="flex items-center gap-3">
                    <i class="fas fa-sync text-gray-400"></i>
                    <span class="text-gray-600">Active Transfers</span>
                </div>
                <span class="font-bold text-gray-800"><?= number_format($pendingTransfers) ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Recent Activity</h3>
        <div class="space-y-4">
            <?php if (empty($recentActivity)): ?>
                <p class="text-gray-500 text-center py-4">No recent activity</p>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i
                                class="fas fa-<?= $activity['action_type'] === 'create' ? 'plus' : ($activity['action_type'] === 'update' ? 'edit' : ($activity['action_type'] === 'delete' ? 'trash' : 'info')) ?> text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800 truncate">
                                <?= Security::escape(truncate($activity['description'], 40)) ?></p>
                            <p class="text-xs text-gray-500"><?= timeAgo($activity['created_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (Auth::isAdmin()): ?>
            <a href="<?= url('public/logs/activity.php') ?>"
                class="mt-4 inline-flex items-center text-blue-600 text-sm font-medium hover:underline">
                View All Logs <i class="fas fa-arrow-right ml-2"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<?php if (Auth::isSupervisor()): ?>
    <div class="mt-8 bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6">
        <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="<?= url('public/inventory/dir.php?action=add') ?>"
                class="flex items-center gap-3 bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-colors">
                <i class="fas fa-plus text-green-400"></i>
                <span class="text-white font-medium">Add DIR Item</span>
            </a>
            <a href="<?= url('public/inventory/pir.php?action=add') ?>"
                class="flex items-center gap-3 bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-colors">
                <i class="fas fa-plus text-blue-400"></i>
                <span class="text-white font-medium">Add PIR Item</span>
            </a>
            <a href="<?= url('public/reports/export.php') ?>"
                class="flex items-center gap-3 bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-colors">
                <i class="fas fa-file-export text-purple-400"></i>
                <span class="text-white font-medium">Export Report</span>
            </a>
            <a href="<?= url('public/qr/index.php') ?>"
                class="flex items-center gap-3 bg-white/10 hover:bg-white/20 p-4 rounded-xl transition-colors">
                <i class="fas fa-qrcode text-yellow-400"></i>
                <span class="text-white font-medium">Generate QR</span>
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Additional scripts for charts
$additionalScripts = <<<SCRIPT
<script>
// Category Chart
const categoryData = <?= json_encode($itemsByCategory) ?>;
new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryData.map(c => c.name),
        datasets: [{
            data: categoryData.map(c => c.count),
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Monthly Chart
const monthlyData = <?= json_encode($monthlyEntries) ?>;
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthlyData.map(m => m.month),
        datasets: [{
            label: 'Entries',
            data: monthlyData.map(m => m.count),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Condition Chart
const conditionData = <?= json_encode($itemsByCondition) ?>;
const conditionColors = {
    'new': '#10b981',
    'good': '#3b82f6',
    'fair': '#f59e0b',
    'poor': '#ef4444',
    'non_serviceable': '#6b7280',
    'scrapped': '#1f2937'
};
new Chart(document.getElementById('conditionChart'), {
    type: 'bar',
    data: {
        labels: conditionData.map(c => c.condition_status),
        datasets: [{
            data: conditionData.map(c => c.count),
            backgroundColor: conditionData.map(c => conditionColors[c.condition_status] || '#6b7280'),
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
SCRIPT;

// Include layout
include __DIR__ . '/../templates/layout.php';
