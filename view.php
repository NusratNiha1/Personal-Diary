<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_login();
$pdo = get_pdo();

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM entries WHERE entry_id = ? AND user_id = ?');
$stmt->execute([$entryId, current_user_id()]);
$entry = $stmt->fetch();
if (!$entry) {
  flash('Entry not found', 'error');
  redirect('dashboard.php');
}

$mstmt = $pdo->prepare('SELECT * FROM media WHERE entry_id = ? ORDER BY media_id ASC');
$mstmt->execute([$entryId]);
$media = $mstmt->fetchAll();

$pageTitle = e($entry['title']);
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-3xl mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-2">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800"><?php echo e($entry['title']); ?></h1>
        <p class="text-sm text-gray-500 mt-1"><?php echo e(human_datetime($entry['timestamp'])); ?><?php echo $entry['mood'] ? ' • ' . e($entry['mood']) : ''; ?></p>
      </div>
      <div class="flex gap-2">
        <a href="edit.php?id=<?php echo (int)$entry['entry_id']; ?>" class="px-4 py-2 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100 shadow-sm transition">Edit</a>
        <form method="post" action="delete.php" onsubmit="return confirm('Delete this entry? This cannot be undone.')">
          <input type="hidden" name="id" value="<?php echo (int)$entry['entry_id']; ?>" />
          <button type="submit" class="px-4 py-2 rounded-2xl bg-red-500 hover:bg-red-600 text-white shadow-sm transition">Delete</button>
        </form>
      </div>
    </div>

    <?php if (!empty($media)): ?>
      <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($media as $m): ?>
          <?php if (strpos($m['file_type'], 'image/') === 0): ?>
            <img src="<?php echo e($m['file_path']); ?>" class="w-full rounded-2xl shadow" />
          <?php elseif (strpos($m['file_type'], 'audio/') === 0): ?>
            <div class="p-4 rounded-2xl bg-white/70 border border-primary-100 shadow">
              <audio controls class="w-full">
                <source src="<?php echo e($m['file_path']); ?>" type="<?php echo e($m['file_type']); ?>" />
              </audio>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="prose max-w-none mt-6">
      <p class="whitespace-pre-wrap text-gray-800 leading-relaxed"><?php echo nl2br(e($entry['content'])); ?></p>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
