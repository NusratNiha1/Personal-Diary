<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
$authed = is_logged_in();
$base = rtrim(app_base_url(), '/');
?>
<nav class="w-full">
  <div class="container mx-auto px-4 pt-6">
    <div class="glass rounded-2xl shadow-xl p-4 flex items-center justify-between">
      <a href="<?php echo e($base); ?>/dashboard.php" class="text-lg font-semibold text-primary-700 hover:text-primary-900 transition">MyDiary</a>
      <div class="flex items-center gap-3">
        <?php if ($authed): ?>
          <a href="<?php echo e($base); ?>/dashboard.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition shadow-sm">Dashboard</a>
          <a href="<?php echo e($base); ?>/profile.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition">Profile</a>
          <span class="hidden sm:inline text-gray-600">Hi, <?php echo e(current_username()); ?></span>
          <a href="<?php echo e($base); ?>/logout.php" class="px-3 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition">Logout</a>
        <?php else: ?>
          <a href="<?php echo e($base); ?>/index.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition">Login</a>
          <a href="<?php echo e($base); ?>/signup.php" class="px-4 py-2 rounded-full bg-primary-600 hover:bg-primary-700 text-white transition shadow-md">Sign Up</a>
        <?php endif; ?>
        <button id="themeToggle" class="px-3 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition border border-primary-100" aria-label="Switch theme">Dark</button>
      </div>
    </div>
  </div>
</nav>
