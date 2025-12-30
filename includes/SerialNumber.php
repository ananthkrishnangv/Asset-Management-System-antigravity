<?php
/**
 * Serial Number Generator
 * Format: YYYY-MMDD-HHMMSS-XXXXX (where XXXXX is random 5-digit)
 */

class SerialNumber
{

    /**
     * Generate unique serial number
     */
    public static function generate($prefix = '')
    {
        $date = date('Y-md-His');
        $random = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);

        $serial = $prefix ? $prefix . '-' . $date . '-' . $random : $date . '-' . $random;

        // Verify uniqueness
        if (self::exists($serial)) {
            return self::generate($prefix); // Regenerate if collision
        }

        return $serial;
    }

    /**
     * Generate with inventory type prefix
     */
    public static function generateForInventory($type = 'dir')
    {
        $prefix = strtoupper($type);
        return self::generate($prefix);
    }

    /**
     * Check if serial number exists
     */
    public static function exists($serialNumber)
    {
        $db = Database::getInstance();
        $count = $db->fetchValue(
            "SELECT COUNT(*) FROM inventory_items WHERE serial_number = ?",
            [$serialNumber]
        );
        return $count > 0;
    }

    /**
     * Generate transfer slip number
     */
    public static function generateTransferSlip()
    {
        $date = date('Ymd');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return 'TS-' . $date . '-' . $random;
    }

    /**
     * Generate stores return number
     */
    public static function generateStoresReturn()
    {
        $date = date('Ymd');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return 'SR-' . $date . '-' . $random;
    }
}
