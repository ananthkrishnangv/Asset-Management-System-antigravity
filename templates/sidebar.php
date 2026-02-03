<!-- Sidebar -->
<aside class="sidebar-gradient w-64 fixed h-full z-30 flex flex-col">
    <!-- Logo Section -->
    <div class="p-4 border-b border-white/10">
        <div class="flex items-center justify-center">
            <img src="<?= url('Image/portal-logo.png') ?>" alt="CSIR-SERC Asset Management" class="max-w-full h-auto"
                style="max-height: 80px; object-fit: contain;">
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-6 px-4 overflow-y-auto">
        <ul class="space-y-2">
            <li>
                <a href="<?= url('public/dashboard.php') ?>"
                    class="nav-item <?= isCurrentPage('dashboard') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                    <i class="fas fa-chart-pie w-5"></i><span>Dashboard</span>
                </a>
            </li>
            <?php if (Auth::isAdmin()): ?>
                <li class="pt-4">
                    <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Admin</p>
                </li>
                <li>
                    <a href="<?= url('admin/dashboard.php') ?>"
                        class="nav-item <?= isCurrentPage('dashboard') && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                        <i class="fas fa-tachometer-alt w-5"></i><span>Admin Dash</span>
                    </a>
                </li>
                <li>
                    <a href="<?= url('admin/users.php') ?>"
                        class="nav-item <?= isCurrentPage('users') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                        <i class="fas fa-users-cog w-5"></i><span>User Management</span>
                    </a>
                </li>
            <?php endif; ?>
            <!-- Inventory Section -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Inventory</p>
            </li>
            <li>
                <a href="<?= url('public/inventory/dir.php') ?>"
                    class="nav-item <?= isCurrentPage('dir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                    <i class="fas fa-building w-5"></i><span>DIR Items</span>
                </a>
            </li>
            <li>
                <a href="<?= url('public/inventory/pir.php') ?>"
                    class="nav-item <?= isCurrentPage('pir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                    <i class="fas fa-user-tag w-5"></i><span>PIR Items</span>
                </a>
            </li>
            <li>
                <a href="<?= url('public/inventory/bulk-import.php') ?>"
                    class="nav-item <?= isCurrentPage('bulk-import') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                    <i class="fas fa-file-import w-5"></i><span>Bulk Import</span>
                </a>
            </li>

            <!-- Reports Section -->
            <li class="pt-4">
                <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Reports</p>
            </li>
            <li>
                <a href="<?= url('public/reports/index.php') ?>"
                    class="nav-item <?= isCurrentPage('index') && strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                    <i class="fas fa-chart-bar w-5"></i><span>Reports & Analytics</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- User Info -->
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3 px-2">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                <?= strtoupper(substr(Auth::user()['emp_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">
                    <?= Security::escape(Auth::user()['emp_name'] ?? '') ?>
                </p>
                <p class="text-blue-300 text-xs capitalize"><?= Auth::role() ?></p>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content Wrapper & Header -->
<div class="flex-1 ml-64">
    <header class="bg-white shadow-sm sticky top-0 z-20">
        <div class="flex items-center justify-between px-8 py-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="dropdown relative">
                    <button class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <span
                            class="text-sm font-semibold text-gray-700"><?= Security::escape(Auth::user()['emp_name'] ?? '') ?></span>
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </button>
                    <div class="dropdown-menu">
                        <div class="py-2">
                            <a href="<?= url('public/logout.php') ?>"
                                class="flex items-center gap-3 px-4 py-2 text-red-600 hover:bg-red-50 transition-colors">
                                <i class="fas fa-sign-out-alt w-4"></i><span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="p-8">