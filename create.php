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
  $musicLink = trim($_POST['music_link'] ?? '');
  $imageUrl = trim($_POST['image_url'] ?? '');
  $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
  $tagsInput = trim($_POST['tags'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $privacyLevel = $_POST['privacy_level'] ?? 'private';

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
    // Debug logging
    error_log("[Create Entry] Processing entry creation");
    error_log("[Create Entry] FILES: " . json_encode($_FILES));
    error_log("[Create Entry] Image URL: " . $imageUrl);
    
    try {
      $entryId = db_tx(function(PDO $pdo) use ($config, $title, $content, $mood, $timestamp, $musicLink, $imageUrl, $categoryId, $tagsInput, $location, $privacyLevel) {

        // Calculate word count manually (replaces trigger)
        $wordCount = calculate_word_count($content);
        
        // INSERT ENTRY
        $stmt = $pdo->prepare('
          INSERT INTO entries (user_id, title, content, mood, timestamp, music_link, category_id, location, privacy_level, word_count)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
          current_user_id(),
          $title,
          $content,
          $mood !== '' ? $mood : null,
          $timestamp,
          $musicLink !== '' ? $musicLink : null,
          $categoryId,
          $location !== '' ? $location : null,
          $privacyLevel,
          $wordCount
        ]);

        $entryId = (int)$pdo->lastInsertId();
        
        // Initialize entry stats (replaces trigger)
        init_entry_stats($entryId);
        
        // Update mood history (replaces trigger)
        if ($mood !== '') {
            update_mood_history(current_user_id(), $mood, date('Y-m-d', strtotime($timestamp)));
        }
        
        // HANDLE TAGS
        if ($tagsInput !== '') {
          $tags = array_map('trim', explode(',', $tagsInput));
          foreach ($tags as $tagName) {
            if ($tagName !== '') {
              $tagId = get_or_create_tag($tagName);
              add_tag_to_entry($entryId, $tagId);
            }
          }
        }

        // HANDLE FILE UPLOADS
        if (!empty($_FILES['media']['name'][0])) {
          $dir = ensure_upload_dir(current_user_id());
          $allowed = $config['uploads']['allowed_mime'];
          $max = (int)$config['uploads']['max_size_bytes'];

          foreach ($_FILES['media']['name'] as $idx => $name) {
            $tmp = $_FILES['media']['tmp_name'][$idx];
            $size = $_FILES['media']['size'][$idx];
            $err = $_FILES['media']['error'][$idx];
            if ($err === UPLOAD_ERR_NO_FILE) continue;
            if ($err !== UPLOAD_ERR_OK) {
              error_log("[Upload] Error code for $name: $err");
              continue;
            }
            if (!is_uploaded_file($tmp)) {
              error_log("[Upload] Not an uploaded file: $tmp");
              continue;
            }
            if ($size > $max) {
              error_log("[Upload] File too large: $size > $max");
              continue;
            }

            $type = @mime_content_type($tmp) ?: 'application/octet-stream';
            if (!in_array($type, $allowed, true)) {
              error_log("[Upload] Mime type not allowed: $type");
              continue;
            }

            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeName = uniqid('m_', true) . ($ext ? ('.' . strtolower($ext)) : '');
            $destAbs = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

            if (move_uploaded_file($tmp, $destAbs)) {
              $relPath = 'uploads/' . current_user_id() . '/' . $safeName;
              $ins = $pdo->prepare('INSERT INTO media (entry_id, file_path, file_type) VALUES (?, ?, ?)');
              $ins->execute([$entryId, $relPath, $type]);
              error_log("[Upload] Successfully inserted media: $relPath");
            } else {
              error_log("[Upload] Failed to move file from $tmp to $destAbs");
            }
          }
        }

        // HANDLE IMAGE URL
        if ($imageUrl !== '') {
          if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (in_array($extension, $imageExtensions)) {
              $type = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
              $ins = $pdo->prepare('INSERT INTO media (entry_id, file_path, file_type) VALUES (?, ?, ?)');
              $ins->execute([$entryId, $imageUrl, $type]);
              error_log("[Image URL] Successfully inserted: $imageUrl");
            } else {
              error_log("[Image URL] Invalid extension: $extension");
            }
          } else {
            error_log("[Image URL] Invalid URL: $imageUrl");
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
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">New Entry</h1>

    <?php if ($error): ?>
      <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 dark:bg-red-900/40 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300">
        <?php echo e($error); ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="space-y-5">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Date</label>
          <input type="date" name="entry_date" value="<?php echo e($today); ?>" 
            class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition" />
        </div>

        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Mood (optional)</label>
          <select name="mood" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
            <option value="">No mood</option>
            <?php foreach (allowed_moods() as $label => $emoji): ?>
              <option value="<?php echo e($label); ?>"><?php echo e($emoji . ' ' . $label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Category</label>
          <select name="category_id" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
            <option value="">No category</option>
            <?php $categories = get_categories(); foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['category_id']; ?>"><?php echo e($cat['icon'] . ' ' . $cat['category_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Privacy Level</label>
          <select name="privacy_level" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
            <?php foreach (get_privacy_levels() as $value => $label): ?>
              <option value="<?php echo e($value); ?>" <?php echo $value === 'private' ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            <i class="fas fa-globe"></i> Public posts will appear in the community feed
          </p>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Title</label>
        <input name="title" required class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition" />
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Content</label>
        <textarea name="content" required rows="8" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition"></textarea>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Tags (comma-separated)</label>
          <input name="tags" placeholder="summer, travel, adventure" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition" />
        </div>
        
        <div>
          <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Location (optional)</label>
          <input name="location" placeholder="e.g., New York, USA" class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition" />
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Music Link (YouTube/Spotify)</label>
        <input type="url" name="music_link" placeholder="https://youtube.com/... or https://open.spotify.com/..."
               class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition" />
      </div>

      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">Media (images/audio, optional)</label>
        
        <!-- Image URL Input -->
        <div class="mb-3">
          <input type="url" name="image_url" placeholder="Or paste image URL (e.g., https://example.com/image.jpg)"
                 class="w-full rounded-2xl px-4 py-3 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 focus:border-primary-400 shadow-sm transition" />
        </div>
        
        <!-- File Upload -->
        <input type="file" name="media[]" multiple accept="image/*,audio/*"
          class="block w-full text-sm text-gray-700 dark:text-gray-300 file:px-4 file:py-2 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-primary-50 dark:file:bg-primary-900/30 file:text-primary-700 dark:file:text-primary-300 hover:file:bg-primary-100 dark:hover:file:bg-primary-900/50 file:cursor-pointer" />
        <div id="preview" class="mt-3 flex gap-3 flex-wrap"></div>
      </div>

      <div class="flex justify-end gap-3">
        <a href="dashboard.php" class="px-5 py-3 rounded-2xl bg-white/70 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 border border-primary-100 dark:border-gray-600 shadow-sm transition hover:bg-white/90 dark:hover:bg-gray-700">Cancel</a>
        <button type="submit" class="px-6 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">Create</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
