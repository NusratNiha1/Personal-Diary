<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
$authed = is_logged_in();
$base = rtrim(app_base_url(), '/');
?>
<nav class="glass container mx-auto sticky top-5 z-50 shadow-xl overflow-visible">
  <div class="container mx-auto px-4 py-4 overflow-visible">
    <div class="flex items-center justify-between overflow-visible">
      <!-- Fancy Logo -->
      <a href="<?php echo e($base); ?>/dashboard.php" class="flex items-center gap-3 hover:opacity-90 transition group no-underline">
        <!-- Logo Icon with gradient and artistic elements -->
        <div class="relative w-12 h-12 flex items-center justify-center">
          <div class="relative bg-gradient-to-br from-primary-500 to-primary-700 rounded-lg w-10 h-10 flex items-center justify-center text-white text-xl font-bold shadow-lg">
            <i class="fas fa-palette"></i>
          </div>
        </div>
        <!-- Fancy Logo Text -->
        <div class="flex flex-col leading-tight">
          <span class="font-display text-2xl font-black bg-gradient-to-r from-primary-600 via-primary-500 to-primary-700 bg-clip-text text-transparent group-hover:from-primary-700 group-hover:to-primary-600 transition no-underline">
            Life Canvas
          </span>
          <span class="font-body text-xs text-gray-500 dark:text-gray-400 tracking-widest uppercase font-medium">
            Your Stories, Our Canvas
          </span>
        </div>
      </a>
      <div class="flex items-center gap-2 md:gap-4 flex-wrap justify-end overflow-visible">
        <?php if ($authed): ?>
          <a href="<?php echo e($base); ?>/dashboard.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 dark:text-gray-200 dark:bg-gray-800/70 dark:hover:bg-gray-800 transition shadow-sm hover:shadow-md text-sm md:text-base">
            <i class="fas fa-home mr-1"></i>My Posts
          </a>
          <a href="<?php echo e($base); ?>/feed.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 dark:text-gray-200 dark:bg-gray-800/70 dark:hover:bg-gray-800 transition shadow-sm hover:shadow-md text-sm md:text-base">
            <i class="fas fa-globe mr-1"></i>Feed
          </a>
          <a href="<?php echo e($base); ?>/analytics.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 dark:text-gray-200 dark:bg-gray-800/70 dark:hover:bg-gray-800 transition shadow-sm hover:shadow-md text-sm md:text-base">
            <i class="fas fa-chart-line mr-1"></i>Analytics
          </a>
          <?php if (is_admin()): ?>
          <a href="<?php echo e($base); ?>/admin.php" class="px-4 py-2 rounded-full bg-red-500/50 hover:bg-red-500/70 text-white-700 hover:text-white-700 transition shadow-sm hover:shadow-md text-sm md:text-base">
            <i class="fas fa-shield-alt mr-1"></i>Admin
          </a>
          <?php endif; ?>
          <!-- Profile Dropdown -->
          <div class="relative overflow-visible">
            <button id="profileDropdown" class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold shadow-md hover:shadow-lg transition text-lg" aria-label="Profile menu">
              <?php echo strtoupper(substr(current_username(), 0, 1)); ?>
            </button>
            <!-- Dropdown Menu -->
            <div id="profileMenu" class="hidden fixed w-48 rounded-2xl shadow-2xl py-2" style="z-index: 9999; background: rgba(32, 45, 30, 0.51); backdrop-filter: blur(25px) saturate(180%); -webkit-backdrop-filter: blur(25px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);">
              <div class="px-4 py-2 border-b" style="border-color: rgba(255, 255, 255, 0.15);">
                <p class="text-sm font-medium text-white"><?php echo e(current_username()); ?></p>
              </div>
              <a href="<?php echo e($base); ?>/profile.php" class="block px-4 py-2 text-sm text-gray-200 hover:bg-white/10 transition">
                <i class="fas fa-user mr-2"></i>Profile
              </a>
              <a href="<?php echo e($base); ?>/create.php" class="block px-4 py-2 text-sm text-gray-200 hover:bg-white/10 transition">
                <i class="fas fa-plus mr-2"></i>New Entry
              </a>
              <a href="<?php echo e($base); ?>/logout.php" class="block px-4 py-2 text-sm text-red-300 hover:bg-red-500/20 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
              </a>
            </div>
          </div>
        <?php else: ?>
          <a href="<?php echo e($base); ?>/index.php" class="px-4 py-2 rounded-full bg-white/70 hover:bg-white/90 text-gray-700 transition text-sm md:text-base">Login</a>
          <a href="<?php echo e($base); ?>/signup.php" class="px-4 py-2 rounded-full bg-primary-600 hover:bg-primary-700 text-white transition shadow-md hover:shadow-lg text-sm md:text-base font-medium">Sign Up</a>
        <?php endif; ?>
        
      </div>
    </div>
  </div>
</nav>
