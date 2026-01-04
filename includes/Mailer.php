<?php
/**
 * Mailer Class
 * Handles SMTP email sending using PHPMailer-like functionality
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;

    public function __construct()
    {
        // Simple mail setup without PHPMailer dependency initially
        // Will use native PHP mail or socket-based SMTP
    }

    /**
     * Send email using native PHP with SMTP socket
     */
    public static function send($to, $subject, $body, $isHtml = true)
    {
        // Try using PHPMailer if available, otherwise use SMTP socket
        $phpmailerPath = __DIR__ . '/../vendor/phpmailer/PHPMailer.php';

        if (file_exists($phpmailerPath)) {
            return self::sendWithPHPMailer($to, $subject, $body, $isHtml);
        }

        return self::sendWithSocket($to, $subject, $body, $isHtml);
    }

    /**
     * Send using SMTP socket (built-in)
     */
    private static function sendWithSocket($to, $subject, $body, $isHtml = true)
    {
        try {
            $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
            }

            // Set stream to blocking mode
            stream_set_blocking($socket, true);

            // Read greeting
            $response = fgets($socket, 512);

            // Start TLS connection for Gmail
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            self::readResponse($socket);

            fputs($socket, "STARTTLS\r\n");
            self::readResponse($socket);

            // Enable TLS encryption
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            // Send EHLO again after TLS
            fputs($socket, "EHLO " . gethostname() . "\r\n");
            self::readResponse($socket);

            // Authenticate
            fputs($socket, "AUTH LOGIN\r\n");
            self::readResponse($socket);

            fputs($socket, base64_encode(SMTP_USERNAME) . "\r\n");
            self::readResponse($socket);

            fputs($socket, base64_encode(SMTP_PASSWORD) . "\r\n");
            self::readResponse($socket);

            // Send email
            fputs($socket, "MAIL FROM:<" . SMTP_FROM_EMAIL . ">\r\n");
            self::readResponse($socket);

            fputs($socket, "RCPT TO:<{$to}>\r\n");
            self::readResponse($socket);

            fputs($socket, "DATA\r\n");
            self::readResponse($socket);

            // Build headers
            $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";

            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }

            $headers .= "\r\n";

            fputs($socket, $headers . $body . "\r\n.\r\n");
            self::readResponse($socket);

            fputs($socket, "QUIT\r\n");

            fclose($socket);

            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (Exception $e) {
            error_log("Mailer error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Read SMTP response
     */
    private static function readResponse($socket)
    {
        $response = '';
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ')
                break;
        }
        return $response;
    }

    /**
     * Send notification email
     */
    public static function sendNotification($userId, $type, $subject, $body)
    {
        $db = Database::getInstance();
        $user = $db->fetch("SELECT email_id, emp_name FROM users WHERE id = ?", [$userId]);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Store notification in database
        $db->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => $subject,
            'message' => strip_tags($body)
        ]);

        // Send email
        $htmlBody = self::wrapInTemplate($subject, $body, $user['emp_name']);
        return self::send($user['email_id'], $subject, $htmlBody);
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordReset($email, $token, $userName)
    {
        $resetLink = APP_URL . '/public/reset-password.php?token=' . $token;

        $subject = 'Password Reset - CSIR-SERC Asset Management System';

        $body = "
        <h2>Password Reset Request</h2>
        <p>Hello {$userName},</p>
        <p>You have requested to reset your password for the Asset Management System.</p>
        <p>Click the button below to reset your password:</p>
        <p style='text-align: center; margin: 30px 0;'>
            <a href='{$resetLink}' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Reset Password</a>
        </p>
        <p>If you did not request this reset, please ignore this email.</p>
        <p>This link will expire in 1 hour.</p>
        ";

        $htmlBody = self::wrapInTemplate($subject, $body, $userName);
        return self::send($email, $subject, $htmlBody);
    }

    /**
     * Send transfer notification
     */
    public static function sendTransferNotification($toUserId, $itemName, $fromUser, $status)
    {
        $subject = "Transfer Request - {$itemName}";

        $statusMessages = [
            'pending_supervisor' => 'A new transfer request is pending your approval.',
            'pending_hod' => 'A transfer request requires HoD approval.',
            'approved' => 'Your transfer request has been approved.',
            'rejected' => 'Your transfer request has been rejected.',
            'completed' => 'The transfer has been completed.'
        ];

        $body = "
        <h2>Transfer Notification</h2>
        <p><strong>Item:</strong> {$itemName}</p>
        <p><strong>From:</strong> {$fromUser}</p>
        <p><strong>Status:</strong> {$statusMessages[$status]}</p>
        <p>Please login to the Asset Management System for more details.</p>
        ";

        return self::sendNotification($toUserId, 'transfer', $subject, $body);
    }

    /**
     * Send deletion notification
     */
    public static function sendDeletionNotification($itemName, $deletedBy, $serialNumber)
    {
        // Send to all admins
        $db = Database::getInstance();
        $admins = $db->fetchAll("SELECT id, email_id, emp_name FROM users WHERE role = 'admin' AND is_active = 1");

        $subject = "Item Deleted - {$serialNumber}";

        $body = "
        <h2>Item Deletion Alert</h2>
        <p><strong>Item:</strong> {$itemName}</p>
        <p><strong>Serial Number:</strong> {$serialNumber}</p>
        <p><strong>Deleted By:</strong> {$deletedBy}</p>
        <p><strong>Date/Time:</strong> " . date('d-M-Y H:i:s') . "</p>
        ";

        foreach ($admins as $admin) {
            self::sendNotification($admin['id'], 'deletion', $subject, $body);
        }

        return ['success' => true];
    }

    /**
     * Wrap content in email template
     */
    private static function wrapInTemplate($title, $content, $recipientName = '')
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa;">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                <tr>
                    <td style="background: linear-gradient(135deg, #1a365d 0%, #2d3748 100%); padding: 30px; text-align: center; border-radius: 12px 12px 0 0;">
                        <h1 style="color: white; margin: 0; font-size: 24px;">CSIR-SERC</h1>
                        <p style="color: #a0aec0; margin: 5px 0 0;">Asset Management System</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 40px 30px;">
                        ' . $content . '
                    </td>
                </tr>
                <tr>
                    <td style="background: #f7fafc; padding: 20px 30px; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #e2e8f0;">
                        <p style="color: #718096; margin: 0; font-size: 12px;">
                            This is an automated message from CSIR-SERC Asset Management System.<br>
                            Please do not reply to this email.
                        </p>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
