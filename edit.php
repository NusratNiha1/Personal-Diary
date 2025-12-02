<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_login();
$pdo = get_pdo();
$config = require __DIR__ . '/config/config.php';

$entryId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
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

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $mood = trim($_POST['mood'] ?? '');
  $toDelete = array_map('intval', $_POST['delete_media'] ?? []);

  if ($title === '' || $content === '') {
    $error = 'Title and content are required';
  } else {
    $upd = $pdo->prepare('UPDATE entries SET title = ?, content = ?, mood = ? WHERE entry_id = ? AND user_id = ?');
    $upd->execute([$title, $content, $mood !== '' ? $mood : null, $entryId, current_user_id()]);

    // Delete selected media
    if (!empty($toDelete)) {
      $ph = implode(',', array_fill(0, count($toDelete), '?'));
      $sel = $pdo->prepare("SELECT media_id, file_path FROM media WHERE media_id IN ($ph) AND entry_id = ?");
      $params = array_merge($toDelete, [$entryId]);
      $sel->execute($params);
      $rows = $sel->fetchAll();
      foreach ($rows as $r) {
        $abs = __DIR__ . DIRECTORY_SEPARATOR . $r['file_path'];
        if (is_file($abs)) { @unlink($abs); }
      }
      $del = $pdo->prepare("DELETE FROM media WHERE media_id IN ($ph) AND entry_id = ?");
      $del->execute($params);
    }

    // Handle new uploads
    if (!empty($_FILES['media']['name'][0])) {
      $dir = ensure_upload_dir(current_user_id());
      $allowed = $config['uploads']['allowed_mime'];
      $max = (int)$config['uploads']['max_size_bytes'];

      foreach ($_FILES['media']['name'] as $idx => $name) {
        $tmp = $_FILES['media']['tmp_name'][$idx];
        $size = $_FILES['media']['size'][$idx];
        $err = $_FILES['media']['error'][$idx];
        $type = mime_content_type($tmp);
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err !== UPLOAD_ERR_OK) { continue; }
        if ($size > $max) { continue; }
        if (!in_array($type, $allowed, true)) { continue; }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $safeName = uniqid('m_', true) . ($ext ? ('.' . strtolower($ext)) : '');
        $destAbs = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        if (move_uploaded_file($tmp, $destAbs)) {
          $relPath = 'uploads/' . current_user_id() . '/' . $safeName;
          $ins = $pdo->prepare('INSERT INTO media (entry_id, file_path, file_type) VALUES (?, ?, ?)');
          $ins->execute([$entryId, $relPath, $type]);
        }
      }
    }

    flash('Entry updated successfully!', 'success');
    redirect('view.php?id=' . $entryId);
  }
}

$pageTitle = 'Edit Entry';
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-3xl mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-2">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Entry</h1>
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="id" value="<?php echo (int)$entry['entry_id']; ?>" />
      <div>
        <label class="block text-sm text-gray-700 mb-1">Title</label>
        <input name="title" value="<?php echo e($entry['title']); ?>" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">Mood (optional)</label>
        <input name="mood" value="<?php echo e($entry['mood']); ?>" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">Content</label>
        <textarea name="content" required rows="8" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition"><?php echo e($entry['content']); ?></textarea>
      </div>

      <?php if (!empty($media)): ?>
        <div>
          <label class="block text-sm text-gray-700 mb-2">Existing Media</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($media as $m): ?>
              <label class="block glass p-3 rounded-2xl border border-primary-100">
                <div class="flex items-center gap-3">
                  <input type="checkbox" name="delete_media[]" value="<?php echo (int)$m['media_id']; ?>" class="mt-1" />
                  <?php if (strpos($m['file_type'], 'image/') === 0): ?>
                    <img src="<?php echo e($m['file_path']); ?>" class="w-20 h-20 object-cover rounded-xl" />
                  <?php else: ?>
                    <audio controls class="w-full"><source src="<?php echo e($m['file_path']); ?>" type="<?php echo e($m['file_type']); ?>"/></audio>
                  <?php endif; ?>
                </div>
                <p class="text-xs text-gray-600 mt-2">Check to delete this file</p>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Add Media</label>
        <input type="file" name="media[]" multiple accept="image/*,audio/*" onchange="previewMedia(this,'preview')" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100" />
        <div id="preview" class="mt-3 flex gap-3 flex-wrap"></div>
      </div>

      <div class="flex justify-end gap-3">
        <a href="view.php?id=<?php echo (int)$entry['entry_id']; ?>" class="px-5 py-3 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100 shadow-sm transition">Cancel</a>
        <button type="submit" class="px-6 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">Save</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
