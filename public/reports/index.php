<?php
/**
 * Enhanced Reports Dashboard
 * Analytics, Charts, and Export Options
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();

$db = Database::getInstance();

// Summary Stats
$stats = [
    'total_dir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'dir' AND is_active = 1") ?? 0,
    'total_pir' => $db->fetchValue("SELECT COUNT(*) FROM inventory_items WHERE inventory_type = 'pir' AND is_active = 1") ?? 0,
    'total_value' => $db->fetchValue("SELECT SUM(amount) FROM inventory_items WHERE is_active = 1") ?? 0,
    'pending_transfers' => $db->fetchValue("SELECT COUNT(*) FROM transfer_requests WHERE status IN ('pending_hod', 'pending_supervisor')") ?? 0,
];

// Department-wise distribution
$deptStats = $db->fetchAll(
    "SELECT d.name, d.code, COUNT(i.id) as count, COALESCE(SUM(i.amount), 0) as value
     FROM departments d
     LEFT JOIN inventory_items i ON d.id = i.department_id AND i.is_active = 1
     GROUP BY d.id, d.name, d.code
     ORDER BY count DESC"
) ?: [];

// Category-wise distribution
$catStats = $db->fetchAll(
    "SELECT c.name, COUNT(i.id) as count, COALESCE(SUM(i.amount), 0) as value
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

// Expiring warranties
$expiringWarranties = $db->fetchAll(
    "SELECT serial_number, item_description, warranty_expiry, d.name as department
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     WHERE i.is_active = 1 
     AND warranty_expiry IS NOT NULL 
     AND warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY warranty_expiry
     LIMIT 10"
) ?: [];

// Expiring AMCs
$expiringAmcs = $db->fetchAll(
    "SELECT serial_number, item_description, amc_expiry, d.name as department
     FROM inventory_items i
     LEFT JOIN departments d ON i.department_id = d.id
     WHERE i.is_active = 1 
     AND amc_expiry IS NOT NULL 
     AND amc_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
     ORDER BY amc_expiry
     LIMIT 10"
) ?: [];

// Old items (> 5 years)
$oldItemsCount = $db->fetchValue(
    "SELECT COUNT(*) FROM inventory_items 
     WHERE purchase_date < DATE_SUB(NOW(), INTERVAL 5 YEAR) AND is_active = 1"
) ?? 0;

// Prepare chart data
$deptChartData = json_encode(array_map(fn($d) => ['name' => $d['name'], 'count' => (int)$d['count'], 'value' => (float)$d['value']], $deptStats));
$catChartData = json_encode(array_map(fn($c) => ['name' => $c['name'], 'count' => (int)$c['count']], $catStats));
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
        $dirCounts[$row['month']] = (int)$row['count'];
    } else {
        $pirCounts[$row['month']] = (int)$row['count'];
    }
}
$monthlyChartData = json_encode([
    'labels' => array_values($months),
    'dir' => array_values($dirCounts),
    'pir' => array_values($pirCounts)
]);

$pageTitle = 'Reports & Analytics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #4F46E5;
            --dark: #1E293B;
            --light: #F8FAFC;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }
        
        body { 
            font-family: 'Noto Sans', sans-serif; 
            background: var(--light); 
            color: #334155; 
        }
        
        .sidebar { 
            width: 260px; 
            background: var(--dark); 
            min-height: 100vh; 
            position: fixed; 
        }
        
        .content { margin-left: 260px; padding: 2rem; }
        
        .nav-link { 
            color: #cbd5e1; 
            padding: 0.8rem 1.5rem; 
            display: block; 
            text-decoration: none;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active { 
            background: #0f172a; 
            color: white;
            border-left-color: var(--primary);
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.blue::before { background: var(--primary); }
        .stat-card.green::before { background: var(--success); }
        .stat-card.yellow::before { background: var(--warning); }
        .stat-card.red::before { background: var(--danger); }
        
        .stat-value { font-size: 2.5rem; font-weight: 700; color: var(--dark); }
        .stat-label { color: #64748b; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
        
        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .report-card .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .report-card .card-body { padding: 1.5rem; }
        
        .export-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: var(--dark);
            transition: all 0.2s;
        }
        
        .export-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .export-btn i { font-size: 1.5rem; }
        
        .expiry-list { max-height: 300px; overflow-y: auto; }
        
        .expiry-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .expiry-item:last-child { border-bottom: none; }
        
        .expiry-badge { 
            font-size: 0.75rem; 
            padding: 0.25rem 0.5rem; 
            border-radius: 6px;
        }
        .expiry-badge.warning { background: #fef3c7; color: #92400e; }
        .expiry-badge.danger { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar py-4">
        <div class="px-4 mb-4">
            <h4 class="text-white fw-bold"><i class="fas fa-cube me-2"></i>AMS</h4>
        </div>
        <a href="<?= url('public/dashboard.php') ?>" class="nav-link"><i class="fas fa-home me-2"></i> Dashboard</a>
        <a href="<?= url('public/inventory/dir.php') ?>" class="nav-link"><i class="fas fa-list-alt me-2"></i> DIR Inventory</a>
        <a href="<?= url('public/inventory/pir.php') ?>" class="nav-link"><i class="fas fa-clipboard-list me-2"></i> PIR Inventory</a>
        <a href="<?= url('public/reports/index.php') ?>" class="nav-link active"><i class="fas fa-chart-pie me-2"></i> Reports</a>
        <?php if (Auth::isAdmin()): ?>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/admin/settings.php') ?>" class="nav-link"><i class="fas fa-cogs me-2"></i> Settings</a>
        <a href="<?= url('public/logs/activity.php') ?>" class="nav-link"><i class="fas fa-history me-2"></i> Logs</a>
        <?php endif; ?>
        <hr class="my-3 mx-3 border-secondary">
        <a href="<?= url('public/logout.php') ?>" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Reports & Analytics</h2>
                <p class="text-muted mb-0">Asset Management Insights</p>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="stat-value"><?= number_format($stats['total_dir']) ?></div>
                    <div class="stat-label">DIR Items</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <div class="stat-value"><?= number_format($stats['total_pir']) ?></div>
                    <div class="stat-label">PIR Items</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card yellow">
                    <div class="stat-value"><?= formatCurrency($stats['total_value']) ?></div>
                    <div class="stat-label">Total Value</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
                    <div class="stat-value"><?= number_format($oldItemsCount) ?></div>
                    <div class="stat-label">Old Items (5+ yrs)</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Charts Column -->
            <div class="col-lg-8">
                <!-- Monthly Trend -->
                <div class="report-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Trend</span>
                        <span class="badge bg-light text-dark">Last 12 Months</span>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="120"></canvas>
                    </div>
                </div>

                <!-- Distribution Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="fas fa-building me-2 text-primary"></i>By Department
                            </div>
                            <div class="card-body">
                                <canvas id="deptChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="report-card">
                            <div class="card-header">
                                <i class="fas fa-tags me-2 text-primary"></i>By Category
                            </div>
                            <div class="card-body">
                                <canvas id="catChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Condition Chart -->
                <div class="report-card">
                    <div class="card-header">
                        <i class="fas fa-heartbeat me-2 text-primary"></i>Asset Condition
                    </div>
                    <div class="card-body">
                        <canvas id="conditionChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Export Options -->
                <div class="report-card">
                    <div class="card-header">
                        <i class="fas fa-file-export me-2 text-primary"></i>Export Reports
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="<?= url('public/reports/export.php?type=dir&format=csv') ?>" class="export-btn">
                                <i class="fas fa-file-csv text-success"></i>
                                <div>
                                    <strong>DIR Inventory</strong>
                                    <small class="d-block text-muted">Export as Excel/CSV</small>
                                </div>
                            </a>
                            <a href="<?= url('public/reports/export.php?type=pir&format=csv') ?>" class="export-btn">
                                <i class="fas fa-file-csv text-primary"></i>
                                <div>
                                    <strong>PIR Inventory</strong>
                                    <small class="d-block text-muted">Export as Excel/CSV</small>
                                </div>
                            </a>
                            <a href="<?= url('public/reports/export.php?type=dir&format=pdf') ?>" class="export-btn">
                                <i class="fas fa-file-pdf text-danger"></i>
                                <div>
                                    <strong>Print Report</strong>
                                    <small class="d-block text-muted">PDF with all assets</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Expiring Warranties -->
                <?php if (!empty($expiringWarranties)): ?>
                <div class="report-card">
                    <div class="card-header">
                        <i class="fas fa-shield-alt me-2 text-warning"></i>Expiring Warranties
                    </div>
                    <div class="card-body p-0">
                        <div class="expiry-list">
                            <?php foreach ($expiringWarranties as $item): 
                                $daysLeft = (strtotime($item['warranty_expiry']) - time()) / 86400;
                            ?>
                            <div class="expiry-item">
                                <div>
                                    <strong class="d-block"><?= Security::escape($item['serial_number']) ?></strong>
                                    <small class="text-muted"><?= Security::escape(truncate($item['item_description'], 30)) ?></small>
                                </div>
                                <span class="expiry-badge <?= $daysLeft < 15 ? 'danger' : 'warning' ?>">
                                    <?= ceil($daysLeft) ?> days
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Expiring AMCs -->
                <?php if (!empty($expiringAmcs)): ?>
                <div class="report-card">
                    <div class="card-header">
                        <i class="fas fa-wrench me-2 text-danger"></i>Expiring AMCs
                    </div>
                    <div class="card-body p-0">
                        <div class="expiry-list">
                            <?php foreach ($expiringAmcs as $item): 
                                $daysLeft = (strtotime($item['amc_expiry']) - time()) / 86400;
                            ?>
                            <div class="expiry-item">
                                <div>
                                    <strong class="d-block"><?= Security::escape($item['serial_number']) ?></strong>
                                    <small class="text-muted"><?= Security::escape(truncate($item['item_description'], 30)) ?></small>
                                </div>
                                <span class="expiry-badge <?= $daysLeft < 15 ? 'danger' : 'warning' ?>">
                                    <?= ceil($daysLeft) ?> days
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Department Summary -->
                <div class="report-card">
                    <div class="card-header">
                        <i class="fas fa-table me-2 text-primary"></i>Department Summary
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Department</th>
                                    <th class="text-end">Items</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($deptStats, 0, 8) as $dept): ?>
                                <tr>
                                    <td><?= Security::escape($dept['code']) ?></td>
                                    <td class="text-end"><?= number_format($dept['count']) ?></td>
                                    <td class="text-end"><?= formatCurrency($dept['value']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Monthly Trend Chart
        const monthlyData = <?= $monthlyChartData ?>;
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: 'DIR',
                        data: monthlyData.dir,
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'PIR',
                        data: monthlyData.pir,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
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
                    backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#84CC16', '#F97316'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '60%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
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
                cutout: '60%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
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
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
