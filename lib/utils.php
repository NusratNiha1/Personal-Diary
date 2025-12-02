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
