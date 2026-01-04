<?php
/**
 * User Model
 * Handles user-related database operations
 */

class User
{
    /**
     * Get all users with optional filtering
     */
    public static function getAll($filters = [], $limit = null, $offset = 0)
    {
        $db = Database::getInstance();
        $sql = "SELECT u.*, d.name as department_name, 
                h.emp_name as hod_name, s.emp_name as supervisor_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN users h ON u.hod_id = h.id
                LEFT JOIN users s ON u.supervisor_id = s.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND u.department_id = :dept_id";
            $params['dept_id'] = $filters['department_id'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (u.emp_name LIKE :search OR u.ams_id LIKE :search OR u.email_id LIKE :search)";
            $params['search'] = $search;
        }

        if (!empty($filters['is_active'])) {
             $sql .= " AND u.is_active = :is_active";
             $params['is_active'] = $filters['is_active'];
        }

        $sql .= " ORDER BY u.emp_name ASC";

        if ($limit) {
            $sql .= " LIMIT " . (int)$offset . ", " . (int)$limit;
        }

        return $db->fetchAll($sql, $params);
    }

    /**
     * Get user by ID
     */
    public static function find($id)
    {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    /**
     * Get user by AMS ID
     */
    public static function findByAmsId($amsId)
    {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE ams_id = ?", [$amsId]);
    }

    /**
     * Get user by Email
     */
    public static function findByEmail($email)
    {
        $db = Database::getInstance();
        return $db->fetch("SELECT * FROM users WHERE email_id = ?", [$email]);
    }

    /**
     * Create new user
     */
    public static function create($data)
    {
        $db = Database::getInstance();
        
        // Hash password if provided, else generate a random one (for email invite flow usually, but setting default here)
        if (empty($data['password'])) {
            $data['password'] = Security::hashPassword('Welcome@123'); // Default password
        } else {
            $data['password'] = Security::hashPassword($data['password']);
        }

        return $db->insert('users', $data);
    }

    /**
     * Update user
     */
    public static function update($id, $data)
    {
        $db = Database::getInstance();
        
        if (isset($data['password'])) {
            $data['password'] = Security::hashPassword($data['password']);
        }

        return $db->update('users', $data, "id = :id", ['id' => $id]);
    }

    /**
     * Delete user (Soft delete)
     */
    public static function delete($id)
    {
        $db = Database::getInstance();
        return $db->update('users', ['is_active' => 0], "id = :id", ['id' => $id]);
    }

    /**
     * Count users for pagination
     */
    public static function count($filters = [])
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
        $params = [];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND u.department_id = :dept_id";
            $params['dept_id'] = $filters['department_id'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (u.emp_name LIKE :search OR u.ams_id LIKE :search OR u.email_id LIKE :search)";
            $params['search'] = $search;
        }

        $result = $db->fetch($sql, $params);
        return $result['total'];
    }
    
    /**
     * Batch insert users (for Excel import)
     */
    public static function batchInsert($usersData) 
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $sql = "INSERT INTO users (ams_id, emp_name, email_id, password, role, department_id, designation, phone) 
                VALUES (:ams_id, :emp_name, :email_id, :password, :role, :department_id, :designation, :phone)
                ON DUPLICATE KEY UPDATE 
                emp_name = VALUES(emp_name), 
                email_id = VALUES(email_id),
                department_id = VALUES(department_id),
                designation = VALUES(designation)";
                
        $stmt = $conn->prepare($sql);
        
        $count = 0;
        $errors = [];
        
        foreach ($usersData as $userData) {
            try {
                // Default password logic if not present
                if (empty($userData['password'])) {
                     $userData['password'] = Security::hashPassword('Welcome@123');
                }
                
                $stmt->execute([
                    ':ams_id' => $userData['ams_id'],
                    ':emp_name' => $userData['emp_name'],
                    ':email_id' => $userData['email_id'],
                    ':password' => $userData['password'],
                    ':role' => $userData['role'] ?? 'employee',
                    ':department_id' => $userData['department_id'] ?? null,
                    ':designation' => $userData['designation'] ?? null,
                    ':phone' => $userData['phone'] ?? null
                ]);
                $count++;
            } catch (PDOException $e) {
                $errors[] = "Error for AMS ID {$userData['ams_id']}: " . $e->getMessage();
            }
        }
        
        return ['count' => $count, 'errors' => $errors];
    }
}
