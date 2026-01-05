<?php
/**
 * Enhanced Dashboard - Tailwind CSS Fluent UI
 * Modern design with light gradients and elegant statistics
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
$totalValue = $db->fetchValue("SELECT SUM(unit_price) FROM inventory_items WHERE is_active = 1") ?? 0;
$totalUsers = $db->fetchValue("SELECT COUNT(*) FROM users WHERE is_active = 1") ?? 0;

// Calculate trends
$lastWeekItems = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0;
$thisWeekItems = $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") ?? 0;
$itemsTrend = $lastWeekItems > 0 ? round((($thisWeekItems - $lastWeekItems) / $lastWeekItems) * 100, 1) : 0;

// Get recent activity
$recentItems = $db->fetchAll(
    "SELECT i.serial_number, i.item_description, i.unit_price, c.name as category, d.name as department, i.created_at
     FROM inventory_items i
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN departments d ON i.department_id = d.id
     WHERE i.is_active = 1
     ORDER BY i.created_at DESC LIMIT 6"
) ?: [];

// Get stores returns
$storesReturns = $db->fetchAll(
    "SELECT sr.*, i.serial_number, i.item_description, u.emp_name as returned_by_name
     FROM stores_returns sr
     LEFT JOIN inventory_items i ON sr.item_id = i.id
     LEFT JOIN users u ON sr.returned_by = u.id
     ORDER BY sr.created_at DESC LIMIT 5"
) ?: [];

// Get recent transfers
$recentTransfers = $db->fetchAll(
    "SELECT tr.*, i.serial_number, i.item_description, 
            fu.emp_name as from_user_name, tu.emp_name as to_user_name,
            fd.code as from_dept_code, td.code as to_dept_code
     FROM transfer_requests tr
     LEFT JOIN inventory_items i ON tr.item_id = i.id
     LEFT JOIN users fu ON tr.from_user_id = fu.id
     LEFT JOIN users tu ON tr.to_user_id = tu.id
     LEFT JOIN departments fd ON tr.from_department_id = fd.id
     LEFT JOIN departments td ON tr.to_department_id = td.id
     ORDER BY tr.created_at DESC LIMIT 5"
) ?: [];

// Items by category for chart
$itemsByCategory = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count 
     FROM categories c 
     LEFT JOIN inventory_items i ON c.id = i.category_id AND i.is_active = 1
     GROUP BY c.id, c.name
     HAVING count > 0
     ORDER BY count DESC LIMIT 6"
) ?: [];

// Monthly data for chart
$monthlyData = $db->fetchAll(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
     FROM inventory_items 
     WHERE is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month"
) ?: [];

// Get departments and categories for forms
$departments = $db->fetchAll("SELECT id, code, name FROM departments ORDER BY name") ?: [];
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name") ?: [];

$categoryChartData = json_encode($itemsByCategory);
$monthlyChartData = json_encode($monthlyData);

$pageTitle = 'Dashboard';
ob_start();
?>

<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .stat-card.gradient-blue {
        background: linear-gradient(135deg, #EBF4FF 0%, #F0F7FF 100%);
        border-color: rgba(59, 130, 246, 0.15);
    }

    .stat-card.gradient-green {
        background: linear-gradient(135deg, #ECFDF5 0%, #F0FDF4 100%);
        border-color: rgba(16, 185, 129, 0.15);
    }

    .stat-card.gradient-amber {
        background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);
        border-color: rgba(245, 158, 11, 0.15);
    }

    .stat-card.gradient-purple {
        background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
        border-color: rgba(139, 92, 246, 0.15);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-icon.blue {
        background: linear-gradient(135deg, #3B82F6, #60A5FA);
        color: white;
    }

    .stat-icon.green {
        background: linear-gradient(135deg, #10B981, #34D399);
        color: white;
    }

    .stat-icon.amber {
        background: linear-gradient(135deg, #F59E0B, #FBBF24);
        color: white;
    }

    .stat-icon.purple {
        background: linear-gradient(135deg, #8B5CF6, #A78BFA);
        color: white;
    }

    .trend-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .trend-badge.up {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .trend-badge.down {
        background: rgba(239, 68, 68, 0.1);
        color: #DC2626;
    }

    .data-card {
        background: white;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .data-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #F3F4F6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .data-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1F2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .data-card-body {
        padding: 1.5rem;
    }

    .data-table {
        width: 100%;
    }

    .data-table th {
        text-align: left;
        padding: 0.75rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: #F9FAFB;
    }

    .data-table td {
        padding: 1rem;
        font-size: 0.875rem;
        color: #374151;
        border-top: 1px solid #F3F4F6;
    }

    .data-table tr:hover td {
        background: #F9FAFB;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-badge.pending {
        background: #FEF3C7;
        color: #B45309;
    }

    .status-badge.approved {
        background: #D1FAE5;
        color: #047857;
    }

    .status-badge.completed {
        background: #DBEAFE;
        color: #1D4ED8;
    }

    .chart-container {
        height: 280px;
        padding: 1rem;
    }

    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1.25rem;
        background: white;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-action-btn:hover {
        border-color: #3B82F6;
        background: #EFF6FF;
    }

    .quick-action-btn i {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #F3F4F6;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366F1, #8B5CF6);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Welcome back, <?= Security::escape($user['emp_name'] ?? 'User') ?>!
    </h1>
    <p class="text-gray-500 mt-1">Here's what's happening with your assets today.</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <!-- Total DIR Items -->
    <div class="stat-card gradient-blue">
        <div class="flex items-center justify-between">
            <div class="stat-icon blue">
                <i class="fas fa-boxes"></i>
            </div>
            <span class="trend-badge <?= $itemsTrend >= 0 ? 'up' : 'down' ?>">
                <i class="fas fa-arrow-<?= $itemsTrend >= 0 ? 'up' : 'down' ?>"></i>
                <?= abs($itemsTrend) ?>%
            </span>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-800"><?= number_format($totalDIR) ?></p>
            <p class="text-sm text-gray-500 mt-1">DIR Items</p>
        </div>
    </div>

    <!-- Total PIR Items -->
    <div class="stat-card gradient-green">
        <div class="flex items-center justify-between">
            <div class="stat-icon green">
                <i class="fas fa-user-tag"></i>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-800"><?= number_format($totalPIR) ?></p>
            <p class="text-sm text-gray-500 mt-1">PIR Items</p>
        </div>
    </div>

    <!-- Pending Transfers -->
    <div class="stat-card gradient-amber">
        <div class="flex items-center justify-between">
            <div class="stat-icon amber">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <?php if ($pendingTransfers > 0): ?>
                <span class="trend-badge down">
                    <i class="fas fa-clock"></i>
                    Pending
                </span>
            <?php endif; ?>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold text-gray-800"><?= number_format($pendingTransfers) ?></p>
            <p class="text-sm text-gray-500 mt-1">Pending Transfers</p>
        </div>
    </div>

    <!-- Total Value -->
    <div class="stat-card gradient-purple">
        <div class="flex items-center justify-between">
            <div class="stat-icon purple">
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-2xl font-bold text-gray-800"><?= formatCurrency($totalValue) ?></p>
            <p class="text-sm text-gray-500 mt-1">Total Asset Value</p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Monthly Trend Chart -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-chart-line text-blue-500"></i>
                Inventory Analytics
            </h3>
            <span class="text-xs text-gray-400">Last 6 months</span>
        </div>
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Category Distribution -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-chart-pie text-purple-500"></i>
                Items by Category
            </h3>
        </div>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
</div>

<!-- Quick Actions & Recent Items -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Quick Actions -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-bolt text-amber-500"></i>
                Quick Actions
            </h3>
        </div>
        <div class="p-4 grid grid-cols-2 gap-3">
            <a href="<?= url('public/inventory/add.php?type=dir') ?>" class="quick-action-btn text-blue-600">
                <i class="fas fa-plus-circle"></i>
                <span class="text-sm font-medium">Add DIR</span>
            </a>
            <a href="<?= url('public/inventory/add.php?type=pir') ?>" class="quick-action-btn text-green-600">
                <i class="fas fa-user-plus"></i>
                <span class="text-sm font-medium">Add PIR</span>
            </a>
            <a href="<?= url('public/transfers/new.php') ?>" class="quick-action-btn text-purple-600">
                <i class="fas fa-exchange-alt"></i>
                <span class="text-sm font-medium">Transfer</span>
            </a>
            <a href="<?= url('public/reports/index.php') ?>" class="quick-action-btn text-amber-600">
                <i class="fas fa-chart-bar"></i>
                <span class="text-sm font-medium">Reports</span>
            </a>
        </div>
    </div>

    <!-- Recent Items Table -->
    <div class="data-card lg:col-span-2">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-clock text-blue-500"></i>
                Recent Items
            </h3>
            <a href="<?= url('public/inventory/dir.php') ?>" class="text-sm text-blue-600 hover:underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentItems)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-gray-400 py-8">No items found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentItems as $item): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-800">
                                        <?= Security::escape(truncate($item['item_description'] ?? '-', 30)) ?></div>
                                    <div class="text-xs text-gray-400"><?= Security::escape($item['serial_number'] ?? '-') ?>
                                    </div>
                                </td>
                                <td><?= Security::escape($item['category'] ?? '-') ?></td>
                                <td><?= Security::escape(truncate($item['department'] ?? '-', 20)) ?></td>
                                <td class="font-medium text-gray-800"><?= formatCurrency($item['unit_price'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transfers & Returns -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Pending Transfers -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-exchange-alt text-purple-500"></i>
                Recent Transfers
            </h3>
        </div>
        <div class="p-4">
            <?php if (empty($recentTransfers)): ?>
                <div class="text-center py-6 text-gray-400">
                    <i class="fas fa-exchange-alt text-3xl mb-2"></i>
                    <p>No recent transfers</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentTransfers as $tr): ?>
                    <div class="activity-item">
                        <div class="activity-avatar"><?= strtoupper(substr($tr['from_user_name'] ?? 'U', 0, 1)) ?></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?= Security::escape(truncate($tr['item_description'] ?? '-', 35)) ?>
                            </p>
                            <p class="text-xs text-gray-400">
                                <?= Security::escape($tr['from_dept_code'] ?? '-') ?> →
                                <?= Security::escape($tr['to_dept_code'] ?? '-') ?>
                            </p>
                        </div>
                        <span class="status-badge <?= ($tr['status'] ?? '') == 'completed' ? 'completed' : 'pending' ?>">
                            <?= ucfirst(str_replace('_', ' ', $tr['status'] ?? 'pending')) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stores Returns -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">
                <i class="fas fa-undo text-green-500"></i>
                Stores Returns
            </h3>
        </div>
        <div class="p-4">
            <?php if (empty($storesReturns)): ?>
                <div class="text-center py-6 text-gray-400">
                    <i class="fas fa-undo text-3xl mb-2"></i>
                    <p>No pending returns</p>
                </div>
            <?php else: ?>
                <?php foreach ($storesReturns as $sr): ?>
                    <div class="activity-item">
                        <div class="activity-avatar" style="background: linear-gradient(135deg, #10B981, #34D399);">
                            <?= strtoupper(substr($sr['returned_by_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                <?= Security::escape(truncate($sr['item_description'] ?? '-', 35)) ?>
                            </p>
                            <p class="text-xs text-gray-400">By: <?= Security::escape($sr['returned_by_name'] ?? '-') ?></p>
                        </div>
                        <span class="status-badge <?= ($sr['status'] ?? '') == 'approved' ? 'approved' : 'pending' ?>">
                            <?= ucfirst(str_replace('_', ' ', $sr['status'] ?? 'pending')) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Analytics Chart
    const monthlyData = <?= $monthlyChartData ?>;
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');

    const gradient = monthlyCtx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
    gradient.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => {
                const [year, month] = d.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('en', { month: 'short' });
            }),
            datasets: [{
                label: 'Items Added',
                data: monthlyData.map(d => d.count),
                borderColor: '#3B82F6',
                backgroundColor: gradient,
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
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

    // Category Chart
    const categoryData = <?= $categoryChartData ?>;
    const catCtx = document.getElementById('categoryChart').getContext('2d');

    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(c => c.name),
            datasets: [{
                data: categoryData.map(c => c.count),
                backgroundColor: [
                    '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#6366F1'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 12 }
                    }
                }
            },
            cutout: '65%'
        }
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../templates/layout.php';
?>