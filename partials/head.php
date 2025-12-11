<?php
require_once __DIR__ . '/../lib/auth.php';
start_session_once();
$flashes = consume_flashes();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' • ' : ''; ?>Life Canvas</title>
  <!-- Google Fonts for fancy typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Macondo+Swash+Caps&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  
  <!-- Theme initialization - runs immediately to prevent flash -->
  <script>
    (function() {
      try {
        const saved = localStorage.getItem('theme');
        const theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
      } catch(e) {}
    })();
  </script>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            'display': ['Macondo Swash Caps', 'cursive'],
            'body': ['Poppins', 'sans-serif'],
          },
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c3d66'
            },
            arctic: {
              50: '#f0f4f8',
              100: '#e1eef7',
              200: '#d4e3ed',
              300: '#b8cfe1',
              400: '#8fa8c5',
              500: '#6b92b8',
              600: '#4a73a6',
              700: '#375a8c',
              800: '#2d4671',
              900: '#1a3a4a'
            }
          },
        }
      }
    }
  </script>
  <link rel="stylesheet" href="assets/css/theme.css?v=<?php echo @filemtime(__DIR__.'/../assets/css/theme.css'); ?>" />
</head>
<body class="min-h-screen gradient-bg flex flex-col">
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
  <main class="flex-1 container mx-auto px-4 py-8">
