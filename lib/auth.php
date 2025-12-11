<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/utils.php';

function start_session_once(): void {
    $config = require __DIR__ . '/../config/config.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_name($config['app']['session_name']);
        session_start();
    }
}

function is_logged_in(): bool {
    start_session_once();
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('Please log in to continue', 'warning');
        redirect('index.php');
    }
}

function current_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

function current_username(): ?string {
    return is_logged_in() ? (string)$_SESSION['username'] : null;
}

function login_user(string $username, string $password): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && $password === $user['password']) {
        start_session_once();
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function register_user(string $username, string $password, string $security_question = '', string $security_answer = ''): array {
    $pdo = get_pdo();
    // Ensure unique username
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
    $exists->execute([$username]);
    if ($exists->fetchColumn()) {
        return [false, 'Username already taken'];
    }
    $answer = !empty($security_answer) ? strtolower(trim($security_answer)) : null;
    
    $stmt = $pdo->prepare('INSERT INTO users (username, password, security_question, security_answer) VALUES (?, ?, ?, ?)');
    try {
        $stmt->execute([$username, $password, $security_question, $answer]);
        return [true, null];
    } catch (Throwable $e) {
        return [false, 'Failed to create user'];
    }
}

function logout_user(): void {
    start_session_once();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Get current user's full details
 */
function get_logged_in_user(): ?array {
    if (!is_logged_in()) return null;
    require_once __DIR__ . '/db.php';
    return db_one("SELECT u.*, r.role_name 
                   FROM users u 
                   LEFT JOIN roles r ON u.role_id = r.role_id 
                   WHERE u.user_id = ?", [current_user_id()]);
}

/**
 * Check if current user has a specific permission
 */
function has_permission(string $permissionName): bool {
    if (!is_logged_in()) return false;
    require_once __DIR__ . '/utils.php';
    return user_has_permission(current_user_id(), $permissionName);
}

/**
 * Require a specific permission or redirect
 */
function require_permission(string $permissionName, string $message = 'You do not have permission to access this page'): void {
    if (!has_permission($permissionName)) {
        flash($message, 'error');
        redirect('dashboard.php');
    }
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    $user = get_logged_in_user();
    return $user && $user['role_name'] === 'Admin';
}

/**
 * Require admin role
 */
function require_admin(): void {
    if (!is_admin()) {
        flash('Admin access required', 'error');
        redirect('dashboard.php');
    }
}

/**
 * Update last login time
 */
function update_last_login(int $userId): void {
    require_once __DIR__ . '/db.php';
    db_exec("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$userId]);
}

/**
 * Log user session
 */
function log_user_session(int $userId): void {
    require_once __DIR__ . '/db.php';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    db_exec("INSERT INTO user_sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)",
            [$userId, $ipAddress, $userAgent]);
}
