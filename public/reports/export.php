<?php
/**
 * Export Handler - PDF and Excel exports
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAuth();
Auth::requireSupervisor();

$type = $_GET['type'] ?? 'dir';
$format = $_GET['format'] ?? 'csv';
$dateFrom = $_GET['from'] ?? null;
$dateTo = $_GET['to'] ?? null;

$db = Database::getInstance();

// Build query
$inventoryType = in_array($type, ['dir', 'pir']) ? $type : 'dir';

$where = "i.inventory_type = ? AND i.is_active = 1";
$params = [$inventoryType];

if ($dateFrom) {
    $where .= " AND DATE(i.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where .= " AND DATE(i.created_at) <= ?";
    $params[] = $dateTo;
}

$items = $db->fetchAll(
    "SELECT i.serial_number, i.item_description, c.name as category, 
            i.quantity, i.quantity_unit, i.amount, i.po_number, i.po_date,
            i.purchase_date, i.budget_head, i.stock_reference,
            d.name as department, i.building_location, i.floor_location, i.room_location,
            u.emp_name as holder, i.condition_status, i.created_at
     FROM inventory_items i
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN departments d ON i.department_id = d.id
     LEFT JOIN users u ON i.current_holder_id = u.id
     WHERE {$where}
     ORDER BY i.serial_number",
    $params
);

// Log export
ActivityLog::log(
    'export',
    'reports',
    null,
    'inventory',
    'Exported ' . strtoupper($inventoryType) . ' inventory (' . count($items) . ' items)'
);

// Export as CSV (Excel compatible)
if ($format === 'csv' || $format === 'excel') {
    $filename = strtoupper($inventoryType) . '_Inventory_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // Headers
    fputcsv($output, [
        'Serial Number',
        'Description',
        'Category',
        'Quantity',
        'Unit',
        'Amount',
        'PO Number',
        'PO Date',
        'Purchase Date',
        'Budget Head',
        'Stock Reference',
        'Department',
        'Building',
        'Floor',
        'Room',
        'Holder',
        'Condition',
        'Created At'
    ]);

    // Data
    foreach ($items as $item) {
        fputcsv($output, [
            $item['serial_number'],
            $item['item_description'],
            $item['category'],
            $item['quantity'],
            $item['quantity_unit'],
            $item['amount'],
            $item['po_number'],
            $item['po_date'],
            $item['purchase_date'],
            $item['budget_head'],
            $item['stock_reference'],
            $item['department'],
            $item['building_location'],
            $item['floor_location'],
            $item['room_location'],
            $item['holder'],
            $item['condition_status'],
            $item['created_at']
        ]);
    }

    fclose($output);
    exit;
}

// Export as PDF (HTML-based for now, can be enhanced with TCPDF/mPDF later)
if ($format === 'pdf' || $type === 'pdf') {
    // Simple HTML print-ready format
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title><?= strtoupper($inventoryType) ?> Inventory Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
                margin: 20px;
            }

            h1 {
                text-align: center;
                margin-bottom: 5px;
            }

            h2 {
                text-align: center;
                color: #666;
                font-size: 12px;
                margin-top: 0;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
            }

            th {
                background: #1a365d;
                color: white;
                font-size: 9px;
            }

            tr:nth-child(even) {
                background: #f9f9f9;
            }

            .header {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 20px;
                margin-bottom: 20px;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            .amount {
                text-align: right;
            }

            .footer {
                margin-top: 20px;
                text-align: center;
                font-size: 9px;
                color: #666;
            }

            @media print {
                body {
                    margin: 0;
                }
            }
        </style>
    </head>

    <body onload="window.print()">
        <div class="header">
            <h1>CSIR-SERC</h1>
        </div>
        <h2><?= strtoupper($inventoryType) ?> Inventory Report - Generated on <?= date('d-M-Y H:i') ?></h2>

        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Serial Number</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>PO No</th>
                    <th>Department</th>
                    <th>Holder</th>
                    <th>Condition</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($item['serial_number']) ?></td>
                        <td><?= htmlspecialchars(substr($item['item_description'], 0, 50)) ?></td>
                        <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td class="amount">₹<?= number_format($item['amount'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars($item['po_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['department'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['holder'] ?? '') ?></td>
                        <td><?= htmlspecialchars($item['condition_status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="5">Total: <?= count($items) ?> items</th>
                    <th class="amount">₹<?= number_format(array_sum(array_column($items, 'amount')), 2) ?></th>
                    <th colspan="4"></th>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>CSIR-SERC Asset Management System | Report generated by <?= Security::escape(Auth::user()['emp_name']) ?></p>
        </div>
    </body>

    </html>
    <?php
    exit;
}

// Default: redirect back
header('Location: index.php');
