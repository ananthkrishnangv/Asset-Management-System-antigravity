        <!-- Sidebar -->
        <aside class="sidebar-gradient w-64 fixed h-full z-30 flex flex-col">
            <!-- Logo Section -->
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <!-- <img src="<?= url('Image/logo-serc.jpg') ?>" alt="Logo" class="h-12 w-12 rounded-full border-2 border-white/30"> -->
                    <div>
                        <h1 class="text-white font-bold text-lg">CSIR-SERC</h1>
                        <p class="text-blue-200 text-xs">Asset Management</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-6 px-4 overflow-y-auto">
                <ul class="space-y-2">
                    <li>
                        <a href="<?= url('public/dashboard.php') ?>" class="nav-item <?= isCurrentPage('dashboard') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-chart-pie w-5"></i><span>Dashboard</span>
                        </a>
                    </li>
                    <?php if (Auth::isAdmin()): ?>
                        <li class="pt-4"><p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Admin</p></li>
                        <li>
                            <a href="<?= url('admin/dashboard.php') ?>" class="nav-item <?= isCurrentPage('dashboard') && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-tachometer-alt w-5"></i><span>Admin Dash</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/users.php') ?>" class="nav-item <?= isCurrentPage('users') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-users-cog w-5"></i><span>User Management</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <!-- Add other links similarly -->
                </ul>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-2">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr(Auth::user()['emp_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate"><?= Security::escape(Auth::user()['emp_name'] ?? '') ?></p>
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
                                <span class="text-sm font-semibold text-gray-700"><?= Security::escape(Auth::user()['emp_name'] ?? '') ?></span>
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </button>
                            <div class="dropdown-menu">
                                <div class="py-2">
                                     <a href="<?= url('public/logout.php') ?>" class="flex items-center gap-3 px-4 py-2 text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt w-4"></i><span>Logout</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="p-8">
