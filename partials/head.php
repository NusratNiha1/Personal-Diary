<?php
require_once __DIR__ . '/../lib/auth.php';
start_session_once();
$flashes = consume_flashes();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' • ' : ''; ?>Diary App</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eef2ff',
              100: '#e0e7ff',
              200: '#c7d2fe',
              300: '#a5b4fc',
              400: '#818cf8',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
              800: '#3730a3',
              900: '#312e81'
            }
          },
        }
      }
    }
  </script>
  <link rel="stylesheet" href="assets/css/theme.css?v=<?php echo @filemtime(__DIR__.'/../assets/css/theme.css'); ?>" />
</head>
<body class="min-h-screen gradient-bg">
  <?php $isAuthed = is_logged_in(); ?>
  <?php include __DIR__ . '/nav.php'; ?>
  <?php if (!empty($flashes)): ?>
    <div class="fixed top-4 right-4 z-50 space-y-3">
      <?php foreach ($flashes as $f): $t = $f['type'] ?? 'info'; ?>
        <div class="toast <?php echo $t === 'success' ? 'toast-success' : ($t === 'warning' ? 'toast-warning' : ($t === 'error' ? 'toast-error' : 'toast-info')); ?> toast-enter">
          <div class="flex items-start gap-3">
            <div class="pt-0.5">
              <?php if ($t === 'success'): ?>
                <span>✅</span>
              <?php elseif ($t === 'warning'): ?>
                <span>⚠️</span>
              <?php elseif ($t === 'error'): ?>
                <span>🗑️</span>
              <?php else: ?>
                <span>ℹ️</span>
              <?php endif; ?>
            </div>
            <p class="text-sm flex-1"><?php echo e($f['msg']); ?></p>
            <button class="toast-dismiss" aria-label="Dismiss">✕</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <main class="container mx-auto px-4 py-8">
