<?php
/**
 * Activity Log Class
 * Handles logging of all user activities
 */

class ActivityLog
{

    /**
     * Log an activity
     */
    public static function log($actionType, $module, $recordId = null, $recordType = null, $description = '', $oldValues = null, $newValues = null)
    {
        $db = Database::getInstance();

        $userId = Auth::id();
        $userName = Auth::user()['emp_name'] ?? 'System';

        $data = [
            'user_id' => $userId,
            'user_name' => $userName,
            'action_type' => $actionType,
            'module' => $module,
            'record_id' => $recordId,
            'record_type' => $recordType,
            'description' => $description,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Security::getClientIP(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ];

        try {
            $db->insert('activity_logs', $data);
        } catch (Exception $e) {
            error_log("Activity log error: " . $e->getMessage());
        }
    }

    /**
     * Get activity logs with filters
     */
    public static function getLogs($filters = [], $limit = 50, $offset = 0)
    {
        $db = Database::getInstance();

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $where[] = 'action_type = :action_type';
            $params['action_type'] = $filters['action_type'];
        }

        if (!empty($filters['module'])) {
            $where[] = 'module = :module';
            $params['module'] = $filters['module'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(description LIKE :search OR user_name LIKE :search2)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT * FROM activity_logs WHERE {$whereClause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Get total count for pagination
     */
    public static function getCount($filters = [])
    {
        $db = Database::getInstance();

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action_type'])) {
            $where[] = 'action_type = :action_type';
            $params['action_type'] = $filters['action_type'];
        }

        if (!empty($filters['module'])) {
            $where[] = 'module = :module';
            $params['module'] = $filters['module'];
        }

        $whereClause = implode(' AND ', $where);

        return $db->fetchValue("SELECT COUNT(*) FROM activity_logs WHERE {$whereClause}", $params);
    }

    /**
     * Clear old logs
     */
    public static function clearOldLogs($days = 90)
    {
        $db = Database::getInstance();
        return $db->delete('activity_logs', 'created_at < DATE_SUB(NOW(), INTERVAL :days DAY)', ['days' => $days]);
    }
}
