<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();
$pdo = db();
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

// Get categories
$categories = get_categories($pdo);

// Get existing tags for this entry
$tagsStmt = $pdo->prepare('SELECT t.tag_name FROM tags t JOIN entry_tags et ON t.tag_id = et.tag_id WHERE et.entry_id = ?');
$tagsStmt->execute([$entryId]);
$existingTags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
$tagsString = implode(', ', $existingTags);

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $mood = trim($_POST['mood'] ?? '');
  $musicLink = trim($_POST['music_link'] ?? '');
  $imageUrl = trim($_POST['image_url'] ?? '');
  $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
  $location = trim($_POST['location'] ?? '');
  $tagsInput = trim($_POST['tags'] ?? '');
  $privacyLevel = $_POST['privacy_level'] ?? 'private';
  $toDelete = array_map('intval', $_POST['delete_media'] ?? []);

  if ($title === '' || $content === '') {
    $error = 'Title and content are required';
  } else {
    $upd = $pdo->prepare('UPDATE entries SET title = ?, content = ?, mood = ?, music_link = ?, category_id = ?, location = ?, privacy_level = ? WHERE entry_id = ? AND user_id = ?');
    $upd->execute([$title, $content, $mood !== '' ? $mood : null, $musicLink !== '' ? $musicLink : null, $categoryId, $location !== '' ? $location : null, $privacyLevel, $entryId, current_user_id()]);

    // Update tags
    // First, delete existing tags
    $delTags = $pdo->prepare('DELETE FROM entry_tags WHERE entry_id = ?');
    $delTags->execute([$entryId]);
    
    // Then add new tags
    if ($tagsInput !== '') {
      $tags = array_map('trim', explode(',', $tagsInput));
      foreach ($tags as $tagName) {
        if ($tagName === '') continue;
        $stmt = $pdo->prepare('SELECT tag_id FROM tags WHERE tag_name = ?');
        $stmt->execute([$tagName]);
        $tag = $stmt->fetch();
        if (!$tag) {
          $ins = $pdo->prepare('INSERT INTO tags (tag_name) VALUES (?)');
          $ins->execute([$tagName]);
          $tagId = $pdo->lastInsertId();
        } else {
          $tagId = $tag['tag_id'];
        }
        $link = $pdo->prepare('INSERT INTO entry_tags (entry_id, tag_id) VALUES (?, ?)');
        $link->execute([$entryId, $tagId]);
      }
    }

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

    // Handle new image URL
    if ($imageUrl !== '') {
      if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (in_array($extension, $imageExtensions)) {
          $type = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
          $ins = $pdo->prepare('INSERT INTO media (entry_id, file_path, file_type) VALUES (?, ?, ?)');
          $ins->execute([$entryId, $imageUrl, $type]);
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
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Edit Entry</h1>
    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/40 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <input type="hidden" name="id" value="<?php echo (int)$entry['entry_id']; ?>" />
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Title</label>
        <input name="title" value="<?php echo e($entry['title']); ?>" required class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Mood (optional)</label>
        <input name="mood" value="<?php echo e($entry['mood']); ?>" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" />
      </div>
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Content</label>
        <textarea name="content" required rows="8" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition"><?php echo e($entry['content']); ?></textarea>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Category (optional)</label>
        <select name="category_id" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
          <option value="">No Category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $entry['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>><?php echo e($cat['icon'] . ' ' . $cat['category_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Location (optional)</label>
        <input name="location" value="<?php echo e($entry['location']); ?>" placeholder="e.g., Paris, France" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" />
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Tags (comma-separated, optional)</label>
        <input name="tags" value="<?php echo e($tagsString); ?>" placeholder="summer, travel, adventure" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" />
      </div>

      <div>
        <label for="music_link" class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Music Link (YouTube/Spotify, optional)</label>
        <input type="text" id="music_link" name="music_link" value="<?php echo e($entry['music_link'] ?? ''); ?>" placeholder="https://youtube.com/... or https://open.spotify.com/..." autocomplete="off"
               class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" style="pointer-events: auto !important;" />
        <small class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enter YouTube or Spotify URL</small>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Privacy</label>
        <select name="privacy_level" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
          <?php foreach (get_privacy_levels() as $value => $label): ?>
            <option value="<?php echo e($value); ?>" <?php echo $entry['privacy_level'] === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          <i class="fas fa-info-circle"></i> Public posts appear in the community feed
        </p>
      </div>

      <?php if (!empty($media)): ?>
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">Existing Media</label>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($media as $m): ?>
              <label class="block glass p-3 rounded-2xl border border-primary-100 dark:border-gray-600">
                <div class="flex items-center gap-3">
                  <input type="checkbox" name="delete_media[]" value="<?php echo (int)$m['media_id']; ?>" class="mt-1" />
                  <?php if (strpos($m['file_type'], 'image/') === 0): ?>
                    <img src="<?php echo e($m['file_path']); ?>" class="w-20 h-20 object-cover rounded-xl" />
                  <?php else: ?>
                    <audio controls class="w-full"><source src="<?php echo e($m['file_path']); ?>" type="<?php echo e($m['file_type']); ?>"/></audio>
                  <?php endif; ?>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">Check to delete this file</p>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">Add New Media</label>
        
        <!-- Image URL Input -->
        <div class="mb-3">
          <input type="url" name="image_url" placeholder="Or paste image URL (e.g., https://example.com/image.jpg)"
                 class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 shadow-sm transition" />
        </div>
        
        <!-- File Upload -->
        <input type="file" name="media[]" multiple accept="image/*,audio/*" onchange="previewMedia(this,'preview')" class="block w-full text-sm text-gray-700 dark:text-gray-300 file:px-4 file:py-2 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-primary-50 dark:file:bg-primary-900/30 file:text-primary-700 dark:file:text-primary-300 hover:file:bg-primary-100 dark:hover:file:bg-primary-900/50 file:cursor-pointer" />
        <div id="preview" class="mt-3 flex gap-3 flex-wrap"></div>
      </div>

      <div class="flex justify-end gap-3">
        <a href="view.php?id=<?php echo (int)$entry['entry_id']; ?>" class="px-5 py-3 rounded-2xl bg-white/70 dark:bg-gray-700/50 hover:bg-white/90 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-primary-100 dark:border-gray-600 shadow-sm transition">Cancel</a>
        <button type="submit" class="px-6 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">Save</button>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
