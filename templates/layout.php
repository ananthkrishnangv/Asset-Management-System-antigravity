<?php
/**
 * Main Layout Template
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - CSIR-SERC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .sidebar-gradient {
            background: linear-gradient(180deg, #1a365d 0%, #0f172a 100%);
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
        }

        .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .table-row-hover:hover {
            background-color: #f8fafc;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            min-width: 200px;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 50;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .toast {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Modal styles */
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
    <?= $additionalStyles ?? '' ?>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar-gradient w-64 fixed h-full z-30 flex flex-col">
            <!-- Logo Section -->
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <img src="<?= url('Image/logo-serc.jpg') ?>" alt="Logo"
                        class="h-12 w-12 rounded-full border-2 border-white/30">
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
                        <a href="<?= url('public/dashboard.php') ?>"
                            class="nav-item <?= isCurrentPage('dashboard') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-chart-pie w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="pt-4">
                        <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Inventory</p>
                    </li>

                    <li>
                        <a href="<?= url('public/inventory/dir.php') ?>"
                            class="nav-item <?= isCurrentPage('dir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-building w-5"></i>
                            <span>DIR</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= url('public/inventory/pir.php') ?>"
                            class="nav-item <?= isCurrentPage('pir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-user-tag w-5"></i>
                            <span>PIR</span>
                        </a>
                    </li>

                    <li class="pt-4">
                        <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Transfers</p>
                    </li>

                    <li>
                        <a href="<?= url('public/transfers/index.php') ?>"
                            class="nav-item <?= isCurrentPage('transfers') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-exchange-alt w-5"></i>
                            <span>Transfers</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= url('public/stores/returns.php') ?>"
                            class="nav-item <?= isCurrentPage('returns') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                            <i class="fas fa-warehouse w-5"></i>
                            <span>Stores Returns</span>
                        </a>
                    </li>

                    <?php if (Auth::isSupervisor()): ?>
                        <li class="pt-4">
                            <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Reports</p>
                        </li>

                        <li>
                            <a href="<?= url('public/reports/index.php') ?>"
                                class="nav-item <?= isCurrentPage('reports') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-chart-bar w-5"></i>
                                <span>Reports</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/qr/index.php') ?>"
                                class="nav-item <?= isCurrentPage('qr') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-qrcode w-5"></i>
                                <span>QR Codes</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::isAdmin()): ?>
                        <li class="pt-4">
                            <p class="px-4 text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Admin</p>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/users.php') ?>"
                                class="nav-item <?= isCurrentPage('users') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-users-cog w-5"></i>
                                <span>User Management</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/departments.php') ?>"
                                class="nav-item <?= isCurrentPage('departments') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-sitemap w-5"></i>
                                <span>Departments</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/logs/activity.php') ?>"
                                class="nav-item <?= isCurrentPage('activity') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-history w-5"></i>
                                <span>Activity Logs</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/backup.php') ?>"
                                class="nav-item <?= isCurrentPage('backup') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-database w-5"></i>
                                <span>Backups</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/settings.php') ?>"
                                class="nav-item <?= isCurrentPage('settings') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-white/80 hover:text-white rounded-xl transition-all">
                                <i class="fas fa-cog w-5"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-white/10">
                <div class="flex items-center gap-3 px-2">
                    <div
                        class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
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

        <!-- Main Content -->
        <div class="flex-1 ml-64">
            <!-- Top Header -->
            <header class="bg-white shadow-sm sticky top-0 z-20">
                <div class="flex items-center justify-between px-8 py-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
                        <p class="text-sm text-gray-500"><?= $pageSubtitle ?? 'Welcome to Asset Management System' ?>
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Global Search -->
                        <div class="relative">
                            <input type="text" id="globalSearch" placeholder="Search anything..."
                                class="pl-10 pr-4 py-2 w-72 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <div id="searchResults"
                                class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 hidden max-h-96 overflow-y-auto z-50">
                                <!-- Results loaded via AJAX -->
                            </div>
                        </div>

                        <!-- Notifications -->
                        <button
                            class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <!-- User Menu -->
                        <div class="dropdown relative">
                            <button
                                class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-xl transition-colors">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                    <?= strtoupper(substr(Auth::user()['emp_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div class="text-left hidden md:block">
                                    <p class="text-sm font-semibold text-gray-700">
                                        <?= Security::escape(Auth::user()['ams_id'] ?? '') ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= Security::escape(Auth::user()['email_id'] ?? '') ?>
                                    </p>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </button>

                            <div class="dropdown-menu">
                                <div class="p-4 border-b border-gray-100">
                                    <p class="font-semibold text-gray-800">
                                        <?= Security::escape(Auth::user()['emp_name'] ?? '') ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= Security::escape(Auth::user()['email_id'] ?? '') ?>
                                    </p>
                                </div>
                                <div class="py-2">
                                    <a href="<?= url('public/profile.php') ?>"
                                        class="flex items-center gap-3 px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-user w-4"></i>
                                        <span>My Profile</span>
                                    </a>
                                    <a href="<?= url('public/change-password.php') ?>"
                                        class="flex items-center gap-3 px-4 py-2 text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-key w-4"></i>
                                        <span>Change Password</span>
                                    </a>
                                </div>
                                <div class="border-t border-gray-100 py-2">
                                    <a href="<?= url('public/logout.php') ?>"
                                        onclick="return confirm('Are you sure you want to logout?')"
                                        class="flex items-center gap-3 px-4 py-2 text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt w-4"></i>
                                        <span>Logout</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php $flash = getFlashMessages(); ?>
            <?php if (!empty($flash)): ?>
                <div id="flashMessages" class="fixed top-4 right-4 z-50 space-y-2">
                    <?php foreach ($flash as $type => $message): ?>
                        <div
                            class="toast max-w-sm p-4 rounded-xl shadow-lg <?= $type === 'error' ? 'bg-red-500' : ($type === 'success' ? 'bg-green-500' : 'bg-blue-500') ?> text-white">
                            <div class="flex items-center gap-3">
                                <i
                                    class="fas <?= $type === 'error' ? 'fa-times-circle' : ($type === 'success' ? 'fa-check-circle' : 'fa-info-circle') ?>"></i>
                                <span><?= Security::escape($message) ?></span>
                                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto hover:opacity-75">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <script>
                    setTimeout(() => {
                        document.getElementById('flashMessages')?.remove();
                    }, 5000);
                </script>
            <?php endif; ?>

            <!-- Page Content -->
            <main class="p-8">
                <?= $content ?? '' ?>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-100 py-4 px-8">
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <p>© <?= date('Y') ?> CSIR-SERC. All rights reserved.</p>
                    <p>Version <?= APP_VERSION ?></p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Common Scripts -->
    <script>
        // Toast notification system
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            const toast = document.createElement('div');
            toast.className = `toast max-w-sm p-4 rounded-xl shadow-lg ${colors[type]} text-white`;
            toast.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto hover:opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            container.appendChild(toast);

            setTimeout(() => toast.remove(), 5000);
        }

        // Confirm delete
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Format currency
        function formatCurrency(amount) {
            return '₹ ' + new Intl.NumberFormat('en-IN').format(amount);
        }
        
        // Global Search
        let searchTimeout;
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('searchResults');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    fetch(`<?= url('api/search.php') ?>?q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.results && data.results.length > 0) {
                            searchResults.innerHTML = data.results.map(r => `
                                <a href="${r.url}" class="flex items-center gap-3 p-3 hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                    ${r.image ? `<img src="${r.image}" class="w-10 h-10 rounded-lg object-cover">` : 
                                       `<div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center"><i class="fas fa-${r.icon} text-gray-400"></i></div>`}
                                    <div>
                                        <p class="font-medium text-gray-800">${r.title}</p>
                                        <p class="text-xs text-gray-500">${r.subtitle}</p>
                                    </div>
                                </a>
                            `).join('');
                            searchResults.classList.remove('hidden');
                        } else {
                            searchResults.innerHTML = '<div class="p-4 text-center text-gray-500">No results found</div>';
                            searchResults.classList.remove('hidden');
                        }
                    });
                }, 300);
            });
            
            // Hide results on click outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }
    </script>

    <?= $additionalScripts ?? '' ?>
</body>

</html>