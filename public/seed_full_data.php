<?php
define('AMS_LOADED', true);
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Seeding Data...</h2>";

    // 1. Departments
    $depts = [
        ['General Administration', 'ADM'],
        ['Civil Engineering', 'CIVIL'],
        ['Electrical Engineering', 'ELEC'],
        ['Stores & Purchase', 'S&P'],
        ['Director Office', 'DIR']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name, code) VALUES (?, ?)");
    foreach ($depts as $d) {
        $stmt->execute($d);
    }
    echo "Departments seeded.<br>";

    // 2. Categories
    $cats = ['Electronics', 'Furniture', 'Lab Equipment', 'Computers', 'Vehicles', 'Office Supplies'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    foreach ($cats as $c) {
        $stmt->execute([$c]);
    }
    echo "Categories seeded.<br>";

    // 3. Inventory Items (DIR & PIR)
    // Get department IDs
    $deptIds = $pdo->query("SELECT id FROM departments")->fetchAll(PDO::FETCH_COLUMN);
    $catIds = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);
    $userId = 1; // Admin

    $stmt = $pdo->prepare("INSERT INTO inventory_items (
        serial_number, item_description, category_id, quantity, quantity_unit, amount, 
        purchase_date, po_number, department_id, current_holder_id, condition_status, 
        inventory_type, is_active, created_by, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?
    )");

    $conditions = ['new', 'good', 'fair', 'poor'];
    $types = ['dir', 'pir'];
    
    // Create 30 items
    for ($i = 1; $i <= 30; $i++) {
        $type = $types[array_rand($types)];
        $catId = $catIds[array_rand($catIds)];
        $deptId = $deptIds[array_rand($deptIds)];
        $cond = $conditions[array_rand($conditions)];
        
        $date = date('Y-m-d', strtotime("-" . rand(1, 1000) . " days"));
        $amount = rand(1000, 500000);
        $serial = strtoupper($type) . '/' . date('Y') . '/' . str_pad($i, 4, '0', STR_PAD_LEFT);
        
        $desc = "Sample Item $i - " . ($type == 'dir' ? 'Division Asset' : 'Project Asset');
        
        $stmt->execute([
            $serial, $desc, $catId, 1, 'Nos', $amount, 
            $date, 'PO-'.rand(100,999), $deptId, $userId, $cond,
            $type, $userId, $date
        ]);
    }
    echo "Inventory Items seeded (30 items).<br>";
    
    // 4. Activity Logs (Mocking recent activity)
    $stmt = $pdo->prepare("INSERT INTO activity_logs (action_type, resource_type, resource_id, resource_category, description, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $actions = ['create', 'update', 'transfer'];
    $lastId = $pdo->lastInsertId();
    
    for ($j = 0; $j < 10; $j++) {
        $act = $actions[array_rand($actions)];
        $stmt->execute([
            $act, 'inventory', rand(1, $lastId), 'dir', 
            ucfirst($act) . "d an item via system", $userId, 
            date('Y-m-d H:i:s', strtotime("-" . rand(1, 48) . " hours"))
        ]);
    }
    echo "Activity Logs seeded.<br>";

    // 5. Transfer Requests (Mocking pending)
    // Need a supervisor user?
    // We'll just create a request for admin for now
    $stmt = $pdo->prepare("INSERT INTO transfer_requests (item_id, from_user_id, to_user_id, status, request_date) VALUES (?, ?, ?, 'pending_supervisor', NOW())");
    $stmt->execute([rand(1, 10), $userId, $userId]);
    echo "Transfer requests seeded.<br>";

    echo "<h3>Seeding Complete! Refresh Dashboard.</h3>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
