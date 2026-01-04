<?php
/**
 * Backup Class
 * Handles database and file backups with local storage priority
 * Secondary options: Google Drive, S3, OneDrive, FTP
 */

class Backup
{

    /**
     * Create database backup (Primary: Local Storage)
     */
    public static function createDatabaseBackup()
    {
        $filename = 'db_backup_' . date('Y-m-d_His') . '.sql';
        $filepath = BACKUP_PATH . $filename;

        // Record backup start
        $db = Database::getInstance();
        $backupId = $db->insert('backups', [
            'backup_type' => 'database',
            'file_name' => $filename,
            'file_path' => $filepath,
            'storage_location' => 'local',
            'status' => 'in_progress',
            'created_by' => Auth::id()
        ]);

        try {
            // Create backup directory if not exists
            if (!is_dir(BACKUP_PATH)) {
                mkdir(BACKUP_PATH, 0755, true);
            }

            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('mysqldump failed: ' . implode("\n", $output));
            }

            // Get file size
            $fileSize = filesize($filepath);

            // Update backup record
            $db->update(
                'backups',
                ['status' => 'completed', 'file_size' => $fileSize],
                'id = :id',
                ['id' => $backupId]
            );

            // Log activity
            ActivityLog::log('backup', 'system', $backupId, 'backup', 'Database backup created: ' . $filename);

            // Clean old backups
            self::cleanOldBackups();

            return [
                'success' => true,
                'message' => 'Database backup created successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize,
                'backup_id' => $backupId
            ];

        } catch (Exception $e) {
            $db->update(
                'backups',
                ['status' => 'failed', 'error_message' => $e->getMessage()],
                'id = :id',
                ['id' => $backupId]
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create files backup (uploads folder)
     */
    public static function createFilesBackup()
    {
        $filename = 'files_backup_' . date('Y-m-d_His') . '.zip';
        $filepath = BACKUP_PATH . $filename;

        $db = Database::getInstance();
        $backupId = $db->insert('backups', [
            'backup_type' => 'files',
            'file_name' => $filename,
            'file_path' => $filepath,
            'storage_location' => 'local',
            'status' => 'in_progress',
            'created_by' => Auth::id()
        ]);

        try {
            if (!is_dir(BACKUP_PATH)) {
                mkdir(BACKUP_PATH, 0755, true);
            }

            $zip = new ZipArchive();

            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Could not create zip file');
            }

            $uploadPath = realpath(UPLOAD_PATH);
            if ($uploadPath && is_dir($uploadPath)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($uploadPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($uploadPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
            }

            $zip->close();

            $fileSize = filesize($filepath);

            $db->update(
                'backups',
                ['status' => 'completed', 'file_size' => $fileSize],
                'id = :id',
                ['id' => $backupId]
            );

            ActivityLog::log('backup', 'system', $backupId, 'backup', 'Files backup created: ' . $filename);

            return [
                'success' => true,
                'message' => 'Files backup created successfully',
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize,
                'backup_id' => $backupId
            ];

        } catch (Exception $e) {
            $db->update(
                'backups',
                ['status' => 'failed', 'error_message' => $e->getMessage()],
                'id = :id',
                ['id' => $backupId]
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create full backup (database + files)
     */
    public static function createFullBackup()
    {
        $dbBackup = self::createDatabaseBackup();
        $filesBackup = self::createFilesBackup();

        return [
            'database' => $dbBackup,
            'files' => $filesBackup,
            'success' => $dbBackup['success'] && $filesBackup['success']
        ];
    }

    /**
     * Upload to secondary storage (optional)
     */
    public static function uploadToSecondary($backupId, $destination = 'google_drive')
    {
        $db = Database::getInstance();
        $backup = $db->fetch("SELECT * FROM backups WHERE id = ?", [$backupId]);

        if (!$backup || $backup['status'] !== 'completed') {
            return ['success' => false, 'message' => 'Invalid or incomplete backup'];
        }

        try {
            switch ($destination) {
                case 'google_drive':
                    $result = self::uploadToGoogleDrive($backup);
                    break;
                case 's3':
                    $result = self::uploadToS3($backup);
                    break;
                case 'onedrive':
                    $result = self::uploadToOneDrive($backup);
                    break;
                case 'ftp':
                    $result = self::uploadToFTP($backup);
                    break;
                default:
                    return ['success' => false, 'message' => 'Unknown destination'];
            }

            if ($result['success']) {
                // Create a secondary record
                $db->insert('backups', [
                    'backup_type' => $backup['backup_type'],
                    'file_name' => $backup['file_name'],
                    'file_path' => $result['remote_path'] ?? $backup['file_path'],
                    'file_size' => $backup['file_size'],
                    'storage_location' => $destination,
                    'cloud_file_id' => $result['file_id'] ?? null,
                    'status' => 'completed',
                    'created_by' => Auth::id()
                ]);
            }

            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Upload to Google Drive (requires Google API setup)
     */
    private static function uploadToGoogleDrive($backup)
    {
        // Placeholder - requires Google API client library
        // Return instructions for setup
        return [
            'success' => false,
            'message' => 'Google Drive integration requires API setup. Please configure Google API credentials in Admin Settings.'
        ];
    }

    /**
     * Upload to Amazon S3
     */
    private static function uploadToS3($backup)
    {
        // Placeholder - requires AWS SDK
        return [
            'success' => false,
            'message' => 'S3 integration requires AWS SDK setup. Please configure AWS credentials in Admin Settings.'
        ];
    }

    /**
     * Upload to OneDrive
     */
    private static function uploadToOneDrive($backup)
    {
        // Placeholder - requires Microsoft Graph API
        return [
            'success' => false,
            'message' => 'OneDrive integration requires Microsoft Graph API setup. Please configure in Admin Settings.'
        ];
    }

    /**
     * Upload to FTP server
     */
    private static function uploadToFTP($backup)
    {
        $db = Database::getInstance();
        $settings = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ftp_%'");

        $ftpConfig = [];
        foreach ($settings as $s) {
            $ftpConfig[$s['setting_key']] = $s['setting_value'];
        }

        if (empty($ftpConfig['ftp_host']) || empty($ftpConfig['ftp_username'])) {
            return ['success' => false, 'message' => 'FTP not configured. Please set FTP details in Admin Settings.'];
        }

        try {
            $ftpConn = ftp_connect($ftpConfig['ftp_host'], $ftpConfig['ftp_port'] ?? 21);

            if (!$ftpConn) {
                throw new Exception('Could not connect to FTP server');
            }

            if (!ftp_login($ftpConn, $ftpConfig['ftp_username'], $ftpConfig['ftp_password'] ?? '')) {
                throw new Exception('FTP login failed');
            }

            ftp_pasv($ftpConn, true);

            $remotePath = ($ftpConfig['ftp_path'] ?? '/') . '/' . $backup['file_name'];

            if (!ftp_put($ftpConn, $remotePath, $backup['file_path'], FTP_BINARY)) {
                throw new Exception('FTP upload failed');
            }

            ftp_close($ftpConn);

            return [
                'success' => true,
                'message' => 'Uploaded to FTP successfully',
                'remote_path' => $remotePath
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Clean old backups based on retention policy
     */
    public static function cleanOldBackups()
    {
        $db = Database::getInstance();
        $retentionDays = BACKUP_RETENTION_DAYS;

        // Get old local backups
        $oldBackups = $db->fetchAll(
            "SELECT * FROM backups WHERE storage_location = 'local' AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$retentionDays]
        );

        foreach ($oldBackups as $backup) {
            // Delete file if exists
            if (file_exists($backup['file_path'])) {
                unlink($backup['file_path']);
            }

            // Delete record
            $db->delete('backups', 'id = :id', ['id' => $backup['id']]);
        }

        return count($oldBackups);
    }

    /**
     * Get backup history
     */
    public static function getHistory($limit = 20)
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT b.*, u.emp_name as created_by_name 
             FROM backups b 
             LEFT JOIN users u ON b.created_by = u.id 
             ORDER BY b.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Download backup file
     */
    public static function download($backupId)
    {
        $db = Database::getInstance();
        $backup = $db->fetch("SELECT * FROM backups WHERE id = ? AND storage_location = 'local'", [$backupId]);

        if (!$backup || !file_exists($backup['file_path'])) {
            return false;
        }

        return $backup;
    }

    /**
     * Restore database from backup
     */
    public static function restoreDatabase($backupId)
    {
        $db = Database::getInstance();
        $backup = $db->fetch("SELECT * FROM backups WHERE id = ? AND backup_type = 'database'", [$backupId]);

        if (!$backup || !file_exists($backup['file_path'])) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }

        try {
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg(DB_HOST),
                escapeshellarg(DB_USER),
                escapeshellarg(DB_PASS),
                escapeshellarg(DB_NAME),
                escapeshellarg($backup['file_path'])
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Restore failed: ' . implode("\n", $output));
            }

            ActivityLog::log('backup', 'system', $backupId, 'backup', 'Database restored from: ' . $backup['file_name']);

            return ['success' => true, 'message' => 'Database restored successfully'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
