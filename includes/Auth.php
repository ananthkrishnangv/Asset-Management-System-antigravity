<?php
/**
 * Authentication Class
 * Handles user authentication, sessions, and role-based access control
 */

class Auth
{
    private static $user = null;

    /**
     * Initialize authentication
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        Security::setSecurityHeaders();

        if (isset($_SESSION['user_id'])) {
            self::loadUser($_SESSION['user_id']);
        }
    }

    /**
     * Load user from database
     */
    private static function loadUser($userId)
    {
        $db = Database::getInstance();
        self::$user = $db->fetch(
            "SELECT u.*, d.name as department_name, d.code as department_code 
             FROM users u 
             LEFT JOIN departments d ON u.department_id = d.id 
             WHERE u.id = ? AND u.is_active = 1",
            [$userId]
        );
    }

    /**
     * Attempt login
     */
    public static function attempt($amsId, $password)
    {
        // Check rate limiting
        if (!Security::checkLoginAttempts($amsId)) {
            return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
        }

        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT * FROM users WHERE ams_id = ? AND is_active = 1",
            [$amsId]
        );

        if (!$user) {
            Security::recordFailedLogin($amsId);
            return ['success' => false, 'message' => 'AMS ID not found'];
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            Security::recordFailedLogin($amsId);
            return ['success' => false, 'message' => 'Incorrect password'];
        }

        // Success - clear login attempts
        Security::clearLoginAttempts($amsId);

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['ams_id'] = $user['ams_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Update last login
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        // Log activity
        ActivityLog::log('login', 'auth', $user['id'], 'user', 'User logged in: ' . $user['emp_name']);

        self::$user = $user;

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        if (self::$user) {
            ActivityLog::log('logout', 'auth', self::$user['id'], 'user', 'User logged out: ' . self::$user['emp_name']);
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        self::$user = null;
    }

    /**
     * Check if user is logged in
     */
    public static function check()
    {
        return self::$user !== null;
    }

    /**
     * Get current user
     */
    public static function user()
    {
        return self::$user;
    }

    /**
     * Get user ID
     */
    public static function id()
    {
        return self::$user['id'] ?? null;
    }

    /**
     * Get user role
     */
    public static function role()
    {
        return self::$user['role'] ?? null;
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        return self::role() === 'admin';
    }

    /**
     * Check if user is supervisor
     */
    public static function isSupervisor()
    {
        return in_array(self::role(), ['admin', 'supervisor']);
    }

    /**
     * Check if user is employee
     */
    public static function isEmployee()
    {
        return self::role() === 'employee';
    }

    /**
     * Require authentication
     */
    public static function requireAuth()
    {
        if (!self::check()) {
            if (self::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'Authentication required']);
                exit;
            }
            header('Location: ' . APP_URL . '/public/index.php?error=auth');
            exit;
        }

        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
            self::logout();
            header('Location: ' . APP_URL . '/public/index.php?error=timeout');
            exit;
        }

        // Refresh session time
        $_SESSION['login_time'] = time();
    }

    /**
     * Require specific role(s)
     */
    public static function requireRole($roles)
    {
        self::requireAuth();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array(self::role(), $roles)) {
            if (self::isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            header('Location: ' . APP_URL . '/public/dashboard.php?error=access');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public static function requireAdmin()
    {
        self::requireRole(['admin']);
    }

    /**
     * Require supervisor or admin
     */
    public static function requireSupervisor()
    {
        self::requireRole(['admin', 'supervisor']);
    }

    /**
     * Check permission for action
     */
    public static function can($action)
    {
        $permissions = [
            'admin' => ['*'],
            'supervisor' => [
                'view_dashboard',
                'view_inventory',
                'add_inventory',
                'edit_inventory',
                'delete_inventory',
                'transfer_items',
                'approve_transfers',
                'stores_return',
                'view_reports',
                'export_data',
                'view_employees'
            ],
            'employee' => [
                'view_dashboard',
                'view_inventory',
                'initiate_transfer',
                'initiate_stores_return',
                'view_own_items'
            ]
        ];

        $role = self::role();
        if (!$role)
            return false;

        $userPermissions = $permissions[$role] ?? [];

        return in_array('*', $userPermissions) || in_array($action, $userPermissions);
    }

    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Generate password reset token
     */
    public static function generatePasswordResetToken($email)
    {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE email_id = ? AND is_active = 1", [$email]);

        if (!$user) {
            return false;
        }

        $token = Security::generateResetToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $db->update(
            'users',
            ['password_reset_token' => $token, 'password_reset_expires' => $expires],
            'id = :id',
            ['id' => $user['id']]
        );

        return ['token' => $token, 'user' => $user];
    }

    /**
     * Reset password with token
     */
    public static function resetPassword($token, $newPassword)
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            "SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW() AND is_active = 1",
            [$token]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }

        $hashedPassword = Security::hashPassword($newPassword);

        $db->update(
            'users',
            ['password' => $hashedPassword, 'password_reset_token' => null, 'password_reset_expires' => null],
            'id = :id',
            ['id' => $user['id']]
        );

        ActivityLog::log('update', 'auth', $user['id'], 'user', 'Password reset for user: ' . $user['emp_name']);

        return ['success' => true, 'message' => 'Password reset successfully'];
    }
}
