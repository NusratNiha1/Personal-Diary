<?php
require_once 'lib/auth.php';
require_once 'lib/db.php';
require_once 'lib/utils.php';

require_login();

$page_title = 'Analytics Dashboard';
$user_id = current_user_id();

// Get or update user stats
$stats = get_user_stats($user_id);
if (!$stats) {
    update_user_stats($user_id);
    $stats = get_user_stats($user_id);
}

// Get mood distribution for last 30 days
$mood_data = db_all("CALL sp_get_mood_distribution(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE())", [$user_id]);

// Get writing calendar for current year
$calendar_data = db_all("CALL sp_get_writing_calendar(?, YEAR(CURDATE()))", [$user_id]);

// Get category breakdown
$category_stats = db_all("SELECT c.category_name, c.color, c.icon, COUNT(e.entry_id) as count
                          FROM categories c
                          LEFT JOIN entries e ON c.category_id = e.category_id AND e.user_id = ? AND e.is_deleted = FALSE
                          GROUP BY c.category_id
                          HAVING count > 0
                          ORDER BY count DESC", [$user_id]);

// Get recent activity
$recent_entries = db_all("SELECT DATE(timestamp) as date, COUNT(*) as count
                          FROM entries
                          WHERE user_id = ? AND is_deleted = FALSE
                          AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          GROUP BY DATE(timestamp)
                          ORDER BY date DESC
                          LIMIT 30", [$user_id]);

// Calculate this month's stats
$this_month_count = db_one("SELECT COUNT(*) as count FROM entries 
                             WHERE user_id = ? AND is_deleted = FALSE 
                             AND MONTH(timestamp) = MONTH(CURDATE()) 
                             AND YEAR(timestamp) = YEAR(CURDATE())", [$user_id])['count'];

include 'partials/head.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <div class="glass rounded-2xl shadow-xl p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
            <i class="fas fa-chart-line text-primary-600"></i>
            Analytics Dashboard
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Your writing insights and statistics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="glass rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <i class="fas fa-book text-2xl text-primary-600 dark:text-primary-400"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 dark:text-white"><?= $stats['total_entries'] ?? 0 ?></span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Entries</p>
        </div>

        <div class="glass rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-fire text-2xl text-green-600 dark:text-green-400"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 dark:text-white"><?= $stats['current_streak'] ?? 0 ?></span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Current Streak (days)</p>
        </div>

        <div class="glass rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-pen-fancy text-2xl text-purple-600 dark:text-purple-400"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 dark:text-white"><?= number_format($stats['total_words'] ?? 0) ?></span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Words</p>
        </div>

        <div class="glass rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-2xl text-orange-600 dark:text-orange-400"></i>
                </div>
                <span class="text-3xl font-bold text-gray-800 dark:text-white"><?= $this_month_count ?></span>
            </div>
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">This Month</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Mood Distribution -->
        <div class="glass rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-smile text-primary-600"></i>
                Mood Distribution (Last 30 Days)
            </h2>
            <?php if (!empty($mood_data)): ?>
            <div class="space-y-3">
                <?php foreach ($mood_data as $mood): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300"><?= e($mood['mood']) ?></span>
                        <span class="text-gray-500 dark:text-gray-400"><?= $mood['percentage'] ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-full h-2.5 transition-all duration-500" style="width: <?= $mood['percentage'] ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No mood data available</p>
            <?php endif; ?>
        </div>

        <!-- Category Breakdown -->
        <div class="glass rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-layer-group text-primary-600"></i>
                Category Breakdown
            </h2>
            <?php if (!empty($category_stats)): ?>
            <div class="space-y-3">
                <?php foreach ($category_stats as $cat): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-700 dark:text-gray-300"><?= e($cat['category_name']) ?></span>
                        <span class="text-gray-500 dark:text-gray-400"><?= $cat['count'] ?> entries</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="rounded-full h-2.5 transition-all duration-500" style="width: <?= ($cat['count'] / $stats['total_entries']) * 100 ?>%; background-color: <?= e($cat['color']) ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No category data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="glass rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-primary-600"></i>
                Writing Stats
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">
                        <i class="fas fa-calculator text-primary-500 w-4"></i> Avg Words/Entry
                    </span>
                    <span class="text-gray-800 dark:text-white font-semibold"><?= number_format($stats['avg_words_per_entry'] ?? 0, 0) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">
                        <i class="fas fa-trophy text-primary-500 w-4"></i> Longest Streak
                    </span>
                    <span class="text-gray-800 dark:text-white font-semibold"><?= $stats['longest_streak'] ?? 0 ?> days</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">
                        <i class="fas fa-heart text-primary-500 w-4"></i> Most Common Mood
                    </span>
                    <span class="text-gray-800 dark:text-white font-semibold"><?= $stats['most_common_mood'] ?? '-' ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">
                        <i class="fas fa-clock text-primary-500 w-4"></i> Last Entry
                    </span>
                    <span class="text-gray-800 dark:text-white font-semibold"><?= $stats['last_entry_date'] ? date('M d, Y', strtotime($stats['last_entry_date'])) : '-' ?></span>
                </div>
            </div>
        </div>

        <div class="glass rounded-2xl shadow-xl p-6 lg:col-span-2">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-history text-primary-600"></i>
                Recent Activity (Last 7 Days)
            </h3>
            <?php if (!empty($recent_entries)): ?>
            <div class="grid grid-cols-7 gap-2">
                <?php foreach (array_reverse($recent_entries) as $entry): ?>
                <div class="text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1"><?= date('M j', strtotime($entry['date'])) ?></div>
                    <div class="mx-auto rounded-lg bg-gradient-to-t from-primary-500 to-primary-300 transition-all duration-300 hover:scale-110" style="opacity: <?= min($entry['count'] / 3, 1) ?>; height: <?= min($entry['count'] * 20, 48) ?>px; width: 100%; max-width: 40px;"></div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 font-semibold"><?= $entry['count'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Button -->
    <div class="mt-8 text-center">
        <button onclick="location.reload()" class="px-6 py-3 rounded-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium shadow-lg hover:shadow-xl transition transform hover:scale-105">
            <i class="fas fa-sync-alt mr-2"></i>Refresh Stats
        </button>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
