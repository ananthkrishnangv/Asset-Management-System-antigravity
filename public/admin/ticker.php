<?php
/**
 * News Ticker Management
 * Admin page to add/edit/delete ticker items with badges
 */
require_once __DIR__ . '/../../bootstrap.php';

Auth::requireAuth();
Auth::requireRole(['admin']);

$db = Database::getInstance();
$user = Auth::user();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        flash('error', 'Invalid request');
        redirect(url('public/admin/ticker.php'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $badgeType = $_POST['badge_type'] ?? 'info';
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

        if (empty($title)) {
            flash('error', 'Title is required.');
        } else {
            $db->query(
                "INSERT INTO news_ticker (title, content, badge_type, expires_at, created_by) VALUES (?, ?, ?, ?, ?)",
                [$title, $content, $badgeType, $expiresAt, $user['id']]
            );
            flash('success', 'Ticker item added successfully.');
        }
        redirect(url('public/admin/ticker.php'));
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $badgeType = $_POST['badge_type'] ?? 'info';
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $db->query(
            "UPDATE news_ticker SET title = ?, content = ?, badge_type = ?, expires_at = ?, is_active = ? WHERE id = ?",
            [$title, $content, $badgeType, $expiresAt, $isActive, $id]
        );
        flash('success', 'Ticker item updated.');
        redirect(url('public/admin/ticker.php'));
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->query("DELETE FROM news_ticker WHERE id = ?", [$id]);
        flash('success', 'Ticker item deleted.');
        redirect(url('public/admin/ticker.php'));
    }

    if ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $db->query("UPDATE news_ticker SET is_active = NOT is_active WHERE id = ?", [$id]);
        flash('success', 'Ticker status toggled.');
        redirect(url('public/admin/ticker.php'));
    }
}

// Fetch all ticker items
$tickerItems = $db->fetchAll("SELECT * FROM news_ticker ORDER BY created_at DESC") ?: [];

$badgeColors = [
    'important' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
    'new' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'dot' => 'bg-green-500'],
    'alert' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
    'warning' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500'],
    'info' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'dot' => 'bg-blue-500'],
];

$pageTitle = 'News Ticker Management';
$pageSubtitle = 'Manage announcements and alerts displayed on the dashboard';
ob_start();
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Add New Ticker -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-plus-circle text-purple-500 mr-2"></i>Add New Item
            </h3>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <input type="text" name="title"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            required placeholder="Announcement title">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content (Optional)</label>
                        <textarea name="content" rows="2"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            placeholder="Additional details..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Badge Type</label>
                        <div class="grid grid-cols-3 gap-2">
                            <?php foreach ($badgeColors as $type => $colors): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="badge_type" value="<?= $type ?>" class="sr-only peer"
                                        <?= $type === 'info' ? 'checked' : '' ?>>
                                    <div
                                        class="peer-checked:ring-2 peer-checked:ring-purple-500 <?= $colors['bg'] ?> <?= $colors['text'] ?> px-3 py-2 rounded-lg text-center text-xs font-bold uppercase transition-all hover:opacity-80">
                                        <?= ucfirst($type) ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expires On</label>
                        <input type="date" name="expires_at"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave empty for no expiration</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3 rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg">
                        <i class="fas fa-plus mr-2"></i>Add Ticker Item
                    </button>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="mt-6 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-4 text-white">
            <p class="text-sm font-medium opacity-75 mb-2">Live Preview</p>
            <div class="overflow-hidden">
                <div class="flex items-center gap-2 text-sm whitespace-nowrap">
                    <span class="bg-green-500 px-2 py-0.5 rounded text-xs font-bold">NEW</span>
                    <span>This is how your ticker will appear on the dashboard</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticker Items List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-purple-500 mr-2"></i>All Ticker Items
                </h3>
                <span class="text-sm text-gray-500"><?= count($tickerItems) ?> items</span>
            </div>

            <?php if (empty($tickerItems)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-bullhorn text-gray-400 text-2xl"></i>
                    </div>
                    <h4 class="text-gray-600 font-medium mb-1">No ticker items yet</h4>
                    <p class="text-gray-400 text-sm">Add your first announcement using the form</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($tickerItems as $item): ?>
                        <?php $colors = $badgeColors[$item['badge_type']] ?? $badgeColors['info']; ?>
                        <div class="p-4 hover:bg-gray-50 transition-colors <?= !$item['is_active'] ? 'opacity-50' : '' ?>">
                            <div class="flex items-start gap-4">
                                <div class="<?= $colors['bg'] ?> rounded-lg p-2">
                                    <i class="fas fa-bullhorn <?= $colors['text'] ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span
                                            class="<?= $colors['bg'] ?> <?= $colors['text'] ?> px-2 py-0.5 rounded text-xs font-bold uppercase">
                                            <?= ucfirst($item['badge_type']) ?>
                                        </span>
                                        <?php if (!$item['is_active']): ?>
                                            <span
                                                class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded text-xs font-medium">Inactive</span>
                                        <?php endif; ?>
                                        <?php if ($item['expires_at'] && strtotime($item['expires_at']) < time()): ?>
                                            <span
                                                class="bg-red-100 text-red-600 px-2 py-0.5 rounded text-xs font-medium">Expired</span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 class="font-semibold text-gray-800"><?= Security::escape($item['title']) ?></h4>
                                    <?php if (!empty($item['content'])): ?>
                                        <p class="text-sm text-gray-500 mt-1"><?= Security::escape($item['content']) ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                                        <span><i
                                                class="far fa-calendar mr-1"></i><?= date('M j, Y', strtotime($item['created_at'])) ?></span>
                                        <?php if ($item['expires_at']): ?>
                                            <span><i class="far fa-clock mr-1"></i>Expires:
                                                <?= date('M j, Y', strtotime($item['expires_at'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="Toggle Active">
                                            <i class="fas fa-<?= $item['is_active'] ? 'eye' : 'eye-slash' ?>"></i>
                                        </button>
                                    </form>
                                    <button onclick="editTicker(<?= htmlspecialchars(json_encode($item)) ?>)"
                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this ticker item?')">
                                        <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
            <h3 class="text-lg font-semibold text-white">Edit Ticker Item</h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="edit_title"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    <textarea name="content" id="edit_content" rows="2"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Badge Type</label>
                    <select name="badge_type" id="edit_badge_type"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="info">Info</option>
                        <option value="new">New</option>
                        <option value="important">Important</option>
                        <option value="alert">Alert</option>
                        <option value="warning">Warning</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expires On</label>
                    <input type="date" name="expires_at" id="edit_expires_at"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="edit_is_active" class="w-4 h-4 text-purple-600 rounded">
                    <label for="edit_is_active" class="text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeEditModal()"
                    class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-50 font-medium">
                    Cancel
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl hover:from-purple-700 hover:to-indigo-700 font-medium">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editTicker(item) {
        document.getElementById('edit_id').value = item.id;
        document.getElementById('edit_title').value = item.title;
        document.getElementById('edit_content').value = item.content || '';
        document.getElementById('edit_badge_type').value = item.badge_type;
        document.getElementById('edit_expires_at').value = item.expires_at || '';
        document.getElementById('edit_is_active').checked = item.is_active == 1;
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    }

    // Close modal on outside click
    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) closeEditModal();
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../templates/layout.php';
?>