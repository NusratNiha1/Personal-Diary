<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();
$pdo = db();

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
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100"><?php echo e($entry['title']); ?></h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?php echo e(human_datetime($entry['timestamp'])); ?><?php echo $entry['mood'] ? ' • ' . e($entry['mood']) : ''; ?></p>
      </div>
      <div class="flex gap-2">
        <a href="edit.php?id=<?php echo (int)$entry['entry_id']; ?>" class="px-4 py-2 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100 shadow-sm transition">Edit</a>
        <button type="button" onclick="openDeleteModal(<?php echo (int)$entry['entry_id']; ?>)" class="px-4 py-2 rounded-2xl bg-red-500 hover:bg-red-600 text-white shadow-sm transition">Delete</button>
      </div>
    </div>

    <!-- MEDIA DISPLAY SECTION -->
    <?php if (!empty($media)): ?>
      <div class="mt-6 flex flex-col items-center gap-4">
        <?php foreach ($media as $m): ?>
          <?php if (strpos($m['file_type'], 'image/') === 0): ?>
            <img src="<?php echo e($m['file_path']); ?>" class="max-w-2xl w-full rounded-2xl shadow-lg" />
          <?php elseif (strpos($m['file_type'], 'audio/') === 0): ?>
            <div class="max-w-2xl w-full p-4 rounded-2xl bg-white/70 dark:bg-gray-700/50 border border-primary-100 dark:border-gray-600 shadow">
              <audio controls class="w-full">
                <source src="<?php echo e($m['file_path']); ?>" type="<?php echo e($m['file_type']); ?>" />
              </audio>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- MUSIC LINK SECTION -->
    <?php if (!empty($entry['music_link'])): ?>
      <?php
        // Convert YouTube URL to embed format
        $musicUrl = $entry['music_link'];
        $videoId = '';
        
        // Handle different YouTube URL formats
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $musicUrl, $matches)) {
          $videoId = $matches[1];
        }
      ?>
      <?php if ($videoId): ?>
        <div class="mt-6">
          <div class="rounded-2xl overflow-hidden shadow-lg">
            <iframe 
              width="100%" 
              height="400" 
              src="https://www.youtube.com/embed/<?php echo e($videoId); ?>" 
              title="Music Player" 
              frameborder="0" 
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
              allowfullscreen
              class="w-full"
            ></iframe>
          </div>
        </div>
      <?php else: ?>
        <div class="mt-6">
          <a href="<?php echo e($entry['music_link']); ?>" target="_blank" rel="noopener noreferrer">
            <button class="px-5 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700 transition flex items-center gap-2">
              <i class="fas fa-music"></i> Play Music
            </button>
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="prose max-w-none mt-6">
      <p class="whitespace-pre-wrap text-gray-800 dark:text-gray-200 leading-relaxed"><?php echo nl2br(e($entry['content'])); ?></p>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
  <div class="glass rounded-3xl shadow-2xl p-8 max-w-sm mx-4 animate-in fade-in duration-200">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Delete Entry?</h2>
      <p class="text-gray-600 dark:text-gray-300">Are you sure you want to delete this entry? This action cannot be undone.</p>
    </div>
    
    <div class="flex gap-3 justify-end">
      <button type="button" onclick="closeDeleteModal()" class="px-6 py-2 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100 shadow-sm transition font-medium">
        Cancel
      </button>
      <form method="post" action="delete.php" style="display: inline;">
        <input type="hidden" name="id" id="deleteEntryId" value="" />
        <button type="submit" class="px-6 py-2 rounded-2xl bg-red-500 hover:bg-red-600 text-white shadow-md transition font-medium">
          Delete Permanently
        </button>
      </form>
    </div>
  </div>
</div>

<script>
function openDeleteModal(entryId) {
  document.getElementById('deleteEntryId').value = entryId;
  document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
  document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', (e) => {
  if (e.target.id === 'deleteModal') {
    closeDeleteModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeDeleteModal();
  }
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
