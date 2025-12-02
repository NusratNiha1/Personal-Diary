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
    $stmt = $pdo->prepare('SELECT user_id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        start_session_once();
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function register_user(string $username, string $password): array {
    $pdo = get_pdo();
    // Ensure unique username
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');
    $exists->execute([$username]);
    if ($exists->fetchColumn()) {
        return [false, 'Username already taken'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
    try {
        $stmt->execute([$username, $hash]);
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
