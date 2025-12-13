<?php
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    $base = rtrim(app_base_url(), '/');
    $target = ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
    header('Location: ' . $target);
    exit;
}

function flash(string $message, string $type = 'info'): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash'][] = ['msg' => $message, 'type' => $type];
}

function consume_flashes(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function ensure_upload_dir(int $userId): string {
    $config = require __DIR__ . '/../config/config.php';
    $dir = rtrim($config['uploads']['dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $userId;
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function human_datetime(string $ts): string {
    return date('M d, Y h:i A', strtotime($ts));
}

function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function app_base_url(): string {
    // Prefer configured base_url if set
    $config = require __DIR__ . '/../config/config.php';
    $cfg = trim((string)($config['app']['base_url'] ?? ''));
    if ($cfg !== '') {
        // Ensure leading slash, no trailing slash
        $cfg = '/' . ltrim($cfg, '/');
        return rtrim($cfg, '/');
    }
    // Derive from the current script path (most reliable under subfolders)
    if (!empty($_SERVER['SCRIPT_NAME'])) {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        // When app is at document root, dirname might be '\' or '.'; normalize to ''
        if ($dir === '/' || $dir === '\\' || $dir === '.') { return ''; }
        return $dir;
    }
    // Auto-detect base URL relative to document root (Apache/XAMPP)
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
    $proj = realpath(__DIR__ . '/..'); // project root
    if ($doc && $proj) {
        $doc = rtrim(str_replace('\\', '/', $doc), '/');
        $proj = rtrim(str_replace('\\', '/', $proj), '/');
        if (strpos($proj, $doc) === 0) {
            $rel = substr($proj, strlen($doc));
            $rel = '/' . ltrim($rel, '/');
            return rtrim($rel, '/');
        }
    }
    // Fallback to empty (site root)
    return '';
}

function allowed_moods(): array {
    return [
        'Happy' => '😀',
        'Sad' => '😢',
        'Angry' => '😠',
        'Calm' => '😌',
        'Excited' => '🤩',
        'Reflective' => '🤔',
    ];
}

function mood_emoji(?string $mood): string {
    if ($mood === null || $mood === '') return '';
    $map = allowed_moods();
    return $map[$mood] ?? '';
}

function excerpt(string $text, int $length = 150): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '...';
}

// ==========================================
// DATABASE UTILITY FUNCTIONS
// ==========================================

/**
 * Get all categories
 */
function get_categories(): array {
    require_once __DIR__ . '/db.php';
    return db_all("SELECT * FROM categories ORDER BY category_name");
}

/**
 * Get category by ID
 */
function get_category(int $categoryId): ?array {
    require_once __DIR__ . '/db.php';
    return db_one("SELECT * FROM categories WHERE category_id = ?", [$categoryId]);
}

/**
 * Create new category
 */
function create_category(string $name, string $description = '', string $color = '#6B7280', string $icon = '', ?int $createdBy = null): int {
    require_once __DIR__ . '/db.php';
    db_exec("INSERT INTO categories (category_name, description, color, icon, created_by) VALUES (?, ?, ?, ?, ?)",
        [$name, $description, $color, $icon, $createdBy]);
    return db()->lastInsertId();
}

/**
 * Get all tags
 */
function get_tags(int $limit = 50): array {
    require_once __DIR__ . '/db.php';
    return db_all("SELECT * FROM tags ORDER BY usage_count DESC, tag_name LIMIT ?", [$limit]);
}

/**
 * Get or create tag by name
 */
function get_or_create_tag(string $tagName): int {
    require_once __DIR__ . '/db.php';
    $tag = db_one("SELECT tag_id FROM tags WHERE tag_name = ?", [$tagName]);
    if ($tag) {
        return $tag['tag_id'];
    }
    db_exec("INSERT INTO tags (tag_name) VALUES (?)", [$tagName]);
    return db()->lastInsertId();
}

/**
 * Get tags for an entry
 */
function get_entry_tags(int $entryId): array {
    require_once __DIR__ . '/db.php';
    return db_all("SELECT t.* FROM tags t 
                   JOIN entry_tags et ON t.tag_id = et.tag_id 
                   WHERE et.entry_id = ?", [$entryId]);
}

/**
 * Add tag to entry (and update count manually)
 */
function add_tag_to_entry(int $entryId, int $tagId): void {
    require_once __DIR__ . '/db.php';
    db_exec("INSERT IGNORE INTO entry_tags (entry_id, tag_id) VALUES (?, ?)", [$entryId, $tagId]);
    // Update tag usage count manually (since we don't have triggers)
    db_exec("UPDATE tags SET usage_count = usage_count + 1 WHERE tag_id = ?", [$tagId]);
}

/**
 * Remove all tags from entry (and update counts manually)
 */
function remove_entry_tags(int $entryId): void {
    require_once __DIR__ . '/db.php';
    // Decrease usage count for all tags of this entry
    db_exec("UPDATE tags t 
             JOIN entry_tags et ON t.tag_id = et.tag_id 
             SET t.usage_count = GREATEST(t.usage_count - 1, 0) 
             WHERE et.entry_id = ?", [$entryId]);
    // Then delete the associations
    db_exec("DELETE FROM entry_tags WHERE entry_id = ?", [$entryId]);
}

/**
 * Check if user has permission (simplified - based on user_role)
 * Only two roles: Admin and User
 */
function user_has_permission(int $userId, string $permissionName): bool {
    require_once __DIR__ . '/db.php';
    $user = db_one("SELECT user_role FROM users WHERE user_id = ? AND is_active = TRUE", [$userId]);
    
    if (!$user) return false;
    
    $role = $user['user_role'];
    
    // Admin has all permissions
    if ($role === 'Admin') return true;
    
    // Regular users have basic permissions
    $basicPerms = ['create_entry', 'edit_entry', 'delete_entry', 'view_entry', 
                  'share_entry', 'comment_entry', 'view_analytics', 'export_data'];
    return in_array($permissionName, $basicPerms);
}

/**
 * Get reaction types
 */
function get_reaction_types(): array {
    return [
        'like' => '👍',
        'love' => '❤️',
        'insightful' => '💡',
        'inspiring' => '✨'
    ];
}

/**
 * Get user's reaction to an entry
 */
function get_user_reaction(int $entryId, int $userId): ?string {
    require_once __DIR__ . '/db.php';
    $result = db_one("SELECT reaction_type FROM reactions WHERE entry_id = ? AND user_id = ?", [$entryId, $userId]);
    return $result ? $result['reaction_type'] : null;
}

/**
 * Add or update reaction
 */
function add_reaction(int $entryId, int $userId, string $reactionType): void {
    require_once __DIR__ . '/db.php';
    db_exec("INSERT INTO reactions (entry_id, user_id, reaction_type) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE reaction_type = ?", [$entryId, $userId, $reactionType, $reactionType]);
}

/**
 * Remove reaction
 */
function remove_reaction(int $entryId, int $userId): void {
    require_once __DIR__ . '/db.php';
    db_exec("DELETE FROM reactions WHERE entry_id = ? AND user_id = ?", [$entryId, $userId]);
}

/**
 * Get reaction counts for an entry
 */
function get_reaction_counts(int $entryId): array {
    require_once __DIR__ . '/db.php';
    $reactions = db_all("SELECT reaction_type, COUNT(*) as count 
                         FROM reactions 
                         WHERE entry_id = ? 
                         GROUP BY reaction_type", [$entryId]);
    $counts = [];
    foreach ($reactions as $r) {
        $counts[$r['reaction_type']] = $r['count'];
    }
    return $counts;
}

/**
 * Check if entry is shared with user
 */
function is_entry_shared_with_user(int $entryId, int $userId): bool {
    require_once __DIR__ . '/db.php';
    $result = db_one("SELECT share_id FROM shared_entries 
                      WHERE entry_id = ? AND shared_with = ?", [$entryId, $userId]);
    return $result !== null;
}

/**
 * Get user stats
 */
function get_user_stats(int $userId): ?array {
    require_once __DIR__ . '/db.php';
    return db_one("SELECT * FROM user_stats WHERE user_id = ?", [$userId]);
}

/**
 * Update user stats using basic SQL queries
 */
function update_user_stats(int $userId): void {
    require_once __DIR__ . '/db.php';
    
    // Calculate basic stats using aggregate functions
    $stats = db_one("SELECT 
        COUNT(*) AS total_entries,
        SUM(word_count) AS total_words,
        AVG(word_count) AS avg_words,
        MAX(DATE(timestamp)) AS last_entry_date
    FROM entries
    WHERE user_id = ? AND is_deleted = FALSE", [$userId]);
    
    // Find most common mood
    $mood = db_one("SELECT mood
        FROM entries
        WHERE user_id = ? AND is_deleted = FALSE AND mood IS NOT NULL
        GROUP BY mood
        ORDER BY COUNT(*) DESC
        LIMIT 1", [$userId]);
    
    $most_common_mood = $mood ? $mood['mood'] : null;
    
    // Calculate current and longest streak (simplified)
    $streak = calculate_writing_streak($userId);
    
    // Insert or update using INSERT...ON DUPLICATE KEY UPDATE
    db_exec("INSERT INTO user_stats 
        (user_id, total_entries, total_words, avg_words_per_entry, 
         most_common_mood, last_entry_date, current_streak, longest_streak)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_entries = VALUES(total_entries),
            total_words = VALUES(total_words),
            avg_words_per_entry = VALUES(avg_words_per_entry),
            most_common_mood = VALUES(most_common_mood),
            last_entry_date = VALUES(last_entry_date),
            current_streak = VALUES(current_streak),
            longest_streak = VALUES(longest_streak)",
        [$userId, $stats['total_entries'], $stats['total_words'] ?? 0, 
         $stats['avg_words'] ?? 0, $most_common_mood, $stats['last_entry_date'],
         $streak['current'], $streak['longest']]);
}

/**
 * Calculate writing streak using basic SQL
 */
function calculate_writing_streak(int $userId): array {
    require_once __DIR__ . '/db.php';
    
    // Get all entry dates ordered descending
    $dates = db_all("SELECT DISTINCT DATE(timestamp) AS entry_date
        FROM entries
        WHERE user_id = ? AND is_deleted = FALSE
        ORDER BY entry_date DESC", [$userId]);
    
    $current_streak = 0;
    $longest_streak = 0;
    $temp_streak = 0;
    $prev_date = null;
    
    foreach ($dates as $row) {
        $date = strtotime($row['entry_date']);
        
        if ($prev_date === null) {
            // First entry
            $temp_streak = 1;
            // Check if it's today or yesterday for current streak
            $today = strtotime(date('Y-m-d'));
            $yesterday = strtotime('-1 day', $today);
            if ($date == $today || $date == $yesterday) {
                $current_streak = 1;
            }
        } elseif (($prev_date - $date) == 86400) { // 1 day difference
            // Consecutive day
            $temp_streak++;
            if ($current_streak > 0) {
                $current_streak++;
            }
        } else {
            // Streak broken
            if ($temp_streak > $longest_streak) {
                $longest_streak = $temp_streak;
            }
            $temp_streak = 1;
        }
        
        $prev_date = $date;
    }
    
    // Final check
    if ($temp_streak > $longest_streak) {
        $longest_streak = $temp_streak;
    }
    
    return ['current' => $current_streak, 'longest' => $longest_streak];
}

/**
 * Get privacy level options
 */
function get_privacy_levels(): array {
    return [
        'private' => 'Private (Only me)',
        'public' => 'Public (Everyone)'
    ];
}

/**
 * Format privacy level for display
 */
function format_privacy(string $privacyLevel): string {
    $levels = get_privacy_levels();
    return $levels[$privacyLevel] ?? $privacyLevel;
}

/**
 * Log audit entry (simplified - removed for simpler schema)
 */
function log_audit(int $userId, string $actionType, string $tableName, int $recordId, ?array $oldValues = null, ?array $newValues = null): void {
    // Audit logging removed in simplified version
    // All frontend features work without it
    return;
}

/**
 * Initialize entry stats (replaces trigger functionality)
 */
function init_entry_stats(int $entryId): void {
    require_once __DIR__ . '/db.php';
    db_exec("INSERT IGNORE INTO entry_stats (entry_id, view_count) VALUES (?, 0)", [$entryId]);
}

/**
 * Update mood history (replaces trigger functionality)
 */
function update_mood_history(int $userId, string $mood, string $date): void {
    require_once __DIR__ . '/db.php';
    if ($mood !== '') {
        db_exec("INSERT INTO mood_history (user_id, mood, entry_date, entry_count)
                 VALUES (?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE entry_count = entry_count + 1",
                [$userId, $mood, $date]);
    }
}

/**
 * Update entry word count manually (replaces trigger functionality)
 */
function calculate_word_count(string $content): int {
    $trimmed = trim($content);
    if ($trimmed === '') return 0;
    return count(explode(' ', preg_replace('/\s+/', ' ', $trimmed)));
}

/**
 * Update entry stats counts manually
 */
function update_entry_stat_count(int $entryId, string $field): void {
    require_once __DIR__ . '/db.php';
    // Initialize if doesn't exist
    db_exec("INSERT IGNORE INTO entry_stats (entry_id, view_count) VALUES (?, 0)", [$entryId]);
    // Increment the specific field
    db_exec("UPDATE entry_stats SET $field = $field + 1 WHERE entry_id = ?", [$entryId]);
}
