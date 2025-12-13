<?php
require_once 'lib/auth.php';
require_once 'lib/db.php';
require_once 'lib/utils.php';

require_login();

$page_title = 'Manage Categories';
$user_id = current_user_id();

// Handle create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['category_name']);
    $description = trim($_POST['description'] ?? '');
    $color = $_POST['color'] ?? '#6B7280';
    $icon = $_POST['icon'] ?? '';
    
    if (!empty($name)) {
        try {
            create_category($name, $description, $color, $icon, $user_id);
            flash('Category created successfully', 'success');
        } catch (Exception $e) {
            flash('Error creating category: Category name must be unique', 'error');
        }
    }
    redirect('categories.php');
}

// Handle delete category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    db_exec("DELETE FROM categories WHERE category_id = ?", [$category_id]);
    flash('Category deleted successfully', 'success');
    redirect('categories.php');
}

// Get all categories with entry counts
$categories = db_all("SELECT c.*, 
                      COUNT(e.entry_id) as entry_count,
                      u.username as creator_name
                      FROM categories c
                      LEFT JOIN entries e ON c.category_id = e.category_id AND e.is_deleted = FALSE
                      LEFT JOIN users u ON c.created_by = u.user_id
                      GROUP BY c.category_id
                      ORDER BY c.category_name");

// Popular tags
$popular_tags = db_all("SELECT * FROM v_popular_tags LIMIT 20");

include 'partials/head.php';
?>

<div class="min-h-screen bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">Categories & Tags</h1>
                <p class="text-gray-400">Organize your diary entries</p>
            </div>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                ➕ New Category
            </button>
        </div>

        <!-- Categories Grid -->
        <div class="mb-12">
            <h2 class="text-xl font-semibold text-white mb-4">Categories</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($categories as $category): ?>
                <div class="bg-gray-800 rounded-lg p-6 border-l-4" style="border-color: <?= e($category['color']) ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3"><?= e($category['icon']) ?></span>
                            <div>
                                <h3 class="text-white font-semibold text-lg"><?= e($category['category_name']) ?></h3>
                                <p class="text-gray-400 text-sm"><?= $category['entry_count'] ?> entries</p>
                            </div>
                        </div>
                        <?php if (is_admin() || $category['created_by'] == $user_id): ?>
                        <form method="POST" onsubmit="return confirm('Delete this category? Entries will not be deleted.')">
                            <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">
                            <button type="submit" name="delete_category" value="1" 
                                    class="text-red-400 hover:text-red-300 text-sm">
                                ❌
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($category['description']): ?>
                    <p class="text-gray-300 text-sm mb-3"><?= e($category['description']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>Color: <span class="inline-block w-4 h-4 rounded" style="background-color: <?= e($category['color']) ?>"></span></span>
                        <?php if ($category['creator_name']): ?>
                        <span>By <?= e($category['creator_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Popular Tags -->
        <div>
            <h2 class="text-xl font-semibold text-white mb-4">Popular Tags</h2>
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($popular_tags as $tag): ?>
                    <span class="inline-flex items-center bg-gray-700 text-gray-200 px-4 py-2 rounded-full text-sm">
                        <span class="font-medium"><?= e($tag['tag_name']) ?></span>
                        <span class="ml-2 bg-gray-600 px-2 py-1 rounded-full text-xs"><?= $tag['usage_count'] ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($popular_tags)): ?>
                <p class="text-gray-400 text-center">No tags created yet. Tags are added when creating entries.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Category Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
    <div class="bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-white">Create Category</h2>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-white text-2xl">
                ×
            </button>
        </div>
        
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-300 mb-2">Category Name *</label>
                    <input type="text" name="category_name" required
                           class="w-full px-4 py-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-gray-300 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-300 mb-2">Color</label>
                        <input type="color" name="color" value="#6B7280"
                               class="w-full h-10 rounded-lg bg-gray-700 border border-gray-600 cursor-pointer">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">Icon (Emoji)</label>
                        <input type="text" name="icon" placeholder="🏷️" maxlength="2"
                               class="w-full px-4 py-2 rounded-lg bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none text-center text-2xl">
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <button type="submit" name="create_category" value="1"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Create Category
                </button>
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'partials/footer.php'; ?>
