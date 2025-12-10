<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();
$pdo = db();
$config = require __DIR__ . '/config/config.php';

$error = null;
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $content = trim($_POST['content'] ?? '');
  $mood = trim($_POST['mood'] ?? '');
  $entryDate = trim($_POST['entry_date'] ?? '');
  $musicLink = trim($_POST['music_link'] ?? ''); // NEW MUSIC FIELD

  // Validate mood
  $allowed = array_keys(allowed_moods());
  if ($mood !== '' && !in_array($mood, $allowed, true)) {
    $mood = '';
  }

  // Validate date
  $timestamp = null;
  if ($entryDate !== '') {
    $dt = DateTime::createFromFormat('Y-m-d', $entryDate);
    if ($dt !== false) {
      $timestamp = $dt->format('Y-m-d') . ' ' . date('H:i:s');
    }
  }
  if ($timestamp === null) {
    $timestamp = date('Y-m-d H:i:s');
  }

  if ($title === '' || $content === '') {
    $error = 'Title and content are required';
  } else {
    try {
      $entryId = db_tx(function(PDO $pdo) use ($config, $title, $content, $mood, $timestamp, $musicLink) {

        // ----------- INSERT ENTRY (UPDATED WITH music_link) ----------
        $stmt = $pdo->prepare('
          INSERT INTO entries (user_id, title, content, mood, timestamp, music_link)
          VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
          current_user_id(),
          $title,
          $content,
          $mood !== '' ? $mood : null,
          $timestamp,
          $musicLink !== '' ? $musicLink : null  // SAVE MUSIC LINK
        ]);

        $entryId = (int)$pdo->lastInsertId();

        // ----------- MEDIA UPLOAD HANDLING (UNCHANGED) ----------
        if (!empty($_FILES['media']['name'][0])) {
          $dir = ensure_upload_dir(current_user_id());
          $allowed = $config['uploads']['allowed_mime'];
          $max = (int)$config['uploads']['max_size_bytes'];

          foreach ($_FILES['media']['name'] as $idx => $name) {
            $tmp = $_FILES['media']['tmp_name'][$idx];
            $size = $_FILES['media']['size'][$idx];
            $err = $_FILES['media']['error'][$idx];
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            if ($err !== UPLOAD_ERR_OK) continue;
            if (!is_uploaded_file($tmp)) continue;
            if ($size > $max) continue;

            $type = @mime_content_type($tmp) ?: 'application/octet-stream';
            if (!in_array($type, $allowed, true)) continue;

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

        return $entryId;
      });

      flash('Entry created successfully!', 'success');
      redirect('view.php?id=' . $entryId);

    } catch (Throwable $e) {
      error_log('[Create Entry] Failed: ' . $e->getMessage());
      $error = 'Failed to create entry. Please try again.';
    }
  }
}

$pageTitle = 'Create Entry';
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-3xl mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-2">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">New Entry</h1>

    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700">
        <?php echo e($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-700 mb-1">Date</label>
          <input type="date" name="entry_date" value="<?php echo e($today); ?>" 
            class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border shadow-sm" />
        </div>

        <div>
          <label class="block text-sm text-gray-700 mb-1">Mood (optional)</label>
          <select name="mood" class="w-full rounded-2xl px-4 py-3 bg-white/70 outline-none border shadow-sm">
            <option value="">No mood</option>
            <?php foreach (allowed_moods() as $label => $emoji): ?>
              <option value="<?php echo e($label); ?>"><?php echo e($emoji . ' ' . $label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-1">Title</label>
        <input name="title" required class="w-full rounded-2xl px-4 py-3 bg-white/70 outline-none border shadow-sm" />
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-1">Content</label>
        <textarea name="content" required rows="8" class="w-full rounded-2xl px-4 py-3 bg-white/70 outline-none border shadow-sm"></textarea>
      </div>

      <!-- ⭐ NEW MUSIC FIELD ⭐ -->
      <div>
        <label class="block text-sm text-gray-700 mb-1">Music Link (YouTube/Spotify)</label>
        <input type="url" name="music_link" placeholder="https://youtube.com/... or https://open.spotify.com/..."
               class="w-full rounded-2xl px-4 py-3 bg-white/70 outline-none border shadow-sm" />
      </div>

      <div>
        <label class="block text-sm text-gray-700 mb-2">Media (images/audio, optional)</label>
        <input type="file" name="media[]" multiple accept="image/*,audio/*"
          class="block w-full text-sm text-gray-700 file:px-4 file:rounded-full" />
        <div id="preview" class="mt-3 flex gap-3 flex-wrap"></div>
      </div>

      <div class="flex justify-end gap-3">
        <a href="dashboard.php" class="px-5 py-3 rounded-2xl bg-white/70 text-gray-700 border shadow-sm">Cancel</a>
        <button type="submit" class="px-6 py-3 rounded-2xl bg-primary-600 text-white shadow-lg">Create</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
