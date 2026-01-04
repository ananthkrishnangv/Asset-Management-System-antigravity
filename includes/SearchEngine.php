<?php
/**
 * Search Engine Class
 * Handles smart search, categorization, and "AI" insights
 */

class SearchEngine
{
    /**
     * Smart search across assets and users
     */
    public static function search($query)
    {
        if (empty($query)) return [];

        $db = Database::getInstance();
        $results = [];
        $query = trim($query);
        $likeQuery = "%$query%";

        // 1. Search Assets
        $assets = $db->fetchAll(
            "SELECT id, 'asset' as type, item_description as title, serial_number as subtitle, 
             condition_status as status, image_path 
             FROM inventory_items 
             WHERE item_description LIKE ? OR serial_number LIKE ? OR po_number LIKE ? 
             LIMIT 10",
            [$likeQuery, $likeQuery, $likeQuery]
        );

        // 2. Search Users
        $users = $db->fetchAll(
            "SELECT id, 'user' as type, emp_name as title, ams_id as subtitle, 
             role as status, NULL as image_path 
             FROM users 
             WHERE emp_name LIKE ? OR ams_id LIKE ? OR email_id LIKE ? 
             LIMIT 5",
            [$likeQuery, $likeQuery, $likeQuery]
        );

        // 3. Search Departments
        $depts = $db->fetchAll(
            "SELECT id, 'department' as type, name as title, code as subtitle, 
             NULL as status, NULL as image_path 
             FROM departments 
             WHERE name LIKE ? OR code LIKE ? 
             LIMIT 3",
            [$likeQuery, $likeQuery]
        );

        return array_merge($assets, $users, $depts);
    }

    /**
     * AI Insights: Categorization Suggestions
     * Suggests a category based on item description keywords
     */
    public static function suggestCategory($description)
    {
        $description = strtolower($description);
        
        $rules = [
            'Furniture' => ['chair', 'table', 'desk', 'cupboard', 'stool', 'sofa', 'rack', 'cabinet'],
            'Computer Equipment' => ['laptop', 'desktop', 'monitor', 'keyboard', 'mouse', 'printer', 'scanner', 'server', 'hdd', 'ssd', 'usb', 'cpu'],
            'Lab Equipment' => ['microscope', 'analyzer', 'meter', 'gauge', 'setup', 'sensor', 'probe'],
            'Electrical Equipment' => ['fan', 'light', 'ac', 'cooler', 'heater', 'switch', 'kettle'],
            'Office Equipment' => ['shredder', 'projector', 'whiteboard', 'stapler']
        ];

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($description, $keyword) !== false) {
                    // Return Category ID if possible, here returning name for demo
                    //Ideally, lookup ID from DB logic
                    return $category;
                }
            }
        }

        return null; // No suggestion
    }

    /**
     * AI Insights: Maintenance Prediction
     * Predicts items needing attention based on age and condition
     */
    public static function getMaintenanceInsights()
    {
        $db = Database::getInstance();
        
        // Items older than 5 years or in 'poor' condition
        $sql = "SELECT * FROM inventory_items 
                WHERE (purchase_date < DATE_SUB(NOW(), INTERVAL 5 YEAR) AND condition_status != 'scrapped')
                OR condition_status IN ('poor', 'broken')
                LIMIT 5";
                
        return $db->fetchAll($sql);
    }
}
