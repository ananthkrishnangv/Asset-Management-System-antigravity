<?php
/**
 * WhatsApp Integration Class
 * Uses WhatsApp Business API for sending notifications
 */

class WhatsApp
{
    private static $apiUrl;
    private static $accessToken;
    private static $phoneNumberId;
    private static $enabled = false;

    /**
     * Initialize WhatsApp settings from database
     */
    public static function init()
    {
        $db = Database::getInstance();
        
        self::$enabled = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_enabled'") === 'true';
        self::$accessToken = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_access_token'") ?? '';
        self::$phoneNumberId = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_phone_number_id'") ?? '';
        self::$apiUrl = "https://graph.facebook.com/v18.0/" . self::$phoneNumberId . "/messages";
    }

    /**
     * Check if WhatsApp notifications are enabled
     */
    public static function isEnabled()
    {
        if (!isset(self::$enabled)) {
            self::init();
        }
        return self::$enabled && !empty(self::$accessToken) && !empty(self::$phoneNumberId);
    }

    /**
     * Send WhatsApp message
     */
    public static function sendMessage($phoneNumber, $message)
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'error' => 'WhatsApp notifications not enabled'];
        }

        // Format phone number (add country code if missing)
        $phoneNumber = self::formatPhoneNumber($phoneNumber);

        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];

        $ch = curl_init(self::$apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            self::logError('cURL Error: ' . $error);
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            self::logSuccess($phoneNumber, $message);
            return ['success' => true, 'response' => $result];
        } else {
            $errorMsg = $result['error']['message'] ?? 'Unknown error';
            self::logError('API Error: ' . $errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }

    /**
     * Send template message (for transactional notifications)
     */
    public static function sendTemplate($phoneNumber, $templateName, $parameters = [])
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'error' => 'WhatsApp notifications not enabled'];
        }

        $phoneNumber = self::formatPhoneNumber($phoneNumber);

        $components = [];
        if (!empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $parameters)
            ];
        }

        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
                'components' => $components
            ]
        ];

        $ch = curl_init(self::$apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        return ($httpCode >= 200 && $httpCode < 300) 
            ? ['success' => true, 'response' => $result]
            : ['success' => false, 'error' => $result['error']['message'] ?? 'Unknown error'];
    }

    /**
     * Notify on Asset Add
     */
    public static function notifyAssetAdded($asset, $user)
    {
        $db = Database::getInstance();
        $notifyOnAdd = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_add'") === 'true';
        
        if (!$notifyOnAdd || empty($user['phone'])) {
            return;
        }

        $message = "📦 *New Asset Added*\n\n";
        $message .= "Item: " . $asset['item_description'] . "\n";
        $message .= "Serial: " . ($asset['serial_number'] ?? 'N/A') . "\n";
        $message .= "Added by: " . Auth::user()['emp_name'] . "\n";
        $message .= "Date: " . date('d-M-Y H:i');

        return self::sendMessage($user['phone'], $message);
    }

    /**
     * Notify on Asset Delete
     */
    public static function notifyAssetDeleted($asset, $user)
    {
        $db = Database::getInstance();
        $notifyOnDelete = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_delete'") === 'true';
        
        if (!$notifyOnDelete || empty($user['phone'])) {
            return;
        }

        $message = "🗑️ *Asset Removed*\n\n";
        $message .= "Item: " . $asset['item_description'] . "\n";
        $message .= "Serial: " . ($asset['serial_number'] ?? 'N/A') . "\n";
        $message .= "Removed by: " . Auth::user()['emp_name'] . "\n";
        $message .= "Date: " . date('d-M-Y H:i');

        return self::sendMessage($user['phone'], $message);
    }

    /**
     * Notify on Transfer Request
     */
    public static function notifyTransferRequest($transfer, $toUser)
    {
        $db = Database::getInstance();
        $notifyOnTransfer = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_transfer'") === 'true';
        
        if (!$notifyOnTransfer || empty($toUser['phone'])) {
            return;
        }

        $message = "🔄 *Transfer Request*\n\n";
        $message .= "Item: " . $transfer['item_description'] . "\n";
        $message .= "From: " . $transfer['from_user_name'] . "\n";
        $message .= "To: " . $transfer['to_user_name'] . "\n";
        $message .= "Status: " . ucfirst(str_replace('_', ' ', $transfer['status'])) . "\n";
        $message .= "Date: " . date('d-M-Y H:i');

        return self::sendMessage($toUser['phone'], $message);
    }

    /**
     * Notify on Transfer Status Change
     */
    public static function notifyTransferStatusChange($transfer, $user)
    {
        $db = Database::getInstance();
        $notifyOnTransfer = $db->fetchValue("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_notify_transfer'") === 'true';
        
        if (!$notifyOnTransfer || empty($user['phone'])) {
            return;
        }

        $statusEmoji = match($transfer['status']) {
            'approved' => '✅',
            'rejected' => '❌',
            'completed' => '🎉',
            default => '📋'
        };

        $message = "$statusEmoji *Transfer Update*\n\n";
        $message .= "Item: " . $transfer['item_description'] . "\n";
        $message .= "Status: " . ucfirst(str_replace('_', ' ', $transfer['status'])) . "\n";
        $message .= "Updated by: " . Auth::user()['emp_name'] . "\n";
        $message .= "Date: " . date('d-M-Y H:i');

        return self::sendMessage($user['phone'], $message);
    }

    /**
     * Format phone number to international format
     */
    private static function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add India country code if not present
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return $phone;
    }

    /**
     * Log successful message
     */
    private static function logSuccess($phone, $message)
    {
        $db = Database::getInstance();
        try {
            $db->insert('whatsapp_logs', [
                'phone_number' => $phone,
                'message' => substr($message, 0, 500),
                'status' => 'sent',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Table may not exist, silently fail
        }
    }

    /**
     * Log error
     */
    private static function logError($error)
    {
        $db = Database::getInstance();
        try {
            $db->insert('whatsapp_logs', [
                'phone_number' => '',
                'message' => $error,
                'status' => 'error',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Table may not exist, silently fail
        }
    }

    /**
     * Test connection
     */
    public static function testConnection()
    {
        self::init();
        
        if (!self::$accessToken || !self::$phoneNumberId) {
            return ['success' => false, 'error' => 'API credentials not configured'];
        }

        // Test by getting phone number details
        $url = "https://graph.facebook.com/v18.0/" . self::$phoneNumberId;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Connection successful'];
        } else {
            $result = json_decode($response, true);
            return ['success' => false, 'error' => $result['error']['message'] ?? 'Connection failed'];
        }
    }
}
