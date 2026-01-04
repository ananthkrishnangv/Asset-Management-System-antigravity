<?php
require_once '../bootstrap.php';

// Auth check
Auth::requireAdmin();

$pageTitle = 'Admin Dashboard';
require_once '../templates/header.php';
require_once '../templates/sidebar.php';

$db = Database::getInstance();

// Stats
$assetCount = $db->fetch("SELECT COUNT(*) as count FROM inventory_items WHERE condition_status != 'scrapped'")['count'];
$userCount = $db->fetch("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
$pendingTransfers = $db->fetch("SELECT COUNT(*) as count FROM transfer_requests WHERE status IN ('pending_supervisor', 'pending_hod')")['count'];
$myDeptItems = 0; // Placeholder or calculate if admin has dept

// AI Insights
$maintenanceItems = SearchEngine::getMaintenanceInsights();

// Handle Search
$searchResults = [];
$searchQuery = $_GET['search'] ?? '';
if ($searchQuery) {
    $searchResults = SearchEngine::search($searchQuery);
}
?>

<div class="main-content">
    <div class="page-header mb-8">
        <h2 class="text-3xl font-bold text-gray-800">Welcome, <?= htmlspecialchars(Auth::user()['emp_name']) ?></h2>
        <p class="text-gray-600">Here's what's happening in your organization today.</p>
    </div>

    <!-- Smart Search Bar -->
    <div class="mb-8">
        <form action="" method="GET" class="relative">
            <div class="flex items-center bg-white rounded-xl shadow-lg border border-gray-100 p-2">
                <i class="fas fa-magic text-purple-500 ml-4 text-xl"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" 
                       placeholder="Ask me anything... (e.g. 'Laptop', 'Surya', 'Department A')" 
                       class="w-full px-4 py-3 text-gray-700 focus:outline-none text-lg bg-transparent" autocomplete="off">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-medium transition-colors">
                    Search
                </button>
            </div>
        </form>
        
        <?php if (!empty($searchResults)): ?>
            <div class="mt-4 bg-white rounded-xl shadow-md overflow-hidden animate-fade-in-up">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Search Results</h3>
                    <span class="text-xs text-gray-500"><?= count($searchResults) ?> matches found</span>
                </div>
                <ul>
                    <?php foreach ($searchResults as $result): ?>
                        <li class="border-b last:border-0 hover:bg-blue-50 transition-colors">
                            <a href="<?= $result['type'] == 'asset' ? '../public/item_details.php?id='.$result['id'] : ($result['type'] == 'user' ? 'user_edit.php?id='.$result['id'] : '#') ?>" class="block p-4 flex items-center">
                                <div class="bg-blue-100 p-2 rounded-full mr-4 text-blue-600">
                                    <i class="fas fa-<?= $result['type'] == 'asset' ? 'box' : ($result['type'] == 'user' ? 'user' : 'building') ?>"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($result['title']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($result['subtitle']) ?></p>
                                </div>
                                <?php if ($result['type'] == 'asset' && $result['status']): ?>
                                    <div class="ml-auto">
                                        <span class="px-2 py-1 rounded-full text-xs <?= getStatusBadge($result['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $result['status'])) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">Total Assets</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= number_format($assetCount) ?></h3>
                </div>
                <div class="bg-blue-50 p-3 rounded-xl text-blue-600">
                    <i class="fas fa-cubes text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-green-600 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i> <span>Active inventory</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">Users</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= number_format($userCount) ?></h3>
                </div>
                <div class="bg-indigo-50 p-3 rounded-xl text-indigo-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
            <div class="mt-4 text-sm text-blue-600">
                <a href="users.php" class="hover:underline">Manage Users &rarr;</a>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">Pending Transfers</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?= number_format($pendingTransfers) ?></h3>
                </div>
                <div class="bg-orange-50 p-3 rounded-xl text-orange-600">
                    <i class="fas fa-exchange-alt text-xl"></i>
                </div>
            </div>
             <div class="mt-4 text-sm text-orange-600">
                <span>Requires approval</span>
            </div>
        </div>
    </div>

    <!-- AI Insights Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gradient-to-br from-indigo-900 to-purple-800 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden">
             <!-- Decorative -->
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
            
            <div class="flex items-center mb-6 z-10 relative">
                <i class="fas fa-robot text-2xl mr-3 text-cyan-300 animate-pulse"></i>
                <h3 class="text-xl font-bold">AI Maintenance Insights</h3>
            </div>
            
            <div class="space-y-4 relative z-10">
                <?php if (empty($maintenanceItems)): ?>
                    <p class="text-indigo-200">System is healthy. No critical maintenance items detected by AI analysis.</p>
                <?php else: ?>
                    <p class="text-indigo-200 text-sm mb-4">Attention needed for these items based on usage patterns and age:</p>
                    <?php foreach ($maintenanceItems as $item): ?>
                        <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg flex items-center justify-between">
                            <div>
                                <p class="font-medium text-white"><?= htmlspecialchars($item['item_description']) ?></p>
                                <p class="text-xs text-indigo-200">Purchased: <?= formatDate($item['purchase_date']) ?></p>
                            </div>
                            <span class="px-2 py-1 rounded text-xs bg-red-500/20 text-red-200 border border-red-500/50">
                                <?= $item['condition_status'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button class="mt-6 w-full bg-white/20 hover:bg-white/30 text-white text-sm py-2 rounded-lg transition-colors">
                View All Recommendations
            </button>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
             <div class="flex items-center mb-6">
                <div class="bg-green-100 p-2 rounded-lg text-green-600 mr-3">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Quick Actions</h3>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <a href="user_create.php" class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 flex flex-col items-center justify-center text-center group transition-all">
                    <div class="h-10 w-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span class="font-medium text-gray-700">Add User</span>
                </a>
                
                <a href="import_users.php" class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 flex flex-col items-center justify-center text-center group transition-all">
                    <div class="h-10 w-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <span class="font-medium text-gray-700">Import Users</span>
                </a>
                
                <a href="#" class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 flex flex-col items-center justify-center text-center group transition-all">
                    <div class="h-10 w-10 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <span class="font-medium text-gray-700">View Reports</span>
                </a>
                
                <a href="#" class="p-4 border border-gray-100 rounded-xl hover:bg-gray-50 flex flex-col items-center justify-center text-center group transition-all">
                    <div class="h-10 w-10 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span class="font-medium text-gray-700">Settings</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
