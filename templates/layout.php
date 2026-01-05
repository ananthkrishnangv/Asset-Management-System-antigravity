<?php
/**
 * CSIR-SERC Asset Management System
 * Main Layout Template - Light Fluent Design
 * Version 3.0
 */

// Fetch active news ticker items (with error handling)
$tickerItems = [];
try {
    $db = Database::getInstance();
    $tickerItems = $db->fetchAll(
        "SELECT * FROM news_ticker WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 10"
    ) ?: [];
} catch (Exception $e) {
    // Silently fail - ticker is not critical
    $tickerItems = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - CSIR-SERC AMS</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Icons & Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ═══════════════════════════════════════════════════════════════
           CSS VARIABLES - LIGHT FLUENT THEME
           ═══════════════════════════════════════════════════════════════ */
        :root {
            --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --gradient-warning: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
            --gradient-danger: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            --color-primary: #667eea;
            --color-secondary: #764ba2;
        }

        * {
            font-family: var(--font-primary);
        }

        /* ═══════════════════════════════════════════════════════════════
           SIDEBAR STYLES - LIGHT FLUENT
           ═══════════════════════════════════════════════════════════════ */
        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-right: 1px solid #e2e8f0;
            position: relative;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: radial-gradient(rgba(102, 126, 234, 0.03) 1px, transparent 1px);
            background-size: 20px 20px;
            pointer-events: none;
        }

        .nav-item {
            position: relative;
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .nav-item:hover,
        .nav-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .nav-item:hover::before,
        .nav-item.active::before {
            opacity: 1;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.08) 100%);
        }

        /* ═══════════════════════════════════════════════════════════════
           NEWS TICKER
           ═══════════════════════════════════════════════════════════════ */
        .ticker-container {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }

        .ticker-content {
            display: flex;
            animation: scroll-left 30s linear infinite;
            white-space: nowrap;
        }

        .ticker-content:hover {
            animation-play-state: paused;
        }

        @keyframes scroll-left {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .badge-important {
            background: #ef4444;
        }

        .badge-new {
            background: #22c55e;
        }

        .badge-alert {
            background: #f97316;
        }

        .badge-warning {
            background: #eab308;
            color: #1f2937;
        }

        .badge-info {
            background: #3b82f6;
        }

        /* ═══════════════════════════════════════════════════════════════
           HEADER & CARDS
           ═══════════════════════════════════════════════════════════════ */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .search-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            background: white;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .card-shadow {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            background: var(--gradient-primary);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .stat-icon.green {
            background: var(--gradient-success);
            box-shadow: 0 8px 20px rgba(56, 239, 125, 0.3);
        }

        .stat-icon.yellow {
            background: var(--gradient-warning);
            box-shadow: 0 8px 20px rgba(242, 201, 76, 0.3);
        }

        .stat-icon.purple {
            background: var(--gradient-secondary);
            box-shadow: 0 8px 20px rgba(118, 75, 162, 0.3);
        }

        .stat-icon.red {
            background: var(--gradient-danger);
            box-shadow: 0 8px 20px rgba(244, 92, 67, 0.3);
        }

        .stat-icon.cyan {
            background: var(--gradient-accent);
            box-shadow: 0 8px 20px rgba(0, 242, 254, 0.3);
        }

        /* ═══════════════════════════════════════════════════════════════
           DROPDOWN & BUTTONS
           ═══════════════════════════════════════════════════════════════ */
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            min-width: 220px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 50;
            overflow: hidden;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
            animation: fadeInDown 0.2s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        /* ═══════════════════════════════════════════════════════════════
           TOAST, MODAL, SCROLLBAR
           ═══════════════════════════════════════════════════════════════ */
        .toast {
            animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
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

        .modal-backdrop {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            animation: modalSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

        .table-row-hover:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
        }

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

        .user-avatar {
            background: var(--gradient-primary);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* ═══════════════════════════════════════════════════════════════
           MOBILE RESPONSIVE
           ═══════════════════════════════════════════════════════════════ */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -280px;
                z-index: 40;
                transition: left 0.3s ease;
            }

            .sidebar.open {
                left: 0;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .mobile-menu-btn {
                display: flex !important;
            }
        }

        .mobile-menu-btn {
            display: none;
        }
    </style>
    <?= $additionalStyles ?? '' ?>
</head>

<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- ═══════════════════════════════════════════════════════════════
             SIDEBAR - LIGHT FLUENT
             ═══════════════════════════════════════════════════════════════ -->
        <aside class="sidebar w-64 fixed h-full z-30 flex flex-col" id="sidebar">
            <!-- Logo Section -->
            <div class="p-5 border-b border-gray-200 relative z-10">
                <a href="<?= url('public/dashboard.php') ?>" class="block">
                    <img src="<?= url('Image/portal-logo.png') ?>" alt="Asset Management System"
                        class="h-16 object-contain">
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-6 px-4 overflow-y-auto relative z-10">
                <ul class="space-y-2">
                    <li>
                        <a href="<?= url('public/dashboard.php') ?>"
                            class="nav-item <?= isCurrentPage('dashboard') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                            <i class="fas fa-chart-pie w-5 text-center text-purple-500"></i>
                            <span class="font-medium">Dashboard</span>
                        </a>
                    </li>

                    <li class="pt-4">
                        <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Inventory</p>
                    </li>

                    <li>
                        <a href="<?= url('public/inventory/dir.php') ?>"
                            class="nav-item <?= isCurrentPage('dir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                            <i class="fas fa-building w-5 text-center text-blue-500"></i>
                            <span class="font-medium">DIR</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= url('public/inventory/pir.php') ?>"
                            class="nav-item <?= isCurrentPage('pir') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                            <i class="fas fa-user-tag w-5 text-center text-green-500"></i>
                            <span class="font-medium">PIR</span>
                        </a>
                    </li>

                    <li class="pt-4">
                        <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Transfers</p>
                    </li>

                    <li>
                        <a href="<?= url('public/transfers/index.php') ?>"
                            class="nav-item <?= isCurrentPage('transfers') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                            <i class="fas fa-exchange-alt w-5 text-center text-amber-500"></i>
                            <span class="font-medium">Transfers</span>
                        </a>
                    </li>

                    <li>
                        <a href="<?= url('public/stores/returns.php') ?>"
                            class="nav-item <?= isCurrentPage('returns') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                            <i class="fas fa-warehouse w-5 text-center text-teal-500"></i>
                            <span class="font-medium">Stores Returns</span>
                        </a>
                    </li>

                    <?php if (Auth::isSupervisor()): ?>
                        <li class="pt-4">
                            <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Reports</p>
                        </li>

                        <li>
                            <a href="<?= url('public/reports/index.php') ?>"
                                class="nav-item <?= isCurrentPage('reports') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-chart-bar w-5 text-center text-indigo-500"></i>
                                <span class="font-medium">Reports</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/qr/index.php') ?>"
                                class="nav-item <?= isCurrentPage('qr') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-qrcode w-5 text-center text-pink-500"></i>
                                <span class="font-medium">QR Codes</span>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (Auth::isAdmin()): ?>
                        <li class="pt-4">
                            <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Admin</p>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/users.php') ?>"
                                class="nav-item <?= isCurrentPage('users') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-users-cog w-5 text-center text-purple-500"></i>
                                <span class="font-medium">User Management</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/departments.php') ?>"
                                class="nav-item <?= isCurrentPage('departments') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-sitemap w-5 text-center text-cyan-500"></i>
                                <span class="font-medium">Departments</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/ticker.php') ?>"
                                class="nav-item <?= isCurrentPage('ticker') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-bullhorn w-5 text-center text-orange-500"></i>
                                <span class="font-medium">News Ticker</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/logs/activity.php') ?>"
                                class="nav-item <?= isCurrentPage('activity') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-history w-5 text-center text-gray-500"></i>
                                <span class="font-medium">Activity Logs</span>
                            </a>
                        </li>

                        <li>
                            <a href="<?= url('public/admin/settings.php') ?>"
                                class="nav-item <?= isCurrentPage('settings') ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 text-gray-600 hover:text-gray-900 transition-all">
                                <i class="fas fa-cog w-5 text-center text-gray-500"></i>
                                <span class="font-medium">Settings</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- User Info -->
            <div class="p-4 border-t border-gray-200 relative z-10 bg-gradient-to-r from-purple-50 to-indigo-50">
                <div class="flex items-center gap-3 px-2">
                    <div class="user-avatar w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr(Auth::user()['emp_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-800 text-sm font-semibold truncate">
                            <?= Security::escape(Auth::user()['emp_name'] ?? '') ?>
                        </p>
                        <p class="text-purple-600 text-xs capitalize font-medium"><?= Auth::role() ?></p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ═══════════════════════════════════════════════════════════════
             MAIN CONTENT
             ═══════════════════════════════════════════════════════════════ -->
        <div class="flex-1 ml-64 main-content">
            <!-- News Ticker -->
            <?php if (!empty($tickerItems)): ?>
                <div class="ticker-container py-2">
                    <div class="ticker-content">
                        <?php for ($i = 0; $i < 2; $i++): // Duplicate for seamless scroll ?>
                            <?php foreach ($tickerItems as $item): ?>
                                <span class="inline-flex items-center gap-2 px-6 text-white text-sm">
                                    <span class="badge-<?= $item['badge_type'] ?> px-2 py-0.5 rounded text-xs font-bold uppercase">
                                        <?= ucfirst($item['badge_type']) ?>
                                    </span>
                                    <span class="font-medium"><?= Security::escape($item['title']) ?></span>
                                    <?php if (!empty($item['content'])): ?>
                                        <span class="opacity-75">— <?= Security::escape(truncate($item['content'], 80)) ?></span>
                                    <?php endif; ?>
                                    <span class="mx-8 opacity-50">•</span>
                                </span>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Top Header -->
            <header class="header sticky top-0 z-20">
                <div class="flex items-center justify-between px-8 py-4">
                    <div class="flex items-center gap-4">
                        <!-- Mobile Menu Button -->
                        <button class="mobile-menu-btn p-2 text-gray-600 hover:bg-gray-100 rounded-xl lg:hidden"
                            onclick="document.getElementById('sidebar').classList.toggle('open')">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
                            <p class="text-sm text-gray-500 font-medium">
                                <?= $pageSubtitle ?? 'Welcome to Asset Management System' ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Global Search -->
                        <div class="relative hidden md:block">
                            <input type="text" id="globalSearch" placeholder="Search anything..."
                                class="search-input pl-10 pr-4 py-2.5 w-72 rounded-xl focus:outline-none">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <div id="searchResults"
                                class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 hidden max-h-96 overflow-y-auto z-50">
                            </div>
                        </div>

                        <!-- Notifications -->
                        <button
                            class="relative p-2.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition-colors">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <!-- User Menu -->
                        <div class="dropdown relative">
                            <button
                                class="flex items-center gap-3 px-3 py-2 hover:bg-gray-100 rounded-xl transition-colors">
                                <div
                                    class="user-avatar w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm">
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
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </button>

                            <div class="dropdown-menu">
                                <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-purple-50 to-indigo-50">
                                    <p class="font-semibold text-gray-800">
                                        <?= Security::escape(Auth::user()['emp_name'] ?? '') ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= Security::escape(Auth::user()['email_id'] ?? '') ?>
                                    </p>
                                </div>
                                <div class="py-2">
                                    <a href="<?= url('public/profile.php') ?>"
                                        class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-user w-4 text-gray-400"></i>
                                        <span class="font-medium">My Profile</span>
                                    </a>
                                </div>
                                <div class="border-t border-gray-100 py-2">
                                    <a href="<?= url('public/logout.php') ?>"
                                        onclick="return confirm('Are you sure you want to logout?')"
                                        class="flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors">
                                        <i class="fas fa-sign-out-alt w-4"></i>
                                        <span class="font-medium">Logout</span>
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
                            class="toast max-w-sm p-4 rounded-xl shadow-lg <?= $type === 'error' ? 'bg-gradient-to-r from-red-500 to-rose-500' : ($type === 'success' ? 'bg-gradient-to-r from-emerald-500 to-green-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500') ?> text-white">
                            <div class="flex items-center gap-3">
                                <i
                                    class="fas <?= $type === 'error' ? 'fa-times-circle' : ($type === 'success' ? 'fa-check-circle' : 'fa-info-circle') ?>"></i>
                                <span class="font-medium"><?= Security::escape($message) ?></span>
                                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto hover:opacity-75">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <script>setTimeout(() => document.getElementById('flashMessages')?.remove(), 5000);</script>
            <?php endif; ?>

            <!-- Page Content -->
            <main class="p-8">
                <?= $content ?? '' ?>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-100 py-4 px-8">
                <div class="flex flex-col md:flex-row items-center justify-between text-sm text-gray-500 gap-2">
                    <p class="font-medium">© <?= date('Y') ?> CSIR-SERC. All rights reserved.</p>
                    <p class="text-gray-400/60 text-xs italic">Prompted and Engineered by - GV AnanthaKrishnan, ICTD</p>
                    <p class="text-gray-400">Version <?= APP_VERSION ?></p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Common Scripts -->
    <script>
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const gradients = {
                success: 'from-emerald-500 to-green-500',
                error: 'from-red-500 to-rose-500',
                warning: 'from-amber-500 to-yellow-500',
                info: 'from-blue-500 to-indigo-500'
            };
            const toast = document.createElement('div');
            toast.className = `toast max-w-sm p-4 rounded-xl shadow-lg bg-gradient-to-r ${gradients[type]} text-white`;
            toast.innerHTML = `
                <div class="flex items-center gap-3">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                    <span class="font-medium">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto hover:opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        function formatCurrency(amount) {
            return '₹ ' + new Intl.NumberFormat('en-IN').format(amount);
        }

        // Global Search
        let searchTimeout;
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('searchResults');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length < 2) { searchResults.classList.add('hidden'); return; }
                searchTimeout = setTimeout(() => {
                    fetch(`<?= url('api/search.php') ?>?q=${encodeURIComponent(query)}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.results && data.results.length > 0) {
                                searchResults.innerHTML = data.results.map(r => `
                                <a href="${r.url}" class="flex items-center gap-3 p-3 hover:bg-gray-50 border-b border-gray-100 last:border-0 transition-colors">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg flex items-center justify-center"><i class="fas fa-${r.icon} text-purple-500"></i></div>
                                    <div><p class="font-semibold text-gray-800">${r.title}</p><p class="text-xs text-gray-500">${r.subtitle}</p></div>
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
            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }

        // Mobile sidebar overlay
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('.mobile-menu-btn')) {
                sidebar.classList.remove('open');
            }
        });
    </script>

    <?= $additionalScripts ?? '' ?>
</body>

</html>