<?php
/**
 * Security Class
 * Handles CSRF, XSS prevention, input validation, and password hashing
 */

class Security
{

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken()
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token)
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCSRFToken()
    {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Get CSRF token input field
     */
    public static function csrfField()
    {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }

    /**
     * Sanitize input - prevent XSS
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for output
     */
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate integer
     */
    public static function validateInt($value)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate date format
     */
    public static function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Hash password using Argon2id (or bcrypt fallback)
     */
    public static function hashPassword($password)
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Generate password reset token
     */
    public static function generateResetToken()
    {
        return self::generateToken(32);
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename)
    {
        // Remove any path components
        $filename = basename($filename);
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        // Remove any characters that aren't alphanumeric, underscores, hyphens, or dots
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
        return $filename;
    }

    /**
     * Validate file type
     */
    public static function validateFileType($filename, $allowedTypes = null)
    {
        if ($allowedTypes === null) {
            $allowedTypes = ALLOWED_FILE_TYPES;
        }
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($ext, $allowedTypes);
    }

    /**
     * Get client IP address
     */
    public static function getClientIP()
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        return 'UNKNOWN';
    }

    /**
     * Prevent clickjacking
     */
    public static function setSecurityHeaders()
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Rate limiting check for login attempts
     */
    public static function checkLoginAttempts($identifier)
    {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }

        $now = time();
        $attempts = $_SESSION['login_attempts'][$identifier] ?? ['count' => 0, 'first_attempt' => $now];

        // Reset if lockout time passed
        if (($now - $attempts['first_attempt']) > LOGIN_LOCKOUT_TIME) {
            $_SESSION['login_attempts'][$identifier] = ['count' => 0, 'first_attempt' => $now];
            return true;
        }

        return $attempts['count'] < MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin($identifier)
    {
        if (!isset($_SESSION['login_attempts'][$identifier])) {
            $_SESSION['login_attempts'][$identifier] = ['count' => 0, 'first_attempt' => time()];
        }
        $_SESSION['login_attempts'][$identifier]['count']++;
    }

    /**
     * Clear login attempts on successful login
     */
    public static function clearLoginAttempts($identifier)
    {
        unset($_SESSION['login_attempts'][$identifier]);
    }
}
