<?php
/**
 * Email Notification Service
 * Handles email notifications for asset operations
 */

class EmailNotification
{
    /**
     * Send notification when asset is added
     */
    public static function notifyAssetAdded($asset, $user)
    {
        $subject = "New Asset Added - " . $asset['item_description'];
        
        $body = self::getTemplate('asset_added', [
            'user_name' => $user['emp_name'],
            'item_description' => $asset['item_description'],
            'serial_number' => $asset['serial_number'] ?? 'N/A',
            'added_by' => Auth::user()['emp_name'],
            'date' => date('d-M-Y H:i')
        ]);

        return self::send($user['email_id'], $subject, $body);
    }

    /**
     * Send notification when asset is transferred
     */
    public static function notifyTransfer($transfer, $fromUser, $toUser)
    {
        $subject = "Asset Transfer Request - " . $transfer['item_description'];
        
        // Notify recipient
        $body = self::getTemplate('transfer_request', [
            'to_user' => $toUser['emp_name'],
            'from_user' => $fromUser['emp_name'],
            'item_description' => $transfer['item_description'],
            'serial_number' => $transfer['serial_number'] ?? 'N/A',
            'remarks' => $transfer['remarks'] ?? 'No remarks',
            'date' => date('d-M-Y H:i')
        ]);

        return self::send($toUser['email_id'], $subject, $body);
    }

    /**
     * Send notification when transfer status changes
     */
    public static function notifyTransferStatus($transfer, $user)
    {
        $statusText = ucfirst(str_replace('_', ' ', $transfer['status']));
        $subject = "Transfer " . $statusText . " - " . $transfer['item_description'];
        
        $body = self::getTemplate('transfer_status', [
            'user_name' => $user['emp_name'],
            'item_description' => $transfer['item_description'],
            'status' => $statusText,
            'updated_by' => Auth::user()['emp_name'],
            'date' => date('d-M-Y H:i')
        ]);

        return self::send($user['email_id'], $subject, $body);
    }

    /**
     * Send asset maintenance reminder
     */
    public static function notifyMaintenanceReminder($asset, $user)
    {
        $subject = "Maintenance Reminder - " . $asset['item_description'];
        
        $body = self::getTemplate('maintenance_reminder', [
            'user_name' => $user['emp_name'],
            'item_description' => $asset['item_description'],
            'purchase_date' => date('d-M-Y', strtotime($asset['purchase_date'])),
            'condition' => $asset['condition_status'],
            'age_years' => floor((time() - strtotime($asset['purchase_date'])) / (365 * 24 * 60 * 60))
        ]);

        return self::send($user['email_id'], $subject, $body);
    }

    /**
     * Send weekly digest
     */
    public static function sendWeeklyDigest($user, $stats)
    {
        $subject = "Weekly Asset Management Digest - " . date('d M Y');
        
        $body = self::getTemplate('weekly_digest', [
            'user_name' => $user['emp_name'],
            'total_assets' => $stats['total_assets'],
            'new_assets' => $stats['new_assets'],
            'pending_transfers' => $stats['pending_transfers'],
            'maintenance_due' => $stats['maintenance_due']
        ]);

        return self::send($user['email_id'], $subject, $body);
    }

    /**
     * Get email template
     */
    private static function getTemplate($template, $data)
    {
        $templates = [
            'asset_added' => "
                <h2>New Asset Added</h2>
                <p>Dear {user_name},</p>
                <p>A new asset has been added to the system:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 400px;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Item</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{item_description}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Serial</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{serial_number}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Added By</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{added_by}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Date</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{date}</td></tr>
                </table>
            ",
            'transfer_request' => "
                <h2>Asset Transfer Request</h2>
                <p>Dear {to_user},</p>
                <p>{from_user} has requested to transfer an asset to you:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 400px;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Item</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{item_description}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>From</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{from_user}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Remarks</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{remarks}</td></tr>
                </table>
                <p>Please login to approve or reject the transfer.</p>
            ",
            'transfer_status' => "
                <h2>Transfer Status Update</h2>
                <p>Dear {user_name},</p>
                <p>The status of your transfer request has been updated:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 400px;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Item</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{item_description}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Status</strong></td><td style='padding: 8px; border: 1px solid #ddd;'><strong>{status}</strong></td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Updated By</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{updated_by}</td></tr>
                </table>
            ",
            'maintenance_reminder' => "
                <h2>Maintenance Reminder</h2>
                <p>Dear {user_name},</p>
                <p>The following asset may need maintenance attention:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 400px;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Item</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{item_description}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Purchase Date</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{purchase_date}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Age</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{age_years} years</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Condition</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{condition}</td></tr>
                </table>
            ",
            'weekly_digest' => "
                <h2>Weekly Asset Digest</h2>
                <p>Dear {user_name},</p>
                <p>Here's your weekly summary:</p>
                <table style='border-collapse: collapse; width: 100%; max-width: 400px;'>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Total Assets</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{total_assets}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>New This Week</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{new_assets}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Pending Transfers</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{pending_transfers}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Maintenance Due</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{maintenance_due}</td></tr>
                </table>
            "
        ];

        $html = $templates[$template] ?? '<p>Notification from CSIR-SERC AMS</p>';
        
        foreach ($data as $key => $value) {
            $html = str_replace('{' . $key . '}', Security::escape($value), $html);
        }

        return self::wrapInLayout($html);
    }

    /**
     * Wrap content in email layout
     */
    private static function wrapInLayout($content)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #fff; padding: 30px; border: 1px solid #e0e0e0; }
                .footer { background: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 20px;'>CSIR-SERC Asset Management System</h1>
                </div>
                <div class='content'>
                    $content
                </div>
                <div class='footer'>
                    <p>This is an automated notification from CSIR-SERC AMS.</p>
                    <p>© " . date('Y') . " CSIR-SERC. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Send email using Mailer class
     */
    private static function send($to, $subject, $body)
    {
        if (empty($to)) {
            return ['success' => false, 'error' => 'No email address'];
        }

        try {
            return Mailer::send($to, $subject, $body);
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
