<?php
/**
 * Bootstrap file - loads all required classes
 */

define('AMS_LOADED', true);

// Load configuration
require_once __DIR__ . '/config/config.php';

// Autoloader for includes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/includes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize authentication
Auth::init();

/**
 * Helper functions
 */

// Redirect helper
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// Flash message helper
function flash($key, $value = null)
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
    } else {
        $val = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
}

// Get flash messages for display
function getFlashMessages()
{
    $messages = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $messages;
}

// JSON response helper
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Format currency
function formatCurrency($amount)
{
    return '₹ ' . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = 'd-M-Y')
{
    if (empty($date) || $date === '0000-00-00')
        return '-';
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'd-M-Y H:i')
{
    if (empty($datetime))
        return '-';
    return date($format, strtotime($datetime));
}

// Truncate text
function truncate($text, $length = 50)
{
    if (strlen($text) <= $length)
        return $text;
    return substr($text, 0, $length) . '...';
}

// Get asset URL
function asset($path)
{
    return APP_URL . '/public/assets/' . ltrim($path, '/');
}

// Get base URL
function url($path = '')
{
    return APP_URL . '/' . ltrim($path, '/');
}

// Check if current page
function isCurrentPage($page)
{
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return $currentPage === $page;
}

// Get status badge class
function getStatusBadge($status)
{
    $badges = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'pending_hod' => 'bg-orange-100 text-orange-800',
        'pending_supervisor' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'completed' => 'bg-green-100 text-green-800',
        'new' => 'bg-blue-100 text-blue-800',
        'good' => 'bg-green-100 text-green-800',
        'fair' => 'bg-yellow-100 text-yellow-800',
        'poor' => 'bg-orange-100 text-orange-800',
        'non_serviceable' => 'bg-red-100 text-red-800',
        'scrapped' => 'bg-gray-100 text-gray-800'
    ];
    return $badges[$status] ?? 'bg-gray-100 text-gray-800';
}

// Format file size
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Time ago helper
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60)
        return 'Just now';
    if ($diff < 3600)
        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)
        return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800)
        return floor($diff / 86400) . ' days ago';

    return formatDate($datetime);
}
