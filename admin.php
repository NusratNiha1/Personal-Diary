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
$recent_users = db_all("SELECT u.user_id, u.username, u.full_name, u.email, r.role_name, u.created_at, u.is_active
                        FROM users u
                        LEFT JOIN roles r ON u.role_id = r.role_id
                        ORDER BY u.created_at DESC
                        LIMIT 10");

// Handle user role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $role_id = (int)$_POST['role_id'];
    db_exec("UPDATE users SET role_id = ? WHERE user_id = ?", [$role_id, $user_id]);
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

// Get all roles for dropdown
$roles = get_roles();

include 'partials/head.php';
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Admin Panel</h1>
            <p class="text-gray-400">Manage users, roles, and system settings</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Users</p>
                        <p class="text-3xl font-bold mt-1"><?= $total_users ?></p>
                    </div>
                    <div class="text-4xl opacity-50">👥</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Total Entries</p>
                        <p class="text-3xl font-bold mt-1"><?= $total_entries ?></p>
                    </div>
                    <div class="text-4xl opacity-50">📝</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Categories</p>
                        <p class="text-3xl font-bold mt-1"><?= $total_categories ?></p>
                    </div>
                    <div class="text-4xl opacity-50">🏷️</div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Tags</p>
                        <p class="text-3xl font-bold mt-1"><?= $total_tags ?></p>
                    </div>
                    <div class="text-4xl opacity-50">🔖</div>
                </div>
            </div>
        </div>

        <!-- Users Management -->
        <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h2 class="text-xl font-semibold text-white">User Management</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        <?php foreach ($recent_users as $user): ?>
                        <tr class="hover:bg-gray-750">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold">
                                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white"><?= e($user['username']) ?></div>
                                        <?php if ($user['full_name']): ?>
                                        <div class="text-sm text-gray-400"><?= e($user['full_name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-300"><?= $user['email'] ? e($user['email']) : '-' ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                    <select name="role_id" onchange="if(confirm('Change user role?')) this.form.submit()" 
                                            class="bg-gray-700 text-white text-sm rounded px-2 py-1 border border-gray-600">
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>" <?= $user['role_name'] === $role['role_name'] ? 'selected' : '' ?>>
                                            <?= e($role['role_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $user['is_active'] ? 'bg-green-900 text-green-200' : 'bg-red-900 text-red-200' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($user['user_id'] != current_user_id()): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                    <button type="submit" name="toggle_active" value="1" 
                                            onclick="return confirm('Toggle user active status?')"
                                            class="text-blue-400 hover:text-blue-300 mr-3">
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

        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="categories.php" class="bg-gray-800 hover:bg-gray-750 rounded-lg p-6 transition duration-200">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">🏷️</div>
                    <div>
                        <h3 class="text-white font-semibold">Manage Categories</h3>
                        <p class="text-gray-400 text-sm">Create and organize categories</p>
                    </div>
                </div>
            </a>

            <a href="analytics.php" class="bg-gray-800 hover:bg-gray-750 rounded-lg p-6 transition duration-200">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">📊</div>
                    <div>
                        <h3 class="text-white font-semibold">View Analytics</h3>
                        <p class="text-gray-400 text-sm">System statistics and insights</p>
                    </div>
                </div>
            </a>

            <a href="migrate.php" class="bg-gray-800 hover:bg-gray-750 rounded-lg p-6 transition duration-200">
                <div class="flex items-center">
                    <div class="text-3xl mr-4">🔧</div>
                    <div>
                        <h3 class="text-white font-semibold">Database Migration</h3>
                        <p class="text-gray-400 text-sm">Update database schema</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
