<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - CSIR-SERC AMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- <link rel="shortcut icon" href="<?= url('Image/logo-serc.jpg') ?>"> -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .sidebar-gradient { background: linear-gradient(180deg, #1a365d 0%, #0f172a 100%); }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); }
        .card-shadow { box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12); }
        /* Simple dropdown and scrollbar styles */
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; margin-top: 0.5rem; min-width: 200px; background: white; border-radius: 0.75rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); z-index: 50; }
        .dropdown:hover .dropdown-menu { display: block; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .toast { animation: slideInRight 0.3s ease-out; }
        @keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
    <?= $additionalStyles ?? '' ?>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
