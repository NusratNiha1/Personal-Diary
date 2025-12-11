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
$dateFilter = isset($_GET['date']) ? trim($_GET['date']) : '';
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$tagFilter = isset($_GET['tag']) ? trim($_GET['tag']) : '';
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Build SQL query with dynamic conditions
$params = [$userId];
$conditions = [];
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

// Filter by date
if ($dateFilter !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $dateFilter);
  if ($dt !== false) {
    $conditions[] = 'DATE(e.timestamp) = ?';
    $params[] = $dt->format('Y-m-d');
  }
}

// Filter by category
if ($categoryFilter > 0) {
  $conditions[] = 'e.category_id = ?';
  $params[] = $categoryFilter;
}

// Filter by tag
if ($tagFilter !== '') {
  $conditions[] = 'EXISTS (SELECT 1 FROM entry_tags et JOIN tags t ON et.tag_id = t.tag_id WHERE et.entry_id = e.entry_id AND t.tag_name = ?)';
  $params[] = $tagFilter;
}

// Sort options
switch ($sortBy) {
  case 'oldest':
    $orderBy = 'ORDER BY e.timestamp ASC';
    break;
  case 'title':
    $orderBy = 'ORDER BY e.title ASC';
    break;
  case 'mood':
    $orderBy = 'ORDER BY e.mood ASC, e.timestamp DESC';
    break;
  default:
    $orderBy = 'ORDER BY e.timestamp DESC';
}

// Construct WHERE clause
$whereClause = 'WHERE e.user_id = ?';
if (!empty($conditions)) {
  $whereClause .= ' AND ' . implode(' AND ', $conditions);
}

// Execute query
$sql = "SELECT e.*, 
  (SELECT m.file_path FROM media m WHERE m.entry_id = e.entry_id AND m.file_type LIKE 'image/%' ORDER BY m.media_id ASC LIMIT 1) AS cover_image,
  c.category_name,
  c.color AS category_color,
  c.icon AS category_icon,
  (SELECT GROUP_CONCAT(t.tag_name ORDER BY t.tag_name SEPARATOR ', ') FROM entry_tags et JOIN tags t ON et.tag_id = t.tag_id WHERE et.entry_id = e.entry_id) AS tags
FROM entries e
LEFT JOIN categories c ON e.category_id = c.category_id
$whereClause
$orderBy";

$entries = db_all($sql, $params);

$pageTitle = 'My Posts';
include __DIR__ . '/partials/head.php';
?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
  <!-- Page Header -->
  <div class="glass rounded-2xl shadow-xl p-6 mb-6">
    
  
  <!-- Search and Filter Form -->
  <form method="get" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      
      <!-- Search Box -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Search</label>
        <input type="text" name="search" value="<?php echo e($searchQuery); ?>" 
               placeholder="Search title or content..." 
               class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition" />
      </div>

      <!-- Mood Filter -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Mood</label>
        <select name="mood" class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition">
          <option value="all" <?php echo $moodFilter === 'all' || $moodFilter === '' ? 'selected' : ''; ?>>All Moods</option>
          <?php foreach (allowed_moods() as $label => $emoji): ?>
            <option value="<?php echo e($label); ?>" <?php echo $moodFilter === $label ? 'selected' : ''; ?>>
              <?php echo e($emoji . ' ' . $label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <!-- Category Filter -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Category</label>
        <select name="category" class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition">
          <option value="0">All Categories</option>
          <?php $all_categories = get_categories(); foreach ($all_categories as $cat): ?>
            <option value="<?php echo $cat['category_id']; ?>" <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>>
              <?php echo e($cat['icon'] . ' ' . $cat['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date Filter -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Date</label>
        <input type="date" name="date" value="<?php echo e($dateFilter); ?>" 
               class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition" />
      </div>
      
      <!-- Tag Filter -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Tag</label>
        <input type="text" name="tag" value="<?php echo e($tagFilter); ?>" 
               placeholder="Filter by tag..." 
               class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition" />
      </div>

      <!-- Sort By -->
      <div>
        <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
        <select name="sort" class="w-full rounded-2xl px-4 py-2 bg-white/70 dark:bg-gray-700/50 text-gray-900 dark:text-gray-100 border border-primary-100 dark:border-gray-600 focus:border-primary-400 dark:focus:border-primary-500 outline-none shadow-sm transition">
          <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
          <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
          <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
          <option value="mood" <?php echo $sortBy === 'mood' ? 'selected' : ''; ?>>Mood</option>
        </select>
      </div>
    </div>

    <div class="flex justify-end gap-3 mt-4">
      <a href="dashboard.php" class="px-4 py-2 rounded-2xl bg-white/70 dark:bg-gray-700/50 hover:bg-white/90 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-primary-100 dark:border-gray-600 shadow-sm transition">
        Clear
      </a>
      <button type="submit" class="px-5 py-2 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">
        <i class="fas fa-search mr-2"></i>Apply Filters
      </button>
    </div>
  </form>
  </div>
  
  <!-- Results Count -->
  <?php if ($searchQuery !== '' || $moodFilter !== '' || $dateFilter !== ''): ?>
    <div class="glass rounded-xl p-4 mb-6">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        Found <strong><?php echo count($entries); ?></strong> 
        <?php echo count($entries) === 1 ? 'entry' : 'entries'; ?>
        <?php if ($searchQuery !== ''): ?>
          matching "<strong><?php echo e($searchQuery); ?></strong>"
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>

<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold text-gray-800">Your Entries</h1>
  <div class="flex items-center gap-3">
    
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
          <?php endif; ?>
          <div class="p-5">
            <div class="flex items-center justify-between">
              <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate"><?php echo e($e['title']); ?></h2>
            </div>
            
            <?php if (!empty($e['category_name'])): ?>
              <div class="mt-2">
                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-medium" 
                      style="background-color: <?php echo e($e['category_color']); ?>20; color: <?php echo e($e['category_color']); ?>; border: 1px solid <?php echo e($e['category_color']); ?>40;">
                  <?php echo e($e['category_icon']); ?> <?php echo e($e['category_name']); ?>
                </span>
              </div>
            <?php endif; ?>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 line-clamp-2"><?php echo e(mb_strimwidth($e['content'], 0, 120, '…')); ?></p>
            
            <?php if (!empty($e['tags'])): ?>
              <div class="mt-2 flex flex-wrap gap-1">
                <?php $tag_list = explode(', ', $e['tags']); foreach (array_slice($tag_list, 0, 3) as $tag): ?>
                  <span class="inline-block px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-black dark:text-gray-300 text-xs">
                    #<?php echo e($tag); ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($tag_list) > 3): ?>
                  <span class="text-xs text-gray-500 dark:text-gray-400">+<?php echo count($tag_list) - 3; ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3"><?php echo e(human_datetime($e['timestamp'])); ?></p>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
