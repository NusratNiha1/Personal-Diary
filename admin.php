<?php
require_once 'lib/auth.php';
require_once 'lib/db.php';
require_once 'lib/utils.php';

require_login();
require_admin();

$page_title = 'Admin Panel';

// Get statistics
$total_users = db_one("SELECT COUNT(*) as count FROM users")['count'];
$total_entries = db_one("SELECT COUNT(*) as count FROM entries WHERE is_deleted = FALSE")['count'];
$total_categories = db_one("SELECT COUNT(*) as count FROM categories")['count'];
$total_tags = db_one("SELECT COUNT(*) as count FROM tags")['count'];

// Get recent users
$recent_users = db_all("SELECT u.user_id, u.username, u.full_name, u.email, u.user_role, u.created_at, u.is_active
                        FROM users u
                        ORDER BY u.created_at DESC
                        LIMIT 10");

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $user_role = $_POST['user_role']; // Now using VARCHAR
    db_exec("UPDATE users SET user_role = ? WHERE user_id = ?", [$user_role, $user_id]);
    flash('User role updated successfully', 'success');
    redirect('admin.php');
}

// Handle user activation toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    $user_id = (int)$_POST['user_id'];
    db_exec("UPDATE users SET is_active = NOT is_active WHERE user_id = ?", [$user_id]);
    flash('User status updated', 'success');
    redirect('admin.php');
}

// Get all roles for dropdown (simplified - Admin and User only)
$roles = [
    ['role_name' => 'Admin'],
    ['role_name' => 'User']
];

include 'partials/head.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Page Header -->
    <div class="glass rounded-3xl shadow-xl p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Admin Panel</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage users, roles, and system settings</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass rounded-2xl p-6 bg-gradient-to-br from-blue-500/10 to-blue-700/10 border border-blue-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Users</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?= $total_users ?></p>
                </div>
                <div class="text-4xl text-blue-500 opacity-50"><i class="fas fa-users"></i></div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 bg-gradient-to-br from-green-500/10 to-green-700/10 border border-green-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Entries</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?= $total_entries ?></p>
                </div>
                <div class="text-4xl text-green-500 opacity-50"><i class="fas fa-book"></i></div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 bg-gradient-to-br from-purple-500/10 to-purple-700/10 border border-purple-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Categories</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?= $total_categories ?></p>
                </div>
                <div class="text-4xl text-purple-500 opacity-50"><i class="fas fa-tags"></i></div>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 bg-gradient-to-br from-orange-500/10 to-orange-700/10 border border-orange-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Tags</p>
                    <p class="text-3xl font-bold text-gray-800 dark:text-gray-100 mt-1"><?= $total_tags ?></p>
                </div>
                <div class="text-4xl text-orange-500 opacity-50"><i class="fas fa-bookmark"></i></div>
            </div>
        </div>
    </div>

    <!-- Users Management -->
    <div class="glass rounded-3xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">User Management</h2>
        </div>
            
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recent_users as $user): ?>
                    <tr class="hover:bg-white/20 dark:hover:bg-gray-700/20 transition">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-semibold">
                                    <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-100"><?= e($user['username']) ?></div>
                                    <?php if ($user['full_name']): ?>
                                    <div class="text-sm text-gray-600 dark:text-gray-400"><?= e($user['full_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-700 dark:text-gray-300"><?= $user['email'] ? e($user['email']) : '-' ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <select name="user_role" onchange="if(confirm('Change user role?')) this.form.submit()" 
                                        class="bg-white/70 dark:bg-gray-700 text-gray-800 dark:text-white text-sm rounded-lg px-3 py-1.5 border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-primary-500">
                                    <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['role_name'] ?>" <?= $user['user_role'] === $role['role_name'] ? 'selected' : '' ?>>
                                        <?= e($role['role_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="update_role" value="1">
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['is_active'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' ?>">
                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            <?= date('M d, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($user['user_id'] != current_user_id()): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <button type="submit" name="toggle_active" value="1" 
                                        onclick="return confirm('Toggle user active status?')"
                                        class="<?= $user['is_active'] ? 'bg-red-100 hover:bg-red-200 text-red-700 dark:bg-red-900/30 dark:hover:bg-red-900/50 dark:text-red-300' : 'bg-green-100 hover:bg-green-200 text-green-700 dark:bg-green-900/30 dark:hover:bg-green-900/50 dark:text-green-300' ?> px-3 py-1.5 rounded-lg transition font-medium text-xs">
                                    <i class="fas fa-<?= $user['is_active'] ? 'ban' : 'check' ?> mr-1"></i>
                                    <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
