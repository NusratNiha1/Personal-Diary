<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/db.php';
require_login();

db_ensure_user_profile_columns();

$pdo = db();
$uid = current_user_id();
$error = null; $success = null;

// Fetch current user
$user = db_one('SELECT user_id, username, full_name, date_of_birth, profile_pic FROM users WHERE user_id = ?', [$uid]);
if (!$user) { logout_user(); redirect('index.php'); }

// Helpers
function compute_age(?string $dob): ?int {
    if (!$dob) return null;
    try { $d = new DateTime($dob); $now = new DateTime(); return (int)$d->diff($now)->y; } catch (Throwable $e) { return null; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'profile';
    try {
        if ($action === 'profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $dob = trim($_POST['date_of_birth'] ?? '');
            $dobVal = null;
            if ($dob !== '') {
                $dt = DateTime::createFromFormat('Y-m-d', $dob);
                if ($dt !== false) { $dobVal = $dt->format('Y-m-d'); }
            }

            // Username unique check if changed
            if ($username === '') { throw new Exception('Username is required'); }
            if ($username !== $user['username']) {
                $exists = db_one('SELECT 1 FROM users WHERE username = ? AND user_id <> ?', [$username, $uid]);
                if ($exists) { throw new Exception('Username already taken'); }
            }

            // Handle profile picture
            $picPath = $user['profile_pic'];
            if (!empty($_FILES['profile_pic']['name'])) {
                $tmp = $_FILES['profile_pic']['tmp_name'];
                $err = $_FILES['profile_pic']['error'];
                $size = (int)($_FILES['profile_pic']['size'] ?? 0);
                if ($err === UPLOAD_ERR_OK && is_uploaded_file($tmp)) {
                    $type = @mime_content_type($tmp) ?: 'application/octet-stream';
                    if (strpos($type, 'image/') === 0 && $size <= (5*1024*1024)) {
                        $dir = ensure_upload_dir($uid);
                        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                        $name = 'profile_' . time() . ($ext ? ('.'.$ext) : '');
                        $dest = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
                        if (move_uploaded_file($tmp, $dest)) {
                            $picPath = 'uploads/' . $uid . '/' . $name;
                        }
                    }
                }
            }

            db_exec('UPDATE users SET full_name = ?, username = ?, date_of_birth = ?, profile_pic = ? WHERE user_id = ?', [
                $fullName !== '' ? $fullName : null,
                $username,
                $dobVal,
                $picPath,
                $uid
            ]);
            // Update session username if changed
            if ($username !== $user['username']) { start_session_once(); $_SESSION['username'] = $username; }
            flash('Profile updated', 'success');
            redirect('profile.php');
        } elseif ($action === 'password') {
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            if ($new === '' || strlen($new) < 6) throw new Exception('New password must be at least 6 characters');
            if ($new !== $confirm) throw new Exception('New password and confirmation do not match');
            // Fetch password and verify
            $row = db_one('SELECT password FROM users WHERE user_id = ?', [$uid]);
            if (!$row || $old !== $row['password']) throw new Exception('Old password is incorrect');
            db_exec('UPDATE users SET password = ? WHERE user_id = ?', [$new, $uid]);
            flash('Password updated', 'success');
            redirect('profile.php');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Your Profile';
include __DIR__ . '/partials/head.php';
?>
<div class="max-w-4xl mx-auto">
  <div class="glass rounded-3xl shadow-2xl p-8 mt-2">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Profile</h1>
    <?php if ($error): ?><div class="mb-4 px-4 py-3 rounded-xl border border-red-200"><?php echo e($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="mb-4 px-4 py-3 rounded-xl border border-green-200"><?php echo e($success); ?></div><?php endif; ?>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <form method="post" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="action" value="profile" />
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-full overflow-hidden border border-primary-100 bg-white/70">
              <?php if (!empty($user['profile_pic'])): ?>
                <img src="<?php echo e($user['profile_pic']); ?>" alt="avatar" class="w-full h-full object-cover" />
              <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-3xl">👤</div>
              <?php endif; ?>
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Profile picture</label>
              <input type="file" name="profile_pic" accept="image/*" class="block w-full text-sm text-gray-700" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Full name</label>
              <input name="full_name" value="<?php echo e($user['full_name'] ?? ''); ?>" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Username</label>
              <input name="username" required value="<?php echo e($user['username']); ?>" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Date of birth</label>
              <input type="date" name="date_of_birth" value="<?php echo e($user['date_of_birth'] ?? ''); ?>" class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
              <?php $age = compute_age($user['date_of_birth'] ?? null); if ($age !== null): ?>
                <p class="text-xs text-gray-600 mt-1">Age: <?php echo (int)$age; ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <a href="dashboard.php" class="px-5 py-3 rounded-2xl bg-white/70 hover:bg-white/90 text-gray-700 border border-primary-100 shadow-sm transition">Cancel</a>
            <button class="px-6 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">Save</button>
          </div>
        </form>
      </div>

      <div>
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="password" />
          <h2 class="text-lg font-semibold text-gray-800">Change password</h2>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Old password</label>
            <input type="password" name="old_password" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">New password</label>
            <input type="password" name="new_password" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Confirm new password</label>
            <input type="password" name="confirm_password" required class="w-full rounded-2xl px-4 py-3 bg-white/70 focus:bg-white outline-none border border-primary-100 focus:border-primary-400 shadow-sm transition" />
          </div>
          <div class="flex justify-end">
            <button class="px-5 py-3 rounded-2xl bg-primary-600 hover:bg-primary-700 text-white shadow-lg transition">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
