<?php
/**
 * BulkImport Class
 * Handles bulk import of inventory items from CSV/Excel files
 */

class BulkImport
{
    private $db;
    private $errors = [];
    private $successCount = 0;
    private $departments = [];
    private $categories = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadLookups();
    }

    /**
     * Load departments and categories for validation
     */
    private function loadLookups()
    {
        $depts = $this->db->fetchAll("SELECT id, code, name FROM departments");
        foreach ($depts as $d) {
            $this->departments[strtolower($d['code'])] = $d['id'];
            $this->departments[strtolower($d['name'])] = $d['id'];
        }

        $cats = $this->db->fetchAll("SELECT id, name FROM categories");
        foreach ($cats as $c) {
            $this->categories[strtolower($c['name'])] = $c['id'];
        }
    }

    /**
     * Get CSV template headers
     */
    public static function getTemplateHeaders()
    {
        return [
            'inventory_type',
            'serial_number',
            'item_description',
            'category',
            'department',
            'custodian',
            'unit_price',
            'purchase_date',
            'condition_status',
            'po_number',
            'supplier_name',
            'location',
            'remarks'
        ];
    }

    /**
     * Generate and download CSV template
     */
    public static function downloadTemplate()
    {
        $headers = self::getTemplateHeaders();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="bulk_import_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write headers
        fputcsv($output, $headers);

        // Write sample rows
        fputcsv($output, [
            'dir',
            'DIR-2026-001',
            'Sample Equipment',
            'Computer & Peripherals',
            'CAD',
            'John Doe',
            '50000',
            '2026-01-01',
            'new',
            'PO-2026-001',
            'ABC Suppliers',
            'Room 101',
            'Sample item for reference'
        ]);

        fputcsv($output, [
            'pir',
            'PIR-2026-001',
            'Personal Laptop',
            'Computer & Peripherals',
            'KM',
            'Jane Smith',
            '75000',
            '2026-01-05',
            'good',
            'PO-2026-002',
            'XYZ Vendors',
            'Desk 5',
            ''
        ]);

        fclose($output);
        exit;
    }

    /**
     * Parse uploaded CSV file
     */
    public function parseCSV($filePath)
    {
        $data = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $headers = array_map('strtolower', array_map('trim', $headers));

            $lineNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;
                if (count($row) !== count($headers)) {
                    $this->errors[] = "Line $lineNum: Column count mismatch";
                    continue;
                }

                $item = array_combine($headers, $row);
                $item['_line'] = $lineNum;
                $data[] = $item;
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Validate a single row
     */
    public function validateRow($row)
    {
        $errors = [];
        $line = $row['_line'] ?? '?';

        // Required fields
        if (empty($row['inventory_type']) || !in_array(strtolower($row['inventory_type']), ['dir', 'pir'])) {
            $errors[] = "Line $line: Invalid inventory_type (must be 'dir' or 'pir')";
        }

        if (empty($row['serial_number'])) {
            $errors[] = "Line $line: serial_number is required";
        } else {
            // Check for duplicate
            $existing = $this->db->fetchValue(
                "SELECT id FROM inventory_items WHERE serial_number = ?",
                [$row['serial_number']]
            );
            if ($existing) {
                $errors[] = "Line $line: serial_number '{$row['serial_number']}' already exists";
            }
        }

        if (empty($row['item_description'])) {
            $errors[] = "Line $line: item_description is required";
        }

        if (empty($row['category'])) {
            $errors[] = "Line $line: category is required";
        } elseif (!isset($this->categories[strtolower($row['category'])])) {
            $errors[] = "Line $line: Unknown category '{$row['category']}'";
        }

        if (empty($row['department'])) {
            $errors[] = "Line $line: department is required";
        } elseif (!isset($this->departments[strtolower($row['department'])])) {
            $errors[] = "Line $line: Unknown department '{$row['department']}'";
        }

        if (empty($row['unit_price']) || !is_numeric($row['unit_price'])) {
            $errors[] = "Line $line: unit_price must be a number";
        }

        // Validate date format if provided
        if (!empty($row['purchase_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $row['purchase_date']);
            if (!$date) {
                $errors[] = "Line $line: purchase_date must be in YYYY-MM-DD format";
            }
        }

        // Validate condition status
        $validConditions = ['new', 'good', 'fair', 'poor', 'non_serviceable', 'scrapped'];
        if (!empty($row['condition_status']) && !in_array(strtolower($row['condition_status']), $validConditions)) {
            $errors[] = "Line $line: Invalid condition_status";
        }

        return $errors;
    }

    /**
     * Import validated data
     */
    public function import($data, $userId)
    {
        $this->errors = [];
        $this->successCount = 0;

        foreach ($data as $row) {
            $rowErrors = $this->validateRow($row);
            if (!empty($rowErrors)) {
                $this->errors = array_merge($this->errors, $rowErrors);
                continue;
            }

            try {
                $categoryId = $this->categories[strtolower($row['category'])] ?? null;
                $departmentId = $this->departments[strtolower($row['department'])] ?? null;

                $itemData = [
                    'inventory_type' => strtolower($row['inventory_type']),
                    'serial_number' => $row['serial_number'],
                    'item_description' => $row['item_description'],
                    'category_id' => $categoryId,
                    'department_id' => $departmentId,
                    'custodian' => $row['custodian'] ?? null,
                    'unit_price' => floatval($row['unit_price']),
                    'purchase_date' => !empty($row['purchase_date']) ? $row['purchase_date'] : null,
                    'condition_status' => !empty($row['condition_status']) ? strtolower($row['condition_status']) : 'good',
                    'po_number' => $row['po_number'] ?? null,
                    'supplier_name' => $row['supplier_name'] ?? null,
                    'location' => $row['location'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                    'created_by' => $userId,
                    'is_active' => 1
                ];

                $this->db->insert('inventory_items', $itemData);
                $this->successCount++;
            } catch (Exception $e) {
                $line = $row['_line'] ?? '?';
                $this->errors[] = "Line $line: Database error - " . $e->getMessage();
            }
        }

        return [
            'success' => $this->successCount,
            'errors' => $this->errors
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }
}
