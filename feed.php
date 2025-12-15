<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();
$pdo = db();
$userId = current_user_id();

// Search and filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$moodFilter = isset($_GET['mood']) ? trim($_GET['mood']) : '';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Build SQL query for PUBLIC entries from ALL users
$params = [];
$conditions = ['e.privacy_level = ?'];
$params[] = 'public';
$conditions[] = 'e.is_deleted = FALSE';

$orderBy = 'ORDER BY e.timestamp DESC';

// Search in title and content
if ($searchQuery !== '') {
  $conditions[] = '(e.title LIKE ? OR e.content LIKE ?)';
  $searchPattern = '%' . $searchQuery . '%';
  $params[] = $searchPattern;
  $params[] = $searchPattern;
}

// Filter by mood
if ($moodFilter !== '' && $moodFilter !== 'all') {
  $conditions[] = 'e.mood = ?';
  $params[] = $moodFilter;
}

// Sorting
switch ($sortBy) {
  case 'oldest':
    $orderBy = 'ORDER BY e.timestamp ASC';
    break;
  case 'popular':
    $orderBy = 'ORDER BY (SELECT COALESCE(SUM(reaction_count + comment_count + share_count), 0) FROM entry_stats WHERE entry_id = e.entry_id) DESC, e.timestamp DESC';
    break;
  default:
    $orderBy = 'ORDER BY e.timestamp DESC';
}

$whereSql = implode(' AND ', $conditions);

// Get entries with user information
$sql = "
  SELECT 
    e.*,
    u.username,
    c.category_name,
    c.color as category_color,
    c.icon as category_icon,
    (SELECT COUNT(*) FROM reactions WHERE entry_id = e.entry_id) as reaction_count,
    (SELECT COUNT(*) FROM reactions WHERE entry_id = e.entry_id AND user_id = ?) as user_reacted
  FROM entries e
  LEFT JOIN users u ON e.user_id = u.user_id
  LEFT JOIN categories c ON e.category_id = c.category_id
  WHERE $whereSql
  $orderBy
";

$allParams = array_merge([$userId], $params);
$stmt = $pdo->prepare($sql);
$stmt->execute($allParams);
$entries = $stmt->fetchAll();

// Get available moods for filter
$moods = allowed_moods();

$pageTitle = 'Public Feed';
include __DIR__ . '/partials/head.php';
?>

<div class="container mx-auto px-4 py-8 max-w-5xl">
  <!-- Page Header -->
  <div class="glass rounded-2xl shadow-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
          <i class="fas fa-globe text-primary-600"></i>
          Public Feed
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Discover stories from the community</p>
      </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2 relative">
          <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          <input type="text" name="search" value="<?php echo e($searchQuery); ?>" placeholder="Search stories..." class="w-full rounded-full pl-10 pr-4 py-2 bg-white/70 dark:bg-gray-700/50 focus:bg-white dark:focus:bg-gray-700 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
        </div>

        <!-- Mood Filter -->
        <div>
          <select name="mood" class="w-full rounded-full px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
            <option value="">All Moods</option>
            <?php foreach ($moods as $val => $label): ?>
              <option value="<?php echo e($val); ?>" <?php echo $moodFilter === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sort -->
        <div>
          <select name="sort" class="w-full rounded-full px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 outline-none border border-primary-100 dark:border-gray-600 shadow-sm transition">
            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="popular" <?php echo $sortBy === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
          </select>
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="px-6 py-2 rounded-full bg-primary-600 hover:bg-primary-700 text-white font-medium transition shadow-md hover:shadow-lg">
          <i class="fas fa-filter mr-2"></i>Apply Filters
        </button>
        <?php if ($searchQuery !== '' || $moodFilter !== '' || $sortBy !== 'newest'): ?>
          <a href="feed.php" class="px-6 py-2 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium transition shadow-md">
            <i class="fas fa-times mr-2"></i>Clear
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Entries Feed -->
  <?php if (count($entries) === 0): ?>
    <div class="glass rounded-2xl shadow-xl p-12 text-center">
      <div class="text-6xl mb-4"><i class="fas fa-globe text-primary-500"></i></div>
      <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">No Public Stories Yet</h3>
      <p class="text-gray-600 dark:text-gray-400 mb-6">Be the first to share your story with the community!</p>
      <a href="<?php echo e(app_base_url()); ?>/create.php" class="inline-block px-8 py-3 rounded-full bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white font-medium shadow-lg hover:shadow-xl transition">
        <i class="fas fa-pen mr-2"></i>Create Public Post
      </a>
    </div>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($entries as $e): ?>
        <?php
        $moodEmoji = mood_emoji($e['mood']);
        $formattedDate = human_datetime($e['timestamp']);
        $excerpt = excerpt($e['content'], 200);
        $isOwnPost = $e['user_id'] == $userId;
        ?>
        <div class="glass rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-1">
          <!-- Header -->
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-xl shadow-md">
                  <?php echo strtoupper(substr($e['username'], 0, 1)); ?>
                </div>
                <div>
                  <h3 class="font-bold text-gray-800 dark:text-white">
                    <?php echo e($e['username']); ?>
                    <?php if ($isOwnPost): ?>
                      <span class="text-xs bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 px-2 py-0.5 rounded-full ml-2">You</span>
                    <?php endif; ?>
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($formattedDate); ?></p>
                </div>
              </div>
              <?php if ($e['mood']): ?>
                <span class="text-2xl" title="<?php echo e($e['mood']); ?>"><?php echo $moodEmoji; ?></span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Content -->
          <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-3"><?php echo e($e['title']); ?></h2>
            
            <?php if ($e['category_id']): ?>
              <span class="inline-block px-3 py-1 rounded-full text-sm font-medium mb-3" style="background-color: <?php echo e($e['category_color']); ?>20; color: <?php echo e($e['category_color']); ?>;">
                <?php echo e($e['category_icon']); ?> <?php echo e($e['category_name']); ?>
              </span>
            <?php endif; ?>

            <div class="prose dark:prose-invert max-w-none mb-4">
              <p class="text-gray-700 dark:text-gray-300 leading-relaxed"><?php echo nl2br(e($excerpt)); ?></p>
            </div>

            <?php if ($e['location']): ?>
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                <i class="fas fa-map-marker-alt mr-1"></i><?php echo e($e['location']); ?>
              </p>
            <?php endif; ?>
          </div>

          <!-- Footer - Actions -->
          <div class="px-6 pb-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
            <div class="flex items-center gap-6">
              <!-- Like Button -->
              <button 
                onclick="toggleReaction(<?php echo $e['entry_id']; ?>, this)"
                class="reaction-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition <?php echo $e['user_reacted'] ? 'text-red-500 dark:text-red-400' : ''; ?>"
                data-reacted="<?php echo $e['user_reacted'] ? 'true' : 'false'; ?>"
              >
                <i class="<?php echo $e['user_reacted'] ? 'fas' : 'far'; ?> fa-heart"></i>
                <span class="reaction-count"><?php echo $e['reaction_count'] ?: '0'; ?></span>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <?php if ($isOwnPost): ?>
                <a href="<?php echo e(app_base_url()); ?>/edit.php?id=<?php echo $e['entry_id']; ?>" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium transition text-sm">
                  <i class="fas fa-edit"></i>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
async function toggleReaction(entryId, btn) {
  const reacted = btn.dataset.reacted === 'true';
  const countSpan = btn.querySelector('.reaction-count');
  const icon = btn.querySelector('i');
  
  try {
    const response = await fetch('api/toggle_reaction.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({entry_id: entryId})
    });
    
    const data = await response.json();
    
    if (data.success) {
      const newReacted = data.reacted;
      btn.dataset.reacted = newReacted;
      countSpan.textContent = data.count;
      
      if (newReacted) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        btn.classList.add('text-red-500', 'dark:text-red-400');
      } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        btn.classList.remove('text-red-500', 'dark:text-red-400');
      }
    }
  } catch (error) {
    console.error('Error toggling reaction:', error);
  }
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
