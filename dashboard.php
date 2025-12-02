<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();
$pdo = db();
$userId = current_user_id();

// Optional date filter
$dateFilter = isset($_GET['date']) ? trim($_GET['date']) : '';
$params = [$userId];
$whereDate = '';
if ($dateFilter !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $dateFilter);
  if ($dt !== false) {
    $whereDate = ' AND DATE(e.timestamp) = ?';
    $params[] = $dt->format('Y-m-d');
  }
}

$entries = db_all("SELECT e.*, (
  SELECT m.file_path FROM media m WHERE m.entry_id = e.entry_id AND m.file_type LIKE 'image/%' ORDER BY m.media_id ASC LIMIT 1
) AS cover_image
FROM entries e
WHERE e.user_id = ?" . $whereDate . "
ORDER BY e.timestamp DESC", $params);

$pageTitle = 'Dashboard';
include __DIR__ . '/partials/head.php';
?>
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-gray-800">Your Entries</h1>
  <div class="flex items-center gap-3">
    <form method="get" class="hidden sm:flex items-center gap-2">
      <input type="date" name="date" value="<?php echo e($dateFilter); ?>" class="rounded-2xl px-3 py-2 bg-white/70 border border-primary-100 focus:border-primary-400 outline-none" />
      <button class="px-3 py-2 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100">Filter</button>
      <?php if ($dateFilter !== ''): ?>
        <a href="dashboard.php" class="px-3 py-2 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100">Clear</a>
      <?php endif; ?>
    </form>
    <a href="create.php" class="px-5 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">+ New Entry</a>
  </div>
</div>

<?php if (empty($entries)): ?>
  <div class="glass rounded-3xl p-8 text-center text-gray-600">No entries yet. Create your first one!</div>
<?php else: ?>
  <div class="masonry">
    <?php foreach ($entries as $e): ?>
      <a href="view.php?id=<?php echo (int)$e['entry_id']; ?>" class="block group masonry-item">
        <div class="glass rounded-3xl shadow-xl overflow-hidden hover:shadow-2xl transition relative">
          <?php if (!empty($e['mood'])): $emo = mood_emoji($e['mood']); ?>
            <div class="absolute top-3 right-3 z-10 px-2 py-1 rounded-full bg-white/70 text-gray-700 border border-primary-100 text-xs flex items-center gap-1">
              <span><?php echo e($emo !== '' ? $emo : ''); ?></span>
              <span><?php echo e($e['mood']); ?></span>
            </div>
          <?php endif; ?>
          <?php if ($e['cover_image']): ?>
            <img src="<?php echo e($e['cover_image']); ?>" alt="cover" class="w-full h-40 object-cover group-hover:scale-105 transition" />
          <?php else: ?>
            <div class="w-full h-40 bg-gradient-to-br from-primary-700 to-primary-600"></div>
          <?php endif; ?>
          <div class="p-5">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-800 truncate"><?php echo e($e['title']); ?></h2>
              <?php if (!empty($e['mood'])): ?>
                <span class="text-xs px-2 py-1 rounded-full bg-white/70 text-gray-700 border border-primary-100"><?php echo e($e['mood']); ?></span>
              <?php endif; ?>
            </div>
            <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?php echo e(mb_strimwidth($e['content'], 0, 120, '…')); ?></p>
            <p class="text-xs text-gray-500 mt-3"><?php echo e(human_datetime($e['timestamp'])); ?></p>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
