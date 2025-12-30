<?php
/**
 * Backup Management (Admin Only)
 */

require_once __DIR__ . '/../../bootstrap.php';
Auth::requireAdmin();

$db = Database::getInstance();

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        jsonResponse(['error' => 'Invalid request'], 403);
    }

    // CREATE BACKUP
    if (isset($_POST['create_backup'])) {
        $type = $_POST['backup_type'] ?? 'database';

        switch ($type) {
            case 'database':
                $result = Backup::createDatabaseBackup();
                break;
            case 'files':
                $result = Backup::createFilesBackup();
                break;
            case 'full':
                $result = Backup::createFullBackup();
                break;
            default:
                jsonResponse(['error' => 'Invalid backup type'], 400);
        }

        jsonResponse($result);
    }

    // UPLOAD TO SECONDARY
    if (isset($_POST['upload_secondary'])) {
        $backupId = (int) $_POST['backup_id'];
        $destination = $_POST['destination'];

        $result = Backup::uploadToSecondary($backupId, $destination);
        jsonResponse($result);
    }

    // DELETE BACKUP
    if (isset($_POST['delete_id'])) {
        $backupId = (int) $_POST['delete_id'];
        $backup = $db->fetch("SELECT * FROM backups WHERE id = ?", [$backupId]);

        if ($backup && file_exists($backup['file_path'])) {
            unlink($backup['file_path']);
        }

        $db->delete('backups', 'id = :id', ['id' => $backupId]);
        jsonResponse(['success' => true]);
    }

    // GET BACKUPS
    $backups = Backup::getHistory(50);
    jsonResponse(['data' => $backups]);
}

// Download backup
if (isset($_GET['download'])) {
    $backupId = (int) $_GET['download'];
    $backup = Backup::download($backupId);

    if ($backup) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup['file_name'] . '"');
        header('Content-Length: ' . filesize($backup['file_path']));
        readfile($backup['file_path']);
        exit;
    }
}

$pageTitle = 'Backup Management';
$pageSubtitle = 'Manage database and file backups';

ob_start();
?>

<!-- Backup Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-database text-blue-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Database Backup</h3>
                <p class="text-sm text-gray-500">Backup all database tables</p>
            </div>
        </div>
        <button onclick="createBackup('database')"
            class="w-full py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
            <i class="fas fa-download mr-2"></i> Create Backup
        </button>
    </div>

    <div class="bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-folder text-green-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Files Backup</h3>
                <p class="text-sm text-gray-500">Backup all uploaded files</p>
            </div>
        </div>
        <button onclick="createBackup('files')"
            class="w-full py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors">
            <i class="fas fa-download mr-2"></i> Create Backup
        </button>
    </div>

    <div class="bg-white rounded-2xl p-6 card-shadow">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-archive text-purple-600 text-xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800">Full Backup</h3>
                <p class="text-sm text-gray-500">Database + Files</p>
            </div>
        </div>
        <button onclick="createBackup('full')"
            class="w-full py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors">
            <i class="fas fa-download mr-2"></i> Create Full Backup
        </button>
    </div>
</div>

<!-- Secondary Storage Info -->
<div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6 mb-8 text-white">
    <h3 class="text-lg font-bold mb-4">
        <i class="fas fa-cloud mr-2"></i> Secondary Storage Options
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white/10 rounded-xl p-4 text-center">
            <i class="fab fa-google-drive text-3xl mb-2"></i>
            <p class="text-sm">Google Drive</p>
            <span class="text-xs text-gray-300">Configure in Settings</span>
        </div>
        <div class="bg-white/10 rounded-xl p-4 text-center">
            <i class="fab fa-aws text-3xl mb-2"></i>
            <p class="text-sm">Amazon S3</p>
            <span class="text-xs text-gray-300">Configure in Settings</span>
        </div>
        <div class="bg-white/10 rounded-xl p-4 text-center">
            <i class="fab fa-microsoft text-3xl mb-2"></i>
            <p class="text-sm">OneDrive</p>
            <span class="text-xs text-gray-300">Configure in Settings</span>
        </div>
        <div class="bg-white/10 rounded-xl p-4 text-center">
            <i class="fas fa-server text-3xl mb-2"></i>
            <p class="text-sm">FTP Server</p>
            <span class="text-xs text-gray-300">Configure in Settings</span>
        </div>
    </div>
</div>

<!-- Backup History -->
<div class="bg-white rounded-2xl card-shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-800">Backup History</h3>
    </div>

    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Filename</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Size</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Storage</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody id="backupTable" class="divide-y divide-gray-100"></tbody>
    </table>
</div>

<script>
    function loadBackups() {
        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => renderBackups(data.data));
    }

    function renderBackups(backups) {
        const typeIcons = {
            database: '<i class="fas fa-database text-blue-600"></i>',
            files: '<i class="fas fa-folder text-green-600"></i>',
            full: '<i class="fas fa-archive text-purple-600"></i>'
        };

        const storageIcons = {
            local: '<i class="fas fa-hdd text-gray-600"></i> Local',
            google_drive: '<i class="fab fa-google-drive text-blue-500"></i> Drive',
            s3: '<i class="fab fa-aws text-orange-500"></i> S3',
            onedrive: '<i class="fab fa-microsoft text-blue-600"></i> OneDrive',
            ftp: '<i class="fas fa-server text-gray-600"></i> FTP'
        };

        document.getElementById('backupTable').innerHTML = backups.map(b => `
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4">${typeIcons[b.backup_type] || ''} ${b.backup_type}</td>
            <td class="px-6 py-4 font-mono text-sm">${b.file_name}</td>
            <td class="px-6 py-4 text-sm">${formatSize(b.file_size)}</td>
            <td class="px-6 py-4">${storageIcons[b.storage_location] || b.storage_location}</td>
            <td class="px-6 py-4">
                <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(b.status)}">
                    ${b.status}
                </span>
            </td>
            <td class="px-6 py-4 text-sm">${new Date(b.created_at).toLocaleString()}</td>
            <td class="px-6 py-4 text-center">
                ${b.storage_location === 'local' && b.status === 'completed' ? `
                    <a href="?download=${b.id}" class="p-2 text-green-600 hover:bg-green-50 rounded-lg inline-block">
                        <i class="fas fa-download"></i>
                    </a>
                    <button onclick="uploadSecondary(${b.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </button>
                ` : ''}
                <button onclick="deleteBackup(${b.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">No backups found</td></tr>';
    }

    function getStatusClass(status) {
        return { completed: 'bg-green-100 text-green-800', failed: 'bg-red-100 text-red-800', in_progress: 'bg-yellow-100 text-yellow-800' }[status] || 'bg-gray-100';
    }

    function formatSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function createBackup(type) {
        showToast('Creating backup...', 'info');

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('create_backup', '1');
        formData.append('backup_type', type);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('Backup created successfully!', 'success');
                    loadBackups();
                } else {
                    showToast(data.message || 'Backup failed', 'error');
                }
            });
    }

    function uploadSecondary(id) {
        const dest = prompt('Enter destination (google_drive, s3, onedrive, ftp):');
        if (!dest) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('upload_secondary', '1');
        formData.append('backup_id', id);
        formData.append('destination', dest);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                loadBackups();
            });
    }

    function deleteBackup(id) {
        if (!confirm('Delete this backup?')) return;

        const formData = new FormData();
        formData.append('ajax', '1');
        formData.append('delete_id', id);
        formData.append('csrf_token', '<?= Security::generateCSRFToken() ?>');

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showToast('Backup deleted', 'success'); loadBackups(); }
            });
    }

    loadBackups();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
