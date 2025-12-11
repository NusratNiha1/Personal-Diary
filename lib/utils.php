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
 * Add tag to entry
 */
function add_tag_to_entry(int $entryId, int $tagId): void {
    require_once __DIR__ . '/db.php';
    db_exec("INSERT IGNORE INTO entry_tags (entry_id, tag_id) VALUES (?, ?)", [$entryId, $tagId]);
}

/**
 * Remove all tags from entry
 */
function remove_entry_tags(int $entryId): void {
    require_once __DIR__ . '/db.php';
    db_exec("DELETE FROM entry_tags WHERE entry_id = ?", [$entryId]);
}

/**
 * Get all roles
 */
function get_roles(): array {
    require_once __DIR__ . '/db.php';
    return db_all("SELECT * FROM roles ORDER BY role_name");
}

/**
 * Get role by ID
 */
function get_role(int $roleId): ?array {
    require_once __DIR__ . '/db.php';
    return db_one("SELECT * FROM roles WHERE role_id = ?", [$roleId]);
}

/**
 * Check if user has permission
 */
function user_has_permission(int $userId, string $permissionName): bool {
    require_once __DIR__ . '/db.php';
    $result = db_one("SELECT fn_has_permission(?, ?) AS has_perm", [$userId, $permissionName]);
    return $result && $result['has_perm'] == 1;
}

/**
 * Get user's permissions
 */
function get_user_permissions(int $userId): array {
    require_once __DIR__ . '/db.php';
    return db_all("SELECT p.permission_name, p.description 
                   FROM users u
                   JOIN role_permissions rp ON u.role_id = rp.role_id
                   JOIN permissions p ON rp.permission_id = p.permission_id
                   WHERE u.user_id = ?", [$userId]);
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
 * Update user stats (calls stored procedure)
 */
function update_user_stats(int $userId): void {
    require_once __DIR__ . '/db.php';
    db_exec("CALL sp_update_user_stats(?)", [$userId]);
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
 * Log audit entry
 */
function log_audit(int $userId, string $actionType, string $tableName, int $recordId, ?array $oldValues = null, ?array $newValues = null): void {
    require_once __DIR__ . '/db.php';
    $oldJson = $oldValues ? json_encode($oldValues) : null;
    $newJson = $newValues ? json_encode($newValues) : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    db_exec("INSERT INTO audit_log (user_id, action_type, table_name, record_id, old_values, new_values, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)", 
            [$userId, $actionType, $tableName, $recordId, $oldJson, $newJson, $ipAddress]);
}
