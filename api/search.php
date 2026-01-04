<?php
/**
 * Global Search API
 * Searches across all inventory, users, transfers, etc.
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();

header('Content-Type: application/json');

$query = Security::sanitize($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';

if (strlen($query) < 2) {
    jsonResponse(['results' => []]);
}

$db = Database::getInstance();
$searchTerm = '%' . $query . '%';
$results = [];

// Search Inventory
if ($type === 'all' || $type === 'inventory') {
    $items = $db->fetchAll(
        "SELECT id, serial_number, item_description, inventory_type, image_path
         FROM inventory_items 
         WHERE is_active = 1 
         AND (serial_number LIKE ? OR item_description LIKE ? OR detailed_description LIKE ? OR po_number LIKE ?)
         LIMIT 10",
        [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
    );

    foreach ($items as $item) {
        $results[] = [
            'type' => 'inventory',
            'id' => $item['id'],
            'title' => $item['item_description'],
            'subtitle' => $item['serial_number'] . ' | ' . strtoupper($item['inventory_type']),
            'icon' => 'box',
            'url' => url('public/inventory/item-details.php?id=' . $item['id']),
            'image' => $item['image_path'] ? url('uploads/' . $item['image_path']) : null
        ];
    }
}

// Search Users (Admin/Supervisor only)
if (($type === 'all' || $type === 'users') && Auth::isSupervisor()) {
    $users = $db->fetchAll(
        "SELECT id, ams_id, emp_name, email_id, role
         FROM users 
         WHERE is_active = 1 
         AND (ams_id LIKE ? OR emp_name LIKE ? OR email_id LIKE ?)
         LIMIT 10",
        [$searchTerm, $searchTerm, $searchTerm]
    );

    foreach ($users as $user) {
        $results[] = [
            'type' => 'user',
            'id' => $user['id'],
            'title' => $user['emp_name'],
            'subtitle' => $user['ams_id'] . ' | ' . ucfirst($user['role']),
            'icon' => 'user',
            'url' => url('public/admin/users.php?search=' . $user['ams_id'])
        ];
    }
}

// Search Transfers
if ($type === 'all' || $type === 'transfers') {
    $transfers = $db->fetchAll(
        "SELECT tr.id, tr.transfer_slip_number, tr.status, i.item_description
         FROM transfer_requests tr
         JOIN inventory_items i ON tr.item_id = i.id
         WHERE tr.transfer_slip_number LIKE ? OR i.item_description LIKE ?
         LIMIT 10",
        [$searchTerm, $searchTerm]
    );

    foreach ($transfers as $tr) {
        $results[] = [
            'type' => 'transfer',
            'id' => $tr['id'],
            'title' => $tr['item_description'],
            'subtitle' => 'Transfer: ' . $tr['transfer_slip_number'] . ' | ' . ucfirst($tr['status']),
            'icon' => 'exchange-alt',
            'url' => url('public/transfers/index.php')
        ];
    }
}

// Search Departments (Admin only)
if (($type === 'all' || $type === 'departments') && Auth::isAdmin()) {
    $depts = $db->fetchAll(
        "SELECT * FROM departments WHERE name LIKE ? OR code LIKE ? LIMIT 5",
        [$searchTerm, $searchTerm]
    );

    foreach ($depts as $dept) {
        $results[] = [
            'type' => 'department',
            'id' => $dept['id'],
            'title' => $dept['name'],
            'subtitle' => 'Code: ' . $dept['code'],
            'icon' => 'building',
            'url' => url('public/admin/departments.php')
        ];
    }
}

// Search Purchase Orders
if ($type === 'all' || $type === 'po') {
    $pos = $db->fetchAll(
        "SELECT DISTINCT po_number, po_date FROM inventory_items 
         WHERE po_number LIKE ? AND po_number IS NOT NULL
         LIMIT 5",
        [$searchTerm]
    );

    foreach ($pos as $po) {
        $results[] = [
            'type' => 'po',
            'title' => 'PO: ' . $po['po_number'],
            'subtitle' => 'Date: ' . formatDate($po['po_date']),
            'icon' => 'file-invoice',
            'url' => url('public/inventory/dir.php?search=' . urlencode($po['po_number']))
        ];
    }
}

jsonResponse([
    'results' => $results,
    'total' => count($results),
    'query' => $query
]);
